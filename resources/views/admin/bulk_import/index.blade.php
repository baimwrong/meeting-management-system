@extends('layouts.app')

@section('title', 'Admin Bulk Data Import')
@section('page_category', 'Administration')
@section('page_title', 'Bulk Data Import Hub')

@section('content')
<!-- Introduction Banner -->
<div class="card mb-4 bg-primary text-white border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                <i class="bi bi-cloud-arrow-up-fill fs-2"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1">Enterprise Bulk Data Import</h4>
                <p class="mb-0 opacity-75">Quickly onboard your organization by importing users, meeting rooms, and scheduled meetings via structured CSV spreadsheets.</p>
            </div>
        </div>
    </div>
</div>

<!-- 3 Import Cards -->
<div class="row g-4 mb-4">
    <!-- Card 1: Users Import -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-top border-4 border-primary">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6"><i class="bi bi-people-fill me-1"></i>Users</span>
                    <a href="{{ route('admin.bulk-import.template', 'users') }}" class="btn btn-outline-secondary btn-sm" title="Download Sample CSV">
                        <i class="bi bi-download me-1"></i> Sample CSV
                    </a>
                </div>

                <h5 class="fw-bold text-dark mb-2">Import User Directory</h5>
                <p class="text-muted small mb-3">Upload employee rosters with roles (<code>admin</code>, <code>organizer</code>, <code>participant</code>), departments, job titles, and credentials.</p>

                <div class="bg-light p-2 rounded small text-secondary mb-3 font-monospace" style="font-size: 0.75rem;">
                    <strong>Columns:</strong> name, email, role, department, phone, job_title, status, password
                </div>

                <form action="{{ route('admin.bulk-import.store') }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                    @csrf
                    <input type="hidden" name="entity_type" value="users">
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-upload me-1"></i> Start Users Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card 2: Rooms Import -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-top border-4 border-success">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="bi bi-door-open-fill me-1"></i>Meeting Rooms</span>
                    <a href="{{ route('admin.bulk-import.template', 'rooms') }}" class="btn btn-outline-secondary btn-sm" title="Download Sample CSV">
                        <i class="bi bi-download me-1"></i> Sample CSV
                    </a>
                </div>

                <h5 class="fw-bold text-dark mb-2">Import Meeting Rooms</h5>
                <p class="text-muted small mb-3">Upload corporate facilities, boardrooms, focus pods with seating capacity, floor location, and amenities equipment lists.</p>

                <div class="bg-light p-2 rounded small text-secondary mb-3 font-monospace" style="font-size: 0.75rem;">
                    <strong>Columns:</strong> name, code, floor_location, capacity, equipment, hourly_rate, status, notes
                </div>

                <form action="{{ route('admin.bulk-import.store') }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                    @csrf
                    <input type="hidden" name="entity_type" value="rooms">
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100 text-white">
                        <i class="bi bi-upload me-1"></i> Start Rooms Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Card 3: Meetings Import -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-top border-4 border-info">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-info-subtle text-info border border-info-subtle fs-6"><i class="bi bi-calendar-event-fill me-1"></i>Meetings</span>
                    <a href="{{ route('admin.bulk-import.template', 'meetings') }}" class="btn btn-outline-secondary btn-sm" title="Download Sample CSV">
                        <i class="bi bi-download me-1"></i> Sample CSV
                    </a>
                </div>

                <h5 class="fw-bold text-dark mb-2">Import Batch Meetings</h5>
                <p class="text-muted small mb-3">Schedule recurring or historical meeting series in bulk. Automatic conflict detection prevents room double-booking.</p>

                <div class="bg-light p-2 rounded small text-secondary mb-3 font-monospace" style="font-size: 0.75rem;">
                    <strong>Columns:</strong> title, meeting_code, room_code, organizer_email, meeting_date, start_time, end_time, meeting_type, virtual_link, department_code, description
                </div>

                <form action="{{ route('admin.bulk-import.store') }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                    @csrf
                    <input type="hidden" name="entity_type" value="meetings">
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-info btn-sm w-100 text-white">
                        <i class="bi bi-upload me-1"></i> Start Meetings Import
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Import Audit History Log Table -->
<div class="card">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Bulk Import Audit History</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th scope="col">Date & Time</th>
                    <th scope="col">Entity</th>
                    <th scope="col">File Name</th>
                    <th scope="col">Admin User</th>
                    <th scope="col">Total Rows</th>
                    <th scope="col">Success</th>
                    <th scope="col">Failed</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst($log->entity_type) }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold font-monospace small text-dark">{{ $log->file_name }}</span>
                        </td>
                        <td class="small">{{ $log->user->name }}</td>
                        <td><strong>{{ $log->total_rows }}</strong></td>
                        <td class="text-success fw-bold">{{ $log->success_rows }}</td>
                        <td class="{{ $log->failed_rows > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $log->failed_rows }}</td>
                        <td>
                            <span class="badge {{ $log->status_badge_class }}">
                                {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($log->failed_rows > 0)
                                <a href="{{ route('admin.bulk-import.show', $log) }}" class="btn btn-sm btn-outline-danger py-0 px-2 small">
                                    <i class="bi bi-bug me-1"></i> Error Report
                                </a>
                            @else
                                <span class="text-muted small"><i class="bi bi-check2 text-success"></i> Clean</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-cloud-arrow-up fs-1 d-block mb-2"></i>
                            No bulk import operations have been recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
