@extends('layouts.app')

@section('title', 'Import Error Report')
@section('page_category', 'Administration')
@section('page_title', 'Import Batch Error Report')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Summary Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Batch Summary: {{ $log->file_name }}</span>
                <span class="badge {{ $log->status_badge_class }} fs-6">{{ ucfirst(str_replace('_', ' ', $log->status)) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center">
                    <div class="col-md-3 border-end">
                        <div class="text-muted small">Entity Type</div>
                        <h5 class="fw-bold text-dark mt-1">{{ ucfirst($log->entity_type) }}</h5>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="text-muted small">Total Rows Processed</div>
                        <h5 class="fw-bold text-dark mt-1">{{ $log->total_rows }}</h5>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="text-muted small">Successful Imports</div>
                        <h5 class="fw-bold text-success mt-1">{{ $log->success_rows }}</h5>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Failed / Skipped Rows</div>
                        <h5 class="fw-bold text-danger mt-1">{{ $log->failed_rows }}</h5>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4 small text-muted">
                    <div>Imported by: <strong>{{ $log->user->name }}</strong> on {{ $log->created_at->format('M d, Y h:i A') }}</div>
                    <a href="{{ route('admin.bulk-import.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Import Hub
                    </a>
                </div>
            </div>
        </div>

        <!-- Line by Line Errors -->
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Validation Failure Details</span>
                <span class="badge bg-white text-danger">{{ $log->failed_rows }} Errors</span>
            </div>
            <div class="card-body p-0">
                @if(empty($log->errors_json))
                    <div class="p-4 text-center text-muted">No line-level error details were recorded.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 90px;">Line #</th>
                                    <th>Identifier / Key</th>
                                    <th>Error Explanation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($log->errors_json as $err)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary font-monospace">Row {{ $err['row'] ?? '?' }}</span>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            {{ $err['identifier'] ?? ($err['email'] ?? 'N/A') }}
                                        </td>
                                        <td>
                                            <ul class="mb-0 text-danger small ps-3">
                                                @foreach((array)($err['errors'] ?? []) as $eMsg)
                                                    <li>{{ $eMsg }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
