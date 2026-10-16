<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Services\MeetingRoomService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MeetingController extends Controller
{
    protected MeetingRoomService $roomService;

    public function __construct(MeetingRoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Meeting::with(['room', 'organizer', 'department'])
            ->forUser($user);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('meeting_type')) {
            $query->where('meeting_type', $request->meeting_type);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('meeting_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('meeting_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meeting_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $meetings = $query->orderBy('meeting_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        $rooms = MeetingRoom::all();
        $departments = Department::where('is_active', true)->get();

        return view('meetings.index', compact('meetings', 'rooms', 'departments'));
    }

    public function calendar(Request $request): View
    {
        $user = Auth::user();
        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $currentDate = Carbon::parse($selectedDate);

        $meetings = Meeting::with(['room', 'organizer'])
            ->forUser($user)
            ->whereDate('meeting_date', $selectedDate)
            ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled', 'completed'])
            ->orderBy('start_time')
            ->get();

        $rooms = MeetingRoom::where('status', 'available')->get();

        return view('meetings.calendar', compact('meetings', 'rooms', 'selectedDate', 'currentDate'));
    }

    public function create(Request $request): View
    {
        $rooms = MeetingRoom::where('status', 'available')->get();
        $departments = Department::where('is_active', true)->get();
        $users = User::where('status', 'active')->orderBy('name')->get();

        $prefillRoomId = $request->input('room_id');
        $prefillDate = $request->input('date', Carbon::tomorrow()->toDateString());

        return view('meetings.create', compact('rooms', 'departments', 'users', 'prefillRoomId', 'prefillDate'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('attendees') && is_array($request->attendees)) {
            $filteredAttendees = array_values(array_filter($request->attendees, function ($att) {
                return is_array($att) && ! empty($att['user_id']);
            }));
            $request->merge(['attendees' => $filteredAttendees]);
        }

        if ($request->has('agendas') && is_array($request->agendas)) {
            $filteredAgendas = array_values(array_filter($request->agendas, function ($ag) {
                return is_array($ag) && ! empty($ag['title']);
            }));
            $request->merge(['agendas' => $filteredAgendas]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_type' => 'required|in:in_person,virtual,hybrid',
            'room_id' => 'nullable|required_if:meeting_type,in_person,hybrid|exists:meeting_rooms,id',
            'department_id' => 'nullable|exists:departments,id',
            'virtual_link' => 'nullable|url|max:500',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_confidential' => 'nullable|boolean',
            // Agendas
            'agendas' => 'nullable|array',
            'agendas.*.title' => 'required|string|max:255',
            'agendas.*.duration_minutes' => 'required|integer|min:1',
            'agendas.*.presenter_id' => 'nullable|exists:users,id',
            // Attendees
            'attendees' => 'nullable|array',
            'attendees.*.user_id' => 'required|exists:users,id',
            'attendees.*.role' => 'required|in:required,optional,presenter',
        ]);

        // Room conflict validation
        if (! empty($validated['room_id'])) {
            $conflictCheck = $this->roomService->checkRoomAvailability(
                $validated['room_id'],
                $validated['meeting_date'],
                $validated['start_time'].':00',
                $validated['end_time'].':00'
            );

            if (! $conflictCheck['available']) {
                return back()->withInput()->withErrors([
                    'room_id' => $conflictCheck['message'],
                ]);
            }
        }

        $meetingCode = 'MTG-'.Carbon::parse($validated['meeting_date'])->format('Ymd').'-'.strtoupper(Str::random(4));

        DB::beginTransaction();
        try {
            $meeting = Meeting::create([
                'meeting_code' => $meetingCode,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'meeting_type' => $validated['meeting_type'],
                'room_id' => $validated['room_id'] ?? null,
                'organizer_id' => Auth::id(),
                'department_id' => $validated['department_id'] ?? Auth::user()->department_id,
                'virtual_link' => $validated['virtual_link'] ?? null,
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'is_confidential' => $request->boolean('is_confidential'),
                'status' => 'scheduled',
            ]);

            // Add Organizer as Host Attendee automatically
            $meeting->attendees()->create([
                'user_id' => Auth::id(),
                'role' => 'host',
                'rsvp_status' => 'accepted',
            ]);

            // Add Invitees
            if (! empty($validated['attendees'])) {
                foreach ($validated['attendees'] as $attendeeData) {
                    if ((int) $attendeeData['user_id'] !== Auth::id()) {
                        $meeting->attendees()->firstOrCreate(
                            ['user_id' => $attendeeData['user_id']],
                            [
                                'role' => $attendeeData['role'],
                                'rsvp_status' => 'pending',
                                'attendance_status' => 'unmarked',
                            ]
                        );
                    }
                }
            }

            // Add Agendas
            if (! empty($validated['agendas'])) {
                $orderIndex = 1;
                foreach ($validated['agendas'] as $agendaData) {
                    $meeting->agendas()->create([
                        'title' => $agendaData['title'],
                        'duration_minutes' => $agendaData['duration_minutes'],
                        'presenter_id' => $agendaData['presenter_id'] ?? null,
                        'order_index' => $orderIndex++,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to schedule meeting: '.$e->getMessage());
        }

        return redirect()->route('meetings.show', $meeting)
            ->with('success', "Meeting '{$meeting->title}' scheduled successfully! [Code: {$meeting->meeting_code}]");
    }

    public function show(Meeting $meeting): View
    {
        $this->authorizeMeetingAccess($meeting);

        $meeting->load([
            'room',
            'organizer.department',
            'department',
            'agendas.presenter',
            'attendees.user.department',
            'minutes.preparedBy',
            'minutes.approvedBy',
            'actionItems.assignedTo',
            'attachments.uploadedBy',
        ]);

        $users = User::where('status', 'active')->orderBy('name')->get();
        $currentUserAttendee = $meeting->attendees->firstWhere('user_id', Auth::id());

        return view('meetings.show', compact('meeting', 'users', 'currentUserAttendee'));
    }

    public function edit(Meeting $meeting): View
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        $meeting->load(['agendas', 'attendees.user']);
        $rooms = MeetingRoom::all();
        $departments = Department::where('is_active', true)->get();
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('meetings.edit', compact('meeting', 'rooms', 'departments', 'users'));
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        if ($request->has('attendees') && is_array($request->attendees)) {
            $filteredAttendees = array_values(array_filter($request->attendees, function ($att) {
                return is_array($att) && ! empty($att['user_id']);
            }));
            $request->merge(['attendees' => $filteredAttendees]);
        }

        if ($request->has('agendas') && is_array($request->agendas)) {
            $filteredAgendas = array_values(array_filter($request->agendas, function ($ag) {
                return is_array($ag) && ! empty($ag['title']);
            }));
            $request->merge(['agendas' => $filteredAgendas]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_type' => 'required|in:in_person,virtual,hybrid',
            'room_id' => 'nullable|required_if:meeting_type,in_person,hybrid|exists:meeting_rooms,id',
            'department_id' => 'nullable|exists:departments,id',
            'virtual_link' => 'nullable|url|max:500',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_confidential' => 'nullable|boolean',
            'status' => 'required|in:scheduled,in_progress,completed,rescheduled,cancelled',
            'agendas' => 'nullable|array',
            'agendas.*.title' => 'required|string|max:255',
            'agendas.*.duration_minutes' => 'required|integer|min:1',
            'agendas.*.presenter_id' => 'nullable|exists:users,id',
            'attendees' => 'nullable|array',
            'attendees.*.user_id' => 'required|exists:users,id',
            'attendees.*.role' => 'required|in:required,optional,presenter',
        ]);

        // Conflict check
        if (! empty($validated['room_id'])) {
            $conflictCheck = $this->roomService->checkRoomAvailability(
                $validated['room_id'],
                $validated['meeting_date'],
                $validated['start_time'].':00',
                $validated['end_time'].':00',
                $meeting->id
            );

            if (! $conflictCheck['available']) {
                return back()->withInput()->withErrors([
                    'room_id' => $conflictCheck['message'],
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $meeting->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'meeting_type' => $validated['meeting_type'],
                'room_id' => $validated['room_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'virtual_link' => $validated['virtual_link'] ?? null,
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'status' => $validated['status'],
                'is_confidential' => $request->boolean('is_confidential'),
            ]);

            // Sync attendees if provided
            if ($request->has('attendees')) {
                $submittedUserIds = collect($validated['attendees'] ?? [])->pluck('user_id')->map(fn ($id) => (int) $id)->all();

                // Remove unselected attendees (except organizer)
                $meeting->attendees()->where('user_id', '!=', $meeting->organizer_id)
                    ->whereNotIn('user_id', $submittedUserIds)
                    ->delete();

                // Add or update attendees
                foreach ($validated['attendees'] ?? [] as $attData) {
                    if ((int) $attData['user_id'] !== (int) $meeting->organizer_id) {
                        $attendee = $meeting->attendees()->firstOrNew(['user_id' => $attData['user_id']]);
                        $attendee->role = $attData['role'];
                        if (! $attendee->exists) {
                            $attendee->rsvp_status = 'pending';
                            $attendee->attendance_status = 'unmarked';
                        }
                        $attendee->save();
                    }
                }
            }

            // Sync agendas if provided
            if ($request->has('agendas')) {
                $meeting->agendas()->delete();
                $orderIndex = 1;
                foreach ($validated['agendas'] ?? [] as $agendaData) {
                    $meeting->agendas()->create([
                        'title' => $agendaData['title'],
                        'duration_minutes' => $agendaData['duration_minutes'],
                        'presenter_id' => $agendaData['presenter_id'] ?? null,
                        'order_index' => $orderIndex++,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to update meeting: '.$e->getMessage());
        }

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting details updated successfully.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        $title = $meeting->title;
        $meeting->delete();

        return redirect()->route('meetings.index')
            ->with('success', "Meeting '{$title}' deleted successfully.");
    }

    public function cancel(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $meeting->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('meetings.show', $meeting)
            ->with('warning', 'Meeting has been cancelled.');
    }

    public function updateStatus(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,rescheduled',
        ]);

        $meeting->update(['status' => $validated['status']]);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting status updated to '.ucfirst(str_replace('_', ' ', $validated['status'])));
    }

    public function updateRsvp(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeMeetingAccess($meeting);

        $validated = $request->validate([
            'rsvp_status' => 'required|in:accepted,declined,tentative',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendee = $meeting->attendees()->firstOrCreate(
            ['user_id' => Auth::id()],
            ['role' => 'required']
        );

        $attendee->update([
            'rsvp_status' => $validated['rsvp_status'],
            'notes' => $validated['notes'] ?? $attendee->notes,
        ]);

        return back()->with('success', 'Your RSVP response has been recorded.');
    }

    public function updateAttendance(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeOrganizerOrAdmin($meeting);

        $validated = $request->validate([
            'attendee_id' => 'required|exists:meeting_attendees,id',
            'attendance_status' => 'required|in:unmarked,present,absent,late,excused',
        ]);

        $attendee = MeetingAttendee::where('meeting_id', $meeting->id)
            ->findOrFail($validated['attendee_id']);

        $attendee->update([
            'attendance_status' => $validated['attendance_status'],
            'checked_in_at' => in_array($validated['attendance_status'], ['present', 'late']) ? Carbon::now() : null,
        ]);

        return back()->with('success', "Attendance updated for {$attendee->display_name}.");
    }

    public function checkRoomAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:meeting_rooms,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'exclude_meeting_id' => 'nullable|integer',
        ]);

        $check = $this->roomService->checkRoomAvailability(
            $request->room_id,
            $request->date,
            $request->start_time.':00',
            $request->end_time.':00',
            $request->exclude_meeting_id
        );

        return response()->json($check);
    }

    protected function authorizeOrganizerOrAdmin(Meeting $meeting): void
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $meeting->organizer_id !== $user->id) {
            abort(403, 'Unauthorized. Only the meeting organizer or an administrator can modify this meeting.');
        }
    }

    protected function authorizeMeetingAccess(Meeting $meeting): void
    {
        if (! $meeting->is_confidential) {
            return;
        }

        $user = Auth::user();
        if ($user->isAdmin() || $meeting->organizer_id === $user->id) {
            return;
        }

        if ($meeting->attendees()->where('user_id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Unauthorized access. This meeting is confidential.');
    }
}
