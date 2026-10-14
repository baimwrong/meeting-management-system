<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingRoom;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingRoomController extends Controller
{
    public function index(Request $request): View
    {
        $query = MeetingRoom::withCount('meetings');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('floor_location', 'like', "%{$search}%");
            });
        }

        $rooms = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('rooms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:meeting_rooms,code',
            'floor_location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'equipment_string' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,maintenance,inactive',
            'notes' => 'nullable|string',
        ]);

        $equipmentList = [];
        if (!empty($validated['equipment_string'])) {
            $parts = explode(',', $validated['equipment_string']);
            $equipmentList = array_values(array_filter(array_map('trim', $parts)));
        }

        $room = MeetingRoom::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'floor_location' => $validated['floor_location'],
            'capacity' => $validated['capacity'],
            'equipment' => $equipmentList,
            'hourly_rate' => $validated['hourly_rate'] ?? 0.00,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('rooms.show', $room)
            ->with('success', "Meeting Room '{$room->name}' created successfully.");
    }

    public function show(MeetingRoom $room): View
    {
        $today = Carbon::today()->toDateString();

        $todayMeetings = Meeting::where('room_id', $room->id)
            ->whereDate('meeting_date', $today)
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->orderBy('start_time')
            ->with('organizer')
            ->get();

        $upcomingMeetings = Meeting::where('room_id', $room->id)
            ->whereDate('meeting_date', '>', $today)
            ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])
            ->orderBy('meeting_date')
            ->orderBy('start_time')
            ->with('organizer')
            ->take(10)
            ->get();

        return view('rooms.show', compact('room', 'todayMeetings', 'upcomingMeetings'));
    }

    public function edit(MeetingRoom $room): View
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, MeetingRoom $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:meeting_rooms,code,' . $room->id,
            'floor_location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'equipment_string' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,maintenance,inactive',
            'notes' => 'nullable|string',
        ]);

        $equipmentList = [];
        if (!empty($validated['equipment_string'])) {
            $parts = explode(',', $validated['equipment_string']);
            $equipmentList = array_values(array_filter(array_map('trim', $parts)));
        }

        $room->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'floor_location' => $validated['floor_location'],
            'capacity' => $validated['capacity'],
            'equipment' => $equipmentList,
            'hourly_rate' => $validated['hourly_rate'] ?? 0.00,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('rooms.show', $room)
            ->with('success', "Meeting Room '{$room->name}' updated successfully.");
    }

    public function destroy(MeetingRoom $room): RedirectResponse
    {
        $hasActiveMeetings = Meeting::where('room_id', $room->id)
            ->where('meeting_date', '>=', Carbon::today()->toDateString())
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->exists();

        if ($hasActiveMeetings) {
            return back()->with('error', "Cannot delete '{$room->name}' because it has active upcoming meeting bookings. Please reassign or cancel those meetings first.");
        }

        $name = $room->name;
        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', "Meeting Room '{$name}' has been deleted.");
    }
}
