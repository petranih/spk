@extends('layouts.app')

@section('title', 'Lupa Password')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Header dengan tombol back -->
                        <div class="d-flex align-items-center mb-4">
                            <a href="{{ route('login') }}" class="btn btn-link text-decoration-none p-0 me-2" title="Kembali ke Login">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div class="flex-grow-1 text-center">
                                <h3 class="card-title mb-1">Lupa Password?</h3>
                                <p class="text-muted mb-0">Kami akan mengirim kode OTP ke email Anda</p>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Masukkan email yang terdaftar di sistem
                                </small>
                            </div>

                            <!-- Tombol Reset dan Kirim OTP dalam satu baris -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFormBtn">
                                        <i class="fas fa-undo me-2"></i>
                                        Reset
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Kirim OTP
                                    </button>
                                </div>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">Sudah ingat password? 
                                    <a href="{{ route('login') }}" class="text-decoration-none">Login disini</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Reset Form Button
document.getElementById('resetFormBtn').addEventListener('click', function() {
    if (confirm('Apakah Anda yakin ingin mengosongkan inputan email?')) {
        const form = document.getElementById('forgotPasswordForm');
        
        // Reset input email
        document.getElementById('email').value = '';
        
        // Hapus error message jika ada
        const errorMessages = form.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(function(error) {
            error.style.display = 'none';
        });
        
        // Hapus class invalid
        const invalidInputs = form.querySelectorAll('.is-invalid');
        invalidInputs.forEach(function(input) {
            input.classList.remove('is-invalid');
        });
        
        // Hapus alert success jika ada
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            successAlert.remove();
        }
        
        // Focus ke email input
        document.getElementById('email').focus();
    }
});

// Konfirmasi sebelum back jika ada input
const backButton = document.querySelector('a[href="{{ route('login') }}"]');
backButton.addEventListener('click', function(e) {
    const email = document.getElementById('email').value;
    
    // Jika email sudah diisi, tampilkan konfirmasi
    if (email) {
        if (!confirm('Anda memiliki data yang belum diproses. Yakin ingin kembali ke halaman login?')) {
            e.preventDefault();
        }
    }
});

// Auto dismiss success alert setelah 5 detik
const successAlert = document.querySelector('.alert-success');
if (successAlert) {
    setTimeout(function() {
        const bsAlert = new bootstrap.Alert(successAlert);
        bsAlert.close();
    }, 5000);
}
</script>
@endsection