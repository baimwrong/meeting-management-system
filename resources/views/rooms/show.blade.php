@extends('layouts.app')

@section('title', $room->name)
@section('page_category', 'Facilities')
@section('page_title', $room->name)

@section('content')
<div class="row g-4">
    <!-- Left Column: Room Profile -->
    <div class="col-lg-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-light text-dark border font-monospace fs-6">{{ $room->code }}</span>
                    <span class="badge {{ $room->status_badge_class }} fs-6">{{ ucfirst($room->status) }}</span>
                </div>

                <h4 class="fw-bold text-dark mb-1">{{ $room->name }}</h4>
                <p class="text-muted small mb-3">
                    <i class="bi bi-geo-alt me-1 text-primary"></i>{{ $room->floor_location }}
                </p>

                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="row text-center g-2">
                        <div class="col-6 border-end">
                            <div class="small text-muted">Capacity</div>
                            <div class="fs-5 fw-bold text-dark">{{ $room->capacity }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Rate / Hour</div>
                            <div class="fs-5 fw-bold text-dark">${{ number_format($room->hourly_rate, 2) }}</div>
                        </div>
                    </div>
                </div>

                @if(!empty($room->equipment))
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark small text-uppercase mb-2">Installed Amenities</h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($room->equipment as $item)
                                <span class="badge bg-white text-secondary border py-1 px-2">
                                    <i class="bi bi-check2 text-success me-1"></i>{{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($room->notes)
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark small text-uppercase mb-1">Room Guidelines / Notes</h6>
                        <p class="text-secondary small mb-0">{{ $room->notes }}</p>
                    </div>
                @endif

                <div class="d-grid gap-2 pt-3 border-top mt-4">
                    @if($room->status === 'available')
                        <a href="{{ route('meetings.create', ['room_id' => $room->id]) }}" class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-1"></i> Book this Room
                        </a>
                    @endif

                    @if(Auth::user()->isAdmin() || Auth::user()->isOrganizer())
                        <a href="{{ route('rooms.edit', $room) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-pencil me-1"></i> Edit Room Details
                        </a>
                    @endif

                    @if(Auth::user()->isAdmin())
                        <form action="{{ route('rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this meeting room?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-trash me-1"></i> Delete Room
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Room Schedules -->
    <div class="col-lg-8">
        <!-- Today's Schedule -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock me-2 text-primary"></i>Today's Room Schedule ({{ \Carbon\Carbon::today()->format('M d, Y') }})</span>
                <span class="badge bg-primary">{{ $todayMeetings->count() }} Bookings</span>
            </div>
            <div class="card-body p-0">
                @if($todayMeetings->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-1 text-success d-block mb-1"></i>
                        This room is free all day today.
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($todayMeetings as $m)
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small fw-bold text-primary">{{ $m->formatted_time_range }}</div>
                                    <a href="{{ route('meetings.show', $m) }}" class="fw-semibold text-dark text-decoration-none">
                                        {{ $m->title }}
                                    </a>
                                    <div class="small text-muted">Organizer: {{ $m->organizer->name }}</div>
                                </div>
                                <span class="badge {{ $m->status_badge_class }}">{{ ucfirst($m->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                <i class="bi bi-calendar3 me-2 text-primary"></i>Upcoming Bookings
            </div>
            <div class="card-body p-0">
                @if($upcomingMeetings->isEmpty())
                    <div class="text-center py-4 text-muted">
                        No upcoming bookings scheduled for this room.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Meeting Title</th>
                                    <th>Organizer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingMeetings as $um)
                                    <tr>
                                        <td class="fw-semibold">{{ $um->meeting_date->format('M d, Y') }}</td>
                                        <td>{{ $um->formatted_time_range }}</td>
                                        <td>
                                            <a href="{{ route('meetings.show', $um) }}" class="text-dark text-decoration-none fw-medium">
                                                {{ $um->title }}
                                            </a>
                                        </td>
                                        <td>{{ $um->organizer->name }}</td>
                                        <td>
                                            <span class="badge {{ $um->status_badge_class }}">{{ ucfirst($um->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
