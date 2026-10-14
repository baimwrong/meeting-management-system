<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingRoom;
use App\Services\MeetingRoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected MeetingRoomService $roomService;

    public function __construct(MeetingRoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $weekEnd = Carbon::today()->addDays(7)->toDateString();

        // 1. KPI Counts
        $todayMeetingsCount = Meeting::whereDate('meeting_date', $today)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();

        $upcomingWeekCount = Meeting::whereBetween('meeting_date', [$today, $weekEnd])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();

        $myPendingTasksCount = ActionItem::where('assigned_to_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $utilizationStats = $this->roomService->getUtilizationStats();

        // 2. Today's Agenda
        $todayMeetings = Meeting::with(['room', 'organizer', 'attendees.user'])
            ->whereDate('meeting_date', $today)
            ->forUser($user)
            ->orderBy('start_time')
            ->get();

        // 3. Upcoming Meetings (Next 5)
        $upcomingMeetings = Meeting::with(['room', 'organizer'])
            ->where(function ($q) use ($today) {
                $q->whereDate('meeting_date', '>', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereDate('meeting_date', $today)
                          ->where('end_time', '>=', Carbon::now()->format('H:i:s'));
                  });
            })
            ->forUser($user)
            ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])
            ->orderBy('meeting_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // 4. My Urgent / Upcoming Action Items
        $myActionItems = ActionItem::with('meeting')
            ->where('assigned_to_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // 5. Chart 1: Meetings by Department
        $departments = Department::withCount('meetings')->get();
        $deptChartLabels = $departments->pluck('name')->toArray();
        $deptChartData = $departments->pluck('meetings_count')->toArray();

        // 6. Chart 2: Top Meeting Rooms Usage (Total meetings booked)
        $rooms = MeetingRoom::withCount('meetings')->orderByDesc('meetings_count')->take(5)->get();
        $roomChartLabels = $rooms->pluck('name')->toArray();
        $roomChartData = $rooms->pluck('meetings_count')->toArray();

        // 7. Meeting status breakdown
        $statusCounts = [
            'scheduled' => Meeting::where('status', 'scheduled')->count(),
            'in_progress' => Meeting::where('status', 'in_progress')->count(),
            'completed' => Meeting::where('status', 'completed')->count(),
            'cancelled' => Meeting::where('status', 'cancelled')->count(),
        ];

        return view('dashboard.index', compact(
            'todayMeetingsCount',
            'upcomingWeekCount',
            'myPendingTasksCount',
            'utilizationStats',
            'todayMeetings',
            'upcomingMeetings',
            'myActionItems',
            'deptChartLabels',
            'deptChartData',
            'roomChartLabels',
            'roomChartData',
            'statusCounts'
        ));
    }
}
