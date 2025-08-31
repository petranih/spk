{{-- resources/views/student/application/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Aplikasi Beasiswa')
@section('page-title', 'Edit Aplikasi Beasiswa')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Progress Steps -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="steps-progress">
                    <div class="step {{ $application->status == 'draft' ? 'active' : 'completed' }}">
                        <div class="step-icon">1</div>
                        <div class="step-label">Data Pribadi</div>
                    </div>
                    <div class="step {{ count($existingValues) > 0 ? 'active' : '' }}">
                        <div class="step-icon">2</div>
                        <div class="step-label">Data Kriteria</div>
                    </div>
                    <div class="step {{ $documents->count() > 0 ? 'active' : '' }}">
                        <div class="step-icon">3</div>
                        <div class="step-label">Upload Dokumen</div>
                    </div>
                    <div class="step {{ $application->status == 'submitted' ? 'completed' : '' }}">
                        <div class="step-icon">4</div>
                        <div class="step-label">Submit</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('student.application.update', $application->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Data Pribadi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                                       id="full_name" name="full_name" value="{{ old('full_name', $application->full_name) }}" required>
                                @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nisn" class="form-label">NISN</label>
                                <input type="text" class="form-control @error('nisn') is-invalid @enderror" 
                                       id="nisn" name="nisn" value="{{ old('nisn', $application->nisn) }}" required>
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
                                       id="school" name="school" value="{{ old('school', $application->school) }}" required>
                                @error('school')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class" class="form-label">Kelas</label>
                                <input type="text" class="form-control @error('class') is-invalid @enderror" 
                                       id="class" name="class" value="{{ old('class', $application->class) }}" required 
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
                                       id="birth_place" name="birth_place" value="{{ old('birth_place', $application->birth_place) }}" required>
                                @error('birth_place')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" name="birth_date" value="{{ old('birth_date', $application->birth_date) }}" required>
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
                                    <option value="L" {{ old('gender', $application->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $application->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
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
                                       id="phone" name="phone" value="{{ old('phone', $application->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3" required>{{ old('address', $application->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Criteria Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>Data Kriteria AHP
                    </h5>
                </div>
                <div class="card-body">
                    @if($criterias->count() > 0)
                        @foreach($criterias as $criteria)
                            <div class="criteria-section mb-4">
                                <h6 class="fw-bold text-primary mb-3">
                                    {{ $criteria->code }} - {{ $criteria->name }}
                                </h6>
                                
                                @if($criteria->subCriterias->count() > 0)
                                    @foreach($criteria->subCriterias as $subCriteria)
                                        <div class="subcriteria-section mb-3">
                                            <label class="form-label fw-semibold">
                                                {{ $subCriteria->code }} - {{ $subCriteria->name }}
                                            </label>
                                            
                                            @if($subCriteria->subSubCriterias->count() > 0)
                                                <div class="row">
                                                    @foreach($subCriteria->subSubCriterias->chunk(2) as $chunk)
                                                        @foreach($chunk as $subSubCriteria)
                                                            <div class="col-md-6">
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" 
                                                                           type="radio" 
                                                                           name="criteria_values[subsubcriteria][{{ $subCriteria->id }}]" 
                                                                           id="subsubcriteria_{{ $subSubCriteria->id }}"
                                                                           value="{{ $subSubCriteria->id }}"
                                                                           {{ isset($existingValues['subsubcriteria_' . $subCriteria->id]) && $existingValues['subsubcriteria_' . $subCriteria->id]->value == $subSubCriteria->id ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="subsubcriteria_{{ $subSubCriteria->id }}">
                                                                        {{ $subSubCriteria->name }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="alert alert-warning">
                                                    <small>Belum ada pilihan untuk kriteria ini</small>
                                                </div>
                                            @endif
                                        </div>
                                        <hr>
                                    @endforeach
                                @else
                                    <div class="alert alert-warning">
                                        <small>Belum ada sub-kriteria untuk kriteria ini</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Kriteria belum dikonfigurasi oleh admin. Silakan hubungi administrator.
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Document Upload Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-upload me-2"></i>Upload Dokumen
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Upload Form -->
                    <form action="{{ route('student.application.upload', $application->id) }}" method="POST" enctype="multipart/form-data" class="border p-3 rounded bg-light mb-3">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="document_type" class="form-label">Jenis Dokumen</label>
                                    <select class="form-select" id="document_type" name="document_type" required>
                                        <option value="">Pilih Jenis</option>
                                        <option value="ktp">KTP Orang Tua</option>
                                        <option value="kk">Kartu Keluarga</option>
                                        <option value="slip_gaji">Slip Gaji / Surat Keterangan Penghasilan</option>
                                        <option value="surat_keterangan">Surat Keterangan Tidak Mampu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="document_name" class="form-label">Nama Dokumen</label>
                                    <input type="text" class="form-control" id="document_name" name="document_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="file" class="form-label">File</label>
                                    <input type="file" class="form-control" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Max 2MB, format: PDF, JPG, PNG</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-upload me-1"></i>Upload
                        </button>
                    </form>
                    
                    <!-- Uploaded Documents List -->
                    @if($documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Jenis Dokumen</th>
                                        <th>Nama</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal Upload</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $doc)
                                    <tr>
                                        <td>
                                            @switch($doc->document_type)
                                                @case('ktp')
                                                    <span class="badge bg-primary">KTP</span>
                                                    @break
                                                @case('kk')
                                                    <span class="badge bg-info">KK</span>
                                                    @break
                                                @case('slip_gaji')
                                                    <span class="badge bg-success">Slip Gaji</span>
                                                    @break
                                                @case('surat_keterangan')
                                                    <span class="badge bg-warning">Surat Keterangan</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $doc->document_type }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $doc->document_name }}</td>
                                        <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                        <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('student.application.document.delete', [$application->id, $doc->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus dokumen ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-file-upload fa-3x mb-2"></i>
                            <p>Belum ada dokumen yang diupload</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Application Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Aplikasi</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>No. Aplikasi</td>
                            <td>: <code>APP-{{ $application->id }}</code></td>
                        </tr>
                        <tr>
                            <td>Periode</td>
                            <td>: {{ $application->period->name }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>: 
                                @if($application->status == 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($application->status == 'submitted')
                                    <span class="badge bg-warning">Menunggu Validasi</span>
                                @elseif($application->status == 'validated')
                                    <span class="badge bg-success">Tervalidasi</span>
                                @elseif($application->status == 'rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Terakhir Update</td>
                            <td>: {{ $application->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Progress Checklist -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Progress Checklist</h6>
                </div>
                <div class="card-body">
                    <div class="checklist">
                        <div class="checklist-item {{ $application->full_name ? 'completed' : '' }}">
                            <i class="fas {{ $application->full_name ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Data pribadi lengkap
                        </div>
                        <div class="checklist-item {{ count($existingValues) > 0 ? 'completed' : '' }}">
                            <i class="fas {{ count($existingValues) > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Data kriteria terisi
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'ktp')->count() > 0 ? 'completed' : '' }}">
                            <i class="fas {{ $documents->where('document_type', 'ktp')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload KTP
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'kk')->count() > 0 ? 'completed' : '' }}">
                            <i class="fas {{ $documents->where('document_type', 'kk')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload KK
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'slip_gaji')->count() > 0 ? 'completed' : '' }}">
                            <i class="fas {{ $documents->where('document_type', 'slip_gaji')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload Slip Gaji
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'surat_keterangan')->count() > 0 ? 'completed' : '' }}">
                            <i class="fas {{ $documents->where('document_type', 'surat_keterangan')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload Surat Keterangan
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                        
                        @if($application->status == 'draft')
                            @php
                                $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
                                $uploadedDocs = $documents->pluck('document_type')->toArray();
                                $canSubmit = count(array_diff($requiredDocs, $uploadedDocs)) == 0 && count($existingValues) > 0;
                            @endphp
                            
                            @if($canSubmit)
                                <form action="{{ route('student.application.submit', $application->id) }}" method="POST" onsubmit="return confirm('Yakin ingin submit aplikasi? Setelah disubmit, Anda tidak dapat mengubah data lagi.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Aplikasi
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-success" disabled title="Lengkapi semua data dan dokumen terlebih dahulu">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Aplikasi
                                </button>
                                <small class="text-muted mt-1">
                                    Lengkapi semua data dan upload semua dokumen untuk dapat submit
                                </small>
                            @endif
                        @endif
                        
                        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('styles')
<style>
.steps-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}

.steps-progress::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50px;
    right: 50px;
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 2;
    background: white;
    padding: 0 10px;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 5px;
}

.step.active .step-icon {
    background: #007bff;
    color: white;
}

.step.completed .step-icon {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 12px;
    text-align: center;
    color: #6c757d;
}

.step.active .step-label,
.step.completed .step-label {
    color: #495057;
    font-weight: 500;
}

.criteria-section {
    border-left: 3px solid #007bff;
    padding-left: 15px;
}

.subcriteria-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.checklist-item {
    display: flex;
    align-items: center;
    padding: 5px 0;
}

.checklist-item i {
    margin-right: 10px;
}

.checklist-item.completed {
    color: #28a745;
}
</style>
@endpush
@endsection