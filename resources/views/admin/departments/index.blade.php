@extends('layouts.app')

@section('title', 'Departments Management')
@section('page_category', 'Administration')
@section('page_title', 'Departments Directory')

@section('content')
<div class="row g-4">
    <!-- Left Column: Add Department Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                <i class="bi bi-buildings me-2 text-primary"></i>Add Department
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.departments.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Information Technology">
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label fw-semibold">Short Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="e.g. IT" maxlength="20">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief function of department..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-semibold" for="is_active">Active Status</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Save Department
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Departments Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                Existing Departments ({{ $departments->total() }} Total)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 90px;">Code</th>
                            <th>Department Name</th>
                            <th>Users</th>
                            <th>Meetings</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $d)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">{{ $d->code }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $d->name }}</div>
                                    @if($d->description)
                                        <small class="text-muted">{{ Str::limit($d->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $d->users_count }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $d->meetings_count }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $d->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $d->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editDeptModal{{ $d->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($d->users_count === 0 && $d->meetings_count === 0)
                                        <form action="{{ route('admin.departments.destroy', $d) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete department {{ $d->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editDeptModal{{ $d->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('admin.departments.update', $d) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Department</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $d->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Short Code</label>
                                                    <input type="text" name="code" class="form-control" value="{{ $d->code }}" required maxlength="20">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea name="description" class="form-control" rows="3">{{ $d->description }}</textarea>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $d->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold">Active Status</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No departments created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($departments->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $departments->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
