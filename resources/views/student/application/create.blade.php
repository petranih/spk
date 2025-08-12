{{-- resources/views/student/application/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Aplikasi Beasiswa')
@section('page-title', 'Buat Aplikasi Beasiswa')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Aplikasi Beasiswa</h5>
                <small class="text-muted">Periode: {{ $activePeriod->name }}</small>
            </div>
            <div class="card-body">
                <form action="{{ route('student.application.store') }}" method="POST">
                    @csrf
                    
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
                <table class="table table-sm">
                    <tr>
                        <td>Nama</td>
                        <td>: {{ $activePeriod->name }}</td>
                    </tr>
                    <tr>
                        <td>Mulai</td>
                        <td>: {{ $activePeriod->start_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Berakhir</td>
                        <td>: {{ $activePeriod->end_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>: <span class="badge bg-success">Aktif</span></td>
                    </tr>
                </table>
                
                @if($activePeriod->description)
                    <hr>
                    <h6>Deskripsi:</h6>
                    <p class="small text-muted">{{ $activePeriod->description }}</p>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Langkah Selanjutnya</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Isi data pribadi (form ini)</li>
                    <li>Lengkapi data kriteria AHP</li>
                    <li>Upload dokumen pendukung</li>
                    <li>Review dan submit aplikasi</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection