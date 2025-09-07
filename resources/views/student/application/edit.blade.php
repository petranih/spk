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

<!-- PERBAIKAN UTAMA: Satu form untuk semua data -->
<form id="mainApplicationForm" action="{{ route('student.application.update', $application->id) }}" method="POST">
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
            
            <!-- PERBAIKAN: Criteria Section dengan input form yang benar -->
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
                                    @php
                                        // Cek apakah ada subcriteria yang memiliki subsubcriteria
                                        $hasSubSubCriteria = $criteria->subCriterias->some(function($sub) {
                                            return $sub->subSubCriterias->count() > 0;
                                        });
                                    @endphp
                                    
                                    @if($hasSubSubCriteria)
                                        {{-- Ada Sub-Sub-Kriteria, tampilkan per subcriteria --}}
                                        @foreach($criteria->subCriterias as $subCriteria)
                                            @if($subCriteria->subSubCriterias->count() > 0)
                                                <div class="subcriteria-section mb-3">
                                                    <label class="form-label fw-semibold">
                                                        {{ $subCriteria->code }} - {{ $subCriteria->name }}
                                                    </label>
                                                    
                                                    <div class="row">
                                                        @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                                            <div class="col-md-6">
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input criteria-input" 
                                                                           type="radio" 
                                                                           name="criteria_values[subsubcriteria][{{ $subCriteria->id }}]" 
                                                                           id="subsubcriteria_{{ $subSubCriteria->id }}"
                                                                           value="{{ $subSubCriteria->id }}"
                                                                           {{ isset($existingValues['subsubcriteria_' . $subCriteria->id]) && $existingValues['subsubcriteria_' . $subCriteria->id]->value == $subSubCriteria->id ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="subsubcriteria_{{ $subSubCriteria->id }}">
                                                                        {{ $subSubCriteria->name }}
                                                                        @if($subSubCriteria->score)
                                                                            <small class="text-muted">(Skor: {{ $subSubCriteria->score }})</small>
                                                                        @endif
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @if(!$loop->last)<hr>@endif
                                            @endif
                                        @endforeach
                                    @else
                                        {{-- Tidak ada subsubcriteria, langsung pilih dari subcriteria --}}
                                        <div class="subcriteria-section">
                                            <label class="form-label fw-semibold mb-3">
                                                Pilih {{ $criteria->name }}:
                                            </label>
                                            
                                            <div class="row">
                                                @foreach($criteria->subCriterias as $subCriteria)
                                                    <div class="col-md-6">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input criteria-input" 
                                                                   type="radio" 
                                                                   name="criteria_values[subcriteria][{{ $criteria->id }}]" 
                                                                   id="subcriteria_{{ $subCriteria->id }}"
                                                                   value="{{ $subCriteria->id }}"
                                                                   {{ isset($existingValues['subcriteria_' . $criteria->id]) && $existingValues['subcriteria_' . $criteria->id]->value == $subCriteria->id ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="subcriteria_{{ $subCriteria->id }}">
                                                                {{ $subCriteria->name }}
                                                                @if($subCriteria->score)
                                                                    <small class="text-muted">(Skor: {{ $subCriteria->score }})</small>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
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
            
            <!-- Document Upload Section - AJAX Upload -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-upload me-2"></i>Upload Dokumen
                    </h5>
                </div>
                <div class="card-body">
                    <!-- PERBAIKAN: Upload form menggunakan AJAX, terpisah dari main form -->
                    <div class="border p-3 rounded bg-light mb-3" id="uploadSection">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="document_type" class="form-label">Jenis Dokumen</label>
                                    <select class="form-select" id="document_type" required>
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
                                    <input type="text" class="form-control" id="document_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="file" class="form-label">File</label>
                                    <input type="file" class="form-control" id="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Max 2MB, format: PDF, JPG, PNG</small>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" id="uploadBtn">
                            <i class="fas fa-upload me-1"></i>Upload
                        </button>
                        <div id="uploadProgress" class="mt-2" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">Uploading...</small>
                        </div>
                    </div>
                    
                    <!-- Documents List -->
                    <div id="documentsContainer">
                        @if($documents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm" id="documentsTable">
                                    <thead>
                                        <tr>
                                            <th>Jenis Dokumen</th>
                                            <th>Nama</th>
                                            <th>Ukuran</th>
                                            <th>Tanggal Upload</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="documentsTableBody">
                                        @foreach($documents as $doc)
                                        <tr id="doc_{{ $doc->id }}" data-doc-type="{{ $doc->document_type }}">
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
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" 
                                                        data-doc-id="{{ $doc->id }}" 
                                                        data-delete-url="{{ route('student.application.document.delete', [$application->id, $doc->id]) }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted" id="emptyDocuments">
                                <i class="fas fa-file-upload fa-3x mb-2"></i>
                                <p>Belum ada dokumen yang diupload</p>
                            </div>
                        @endif
                    </div>
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
                    <div class="checklist" id="progressChecklist">
                        <div class="checklist-item {{ $application->full_name ? 'completed' : '' }}">
                            <i class="fas {{ $application->full_name ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Data pribadi lengkap
                        </div>
                        <div class="checklist-item {{ count($existingValues) > 0 ? 'completed' : '' }}" id="criteria-check">
                            <i class="fas {{ count($existingValues) > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Data kriteria terisi
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'ktp')->count() > 0 ? 'completed' : '' }}" id="ktp-check">
                            <i class="fas {{ $documents->where('document_type', 'ktp')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload KTP
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'kk')->count() > 0 ? 'completed' : '' }}" id="kk-check">
                            <i class="fas {{ $documents->where('document_type', 'kk')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload KK
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'slip_gaji')->count() > 0 ? 'completed' : '' }}" id="slip_gaji-check">
                            <i class="fas {{ $documents->where('document_type', 'slip_gaji')->count() > 0 ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                            Upload Slip Gaji
                        </div>
                        <div class="checklist-item {{ $documents->where('document_type', 'surat_keterangan')->count() > 0 ? 'completed' : '' }}" id="surat_keterangan-check">
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
                        <!-- PERBAIKAN: Button untuk save data utama -->
                        <button type="submit" form="mainApplicationForm" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                        
                        @if($application->status == 'draft')
                            @php
                                $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
                                $uploadedDocs = $documents->pluck('document_type')->toArray();
                                $canSubmit = count(array_diff($requiredDocs, $uploadedDocs)) == 0 && count($existingValues) > 0;
                            @endphp
                            
                            <div id="submitSection">
                                <button type="button" class="btn btn-success w-100" 
                                        id="submitApplicationBtn" 
                                        data-url="{{ route('student.application.submit', $application->id) }}"
                                        {{ !$canSubmit ? 'disabled' : '' }}>
                                    <i class="fas fa-paper-plane me-2"></i>Submit Aplikasi
                                </button>
                                @if(!$canSubmit)
                                    <small class="text-muted mt-1 d-block" id="submitHelp">
                                        Lengkapi semua data dan upload semua dokumen untuk dapat submit
                                    </small>
                                @endif
                            </div>
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
    transition: all 0.3s ease;
}

.checklist-item i {
    margin-right: 10px;
}

.checklist-item.completed {
    color: #28a745;
}

.criteria-input:checked + label {
    font-weight: 500;
    color: #007bff;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-fill document name berdasarkan jenis dokumen
    $('#document_type').on('change', function() {
        const type = $(this).val();
        const documentNames = {
            'ktp': 'KTP Orang Tua',
            'kk': 'Kartu Keluarga',
            'slip_gaji': 'Slip Gaji Orang Tua',
            'surat_keterangan': 'Surat Keterangan Tidak Mampu'
        };
        
        if (documentNames[type]) {
            $('#document_name').val(documentNames[type]);
        }
    });

    // PERBAIKAN: Upload dokumen dengan AJAX
    $('#uploadBtn').on('click', function(e) {
        e.preventDefault();
        
        const documentType = $('#document_type').val();
        const documentName = $('#document_name').val();
        const file = $('#file')[0].files[0];
        
        if (!documentType || !documentName || !file) {
            showAlert('danger', 'Mohon lengkapi semua field upload');
            return;
        }
        
        if (file.size > 2048 * 1024) {
            showAlert('danger', 'Ukuran file maksimal 2MB');
            return;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('danger', 'Tipe file harus PDF, JPG, JPEG, atau PNG');
            return;
        }
        
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('document_type', documentType);
        formData.append('document_name', documentName);
        formData.append('file', file);
        
        const uploadBtn = $('#uploadBtn');
        const uploadProgress = $('#uploadProgress');
        
        uploadBtn.prop('disabled', true);
        uploadBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Uploading...');
        uploadProgress.show();
        
        $.ajax({
            url: '{{ route("student.application.upload", $application->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Reset upload form
                    $('#document_type').val('');
                    $('#document_name').val('');
                    $('#file').val('');
                    
                    updateDocumentsTable(response.document, response);
                    updateChecklist(response.document.document_type);
                    updateSubmitButton();
                    
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Upload gagal');
                }
            },
            error: function(xhr) {
                let message = 'Upload gagal. Silakan coba lagi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('danger', message);
            },
            complete: function() {
                uploadBtn.prop('disabled', false);
                uploadBtn.html('<i class="fas fa-upload me-1"></i>Upload');
                uploadProgress.hide();
            }
        });
    });
    
    // Delete document
    $(document).on('click', '.delete-doc-btn', function(e) {
        e.preventDefault();
        
        if (!confirm('Yakin hapus dokumen ini?')) {
            return;
        }
        
        const docId = $(this).data('doc-id');
        const deleteUrl = $(this).data('delete-url');
        const btn = $(this);
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: deleteUrl,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#doc_' + docId).fadeOut(300, function() {
                        $(this).remove();
                        
                        if ($('#documentsTableBody tr').length === 0) {
                            $('#documentsContainer').html(`
                                <div class="text-center text-muted" id="emptyDocuments">
                                    <i class="fas fa-file-upload fa-3x mb-2"></i>
                                    <p>Belum ada dokumen yang diupload</p>
                                </div>
                            `);
                        }
                    });
                    
                    updateChecklistAfterDelete();
                    updateSubmitButton();
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Gagal menghapus dokumen');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal menghapus dokumen. Silakan coba lagi.');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-trash"></i>');
            }
        });
    });
    
    // PERBAIKAN: Handle submit aplikasi
    $('#submitApplicationBtn').on('click', function(e) {
        e.preventDefault();
        
        if ($(this).prop('disabled')) {
            showAlert('warning', 'Pastikan semua data sudah lengkap sebelum submit');
            return;
        }
        
        if (!confirm('Yakin ingin submit aplikasi?\n\nSetelah disubmit, Anda tidak dapat mengubah data lagi.')) {
            return;
        }
        
        const submitUrl = $(this).data('url');
        const btn = $(this);
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...');
        
        $.ajax({
            url: submitUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message + ' Redirecting...');
                    
                    setTimeout(function() {
                        window.location.href = response.redirect_url || '{{ route("student.dashboard") }}';
                    }, 2000);
                } else {
                    showAlert('danger', response.message || 'Gagal submit aplikasi');
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-paper-plane me-2"></i>Submit Aplikasi');
                }
            },
            error: function(xhr) {
                let message = 'Gagal submit aplikasi. Silakan coba lagi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('danger', message);
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-paper-plane me-2"></i>Submit Aplikasi');
            }
        });
    });
    
    // PERBAIKAN: Update checklist criteria saat input berubah
    $('.criteria-input').on('change', function() {
        const checkedCriteria = $('.criteria-input:checked').length;
        const criteriaCheck = $('#criteria-check');
        
        if (checkedCriteria > 0) {
            criteriaCheck.addClass('completed');
            criteriaCheck.find('i').removeClass('fa-circle text-muted').addClass('fa-check-circle text-success');
        } else {
            criteriaCheck.removeClass('completed');
            criteriaCheck.find('i').removeClass('fa-check-circle text-success').addClass('fa-circle text-muted');
        }
        
        updateSubmitButton();
    });
    
    // Helper functions
    function updateDocumentsTable(document, response) {
        if ($('#documentsTable').length === 0) {
            $('#documentsContainer').html(`
                <div class="table-responsive">
                    <table class="table table-sm" id="documentsTable">
                        <thead>
                            <tr>
                                <th>Jenis Dokumen</th>
                                <th>Nama</th>
                                <th>Ukuran</th>
                                <th>Tanggal Upload</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="documentsTableBody"></tbody>
                    </table>
                </div>
            `);
        }
        
        // Remove existing row with same document type
        $(`tr[data-doc-type="${document.document_type}"]`).remove();
        
        // Add new row
        const badgeClass = getBadgeClass(document.document_type);
        const newRow = `
            <tr id="doc_${document.id}" data-doc-type="${document.document_type}">
                <td><span class="badge ${badgeClass}">${response.document_type_display}</span></td>
                <td>${document.document_name}</td>
                <td>${response.file_size_display}</td>
                <td>${response.created_at_display}</td>
                <td>
                    <a href="${response.view_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" 
                            data-doc-id="${document.id}" 
                            data-delete-url="${response.delete_url}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#documentsTableBody').append(newRow);
    }
    
    function getBadgeClass(documentType) {
        const badges = {
            'ktp': 'bg-primary',
            'kk': 'bg-info', 
            'slip_gaji': 'bg-success',
            'surat_keterangan': 'bg-warning'
        };
        return badges[documentType] || 'bg-secondary';
    }
    
    function updateChecklist(documentType) {
        const checkElement = $(`#${documentType}-check`);
        checkElement.addClass('completed');
        checkElement.find('i')
            .removeClass('fa-circle text-muted')
            .addClass('fa-check-circle text-success');
    }
    
    function updateChecklistAfterDelete() {
        const docTypes = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        
        docTypes.forEach(type => {
            const hasDoc = $(`tr[data-doc-type="${type}"]`).length > 0;
            const checkElement = $(`#${type}-check`);
            
            if (hasDoc) {
                checkElement.addClass('completed');
                checkElement.find('i')
                    .removeClass('fa-circle text-muted')
                    .addClass('fa-check-circle text-success');
            } else {
                checkElement.removeClass('completed');
                checkElement.find('i')
                    .removeClass('fa-check-circle text-success')
                    .addClass('fa-circle text-muted');
            }
        });
    }
    
    function updateSubmitButton() {
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const allDocsUploaded = requiredDocs.every(type => 
            $(`tr[data-doc-type="${type}"]`).length > 0
        );
        
        const hasCriteriaValues = $('.criteria-input:checked').length > 0;
        const canSubmit = allDocsUploaded && hasCriteriaValues;
        
        const submitBtn = $('#submitApplicationBtn');
        const submitHelp = $('#submitHelp');
        
        if (canSubmit) {
            submitBtn.prop('disabled', false);
            submitBtn.removeClass('btn-secondary').addClass('btn-success');
            if (submitHelp.length) submitHelp.hide();
        } else {
            submitBtn.prop('disabled', true);
            submitBtn.removeClass('btn-success').addClass('btn-secondary');
            if (submitHelp.length) {
                submitHelp.show();
                
                const missingItems = [];
                if (!hasCriteriaValues) missingItems.push('pilih kriteria');
                if (!allDocsUploaded) {
                    const missingDocs = requiredDocs.filter(type => 
                        $(`tr[data-doc-type="${type}"]`).length === 0
                    );
                    if (missingDocs.length > 0) {
                        missingItems.push(`upload ${missingDocs.length} dokumen`);
                    }
                }
                
                if (missingItems.length > 0) {
                    submitHelp.text('Lengkapi: ' + missingItems.join(', '));
                }
            }
        }
    }
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.alert-dismissible').remove();
        $('.steps-progress').closest('.card').after(alertHtml);
        
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                $('.alert-dismissible').fadeOut();
            }, 5000);
        }
        
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    function getAlertIcon(type) {
        const icons = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-triangle',
            'warning': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }
    
    // Initialize submit button status
    updateSubmitButton();
    
    // Debug logging
    console.log('Application edit form initialized');
    console.log('Criteria inputs found:', $('.criteria-input').length);
    console.log('Checked criteria inputs:', $('.criteria-input:checked').length);
    console.log('Documents found:', $('tr[data-doc-type]').length);
});
</script>
@endpush
@endsection