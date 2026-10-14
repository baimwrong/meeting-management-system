@extends('layouts.app')

@section('title', 'Room Booking Timetable')
@section('page_category', 'Meetings')
@section('page_title', 'Daily Room Timetable')

@section('content')
<!-- Date Navigator Header -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('meetings.calendar', ['date' => $currentDate->copy()->subDay()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> Previous Day
                </a>
                <a href="{{ route('meetings.calendar', ['date' => \Carbon\Carbon::today()->toDateString()]) }}" class="btn btn-sm btn-outline-primary {{ $selectedDate === \Carbon\Carbon::today()->toDateString() ? 'active' : '' }}">
                    Today
                </a>
                <a href="{{ route('meetings.calendar', ['date' => $currentDate->copy()->addDay()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">
                    Next Day <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('meetings.calendar') }}" class="d-flex align-items-center gap-2 m-0">
                    <label for="calendarDate" class="small fw-semibold text-muted text-nowrap">Jump to Date:</label>
                    <input type="date" id="calendarDate" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" onchange="this.form.submit()">
                </form>
            </div>

            <div>
                <h5 class="fw-bold m-0 text-primary">
                    <i class="bi bi-calendar3 me-1"></i>{{ $currentDate->format('l, F j, Y') }}
                </h5>
            </div>
        </div>
    </div>
</div>

<!-- Timetable Grid -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Meeting Rooms Schedule</span>
        <a href="{{ route('meetings.create', ['date' => $selectedDate]) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Schedule on this Day
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 100px;">Time</th>
                        @foreach($rooms as $room)
                            <th style="min-width: 180px;">
                                <div class="fw-bold text-dark">{{ $room->name }}</div>
                                <small class="text-muted">{{ $room->code }} &bull; Cap: {{ $room->capacity }}</small>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $timeSlots = [
                            '08:00', '09:00', '10:00', '11:00', '12:00',
                            '13:00', '14:00', '15:00', '16:00', '17:00'
                        ];
                    @endphp

                    @foreach($timeSlots as $slot)
                        @php
                            $slotStart = $slot . ':00';
                            $slotEnd = date('H:i:s', strtotime($slot . ' +1 hour'));
                        @endphp
                        <tr>
                            <td class="text-center bg-light fw-semibold text-muted small py-3">
                                {{ date('h:i A', strtotime($slot)) }}
                            </td>
                            @foreach($rooms as $room)
                                @php
                                    $bookedMeeting = $meetings->first(function($m) use ($room, $slotStart, $slotEnd) {
                                        return $m->room_id === $room->id &&
                                               $m->start_time < $slotEnd &&
                                               $m->end_time > $slotStart;
                                    });
                                @endphp

                                <td class="p-2 {{ $bookedMeeting ? 'bg-primary-subtle' : '' }}" style="vertical-align: top; height: 75px;">
                                    @if($bookedMeeting)
                                        <div class="p-2 bg-white rounded border border-primary shadow-sm">
                                            <a href="{{ route('meetings.show', $bookedMeeting) }}" class="fw-bold text-primary text-decoration-none d-block small">
                                                {{ Str::limit($bookedMeeting->title, 26) }}
                                            </a>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i>{{ $bookedMeeting->formatted_time_range }}
                                            </div>
                                            <div class="small text-secondary" style="font-size: 0.75rem;">
                                                <i class="bi bi-person me-1"></i>{{ $bookedMeeting->organizer->name }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-2 opacity-50" style="font-size: 0.75rem;">
                                            Available
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
