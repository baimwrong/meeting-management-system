@extends('layouts.app')

@section('title', $meeting->title)
@section('page_category', 'Meetings')
@section('page_title', $meeting->title)

@section('content')
<!-- Header Card -->
<div class="card mb-4 shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-light text-dark border font-monospace fs-6">{{ $meeting->meeting_code }}</span>
                    <span class="badge {{ $meeting->status_badge_class }} fs-6">{{ ucfirst(str_replace('_', ' ', $meeting->status)) }}</span>
                    <span class="badge {{ $meeting->meeting_type_badge_class }} fs-6">{{ ucfirst(str_replace('_', ' ', $meeting->meeting_type)) }}</span>
                    @if($meeting->is_confidential)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-shield-lock-fill me-1"></i>Confidential</span>
                    @endif
                </div>
                <h3 class="fw-bold text-dark mb-2">{{ $meeting->title }}</h3>
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                    <span><i class="bi bi-calendar3 me-1 text-primary"></i><strong>{{ $meeting->meeting_date->format('l, F j, Y') }}</strong></span>
                    <span>&bull;</span>
                    <span><i class="bi bi-clock me-1 text-primary"></i><strong>{{ $meeting->formatted_time_range }}</strong></span>
                    <span>&bull;</span>
                    @if($meeting->room)
                        <span><i class="bi bi-door-open me-1 text-primary"></i>{{ $meeting->room->name }} ({{ $meeting->room->floor_location }})</span>
                    @else
                        <span><i class="bi bi-camera-video me-1 text-info"></i>Virtual Meeting</span>
                    @endif
                    <span>&bull;</span>
                    <span><i class="bi bi-person me-1 text-primary"></i>Organizer: <strong>{{ $meeting->organizer->name }}</strong></span>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('meetings.print-mom', $meeting) }}" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-printer"></i>
                    <span>Print MoM</span>
                </a>

                @if(Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                    @if($meeting->status === 'scheduled')
                        <form action="{{ route('meetings.status', $meeting) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="btn btn-sm btn-info text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-play-fill fs-6"></i>
                                <span>Start Meeting</span>
                            </button>
                        </form>
                    @elseif($meeting->status === 'in_progress')
                        <form action="{{ route('meetings.status', $meeting) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>End & Complete</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-sm btn-light border d-flex align-items-center gap-1">
                        <i class="bi bi-pencil"></i>
                        <span>Edit</span>
                    </a>

                    @if(!in_array($meeting->status, ['completed', 'cancelled']))
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelMeetingModal">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @if($meeting->virtual_link)
            <div class="alert alert-info py-2 px-3 mt-3 mb-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-camera-video-fill fs-5 text-primary"></i>
                    <span><strong>Virtual Join Link:</strong> <a href="{{ $meeting->virtual_link }}" target="_blank" class="text-decoration-none">{{ $meeting->virtual_link }}</a></span>
                </div>
                <a href="{{ $meeting->virtual_link }}" target="_blank" class="btn btn-sm btn-primary py-1 px-3">Join Meeting</a>
            </div>
        @endif

        @if($meeting->status === 'cancelled')
            <div class="alert alert-danger py-2 px-3 mt-3 mb-0">
                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Meeting Cancelled:</strong> {{ $meeting->cancellation_reason ?? 'No reason provided.' }}
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Left Main Column: Tabs for Agendas, Attendees, MoM, Tasks, Files -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3 border-0" id="meetingTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3 fw-semibold" id="agendas-tab" data-bs-toggle="tab" data-bs-target="#agendas" type="button" role="tab">
                            <i class="bi bi-card-checklist me-1"></i> Agendas ({{ $meeting->agendas->count() }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" id="attendees-tab" data-bs-toggle="tab" data-bs-target="#attendees" type="button" role="tab">
                            <i class="bi bi-people me-1"></i> Attendees ({{ $meeting->attendees->count() }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" id="mom-tab" data-bs-toggle="tab" data-bs-target="#mom" type="button" role="tab">
                            <i class="bi bi-journal-text me-1"></i> Minutes (MoM)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                            <i class="bi bi-check2-square me-1"></i> Action Items ({{ $meeting->actionItems->count() }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" id="attachments-tab" data-bs-toggle="tab" data-bs-target="#attachments" type="button" role="tab">
                            <i class="bi bi-paperclip me-1"></i> Documents ({{ $meeting->attachments->count() }})
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="meetingTabsContent">
                    <!-- Tab 1: Agendas -->
                    <div class="tab-pane fade show active" id="agendas" role="tabpanel">
                        @if($meeting->description)
                            <div class="mb-4 p-3 bg-light rounded border">
                                <h6 class="fw-bold text-dark mb-1">Meeting Objective:</h6>
                                <p class="text-secondary mb-0 small">{{ $meeting->description }}</p>
                            </div>
                        @endif

                        <h6 class="fw-bold mb-3">Agenda Breakdown:</h6>
                        @if($meeting->agendas->isEmpty())
                            <p class="text-muted text-center py-4">No agenda topics defined for this meeting.</p>
                        @else
                            <div class="list-group list-group-flush border rounded">
                                @foreach($meeting->agendas as $index => $agenda)
                                    <div class="list-group-item p-3 d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="badge bg-primary rounded-circle p-2 px-3 fs-6">{{ $agenda->order_index }}</span>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1">{{ $agenda->title }}</h6>
                                                <div class="small text-muted">
                                                    @if($agenda->presenter)
                                                        <span><i class="bi bi-person me-1"></i>Presenter: <strong>{{ $agenda->presenter->name }}</strong></span>
                                                    @elseif($agenda->presenter_name)
                                                        <span><i class="bi bi-person me-1"></i>Presenter: <strong>{{ $agenda->presenter_name }}</strong></span>
                                                    @endif
                                                </div>
                                                @if($agenda->discussion_notes)
                                                    <div class="mt-2 p-2 bg-light rounded small text-secondary">
                                                        {{ $agenda->discussion_notes }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-stopwatch me-1"></i>{{ $agenda->duration_minutes }} mins
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Tab 2: Attendees & Attendance -->
                    <div class="tab-pane fade" id="attendees" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Participant Roll Call & RSVPs</h6>
                            <small class="text-muted">Total Invited: {{ $meeting->attendees->count() }}</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>RSVP</th>
                                        <th>Attendance</th>
                                        @if(Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                                            <th class="text-end">Mark Status</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meeting->attendees as $att)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                        {{ strtoupper(substr($att->display_name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $att->display_name }}</div>
                                                        <small class="text-muted">{{ $att->display_email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ ucfirst($att->role) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $att->rsvp_badge_class }}">{{ ucfirst($att->rsvp_status) }}</span>
                                                @if($att->notes)
                                                    <small class="d-block text-muted fst-italic" style="font-size: 0.75rem;">"{{ $att->notes }}"</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $att->attendance_badge_class }}">{{ ucfirst($att->attendance_status) }}</span>
                                                @if($att->checked_in_at)
                                                    <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $att->checked_in_at->format('h:i A') }}</small>
                                                @endif
                                            </td>
                                            @if(Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                                                <td class="text-end">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" data-bs-toggle="dropdown" style="font-size: 0.8rem;">
                                                            Mark
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                            @foreach(['present', 'absent', 'late', 'excused', 'unmarked'] as $statusOption)
                                                                <li>
                                                                    <form action="{{ route('meetings.attendance', $meeting) }}" method="POST">
                                                                        @csrf
                                                                        <input type="hidden" name="attendee_id" value="{{ $att->id }}">
                                                                        <input type="hidden" name="attendance_status" value="{{ $statusOption }}">
                                                                        <button type="submit" class="dropdown-item py-1 small">
                                                                            {{ ucfirst($statusOption) }}
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Minutes of Meeting (MoM) -->
                    <div class="tab-pane fade" id="mom" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Official Minutes of Meeting (MoM)</h6>
                            @if($meeting->minutes)
                                <span class="badge {{ $meeting->minutes->status_badge_class }}">
                                    {{ ucfirst($meeting->minutes->status) }}
                                </span>
                            @endif
                        </div>

                        @if(Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                            <form action="{{ route('meetings.minutes.store', $meeting) }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="summary" class="form-label fw-semibold">Executive Discussion Summary <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="summary" name="summary" rows="5" required placeholder="Outline key topics covered, discussions, and observations...">{{ old('summary', $meeting->minutes->summary ?? '') }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="key_decisions" class="form-label fw-semibold">Key Decisions Agreed Upon</label>
                                    <textarea class="form-control" id="key_decisions" name="key_decisions" rows="4" placeholder="List concrete decisions, policy approvals, or agreements reached...">{{ old('key_decisions', $meeting->minutes->key_decisions ?? '') }}</textarea>
                                </div>

                                <div class="row align-items-center g-3">
                                    <div class="col-md-6">
                                        <label for="mom_status" class="form-label fw-semibold">Minutes Status</label>
                                        <select class="form-select" id="mom_status" name="status">
                                            <option value="draft" {{ ($meeting->minutes->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft (In Progress)</option>
                                            <option value="reviewed" {{ ($meeting->minutes->status ?? '') === 'reviewed' ? 'selected' : '' }}>Reviewed by Team</option>
                                            <option value="approved" {{ ($meeting->minutes->status ?? '') === 'approved' ? 'selected' : '' }}>Approved & Finalized</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 text-md-end mt-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Save Minutes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            @if($meeting->minutes)
                                <div class="mb-4">
                                    <h6 class="text-dark fw-bold">Discussion Summary:</h6>
                                    <div class="p-3 bg-light rounded border text-secondary">
                                        {!! nl2br(e($meeting->minutes->summary)) !!}
                                    </div>
                                </div>
                                @if($meeting->minutes->key_decisions)
                                    <div class="mb-4">
                                        <h6 class="text-dark fw-bold">Decisions:</h6>
                                        <div class="p-3 bg-light rounded border text-secondary">
                                            {!! nl2br(e($meeting->minutes->key_decisions)) !!}
                                        </div>
                                    </div>
                                @endif
                                <div class="small text-muted border-top pt-2">
                                    Prepared by <strong>{{ $meeting->minutes->preparedBy->name }}</strong>
                                    @if($meeting->minutes->approvedBy)
                                        &bull; Approved by <strong>{{ $meeting->minutes->approvedBy->name }}</strong> on {{ $meeting->minutes->approved_at->format('M d, Y') }}
                                    @endif
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-text fs-1 mb-2 d-block"></i>
                                    Minutes of Meeting have not been recorded yet.
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Tab 4: Action Items / Tasks -->
                    <div class="tab-pane fade" id="tasks" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Action Items & Deliverables</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addActionItemModal">
                                <i class="bi bi-plus-lg me-1"></i> Add Action Item
                            </button>
                        </div>

                        @if($meeting->actionItems->isEmpty())
                            <p class="text-muted text-center py-4">No action items assigned for this meeting yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase">
                                        <tr>
                                            <th>Task</th>
                                            <th>Assigned To</th>
                                            <th>Due Date</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($meeting->actionItems as $task)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark">{{ $task->title }}</div>
                                                    @if($task->description)
                                                        <small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="small fw-medium">{{ $task->assignedTo->name }}</span>
                                                </td>
                                                <td>
                                                    <span class="small {{ $task->is_overdue ? 'text-danger fw-bold' : '' }}">
                                                        {{ $task->due_date->format('M d, Y') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $task->priority_badge_class }}">{{ ucfirst($task->priority) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $task->status_badge_class }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    @if(Auth::id() === $task->assigned_to_id || Auth::user()->isAdmin() || Auth::id() === $meeting->organizer_id)
                                                        <form action="{{ route('action-items.status', $task) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            @if($task->status !== 'completed')
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-xs btn-outline-success py-0 px-2" title="Mark as Completed" style="font-size: 0.75rem;">
                                                                    <i class="bi bi-check-lg"></i> Complete
                                                                </button>
                                                            @else
                                                                <input type="hidden" name="status" value="in_progress">
                                                                <button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Reopen" style="font-size: 0.75rem;">
                                                                    Reopen
                                                                </button>
                                                            @endif
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Tab 5: Documents & Attachments -->
                    <div class="tab-pane fade" id="attachments" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Meeting Materials & Files</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Upload File
                            </button>
                        </div>

                        @if($meeting->attachments->isEmpty())
                            <p class="text-muted text-center py-4">No documents or presentations attached to this meeting.</p>
                        @else
                            <div class="list-group list-group-flush border rounded">
                                @foreach($meeting->attachments as $att)
                                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-file-earmark-arrow-down fs-2 text-primary"></i>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $att->original_name }}</div>
                                                <small class="text-muted">
                                                    {{ $att->formatted_file_size }} &bull; Uploaded by {{ $att->uploadedBy->name }} &bull; {{ $att->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('attachments.download', $att) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                            @if(Auth::user()->isAdmin() || Auth::id() === $att->uploaded_by_id)
                                                <form action="{{ route('attachments.destroy', $att) }}" method="POST" onsubmit="return confirm('Delete this file?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar: RSVP Card & Room Specifications -->
    <div class="col-lg-4">
        <!-- RSVP Status Card for Logged-In User -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-envelope-paper me-1"></i> Your Attendance RSVP
            </div>
            <div class="card-body">
                @if($currentUserAttendee)
                    <div class="mb-3 text-center">
                        <span class="text-muted small d-block">Your Current Status:</span>
                        <span class="badge {{ $currentUserAttendee->rsvp_badge_class }} fs-6 px-3 py-2 mt-1">
                            {{ ucfirst($currentUserAttendee->rsvp_status) }}
                        </span>
                    </div>

                    <form action="{{ route('meetings.rsvp', $meeting) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Update Response:</label>
                            <div class="d-grid gap-2">
                                <button type="submit" name="rsvp_status" value="accepted" class="btn btn-outline-success btn-sm text-start">
                                    <i class="bi bi-check-circle me-1"></i> Accept Invitation
                                </button>
                                <button type="submit" name="rsvp_status" value="tentative" class="btn btn-outline-warning text-dark btn-sm text-start">
                                    <i class="bi bi-question-circle me-1"></i> Tentative
                                </button>
                                <button type="submit" name="rsvp_status" value="declined" class="btn btn-outline-danger btn-sm text-start">
                                    <i class="bi bi-x-circle me-1"></i> Decline
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Add optional RSVP note..." value="{{ $currentUserAttendee->notes }}">
                        </div>
                    </form>
                @else
                    <div class="text-center text-muted small py-2">
                        You are not listed as an invitee for this meeting.
                    </div>
                @endif
            </div>
        </div>

        <!-- Room Information Card -->
        @if($meeting->room)
            <div class="card mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-door-open me-2 text-primary"></i>Room Specifications
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">{{ $meeting->room->name }}</h5>
                    <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i>{{ $meeting->room->floor_location }}</p>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i>Capacity: {{ $meeting->room->capacity }} people</span>
                        <span class="badge {{ $meeting->room->status_badge_class }}">{{ ucfirst($meeting->room->status) }}</span>
                    </div>

                    @if(!empty($meeting->room->equipment))
                        <div class="small fw-semibold text-muted mb-1">Available Facilities:</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($meeting->room->equipment as $item)
                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2">{{ $item }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Cancel Meeting -->
<div class="modal fade" id="cancelMeetingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('meetings.cancel', $meeting) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Cancel Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Please specify the reason for cancellation. Attendees will be informed.</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required placeholder="e.g. Rescheduled due to key stakeholder unavailability..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Action Item -->
<div class="modal fade" id="addActionItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('meetings.action-items.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check2-circle me-1 text-primary"></i>Assign Action Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="task_title" class="form-label fw-semibold">Task Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="task_title" name="title" required placeholder="e.g. Prepare vendor assessment spreadsheet">
                    </div>
                    <div class="mb-3">
                        <label for="assigned_to_id" class="form-label fw-semibold">Assignee <span class="text-danger">*</span></label>
                        <select class="form-select" id="assigned_to_id" name="assigned_to_id" required>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->job_title ?? 'Staff' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="due_date" class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Action Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Upload Attachment -->
<div class="modal fade" id="uploadAttachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('meetings.attachments.store', $meeting) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-paperclip me-1 text-primary"></i>Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label fw-semibold">Select File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <div class="form-text">Supported formats: PDF, DOCX, XLSX, PPTX, Images (Max 20 MB).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
