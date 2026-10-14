@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card auth-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-event fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">{{ config('app.name', 'Meeting Management System') }}</h3>
                    <p class="text-muted small">Enterprise Meeting Management System</p>
                </div>

                @if(session('info'))
                    <div class="alert alert-info py-2 small">{{ session('info') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Work Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.local">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                            <label class="form-check-label small text-muted" for="remember">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <!-- Quick Demo Login Presets -->
                <div class="mt-4 pt-3 border-top">
                    <p class="text-center text-muted small fw-semibold mb-2">Quick Demo Accounts (1-Click Fill):</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger text-start d-flex justify-content-between align-items-center" onclick="fillCredentials('admin@company.local', 'password')">
                            <span><i class="bi bi-shield-lock me-1"></i> <strong>Admin</strong> (System Admin)</span>
                            <span class="badge bg-danger">Full Access</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary text-start d-flex justify-content-between align-items-center" onclick="fillCredentials('organizer@company.local', 'password')">
                            <span><i class="bi bi-person-video3 me-1"></i> <strong>Organizer</strong> (Meeting Lead)</span>
                            <span class="badge bg-primary">Schedule & MoM</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary text-start d-flex justify-content-between align-items-center" onclick="fillCredentials('participant@company.local', 'password')">
                            <span><i class="bi bi-person me-1"></i> <strong>Participant</strong> (Staff Member)</span>
                            <span class="badge bg-secondary">RSVP & Tasks</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function fillCredentials(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
