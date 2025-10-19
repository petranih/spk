{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <!-- Header dengan tombol back ke home -->
                        <div class="d-flex align-items-center mb-4">
                            <a href="{{ route('home') }}" class="btn btn-link text-decoration-none p-0 me-2" title="Kembali ke Beranda">
                                <i class="fas fa-home"></i>
                            </a>
                            <div class="flex-grow-1 text-center">
                                <h3 class="card-title mb-1">Sistem Beasiswa</h3>
                                <p class="text-muted mb-0">Login untuk melanjutkan</p>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-color: #dee2e6;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ingat saya
                                </label>
                            </div>

                            <div class="mb-3 text-end">
                                <a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size: 0.9rem;">
                                    Lupa Password?
                                </a>
                            </div>
                            
                            <!-- Tombol Reset dan Login dalam satu baris -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFormBtn">
                                        <i class="fas fa-undo me-2"></i>
                                        Reset
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Login
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Belum punya akun? 
                                    <a href="{{ route('register') }}" class="text-decoration-none">Daftar disini</a>
                                </p>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Demo Login:</strong><br>
                                Admin: admin@beasiswa.com / admin123<br>
                                Validator: validator1@beasiswa.com / validator123<br>
                                Student: alfian@student.com / student123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Password
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Reset Form Button
document.getElementById('resetFormBtn').addEventListener('click', function() {
    if (confirm('Apakah Anda yakin ingin mengosongkan semua inputan?')) {
        const form = document.getElementById('loginForm');
        
        // Reset semua input
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('remember').checked = false;
        
        // Reset tipe password ke hidden
        document.getElementById('password').type = 'password';
        
        // Reset icon mata
        const eyeIcon = document.querySelector('#togglePassword i');
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
        
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
        
        // Focus ke email
        document.getElementById('email').focus();
    }
});
</script>
@endsection