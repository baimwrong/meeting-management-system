<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActionItemController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = ActionItem::with(['meeting.room', 'assignedTo']);

        // Filter: default to assigned to me unless 'all' is requested and user has rights
        $scope = $request->input('scope', 'mine');
        if ($scope === 'mine' || !$user->isOrganizer()) {
            $query->where('assigned_to_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('overdue') && $request->overdue == '1') {
            $query->where('due_date', '<', Carbon::today()->toDateString())
                  ->whereNotIn('status', ['completed', 'cancelled']);
        }

        $actionItems = $query->orderBy('due_date')->paginate(15)->withQueryString();

        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('action_items.index', compact('actionItems', 'users', 'scope'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_id' => 'required|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $meeting->actionItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to_id' => $validated['assigned_to_id'],
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Action item assigned successfully.');
    }

    public function updateStatus(Request $request, ActionItem $actionItem): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $completedAt = $validated['status'] === 'completed' ? Carbon::now() : null;

        $actionItem->update([
            'status' => $validated['status'],
            'completed_at' => $completedAt,
        ]);

        return back()->with('success', "Action item marked as " . ucfirst(str_replace('_', ' ', $validated['status'])));
    }

    public function destroy(ActionItem $actionItem): RedirectResponse
    {
        $actionItem->delete();
        return back()->with('success', 'Action item deleted successfully.');
    }
}
