@extends('layouts.app')

@section('title', 'Meetings Directory')
@section('page_category', 'Meetings')
@section('page_title', 'All Meetings')

@section('content')
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('meetings.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search title or code..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rescheduled" {{ request('status') === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="room_id" class="form-select form-select-sm">
                    <option value="">All Rooms</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" placeholder="From Date" value="{{ request('date_from') }}">
            </div>

            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" placeholder="To Date" value="{{ request('date_to') }}">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filters">Filter</button>
                @if(request()->hasAny(['search', 'status', 'room_id', 'date_from', 'date_to']))
                    <a href="{{ route('meetings.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Meetings List ({{ $meetings->total() }} Total)</span>
        <div class="d-flex gap-2">
            <a href="{{ route('meetings.calendar') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calendar3 me-1"></i> Timetable View
            </a>
            <a href="{{ route('meetings.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Schedule Meeting
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th scope="col" style="width: 140px;">Code</th>
                    <th scope="col">Meeting Title</th>
                    <th scope="col">Location / Room</th>
                    <th scope="col">Date & Time</th>
                    <th scope="col">Organizer</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end" style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meetings as $meeting)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace">{{ $meeting->meeting_code }}</span>
                        </td>
                        <td>
                            <a href="{{ route('meetings.show', $meeting) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $meeting->title }}
                            </a>
                            <div class="small text-muted">
                                <span class="badge {{ $meeting->meeting_type_badge_class }} py-0 px-1">{{ ucfirst(str_replace('_', ' ', $meeting->meeting_type)) }}</span>
                                @if($meeting->department)
                                    <span class="text-secondary">&bull; {{ $meeting->department->name }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($meeting->room)
                                <div class="fw-medium text-dark"><i class="bi bi-door-open me-1 text-primary"></i>{{ $meeting->room->name }}</div>
                                <small class="text-muted">{{ $meeting->room->floor_location }}</small>
                            @else
                                <span class="text-muted"><i class="bi bi-camera-video me-1"></i>Virtual Only</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $meeting->meeting_date->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $meeting->formatted_time_range }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    {{ strtoupper(substr($meeting->organizer->name, 0, 2)) }}
                                </div>
                                <span class="small">{{ $meeting->organizer->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $meeting->status_badge_class }}">{{ ucfirst(str_replace('_', ' ', $meeting->status)) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-light border" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                                    <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-light border" title="Edit Meeting">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-light border text-danger" title="Delete Meeting" onclick="if(confirm('Are you sure you want to delete this meeting?')) document.getElementById('delete-meeting-{{ $meeting->id }}').submit();">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-meeting-{{ $meeting->id }}" action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No meetings found matching your filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($meetings->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $meetings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
