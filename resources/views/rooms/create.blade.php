@extends('layouts.app')

@section('title', 'Add Meeting Room')
@section('page_category', 'Facilities')
@section('page_title', 'Create Meeting Room')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                <i class="bi bi-door-open me-2 text-primary"></i>Room Specifications & Facilities
            </div>
            <div class="card-body p-4">
                <form action="{{ route('rooms.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">Room Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Executive Boardroom Alpha">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label fw-semibold">Room Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g. BOARD-01">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="floor_location" class="form-label fw-semibold">Floor / Building Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('floor_location') is-invalid @enderror" id="floor_location" name="floor_location" value="{{ old('floor_location') }}" required placeholder="e.g. Floor 3, East Wing">
                            @error('floor_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="capacity" class="form-label fw-semibold">Capacity (Seats) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 12) }}" required min="1">
                            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" selected>Available</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="equipment_string" class="form-label fw-semibold">Amenities & Equipment</label>
                        <input type="text" class="form-control" id="equipment_string" name="equipment_string" value="{{ old('equipment_string') }}" placeholder="Comma-separated: e.g. 4K Projector, Polycom Video Conference, Dual Whiteboard, Wireless Presentation">
                        <div class="form-text">Enter available equipment separated by commas.</div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">Room Notes / Policies</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g. Catering permitted with 24h notice. Cleanup mandatory.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Meeting Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
