@extends('layouts.app')

@section('title', 'Schedule Meeting')
@section('page_category', 'Meetings')
@section('page_title', 'Schedule New Meeting')

@section('content')
<form action="{{ route('meetings.store') }}" method="POST" id="createMeetingForm">
    @csrf

    <div class="row g-4">
        <!-- Left Column: Core Meeting Details -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Meeting Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Q4 Executive Strategy Alignment">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="meeting_type" class="form-label fw-semibold">Meeting Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="meeting_type" name="meeting_type" required onchange="toggleRoomRequired()">
                                <option value="in_person" {{ old('meeting_type') === 'in_person' ? 'selected' : '' }}>In-Person (Room Required)</option>
                                <option value="hybrid" {{ old('meeting_type') === 'hybrid' ? 'selected' : '' }}>Hybrid (Room + Online Link)</option>
                                <option value="virtual" {{ old('meeting_type') === 'virtual' ? 'selected' : '' }}>Virtual Only</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label fw-semibold">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Select Department (Optional)</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }} ({{ $dept->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Room Selection & Conflict Alert -->
                    <div class="mb-3" id="roomSelectionGroup">
                        <label for="room_id" class="form-label fw-semibold">Meeting Room <span class="text-danger" id="roomAsterisk">*</span></label>
                        <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id" onchange="checkAvailability()">
                            <option value="">-- Choose Meeting Room --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" data-capacity="{{ $room->capacity }}" {{ (old('room_id', $prefillRoomId) == $room->id) ? 'selected' : '' }}>
                                    {{ $room->name }} ({{ $room->code }}) &bull; Cap: {{ $room->capacity }} &bull; {{ $room->floor_location }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <!-- Live Conflict Feedback Banner -->
                        <div id="conflictAlert" class="mt-2 p-2 rounded small d-none"></div>
                    </div>

                    <div class="mb-3" id="virtualLinkGroup">
                        <label for="virtual_link" class="form-label fw-semibold">Virtual Meeting URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                            <input type="url" class="form-control" id="virtual_link" name="virtual_link" value="{{ old('virtual_link') }}" placeholder="https://meet.google.com/... or https://teams.microsoft.com/...">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="meeting_date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date" value="{{ old('meeting_date', $prefillDate) }}" required onchange="checkAvailability()">
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="{{ old('start_time', '09:00') }}" required onchange="checkAvailability()">
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="{{ old('end_time', '10:30') }}" required onchange="checkAvailability()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description / Objective</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="State the primary objective, expected outcomes, or preparation required...">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_confidential" name="is_confidential" value="1" {{ old('is_confidential') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_confidential">Mark as Confidential Meeting</label>
                        <div class="form-text">Restricts visibility and minutes access to invited attendees only.</div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Agenda Builder -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-card-checklist me-2 text-primary"></i>Meeting Agendas & Topics</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAgendaRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Topic
                    </button>
                </div>
                <div class="card-body">
                    <div id="agendasContainer">
                        <!-- Initial Row 1 -->
                        <div class="row g-2 mb-2 align-items-center agenda-row">
                            <div class="col-md-6">
                                <input type="text" name="agendas[0][title]" class="form-control form-control-sm" placeholder="Agenda Title / Topic" required value="Welcome & Objective Overview">
                            </div>
                            <div class="col-md-2">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="agendas[0][duration_minutes]" class="form-control" min="1" value="15" required>
                                    <span class="input-group-text">min</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="agendas[0][presenter_id]" class="form-select form-select-sm">
                                    <option value="">-- Presenter --</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAgendaRow(this)" disabled><i class="bi bi-dash-circle"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Attendees Selection & Summary -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-people me-2 text-primary"></i>Invite Participants
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-2">Select staff members to invite and define their participation role.</p>
                    <div style="max-height: 400px; overflow-y: auto;" class="border rounded p-2">
                        @foreach($users as $index => $user)
                            <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="attendees[{{ $index }}][user_id]" value="{{ $user->id }}" id="user_{{ $user->id }}" {{ $user->id === Auth::id() ? 'checked disabled' : '' }}>
                                    <label class="form-check-label small fw-semibold ms-1" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $user->job_title ?? ($user->department ? $user->department->name : 'Staff') }}</div>
                                    </label>
                                </div>
                                <select name="attendees[{{ $index }}][role]" class="form-select form-select-sm w-auto py-0 px-2" style="font-size: 0.8rem;">
                                    <option value="required" selected>Required</option>
                                    <option value="optional">Optional</option>
                                    <option value="presenter">Presenter</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Submission Card -->
            <div class="card bg-light border-primary">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-2">Ready to Schedule?</h6>
                    <p class="small text-muted mb-3">All attendees will receive notification and calendar placement. Room bookings will be strictly reserved.</p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-calendar-check me-1"></i> Confirm & Book Meeting
                        </button>
                        <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let agendaIndex = 1;

    function addAgendaRow() {
        const container = document.getElementById('agendasContainer');
        const userOptions = `{!! addslashes(collect($users)->map(fn($u) => "<option value='{$u->id}'>{$u->name}</option>")->implode('')) !!}`;

        const html = `
            <div class="row g-2 mb-2 align-items-center agenda-row">
                <div class="col-md-6">
                    <input type="text" name="agendas[${agendaIndex}][title]" class="form-control form-control-sm" placeholder="Agenda Title / Topic" required>
                </div>
                <div class="col-md-2">
                    <div class="input-group input-group-sm">
                        <input type="number" name="agendas[${agendaIndex}][duration_minutes]" class="form-control" min="1" value="15" required>
                        <span class="input-group-text">min</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="agendas[${agendaIndex}][presenter_id]" class="form-select form-select-sm">
                        <option value="">-- Presenter --</option>
                        ${userOptions}
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAgendaRow(this)"><i class="bi bi-dash-circle"></i></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        agendaIndex++;
    }

    function removeAgendaRow(btn) {
        btn.closest('.agenda-row').remove();
    }

    function toggleRoomRequired() {
        const type = document.getElementById('meeting_type').value;
        const roomSelect = document.getElementById('room_id');
        const asterisk = document.getElementById('roomAsterisk');
        const linkGroup = document.getElementById('virtualLinkGroup');

        if (type === 'virtual') {
            roomSelect.value = '';
            asterisk.classList.add('d-none');
            roomSelect.removeAttribute('required');
            linkGroup.classList.remove('d-none');
        } else if (type === 'hybrid') {
            asterisk.classList.remove('d-none');
            roomSelect.setAttribute('required', 'required');
            linkGroup.classList.remove('d-none');
        } else {
            asterisk.classList.remove('d-none');
            roomSelect.setAttribute('required', 'required');
        }
        checkAvailability();
    }

    function checkAvailability() {
        const roomId = document.getElementById('room_id').value;
        const date = document.getElementById('meeting_date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const alertBox = document.getElementById('conflictAlert');

        if (!roomId || !date || !startTime || !endTime) {
            alertBox.classList.add('d-none');
            return;
        }

        const url = `{{ route('meetings.check-availability') }}?room_id=${roomId}&date=${date}&start_time=${startTime}&end_time=${endTime}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                alertBox.classList.remove('d-none', 'alert-danger', 'alert-success', 'bg-danger-subtle', 'text-danger', 'bg-success-subtle', 'text-success');
                if (data.available) {
                    alertBox.classList.add('bg-success-subtle', 'text-success', 'border', 'border-success-subtle');
                    alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${data.message}`;
                } else {
                    alertBox.classList.add('bg-danger-subtle', 'text-danger', 'border', 'border-danger-subtle');
                    alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Conflict Detected:</strong> ${data.message}`;
                }
            })
            .catch(err => {
                console.error('Availability check failed:', err);
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleRoomRequired();
    });
</script>
@endpush
