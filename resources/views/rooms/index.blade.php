@extends('layouts.app')

@section('title', 'Meeting Rooms')
@section('page_category', 'Facilities')
@section('page_title', 'Meeting Rooms Directory')

@section('content')
<!-- Filter & Action Bar -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-md-8">
                <form method="GET" action="{{ route('rooms.index') }}" class="row g-2 align-items-center">
                    <div class="col-sm-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search room name, code, floor..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <input type="number" name="min_capacity" class="form-control form-control-sm" placeholder="Min Capacity" value="{{ request('min_capacity') }}" min="1">
                    </div>
                    <div class="col-sm-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i></button>
                    </div>
                </form>
            </div>

            @if(Auth::user()->isAdmin() || Auth::user()->isOrganizer())
                <div class="col-md-4 text-md-end">
                    <a href="{{ route('rooms.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add Meeting Room
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Rooms Grid -->
<div class="row g-4">
    @forelse($rooms as $room)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-dark border font-monospace">{{ $room->code }}</span>
                        <span class="badge {{ $room->status_badge_class }}">{{ ucfirst($room->status) }}</span>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">
                        <a href="{{ route('rooms.show', $room) }}" class="text-dark text-decoration-none">
                            {{ $room->name }}
                        </a>
                    </h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-geo-alt me-1 text-primary"></i>{{ $room->floor_location }}
                    </p>

                    <div class="d-flex align-items-center gap-3 text-muted small mb-3 p-2 bg-light rounded">
                        <div><i class="bi bi-people me-1 text-dark"></i>Cap: <strong>{{ $room->capacity }}</strong></div>
                        <div>&bull;</div>
                        <div><i class="bi bi-calendar-event me-1 text-dark"></i><strong>{{ $room->meetings_count }}</strong> bookings</div>
                    </div>

                    @if(!empty($room->equipment))
                        <div class="mb-3 flex-grow-1">
                            <div class="small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">AMENITIES:</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($room->equipment as $item)
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.75rem;">{{ $item }}</span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex-grow-1"></div>
                    @endif

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar-range me-1"></i> Schedule
                        </a>
                        @if($room->status === 'available')
                            <a href="{{ route('meetings.create', ['room_id' => $room->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-calendar-plus me-1"></i> Book Now
                            </a>
                        @else
                            <button class="btn btn-sm btn-light border text-muted" disabled>Unavailable</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                <i class="bi bi-door-closed fs-1 mb-2"></i>
                <h5>No meeting rooms found</h5>
                <p class="small mb-0">Try adjusting your search filters or add a new meeting room.</p>
            </div>
        </div>
    @endforelse
</div>

@if($rooms->hasPages())
    <div class="mt-4">
        {{ $rooms->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
