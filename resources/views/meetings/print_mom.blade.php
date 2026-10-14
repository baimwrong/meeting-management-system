<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minutes of Meeting - {{ $meeting->meeting_code }}</title>

    <!-- Local Offline Assets: Bootstrap 5.3 -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 2rem 0;
        }

        .mom-document {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .mom-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .mom-document {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Floating Print Bar (Hidden on print) -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print" style="max-width: 900px; margin: 0 auto;">
            <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Meeting
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4 shadow-sm">
                <i class="bi bi-printer me-1"></i> Print / Save as PDF
            </button>
        </div>

        <div class="mom-document">
            <!-- Header Section -->
            <div class="mom-header d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="fw-bold text-dark mb-1">MINUTES OF MEETING</h2>
                    <h5 class="text-primary mb-0">{{ $meeting->title }}</h5>
                </div>
                <div class="text-end">
                    <div class="badge bg-dark font-monospace fs-6 mb-1">{{ $meeting->meeting_code }}</div>
                    <div class="small text-muted">Status: <strong>{{ ucfirst($meeting->minutes ? $meeting->minutes->status : 'Draft') }}</strong></div>
                </div>
            </div>

            <!-- Metadata Table -->
            <div class="row g-3 mb-4 p-3 bg-light rounded border">
                <div class="col-md-6">
                    <div class="small text-muted">Date & Time:</div>
                    <div class="fw-bold">{{ $meeting->meeting_date->format('l, F j, Y') }} &bull; {{ $meeting->formatted_time_range }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Venue / Platform:</div>
                    <div class="fw-bold">{{ $meeting->room ? $meeting->room->name . ' (' . $meeting->room->floor_location . ')' : 'Virtual Conferencing' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Meeting Organizer:</div>
                    <div class="fw-bold">{{ $meeting->organizer->name }} ({{ $meeting->organizer->email }})</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Department:</div>
                    <div class="fw-bold">{{ $meeting->department ? $meeting->department->name : 'N/A' }}</div>
                </div>
            </div>

            <!-- Attendees Roll-Call -->
            <div class="section-title">1. Attendance & Roll Call</div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm small align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Attendee Name</th>
                            <th>Role</th>
                            <th>Attendance Status</th>
                            <th>RSVP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->attendees as $att)
                            <tr>
                                <td class="fw-semibold">{{ $att->display_name }}</td>
                                <td>{{ ucfirst($att->role) }}</td>
                                <td>
                                    <span class="badge {{ $att->attendance_badge_class }}">{{ ucfirst($att->attendance_status) }}</span>
                                </td>
                                <td>{{ ucfirst($att->rsvp_status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Agendas -->
            <div class="section-title">2. Agenda Topics</div>
            @if($meeting->agendas->isEmpty())
                <p class="text-muted small">No agenda breakdown specified.</p>
            @else
                <ol class="list-group list-group-numbered mb-0">
                    @foreach($meeting->agendas as $agenda)
                        <li class="list-group-item d-flex justify-content-between align-items-start border-0 ps-0">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ $agenda->title }}</div>
                                @if($agenda->presenter)
                                    <small class="text-muted">Presenter: {{ $agenda->presenter->name }}</small>
                                @endif
                                @if($agenda->discussion_notes)
                                    <div class="small text-secondary mt-1">{{ $agenda->discussion_notes }}</div>
                                @endif
                            </div>
                            <span class="badge bg-light text-dark border">{{ $agenda->duration_minutes }} min</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            <!-- Summary & Discussion -->
            <div class="section-title">3. Discussion Summary</div>
            <div class="p-3 bg-light rounded border text-secondary small">
                @if($meeting->minutes && $meeting->minutes->summary)
                    {!! nl2br(e($meeting->minutes->summary)) !!}
                @else
                    <em>No discussion summary recorded yet.</em>
                @endif
            </div>

            <!-- Key Decisions -->
            <div class="section-title">4. Key Decisions & Resolutions</div>
            <div class="p-3 bg-light rounded border text-secondary small">
                @if($meeting->minutes && $meeting->minutes->key_decisions)
                    {!! nl2br(e($meeting->minutes->key_decisions)) !!}
                @else
                    <em>No explicit decisions logged.</em>
                @endif
            </div>

            <!-- Action Items -->
            <div class="section-title">5. Action Items & Assigned Deliverables</div>
            @if($meeting->actionItems->isEmpty())
                <p class="text-muted small">No action items assigned.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Action Item / Deliverable</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meeting->actionItems as $i => $task)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-semibold">{{ $task->title }}</td>
                                    <td>{{ $task->assignedTo->name }}</td>
                                    <td>{{ $task->due_date->format('M d, Y') }}</td>
                                    <td>{{ ucfirst($task->priority) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Formal Sign-Off Footer -->
            <div class="mt-5 pt-4 border-top">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-bottom mx-auto mb-2" style="width: 200px; height: 60px;"></div>
                        <div class="fw-bold">{{ $meeting->organizer->name }}</div>
                        <div class="small text-muted">Meeting Organizer / Host</div>
                    </div>
                    <div class="col-6">
                        <div class="border-bottom mx-auto mb-2" style="width: 200px; height: 60px;"></div>
                        <div class="fw-bold">{{ $meeting->minutes && $meeting->minutes->approvedBy ? $meeting->minutes->approvedBy->name : 'Chairperson / Approver' }}</div>
                        <div class="small text-muted">Approved & Finalized</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
