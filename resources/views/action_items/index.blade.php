@extends('layouts.app')

@section('title', 'Action Items & Deliverables')
@section('page_category', 'Tasks')
@section('page_title', 'Action Items Tracker')

@section('content')
<!-- Filter & Scope Bar -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('action-items.index') }}" class="row g-2 align-items-center">
            @if(Auth::user()->isOrganizer())
                <div class="col-md-2">
                    <select name="scope" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="mine" {{ $scope === 'mine' ? 'selected' : '' }}>Assigned to Me</option>
                        <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>All Tasks</option>
                    </select>
                </div>
            @endif

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>

            <div class="col-md-3">
                <div class="form-check form-switch m-0 pt-1">
                    <input class="form-check-input" type="checkbox" name="overdue" value="1" id="overdueCheck" {{ request('overdue') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold text-danger" for="overdueCheck">Show Overdue Only</label>
                </div>
            </div>

            <div class="col-md-2 text-end">
                <button type="submit" class="btn btn-sm btn-primary">Filter Tasks</button>
                @if(request()->hasAny(['status', 'priority', 'overdue']))
                    <a href="{{ route('action-items.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span>Action Items ({{ $actionItems->total() }} Total)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th scope="col" style="width: 40px;"></th>
                    <th scope="col">Task Description</th>
                    <th scope="col">Meeting Reference</th>
                    <th scope="col">Assignee</th>
                    <th scope="col">Due Date</th>
                    <th scope="col">Priority</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actionItems as $task)
                    <tr class="{{ $task->status === 'completed' ? 'opacity-75 bg-light' : '' }}">
                        <td class="text-center">
                            @if(Auth::id() === $task->assigned_to_id || Auth::user()->isAdmin())
                                <form action="{{ route('action-items.status', $task) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $task->status === 'completed' ? 'in_progress' : 'completed' }}">
                                    <button type="submit" class="btn btn-sm p-0 border-0 text-{{ $task->status === 'completed' ? 'success' : 'secondary' }}" title="Toggle Complete">
                                        <i class="bi {{ $task->status === 'completed' ? 'bi-check-circle-fill fs-5' : 'bi-circle fs-5' }}"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold {{ $task->status === 'completed' ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                                {{ $task->title }}
                            </div>
                            @if($task->description)
                                <small class="text-muted d-block">{{ Str::limit($task->description, 80) }}</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('meetings.show', $task->meeting) }}" class="text-decoration-none small fw-medium">
                                <i class="bi bi-box-arrow-up-right me-1"></i>{{ Str::limit($task->meeting->title, 25) }}
                            </a>
                            <div class="text-muted font-monospace" style="font-size: 0.7rem;">{{ $task->meeting->meeting_code }}</div>
                        </td>
                        <td>
                            <span class="small fw-semibold">{{ $task->assignedTo->name }}</span>
                        </td>
                        <td>
                            <span class="small {{ $task->is_overdue ? 'text-danger fw-bold' : '' }}">
                                <i class="bi bi-calendar-event me-1"></i>{{ $task->due_date->format('M d, Y') }}
                                @if($task->is_overdue)
                                    <span class="badge bg-danger ms-1 py-0 px-1" style="font-size: 0.65rem;">OVERDUE</span>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $task->priority_badge_class }}">{{ ucfirst($task->priority) }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $task->status_badge_class }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                        </td>
                        <td class="text-end">
                            @if(Auth::user()->isAdmin() || Auth::id() === $task->meeting->organizer_id)
                                <form action="{{ route('action-items.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this action item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-all fs-1 text-success d-block mb-2"></i>
                            No action items found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($actionItems->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $actionItems->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
