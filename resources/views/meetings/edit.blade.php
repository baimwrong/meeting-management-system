@extends('layouts.app')

@section('title', 'Edit Meeting')
@section('page_category', 'Meetings')
@section('page_title', 'Edit: ' . $meeting->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form action="{{ route('meetings.update', $meeting) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Meeting Details & Status</span>
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
                            <select class="form-select" id="status" name="status" required>
                                <option value="scheduled" {{ old('status', $meeting->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="in_progress" {{ old('status', $meeting->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ old('status', $meeting->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rescheduled" {{ old('status', $meeting->status) === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                                <option value="cancelled" {{ old('status', $meeting->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="meeting_type" class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="meeting_type" name="meeting_type" required>
                                <option value="in_person" {{ old('meeting_type', $meeting->meeting_type) === 'in_person' ? 'selected' : '' }}>In-Person</option>
                                <option value="hybrid" {{ old('meeting_type', $meeting->meeting_type) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                <option value="virtual" {{ old('meeting_type', $meeting->meeting_type) === 'virtual' ? 'selected' : '' }}>Virtual Only</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="department_id" class="form-label fw-semibold">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">None</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $meeting->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_id" class="form-label fw-semibold">Meeting Room</label>
                        <select class="form-select" id="room_id" name="room_id">
                            <option value="">-- No Room (Virtual) --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id', $meeting->room_id) == $room->id ? 'selected' : '' }}>
                                    {{ $room->name }} ({{ $room->code }}) - Capacity: {{ $room->capacity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="virtual_link" class="form-label fw-semibold">Virtual Meeting Link</label>
                        <input type="url" class="form-control" id="virtual_link" name="virtual_link" value="{{ old('virtual_link', $meeting->virtual_link) }}">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="meeting_date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date" value="{{ old('meeting_date', $meeting->meeting_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="{{ old('start_time', substr($meeting->start_time, 0, 5)) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="{{ old('end_time', substr($meeting->end_time, 0, 5)) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $meeting->description) }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="is_confidential" name="is_confidential" value="1" {{ old('is_confidential', $meeting->is_confidential) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_confidential">Mark as Confidential</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
