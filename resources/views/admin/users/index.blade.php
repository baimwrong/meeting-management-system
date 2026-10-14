@extends('layouts.app')

@section('title', 'Users Management')
@section('page_category', 'Administration')
@section('page_title', 'User Directory')

@section('content')
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center justify-content-between">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, title..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="organizer" {{ request('role') === 'organizer' ? 'selected' : '' }}>Organizer</option>
                    <option value="participant" {{ request('role') === 'participant' ? 'selected' : '' }}>Participant</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-2 text-end">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-success text-white ms-1">
                    <i class="bi bi-person-plus-fill me-1"></i> Add User
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span>Users ({{ $users->total() }} Total)</span>
        <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cloud-arrow-up me-1"></i> Bulk Import Users
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Contact Phone</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $u->name }}</div>
                                    <small class="text-muted">{{ $u->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $u->role_badge_class }}">{{ ucfirst($u->role) }}</span>
                        </td>
                        <td>
                            {{ $u->department ? $u->department->name : '-' }}
                            @if($u->job_title)
                                <small class="d-block text-muted">{{ $u->job_title }}</small>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $u->phone ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $u->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-light border" title="Edit User">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($u->id !== Auth::id())
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete user {{ $u->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border text-danger" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
