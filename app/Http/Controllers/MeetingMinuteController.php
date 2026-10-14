<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMinute;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingMinuteController extends Controller
{
    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'summary' => 'required|string',
            'key_decisions' => 'nullable|string',
            'status' => 'required|in:draft,reviewed,approved',
        ]);

        $minute = $meeting->minutes()->updateOrCreate(
            ['meeting_id' => $meeting->id],
            [
                'prepared_by_id' => Auth::id(),
                'summary' => $validated['summary'],
                'key_decisions' => $validated['key_decisions'] ?? null,
                'status' => $validated['status'],
                'approved_by_id' => $validated['status'] === 'approved' ? Auth::id() : null,
                'approved_at' => $validated['status'] === 'approved' ? Carbon::now() : null,
            ]
        );

        return back()->with('success', 'Minutes of Meeting (MoM) saved successfully.');
    }

    public function printView(Meeting $meeting): View
    {
        $meeting->load([
            'room',
            'organizer.department',
            'department',
            'agendas.presenter',
            'attendees.user.department',
            'minutes.preparedBy',
            'minutes.approvedBy',
            'actionItems.assignedTo',
        ]);

        return view('meetings.print_mom', compact('meeting'));
    }
}
