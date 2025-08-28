{{-- resources/views/admin/period/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Periode Baru')
@section('page-title', 'Buat Periode Pendaftaran Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Form Periode Baru
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.period.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Contoh: Periode Beasiswa 2024/2025 Semester Ganjil"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Berikan nama yang jelas dan mudah diidentifikasi untuk periode ini
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Deskripsi periode pendaftaran beasiswa ini...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Opsional. Berikan informasi tambahan tentang periode ini
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('start_date') is-invalid @enderror" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="{{ old('start_date', date('Y-m-d')) }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('end_date') is-invalid @enderror" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="{{ old('end_date') }}" 
                                       required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>Aktifkan periode ini sekarang</strong>
                            </label>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                            Jika dicentang, periode lain yang sedang aktif akan dinonaktifkan secara otomatis
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Informasi Penting:
                        </h6>
                        <ul class="mb-0 small">
                            <li>Hanya satu periode yang bisa aktif pada satu waktu</li>
                            <li>Siswa hanya bisa mendaftar pada periode yang aktif</li>
                            <li>Tanggal berakhir harus lebih besar dari tanggal mulai</li>
                            <li>Setelah ada aplikasi masuk, periode tidak dapat dihapus</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.period.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Periode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Tips Periode
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <h6>Penamaan yang Baik:</h6>
                    <ul>
                        <li>Periode Beasiswa 2024/2025</li>
                        <li>Pendaftaran Semester Ganjil 2024</li>
                        <li>Beasiswa Prestasi Tahun 2024</li>
                    </ul>
                    
                    <h6 class="mt-3">Durasi yang Disarankan:</h6>
                    <ul>
                        <li>Minimal: 2 minggu</li>
                        <li>Optimal: 1-2 bulan</li>
                        <li>Maksimal: 3 bulan</li>
                    </ul>
                    
                    <h6 class="mt-3">Hal yang Perlu Dipersiapkan:</h6>
                    <ul>
                        <li>Kriteria AHP sudah setup</li>
                        <li>Pairwise comparison selesai</li>
                        <li>Validator sudah ditentukan</li>
                        <li>Dokumen persyaratan jelas</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Periode Aktif Saat Ini
                </h6>
            </div>
            <div class="card-body">
                @php
                    $activePeriod = \App\Models\Period::where('is_active', true)->first();
                @endphp
                
                @if($activePeriod)
                    <div class="text-center">
                        <span class="badge bg-success fs-6">{{ $activePeriod->name }}</span>
                        <div class="small text-muted mt-2">
                            {{ $activePeriod->start_date->format('d/m/Y') }} - {{ $activePeriod->end_date->format('d/m/Y') }}
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted">
                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                        <p class="small mb-0">Tidak ada periode aktif</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        // Set minimum end date to start date + 1 day
        const startDate = new Date(this.value);
        startDate.setDate(startDate.getDate() + 1);
        endDateInput.min = startDate.toISOString().split('T')[0];
        
        // If end date is before start date, clear it
        if (endDateInput.value && new Date(endDateInput.value) <= new Date(this.value)) {
            endDateInput.value = '';
        }
    });
    
    // Set initial minimum end date
    if (startDateInput.value) {
        const startDate = new Date(startDateInput.value);
        startDate.setDate(startDate.getDate() + 1);
        endDateInput.min = startDate.toISOString().split('T')[0];
    }
});
</script>
@endsection