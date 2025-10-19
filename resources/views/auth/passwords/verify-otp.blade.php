@extends('layouts.app')

@section('title', 'Verifikasi OTP')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h3 class="card-title mb-1">Verifikasi Kode OTP</h3>
                            <p class="text-muted">Masukkan kode OTP yang telah dikirim ke email Anda</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Untuk Development: Tampilkan OTP -->
                        @if (app()->isLocal() && session('otp_for_testing'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <strong>🔒 Development Mode:</strong><br>
                                Kode OTP Anda: <strong style="font-size: 1.2rem; color: #0c5460;">{{ session('otp_for_testing') }}</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.verify-otp.post') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ $email ?? old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="otp" class="form-label">Kode OTP (6 Digit)</label>
                                <input type="text" class="form-control @error('otp') is-invalid @enderror"
                                       id="otp" name="otp" placeholder="000000" maxlength="6" 
                                       inputmode="numeric" required autofocus>
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-2">Kode OTP berlaku selama 15 menit</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-check me-2"></i>
                                Verifikasi OTP
                            </button>

                            <div class="text-center">
                                <p class="mb-2">
                                    <a href="{{ route('password.request') }}" class="text-decoration-none">
                                        Minta Kode OTP Baru
                                    </a>
                                </p>
                                <p class="mb-0">Kembali ke 
                                    <a href="{{ route('login') }}" class="text-decoration-none">Login</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection