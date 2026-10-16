@extends('layouts.app')

@section('title', 'Edit Meeting')
@section('page_category', 'Meetings')
@section('page_title', 'Edit: ' . $meeting->title)

@section('content')
<form action="{{ route('meetings.update', $meeting) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Left Column: Details & Agendas -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Meeting Details & Status</span>
                    <span class="badge bg-light text-dark font-monospace">{{ $meeting->meeting_code }}</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $meeting->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="scheduled" {{ old('status', $meeting->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="in_progress" {{ old('status', $meeting->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ old('status', $meeting->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rescheduled" {{ old('status', $meeting->status) === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                                <option value="cancelled" {{ old('status', $meeting->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="meeting_type" class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('meeting_type') is-invalid @enderror" id="meeting_type" name="meeting_type" required onchange="toggleRoomRequired()">
                                <option value="in_person" {{ old('meeting_type', $meeting->meeting_type) === 'in_person' ? 'selected' : '' }}>In-Person</option>
                                <option value="hybrid" {{ old('meeting_type', $meeting->meeting_type) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                <option value="virtual" {{ old('meeting_type', $meeting->meeting_type) === 'virtual' ? 'selected' : '' }}>Virtual Only</option>
                            </select>
                            @error('meeting_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="department_id" class="form-label fw-semibold">Department</label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                                <option value="">None</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $meeting->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3" id="roomGroup">
                        <label for="room_id" class="form-label fw-semibold">Meeting Room <span id="roomAsterisk" class="text-danger">*</span></label>
                        <select class="form-select @error('room_id') is-invalid @enderror" id="room_id" name="room_id" onchange="checkAvailability()">
                            <option value="">-- Select Room --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id', $meeting->room_id) == $room->id ? 'selected' : '' }}>
                                    {{ $room->name }} ({{ $room->code }}) - Capacity: {{ $room->capacity }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div id="conflictAlert" class="alert py-2 px-3 mt-2 d-none small"></div>
                    </div>

                    <div class="mb-3 {{ $meeting->meeting_type === 'in_person' ? 'd-none' : '' }}" id="virtualLinkGroup">
                        <label for="virtual_link" class="form-label fw-semibold">Virtual Meeting Link</label>
                        <input type="url" class="form-control @error('virtual_link') is-invalid @enderror" id="virtual_link" name="virtual_link" value="{{ old('virtual_link', $meeting->virtual_link) }}" placeholder="https://meet.google.com/xyz or Zoom link">
                        @error('virtual_link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="meeting_date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('meeting_date') is-invalid @enderror" id="meeting_date" name="meeting_date" value="{{ old('meeting_date', $meeting->meeting_date->format('Y-m-d')) }}" required onchange="checkAvailability()">
                            @error('meeting_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', substr($meeting->start_time, 0, 5)) }}" required onchange="checkAvailability()">
                            @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ old('end_time', substr($meeting->end_time, 0, 5)) }}" required onchange="checkAvailability()">
                            @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $meeting->description) }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="is_confidential" name="is_confidential" value="1" {{ old('is_confidential', $meeting->is_confidential) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_confidential">Mark as Confidential</label>
                    </div>
                </div>
            </div>

            <!-- Agendas Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-card-checklist me-2 text-primary"></i>Meeting Agendas & Topics</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAgendaRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Topic
                    </button>
                </div>
                <div class="card-body">
                    <div id="agendasContainer">
                        @forelse($meeting->agendas as $index => $agenda)
                            <div class="row g-2 mb-2 align-items-center agenda-row">
                                <div class="col-md-6">
                                    <input type="text" name="agendas[{{ $index }}][title]" class="form-control form-control-sm" placeholder="Agenda Title / Topic" required value="{{ old("agendas.{$index}.title", $agenda->title) }}">
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="agendas[{{ $index }}][duration_minutes]" class="form-control" min="1" value="{{ old("agendas.{$index}.duration_minutes", $agenda->duration_minutes) }}" required>
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select name="agendas[{{ $index }}][presenter_id]" class="form-select form-select-sm">
                                        <option value="">-- Presenter --</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" {{ old("agendas.{$index}.presenter_id", $agenda->presenter_id) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAgendaRow(this)"><i class="bi bi-dash-circle"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="row g-2 mb-2 align-items-center agenda-row">
                                <div class="col-md-6">
                                    <input type="text" name="agendas[0][title]" class="form-control form-control-sm" placeholder="Agenda Title / Topic" required value="General Discussion">
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="agendas[0][duration_minutes]" class="form-control" min="1" value="30" required>
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
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAgendaRow(this)"><i class="bi bi-dash-circle"></i></button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Attendees Selection & Actions -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header fw-bold">
                    <i class="bi bi-people me-2 text-primary"></i>Invited Participants
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-2">Check or uncheck attendees and define their participation role.</p>
                    <div style="max-height: 420px; overflow-y: auto;" class="border rounded p-2">
                        @foreach($users as $index => $user)
                            @php
                                $isOrganizer = $user->id === $meeting->organizer_id;
                                $existingAttendee = $meeting->attendees->firstWhere('user_id', $user->id);
                                $isChecked = $isOrganizer || old("attendees.{$index}.user_id") == $user->id || (!old() && $existingAttendee !== null);
                                $currentRole = old("attendees.{$index}.role", $existingAttendee ? $existingAttendee->role : 'required');
                            @endphp
                            <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                <div class="form-check m-0">
                                    <input class="form-check-input attendee-checkbox" type="checkbox" name="attendees[{{ $index }}][user_id]" value="{{ $user->id }}" id="user_{{ $user->id }}" {{ $isChecked ? 'checked' : '' }} {{ $isOrganizer ? 'disabled' : '' }} onchange="const sel = document.getElementById('role_{{ $user->id }}'); if (sel) { sel.disabled = !this.checked; }">
                                    <label class="form-check-label small fw-semibold ms-1" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                        @if($isOrganizer)
                                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.7rem;">Host (Organizer)</span>
                                        @elseif($existingAttendee)
                                            <span class="badge {{ $existingAttendee->rsvp_badge_class }} ms-1" style="font-size: 0.65rem;">{{ ucfirst($existingAttendee->rsvp_status) }}</span>
                                        @endif
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $user->job_title ?? ($user->department ? $user->department->name : 'Staff') }}</div>
                                    </label>
                                </div>
                                <select name="attendees[{{ $index }}][role]" id="role_{{ $user->id }}" class="form-select form-select-sm w-auto py-0 px-2" style="font-size: 0.8rem;" {{ (!$isChecked || $isOrganizer) ? 'disabled' : '' }}>
                                    <option value="required" {{ $currentRole === 'required' ? 'selected' : '' }}>Required</option>
                                    <option value="optional" {{ $currentRole === 'optional' ? 'selected' : '' }}>Optional</option>
                                    <option value="presenter" {{ $currentRole === 'presenter' ? 'selected' : '' }}>Presenter</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card bg-light border-primary">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-2">Update Meeting</h6>
                    <p class="small text-muted mb-3">Changes will be saved and reflected on room schedule and participants dashboard.</p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Meeting Changes
                        </button>
                        <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let agendaIndex = {{ max(count($meeting->agendas), 1) + 10 }};

    function addAgendaRow() {
        const container = document.getElementById('agendasContainer');
        const userOptions = `
            @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        `;

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
            linkGroup.classList.add('d-none');
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

        const url = `{{ route('meetings.check-availability') }}?room_id=${roomId}&date=${date}&start_time=${startTime}&end_time=${endTime}&exclude_meeting_id={{ $meeting->id }}`;

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
