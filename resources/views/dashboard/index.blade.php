@extends('layouts.app')

@section('title', 'Executive Dashboard')
@section('page_category', 'Overview')
@section('page_title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Stat 1: Today's Meetings -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Meetings Today</span>
                    <h3 class="fw-bold my-1 text-primary">{{ $todayMeetingsCount }}</h3>
                    <span class="small text-muted"><i class="bi bi-clock me-1"></i>Scheduled for today</span>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 2: Upcoming This Week -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Next 7 Days</span>
                    <h3 class="fw-bold my-1 text-info">{{ $upcomingWeekCount }}</h3>
                    <span class="small text-muted"><i class="bi bi-calendar-week me-1"></i>Upcoming sessions</span>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 3: Room Utilization Rate -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Room Utilization</span>
                    <h3 class="fw-bold my-1 text-success">{{ $utilizationStats['utilization_rate'] }}%</h3>
                    <span class="small text-muted">{{ $utilizationStats['booked_hours_today'] }}h booked of {{ $utilizationStats['available_rooms'] * 9 }}h capacity</span>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 4: My Pending Action Items -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">My Action Items</span>
                    <h3 class="fw-bold my-1 text-warning">{{ $myPendingTasksCount }}</h3>
                    <span class="small text-muted"><i class="bi bi-exclamation-circle me-1"></i>Pending / In Progress</span>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-list-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Today's Schedule -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-calendar-day me-2 text-primary"></i>Today's Agenda ({{ \Carbon\Carbon::today()->format('l, M d, Y') }})</span>
                <a href="{{ route('meetings.calendar') }}" class="btn btn-outline-primary btn-sm py-1">View Full Timetable</a>
            </div>
            <div class="card-body p-0">
                @if($todayMeetings->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-calendar2-x fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No meetings scheduled for today.</p>
                        <a href="{{ route('meetings.create') }}" class="btn btn-sm btn-link text-decoration-none mt-2">Schedule one now</a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($todayMeetings as $meeting)
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="text-center bg-light border rounded p-2" style="min-width: 85px;">
                                        <div class="small fw-bold text-primary">{{ \Carbon\Carbon::parse($meeting->start_time)->format('h:i A') }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">to {{ \Carbon\Carbon::parse($meeting->end_time)->format('h:i A') }}</div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="{{ route('meetings.show', $meeting) }}" class="text-dark text-decoration-none fw-semibold">
                                                {{ $meeting->title }}
                                            </a>
                                        </h6>
                                        <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
                                            <span><i class="bi bi-door-open me-1"></i>{{ $meeting->room ? $meeting->room->name : 'Virtual' }}</span>
                                            <span>&bull;</span>
                                            <span><i class="bi bi-person me-1"></i>{{ $meeting->organizer->name }}</span>
                                            <span class="badge {{ $meeting->status_badge_class }}">{{ ucfirst($meeting->status) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-light border">Details</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- My Action Items Pending -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-check2-circle me-2 text-warning"></i>Action Items Assigned to You</span>
                <a href="{{ route('action-items.index') }}" class="btn btn-outline-secondary btn-sm py-1">View All</a>
            </div>
            <div class="card-body p-0">
                @if($myActionItems->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-emoji-smile fs-1 text-success"></i>
                        <p class="text-muted mt-2 mb-0">All caught up! No pending action items.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($myActionItems as $task)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="fw-semibold text-dark">{{ $task->title }}</div>
                                    <span class="badge {{ $task->priority_badge_class }}">{{ ucfirst($task->priority) }}</span>
                                </div>
                                <div class="small text-muted d-flex justify-content-between align-items-center">
                                    <span>From: <a href="{{ route('meetings.show', $task->meeting) }}" class="text-decoration-none">{{ Str::limit($task->meeting->title, 25) }}</a></span>
                                    <span class="{{ $task->is_overdue ? 'text-danger fw-bold' : '' }}">
                                        <i class="bi bi-calendar-event me-1"></i>Due {{ $task->due_date->format('M d') }}
                                    </span>
                                </div>
                                <form action="{{ route('action-items.status', $task) }}" method="POST" class="mt-2 text-end">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-lg me-1"></i>Mark Done
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Offline Chart.js Visualizations -->
<div class="row g-4">
    <!-- Chart 1: Department Distribution -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-bold">
                <i class="bi bi-bar-chart-line me-2 text-primary"></i>Meetings by Department
            </div>
            <div class="card-body">
                <canvas id="deptChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Room Bookings Frequency -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-bold">
                <i class="bi bi-pie-chart me-2 text-success"></i>Busiest Meeting Rooms
            </div>
            <div class="card-body">
                <canvas id="roomChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Department Chart (Bar)
        const deptLabels = {!! json_encode($deptChartLabels) !!};
        const deptData = {!! json_encode($deptChartData) !!};

        const ctxDept = document.getElementById('deptChart');
        if (ctxDept && deptLabels.length > 0) {
            new Chart(ctxDept, {
                type: 'bar',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        label: 'Total Meetings',
                        data: deptData,
                        backgroundColor: '#0284c7',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        // 2. Room Chart (Doughnut)
        const roomLabels = {!! json_encode($roomChartLabels) !!};
        const roomData = {!! json_encode($roomChartData) !!};

        const ctxRoom = document.getElementById('roomChart');
        if (ctxRoom && roomLabels.length > 0) {
            new Chart(ctxRoom, {
                type: 'doughnut',
                data: {
                    labels: roomLabels,
                    datasets: [{
                        data: roomData,
                        backgroundColor: ['#0284c7', '#0d9488', '#f59e0b', '#6366f1', '#ec4899', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    });
</script>
@endpush
