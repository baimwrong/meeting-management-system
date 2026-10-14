@extends('layouts.app')

@section('title', 'Edit Room')
@section('page_category', 'Facilities')
@section('page_title', 'Edit Room: ' . $room->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Edit Room Details</span>
                <span class="badge bg-light text-dark border font-monospace">{{ $room->code }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('rooms.update', $room) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">Room Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $room->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label fw-semibold">Room Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $room->code) }}" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="floor_location" class="form-label fw-semibold">Floor / Building Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('floor_location') is-invalid @enderror" id="floor_location" name="floor_location" value="{{ old('floor_location', $room->floor_location) }}" required>
                            @error('floor_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="capacity" class="form-label fw-semibold">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $room->capacity) }}" required min="1">
                            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" {{ old('status', $room->status) === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="maintenance" {{ old('status', $room->status) === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="inactive" {{ old('status', $room->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="equipment_string" class="form-label fw-semibold">Amenities & Equipment</label>
                        @php
                            $equipStr = is_array($room->equipment) ? implode(', ', $room->equipment) : '';
                        @endphp
                        <input type="text" class="form-control" id="equipment_string" name="equipment_string" value="{{ old('equipment_string', $equipStr) }}">
                        <div class="form-text">Comma-separated facilities list.</div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">Room Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $room->notes) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
