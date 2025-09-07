{{-- resources/views/student/application/create.blade.php - PERBAIKAN untuk Multiple Periods --}}
@extends('layouts.app')

@section('title', 'Buat Aplikasi Beasiswa')
@section('page-title', 'Buat Aplikasi Beasiswa')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Aplikasi Beasiswa</h5>
                <small class="text-muted">
                    Periode: <strong>{{ $targetPeriod->name }}</strong>
                    <span class="badge bg-{{ $targetPeriod->is_ongoing ? 'success' : 'info' }}">
                        {{ $targetPeriod->remaining_days }}
                    </span>
                </small>
            </div>
            <div class="card-body">
                <form action="{{ route('student.application.store') }}" method="POST">
                    @csrf
                    
                    {{-- Hidden field untuk period_id --}}
                    <input type="hidden" name="period_id" value="{{ $targetPeriod->id }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                                       id="full_name" name="full_name" value="{{ old('full_name', Auth::user()->name) }}" required>
                                @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nisn" class="form-label">NISN</label>
                                <input type="text" class="form-control @error('nisn') is-invalid @enderror" 
                                       id="nisn" name="nisn" value="{{ old('nisn') }}" required>
                                @error('nisn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school" class="form-label">Sekolah</label>
                                <input type="text" class="form-control @error('school') is-invalid @enderror" 
                                       id="school" name="school" value="{{ old('school') }}" required>
                                @error('school')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class" class="form-label">Kelas</label>
                                <input type="text" class="form-control @error('class') is-invalid @enderror" 
                                       id="class" name="class" value="{{ old('class') }}" required 
                                       placeholder="Contoh: XII IPA 1">
                                @error('class')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_place" class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control @error('birth_place') is-invalid @enderror" 
                                       id="birth_place" name="birth_place" value="{{ old('birth_place') }}" required>
                                @error('birth_place')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required>
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Jenis Kelamin</label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                        id="gender" name="gender" required>
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">No. Telepon</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', Auth::user()->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3" required>{{ old('address', Auth::user()->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan & Lanjutkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Periode</h6>
            </div>
            <div class="card-body">
                @if(isset($targetPeriod))
                    <table class="table table-sm">
                        <tr>
                            <td>Nama</td>
                            <td>: {{ $targetPeriod->name }}</td>
                        </tr>
                        <tr>
                            <td>Mulai</td>
                            <td>: {{ $targetPeriod->start_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>Berakhir</td>
                            <td>: {{ $targetPeriod->end_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>: 
                                <span class="badge bg-{{ $targetPeriod->is_ongoing ? 'success' : ($targetPeriod->is_upcoming ? 'info' : 'secondary') }}">
                                    @if($targetPeriod->is_ongoing)
                                        Sedang Berlangsung
                                    @elseif($targetPeriod->is_upcoming)
                                        Akan Datang
                                    @else
                                        Berakhir
                                    @endif
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Sisa Waktu</td>
                            <td>: <span class="badge bg-info">{{ $targetPeriod->remaining_days }}</span></td>
                        </tr>
                        @if($targetPeriod->max_applications)
                        <tr>
                            <td>Kuota</td>
                            <td>: {{ $targetPeriod->applications_count }}/{{ $targetPeriod->max_applications }} pendaftar</td>
                        </tr>
                        @endif
                    </table>
                    
                    @if($targetPeriod->description)
                        <hr>
                        <h6>Deskripsi:</h6>
                        <p class="small text-muted">{{ $targetPeriod->description }}</p>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Tidak ada periode aktif yang tersedia saat ini.
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Langkah Selanjutnya</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li class="text-success"><i class="fas fa-check me-1"></i> Isi data pribadi (form ini)</li>
                    <li>Lengkapi data kriteria AHP</li>
                    <li>Upload dokumen pendukung</li>
                    <li>Review dan submit aplikasi</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Pastikan semua data yang diisi sudah benar karena setelah submit tidak dapat diubah kembali.
                    </small>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Dokumen Yang Perlu Disiapkan
                </h6>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li><i class="fas fa-id-card text-primary me-1"></i> Kartu Keluarga (KK)</li>
                    <li><i class="fas fa-id-card text-primary me-1"></i> KTP Orang Tua</li>
                    <li><i class="fas fa-file-invoice-dollar text-success me-1"></i> Slip Gaji / Surat Keterangan Penghasilan</li>
                    <li><i class="fas fa-file-alt text-info me-1"></i> Surat Keterangan Tidak Mampu (SKTM)</li>
                </ul>
                
                <div class="alert alert-light mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Format: PDF/JPG, Maksimal 2MB per file
                    </small>
                </div>
            </div>
        </div>
        
        {{-- Peringatan periode --}}
        @if(!$targetPeriod->is_ongoing)
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Peringatan
                </h6>
            </div>
            <div class="card-body">
                <p class="small mb-0">
                    @if($targetPeriod->is_upcoming)
                        Periode ini belum dimulai. Aplikasi akan disimpan sebagai draft dan dapat diselesaikan setelah periode dimulai.
                    @else
                        Periode ini sudah berakhir. Pastikan Anda menyelesaikan aplikasi sebelum deadline.
                    @endif
                </p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill NISN validation
    const nisnInput = document.getElementById('nisn');
    nisnInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
    });
    
    // Auto-fill phone validation
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9+\-\s]/g, '').substring(0, 15);
    });
    
    // Form validation before submit
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const requiredFields = ['full_name', 'nisn', 'school', 'class', 'birth_date', 'birth_place', 'gender', 'address', 'phone'];
        let isValid = true;
        
        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang wajib diisi');
            return false;
        }
    });
});
</script>
@endsection