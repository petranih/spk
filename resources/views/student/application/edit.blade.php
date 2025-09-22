{{-- resources/views/student/application/edit.blade.php - FIXED --}}
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

<div class="row">
    <div class="col-md-8">
        <!-- Personal Information Form -->
        <form id="personalDataForm" action="{{ route('student.application.update', $application->id) }}" method="POST">
            @csrf
            @method('PUT')
            
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
                                       id="birth_date" name="birth_date" 
                                       value="{{ old('birth_date', $application->birth_date ? \Carbon\Carbon::parse($application->birth_date)->format('Y-m-d') : '') }}" 
                                       required>
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
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="savePersonalDataBtn">
                            <i class="fas fa-save me-2"></i>Simpan Data Pribadi
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- FIXED: Enhanced Criteria Section dengan Auto-Save yang benar -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list-check me-2"></i>Data Kriteria AHP
                </h5>
                <small class="text-muted">Pilih kriteria yang sesuai dengan kondisi Anda. Data akan tersimpan otomatis.</small>
                <div class="mt-2" id="autoSaveIndicator" style="display: none;">
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        <span id="autoSaveText">Kriteria tersimpan otomatis</span>
                    </small>
                </div>
            </div>
            <div class="card-body">
                @if($criterias->count() > 0)
                    @foreach($criterias as $criteria)
                        <div class="criteria-section mb-4" data-criteria-id="{{ $criteria->id }}">
                            <h6 class="fw-bold text-primary mb-3">
                                {{ $criteria->code }} - {{ $criteria->name }}
                            </h6>
                            
                            @if($criteria->subCriterias->count() > 0)
                                @php
                                    $hasSubSubCriteria = $criteria->subCriterias->some(function($sub) {
                                        return $sub->subSubCriterias->count() > 0;
                                    });
                                @endphp
                                
                                @if($hasSubSubCriteria)
                                    {{-- Handle Sub-Sub-Criteria --}}
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
                                                                @php
                                                                    $existingKey = 'subsubcriteria_' . $subCriteria->id;
                                                                    $isChecked = isset($existingValues[$existingKey]) && $existingValues[$existingKey]->value == $subSubCriteria->id;
                                                                @endphp
                                                                <input class="form-check-input criteria-input" 
                                                                       type="radio" 
                                                                       name="subsubcriteria_{{ $subCriteria->id }}" 
                                                                       id="subsubcriteria_{{ $subSubCriteria->id }}"
                                                                       value="{{ $subSubCriteria->id }}"
                                                                       data-criteria-type="subsubcriteria"
                                                                       data-criteria-id="{{ $subCriteria->id }}"
                                                                       data-criteria-name="{{ $subCriteria->name }}"
                                                                       {{ $isChecked ? 'checked' : '' }}>
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
                                    {{-- Handle Direct Sub-Criteria --}}
                                    <div class="subcriteria-section">
                                        <label class="form-label fw-semibold mb-3">
                                            Pilih {{ $criteria->name }}:
                                        </label>
                                        
                                        <div class="row">
                                            @foreach($criteria->subCriterias as $subCriteria)
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        @php
                                                            $existingKey = 'subcriteria_' . $criteria->id;
                                                            $isChecked = isset($existingValues[$existingKey]) && $existingValues[$existingKey]->value == $subCriteria->id;
                                                        @endphp
                                                        <input class="form-check-input criteria-input" 
                                                               type="radio" 
                                                               name="subcriteria_{{ $criteria->id }}" 
                                                               id="subcriteria_{{ $subCriteria->id }}"
                                                               value="{{ $subCriteria->id }}"
                                                               data-criteria-type="subcriteria"
                                                               data-criteria-id="{{ $criteria->id }}"
                                                               data-criteria-name="{{ $criteria->name }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
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
                    
                    <!-- Summary info -->
                    <div class="alert alert-info" id="criteriaSummary">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Status Kriteria:</strong> 
                        <span id="criteriaStatusText">{{ count($existingValues) }} dari {{ $criterias->count() }} kriteria telah dipilih</span>
                    </div>
                    
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Kriteria belum dikonfigurasi oleh admin. Silakan hubungi administrator.
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Document Upload Section (unchanged) -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-upload me-2"></i>Upload Dokumen
                </h5>
            </div>
            <div class="card-body">
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
        
        <!-- FIXED: Progress Checklist -->
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
                        <span id="criteria-check-text">Data kriteria terisi ({{ count($existingValues) }}/{{ $criterias->where('subCriterias')->count() }})</span>
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
        
        <!-- FIXED: Action Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($application->status == 'draft')
                        @php
                            $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
                            $uploadedDocs = $documents->pluck('document_type')->toArray();
                            $hasPersonalData = !empty($application->full_name) && 
                                             !empty($application->nisn) &&
                                             !empty($application->school) &&
                                             !empty($application->class) &&
                                             !empty($application->birth_date) &&
                                             !empty($application->birth_place) &&
                                             !empty($application->gender) &&
                                             !empty($application->address) &&
                                             !empty($application->phone);
                            $hasCriteriaValues = count($existingValues) > 0;
                            $hasAllDocuments = count(array_diff($requiredDocs, $uploadedDocs)) == 0;
                            $canSubmit = $hasPersonalData && $hasCriteriaValues && $hasAllDocuments;
                        @endphp
                        
                        <div id="submitSection">
                            <button type="button" class="btn btn-success w-100" 
                                    id="submitApplicationBtn" 
                                    data-url="{{ route('student.application.submit', $application->id) }}"
                                    {{ !$canSubmit ? 'disabled' : '' }}>
                                <i class="fas fa-paper-plane me-2"></i>Submit Aplikasi
                            </button>
                            <small class="text-muted mt-1 d-block" id="submitHelp">
                                @if(!$canSubmit)
                                    @php
                                        $missingItems = [];
                                        if (!$hasPersonalData) $missingItems[] = 'data pribadi';
                                        if (!$hasCriteriaValues) $missingItems[] = 'pilih kriteria';
                                        if (!$hasAllDocuments) {
                                            $missingDocCount = count(array_diff($requiredDocs, $uploadedDocs));
                                            $missingItems[] = "upload {$missingDocCount} dokumen";
                                        }
                                    @endphp
                                    Lengkapi: {{ implode(', ', $missingItems) }}
                                @else
                                    Semua data sudah lengkap. Silakan submit aplikasi!
                                @endif
                            </small>
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

.criteria-input {
    margin-right: 8px;
}

.form-check-label {
    cursor: pointer;
}

.auto-saving {
    border-left: 3px solid #28a745 !important;
    background-color: #d4edda;
}

.auto-save-success {
    color: #155724;
    font-weight: 500;
}
</style>
@endpush

@push('scripts')
// COMPLETE FIXED JavaScript untuk edit.blade.php dengan semua variabel yang diperlukan
<script>
$(document).ready(function() {
    console.log('=== APPLICATION EDIT INITIALIZATION WITH FIXED AUTO-SAVE ===');
    console.log('Application ID:', {{ $application->id }});
    console.log('Total criteria sections:', {{ $criterias->count() }});
    console.log('Existing values:', @json($existingValues->keys()));
    
    // FIXED: Define global variables dengan nilai yang benar
    const expectedCriteriaCount = {{ $criterias->filter(function($c) { return $c->subCriterias->count() > 0; })->count() }};
    let totalCriteriaSaved = {{ count($existingValues) }};
    
    // FIXED: Store initial counts for reference
    const initialCounts = {
        expectedCriteria: expectedCriteriaCount,
        savedCriteria: totalCriteriaSaved,
        applicationId: {{ $application->id }}
    };
    
    console.log('Expected criteria count:', expectedCriteriaCount);
    console.log('Currently saved:', totalCriteriaSaved);
    console.log('Initial counts:', initialCounts);
    
    // Auto-fill document name based on type
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

    // Personal Data Form Submit
    $('#personalDataForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const saveBtn = $('#savePersonalDataBtn');
        const originalText = saveBtn.html();
        
        saveBtn.prop('disabled', true);
        saveBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
        
        $.ajax({
            url: this.action,
            type: this.method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                showAlert('success', response.message || 'Data pribadi berhasil disimpan!');
                updatePersonalDataChecklist();
                updateSubmitButton();
            },
            error: function(xhr) {
                let message = 'Gagal menyimpan data pribadi. Silakan coba lagi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('danger', message);
            },
            complete: function() {
                saveBtn.prop('disabled', false);
                saveBtn.html(originalText);
            }
        });
    });

    // FIXED: Auto-save kriteria dengan timeout dan error handling yang lebih baik
    let autoSaveTimeout;
    
    $('.criteria-input').on('change', function() {
        const input = $(this);
        const criteriaType = input.data('criteria-type');
        const criteriaId = input.data('criteria-id');
        const criteriaName = input.data('criteria-name');
        const value = input.val();
        
        console.log('=== CRITERIA AUTO-SAVE TRIGGERED ===');
        console.log('Criteria selection data:', {
            type: criteriaType,
            criteria_id: criteriaId,
            value: value,
            name: criteriaName
        });
        
        // Clear previous timeout if exists
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        
        // Visual feedback: show saving state
        const section = input.closest('.criteria-section');
        section.addClass('auto-saving');
        showAutoSaveIndicator('Menyimpan kriteria...', 'info');
        
        // Debounce auto-save
        autoSaveTimeout = setTimeout(function() {
            performAutoSave(criteriaType, criteriaId, value, criteriaName, section);
        }, 300);
    });
    
    function performAutoSave(criteriaType, criteriaId, value, criteriaName, section) {
        console.log('=== PERFORMING AUTO-SAVE ===');
        console.log('Save data:', {
            criteria_type: criteriaType,
            criteria_id: criteriaId,
            value: value
        });
        
        const saveData = {
            criteria_type: String(criteriaType),
            criteria_id: parseInt(criteriaId, 10),
            value: String(value)
        };
        
        console.log('Processed save data:', saveData);
        
        $.ajax({
            url: '{{ route("student.application.save-criteria", $application->id) }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            data: JSON.stringify(saveData),
            success: function(response) {
                console.log('=== AUTO-SAVE SUCCESS ===', response);
                
                if (response && response.success) {
                    // FIXED: Update totalCriteriaSaved dengan response dari server
                    if (typeof response.total_criteria_saved === 'number') {
                        totalCriteriaSaved = response.total_criteria_saved;
                    } else {
                        // Fallback: recount from DOM
                        totalCriteriaSaved = $('.criteria-input:checked').length;
                    }
                    
                    console.log('Updated totalCriteriaSaved to:', totalCriteriaSaved);
                    
                    // Visual feedback: success
                    section.removeClass('auto-saving');
                    showAutoSaveIndicator(`${criteriaName} tersimpan!`, 'success');
                    
                    // Update UI
                    updateCriteriaChecklist();
                    updateCriteriaSummary();
                    updateSubmitButton();
                    
                    // Hide success message after 3 seconds
                    setTimeout(function() {
                        hideAutoSaveIndicator();
                    }, 3000);
                    
                } else {
                    console.error('Auto-save failed - invalid response:', response);
                    const errorMsg = response && response.message ? response.message : 'Gagal menyimpan kriteria';
                    handleAutoSaveError(section, errorMsg);
                }
            },
            error: function(xhr) {
                console.error('=== AUTO-SAVE ERROR ===', {
                    status: xhr.status,
                    response: xhr.responseJSON,
                    responseText: xhr.responseText
                });
                
                let errorMessage = 'Gagal menyimpan kriteria. Silakan coba lagi.';
                
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMessage = 'Data kriteria tidak valid. Silakan periksa pilihan Anda.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Akses tidak diizinkan. Silakan refresh halaman.';
                    } else if (xhr.status === 429) {
                        errorMessage = 'Terlalu banyak permintaan. Mohon tunggu sebentar.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Terjadi kesalahan server. Silakan coba lagi.';
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                handleAutoSaveError(section, errorMessage);
            }
        });
    }
    
    function handleAutoSaveError(section, message) {
        section.removeClass('auto-saving');
        showAlert('warning', message);
        hideAutoSaveIndicator();
    }

    // Upload Handler
    $('#uploadBtn').on('click', function(e) {
        e.preventDefault();
        
        if (!validateUpload()) return;
        
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('document_type', $('#document_type').val());
        formData.append('document_name', $('#document_name').val());
        formData.append('file', $('#file')[0].files[0]);
        
        setUploadLoading(true);
        
        $.ajax({
            url: '{{ route("student.application.upload", $application->id) }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response && response.success) {
                    resetUploadForm();
                    updateDocumentsTable(response.document, response);
                    updateChecklist(response.document.document_type);
                    updateSubmitButton();
                    showAlert('success', response.message);
                } else {
                    const errorMsg = response && response.message ? response.message : 'Upload gagal';
                    showAlert('danger', errorMsg);
                }
            },
            error: function(xhr) {
                let message = 'Upload gagal. Silakan coba lagi.';
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                } catch (e) {
                    console.error('Error parsing upload error:', e);
                }
                showAlert('danger', message);
            },
            complete: function() {
                setUploadLoading(false);
            }
        });
    });
    
    // FIXED: Submit Application Handler dengan semua variabel yang diperlukan
    $('#submitApplicationBtn').on('click', function(e) {
        e.preventDefault();
        
        console.log('=== SUBMIT BUTTON CLICKED ===');
        console.log('Button disabled status:', $(this).prop('disabled'));
        console.log('Total criteria saved:', totalCriteriaSaved);
        console.log('Expected criteria count:', expectedCriteriaCount);
        
        if ($(this).prop('disabled')) {
            console.log('Submit blocked - button is disabled');
            showAlert('warning', 'Pastikan semua data sudah lengkap sebelum submit');
            return;
        }
        
        // Double check requirements
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const uploadedDocs = [];
        $('tr[data-doc-type]').each(function() {
            const docType = $(this).data('doc-type');
            if (docType) {
                uploadedDocs.push(docType);
            }
        });
        
        const hasAllDocs = requiredDocs.every(doc => uploadedDocs.includes(doc));
        const hasCriteria = totalCriteriaSaved >= expectedCriteriaCount;
        
        console.log('Final validation check:', {
            hasAllDocs: hasAllDocs,
            hasCriteria: hasCriteria,
            uploadedDocs: uploadedDocs,
            requiredDocs: requiredDocs,
            totalSaved: totalCriteriaSaved,
            expected: expectedCriteriaCount
        });
        
        if (!hasAllDocs || !hasCriteria) {
            const missingItems = [];
            if (!hasCriteria) {
                missingItems.push(`kriteria belum lengkap (${totalCriteriaSaved}/${expectedCriteriaCount})`);
            }
            if (!hasAllDocs) {
                const missing = requiredDocs.filter(doc => !uploadedDocs.includes(doc));
                missingItems.push(`dokumen belum lengkap (kurang: ${missing.join(', ')})`);
            }
            
            showAlert('warning', 'Aplikasi belum dapat disubmit: ' + missingItems.join(', '));
            return;
        }
        
        if (!confirm('Yakin ingin submit aplikasi?\n\nSetelah disubmit, Anda tidak dapat mengubah data lagi.\n\nPastikan semua data sudah benar!')) {
            return;
        }
        
        // Perform submit with retry mechanism
        performSubmitWithRetry();
    });

    // FIXED: Function untuk submit dengan retry mechanism
    function performSubmitWithRetry(attempt = 1, maxAttempts = 3) {
        const submitUrl = $('#submitApplicationBtn').data('url');
        const submitBtn = $('#submitApplicationBtn');
        
        console.log(`Attempting submit (attempt ${attempt}/${maxAttempts}) to URL:`, submitUrl);
        
        submitBtn.prop('disabled', true);
        
        if (attempt === 1) {
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...');
        } else {
            submitBtn.html(`<i class="fas fa-spinner fa-spin me-2"></i>Retry ${attempt}/${maxAttempts}...`);
        }
        
        $.ajax({
            url: submitUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            data: JSON.stringify({}),
            success: function(response) {
                console.log('=== SUBMIT SUCCESS ===', response);
                
                if (response && response.success) {
                    showAlert('success', response.message + ' Redirecting...');
                    
                    // Disable all form controls
                    $('input, select, textarea, button').prop('disabled', true);
                    
                    setTimeout(function() {
                        const redirectUrl = response.redirect_url || '{{ route("student.dashboard") }}';
                        window.location.href = redirectUrl;
                    }, 2000);
                } else {
                    const errorMsg = response && response.detailed_message ? 
                        response.detailed_message : 
                        (response && response.message ? response.message : 'Gagal submit aplikasi');
                    showAlert('danger', errorMsg);
                    resetSubmitButton(submitBtn);
                }
            },
            error: function(xhr) {
                console.error('=== SUBMIT ERROR ===', {
                    status: xhr.status,
                    response: xhr.responseJSON,
                    responseText: xhr.responseText,
                    attempt: attempt
                });
                
                // Handle throttle error dengan retry mechanism
                if (xhr.status === 429) {
                    if (attempt < maxAttempts) {
                        console.log(`Rate limited, retrying in 5 seconds (attempt ${attempt + 1}/${maxAttempts})`);
                        showAlert('warning', `Rate limit exceeded. Retrying in 5 seconds... (${attempt}/${maxAttempts})`);
                        
                        setTimeout(function() {
                            performSubmitWithRetry(attempt + 1, maxAttempts);
                        }, 5000);
                        
                        return;
                    } else {
                        showAlert('danger', 'Terlalu banyak percobaan submit. Silakan tunggu beberapa menit dan refresh halaman.');
                        resetSubmitButton(submitBtn);
                        return;
                    }
                }
                
                // Handle other errors
                let message = 'Gagal submit aplikasi. Silakan coba lagi.';
                
                try {
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.detailed_message) {
                            message = xhr.responseJSON.detailed_message;
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors && Array.isArray(xhr.responseJSON.errors)) {
                            message = 'Aplikasi belum dapat disubmit: ' + xhr.responseJSON.errors.join('; ');
                        }
                    }
                } catch (e) {
                    console.error('Error parsing submit error:', e);
                }
                
                showAlert('danger', message);
                resetSubmitButton(submitBtn);
            }
        });
    }
    
    function resetSubmitButton(submitBtn) {
        submitBtn.prop('disabled', false);
        submitBtn.html('<i class="fas fa-paper-plane me-2"></i>Submit Aplikasi');
    }
    
    // Delete document handler
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
                if (response && response.success) {
                    handleDeleteSuccess(docId, response.message || 'Dokumen berhasil dihapus');
                } else {
                    const errorMsg = response && response.message ? response.message : 'Gagal menghapus dokumen';
                    showAlert('danger', errorMsg);
                }
            },
            error: function(xhr) {
                let message = 'Gagal menghapus dokumen. Silakan coba lagi.';
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                } catch (e) {
                    console.error('Error parsing delete error:', e);
                }
                showAlert('danger', message);
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-trash"></i>');
            }
        });
    });
    
    // HELPER FUNCTIONS
    function showAutoSaveIndicator(text, type = 'info') {
        const indicator = $('#autoSaveIndicator');
        const textElement = $('#autoSaveText');
        
        if (indicator.length === 0 || textElement.length === 0) {
            console.warn('Auto-save indicator elements not found');
            return;
        }
        
        textElement.text(text);
        
        indicator.removeClass('text-success text-info text-warning text-danger');
        if (type === 'success') {
            indicator.addClass('text-success');
            textElement.html('<i class="fas fa-check-circle me-1"></i>' + text);
        } else if (type === 'info') {
            indicator.addClass('text-info');
            textElement.html('<i class="fas fa-spinner fa-spin me-1"></i>' + text);
        }
        
        indicator.show();
    }
    
    function hideAutoSaveIndicator() {
        $('#autoSaveIndicator').fadeOut();
    }
    
    function updateCriteriaChecklist() {
        const criteriaCheck = $('#criteria-check');
        const criteriaCheckText = $('#criteria-check-text');
        
        if (criteriaCheckText.length) {
            criteriaCheckText.text(`Data kriteria terisi (${totalCriteriaSaved}/${expectedCriteriaCount})`);
        }
        
        if (criteriaCheck.length) {
            if (totalCriteriaSaved >= expectedCriteriaCount) {
                criteriaCheck.addClass('completed');
                criteriaCheck.find('i').removeClass('fa-circle text-muted').addClass('fa-check-circle text-success');
            } else if (totalCriteriaSaved > 0) {
                criteriaCheck.addClass('completed');
                criteriaCheck.find('i').removeClass('fa-circle text-muted').addClass('fa-check-circle text-success');
            } else {
                criteriaCheck.removeClass('completed');
                criteriaCheck.find('i').removeClass('fa-check-circle text-success').addClass('fa-circle text-muted');
            }
        }
    }
    
    function updateCriteriaSummary() {
        const statusText = $('#criteriaStatusText');
        if (statusText.length) {
            statusText.text(`${totalCriteriaSaved} dari ${expectedCriteriaCount} kriteria telah dipilih dan tersimpan`);
        }
    }
    
    function updatePersonalDataChecklist() {
        const personalCheck = $('.checklist-item').first();
        if (personalCheck.length) {
            personalCheck.addClass('completed');
            personalCheck.find('i').removeClass('fa-circle text-muted').addClass('fa-check-circle text-success');
        }
    }
    
    function validateUpload() {
        const documentType = $('#document_type').val();
        const documentName = $('#document_name').val();
        const file = $('#file')[0].files[0];
        
        if (!documentType || !documentName || !file) {
            showAlert('danger', 'Mohon lengkapi semua field upload');
            return false;
        }
        
        if (file.size > 2048 * 1024) {
            showAlert('danger', 'Ukuran file maksimal 2MB');
            return false;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('danger', 'Tipe file harus PDF, JPG, JPEG, atau PNG');
            return false;
        }
        
        return true;
    }
    
    function setUploadLoading(loading) {
        const uploadBtn = $('#uploadBtn');
        const uploadProgress = $('#uploadProgress');
        
        if (loading) {
            uploadBtn.prop('disabled', true);
            uploadBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Uploading...');
            uploadProgress.show();
        } else {
            uploadBtn.prop('disabled', false);
            uploadBtn.html('<i class="fas fa-upload me-1"></i>Upload');
            uploadProgress.hide();
        }
    }
    
    function resetUploadForm() {
        $('#document_type').val('');
        $('#document_name').val('');
        $('#file').val('');
    }
    
    function updateDocumentsTable(document, response) {
        let table = $('#documentsTable');
        
        if (table.length === 0) {
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
        
        $(`tr[data-doc-type="${document.document_type}"]`).remove();
        
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
        if (checkElement.length) {
            checkElement.addClass('completed');
            checkElement.find('i')
                .removeClass('fa-circle text-muted')
                .addClass('fa-check-circle text-success');
        }
    }
    
    function handleDeleteSuccess(docId, message) {
        const row = $(`#doc_${docId}`);
        row.fadeOut(300, function() {
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
        showAlert('success', message);
    }
    
    function updateChecklistAfterDelete() {
        const docTypes = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        
        docTypes.forEach(type => {
            const hasDoc = $(`tr[data-doc-type="${type}"]`).length > 0;
            const checkElement = $(`#${type}-check`);
            
            if (checkElement.length) {
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
            }
        });
    }
    
    // FIXED: Submit button validation dengan semua variabel yang diperlukan
    function updateSubmitButton() {
        console.log('=== UPDATING SUBMIT BUTTON ===');
        console.log('Current totalCriteriaSaved:', totalCriteriaSaved);
        console.log('Current expectedCriteriaCount:', expectedCriteriaCount);
        
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const uploadedDocs = [];
        
        // Check uploaded documents
        $('tr[data-doc-type]').each(function() {
            const docType = $(this).data('doc-type');
            if (docType) {
                uploadedDocs.push(docType);
            }
        });
        
        const allDocsUploaded = requiredDocs.every(type => uploadedDocs.includes(type));
        const hasCriteriaValues = totalCriteriaSaved >= expectedCriteriaCount;
        
        // Check personal data (basic check)
        const hasBasicPersonalData = $('#full_name').val() !== '' && 
                                   $('#nisn').val() !== '' && 
                                   $('#school').val() !== '' && 
                                   $('#class').val() !== '' &&
                                   $('#birth_date').val() !== '' &&
                                   $('#birth_place').val() !== '' &&
                                   $('#gender').val() !== '' &&
                                   $('#address').val() !== '' &&
                                   $('#phone').val() !== '';
        
        const canSubmit = allDocsUploaded && hasCriteriaValues && hasBasicPersonalData;
        
        console.log('Submit validation:', {
            allDocsUploaded: allDocsUploaded,
            hasCriteriaValues: hasCriteriaValues,
            hasBasicPersonalData: hasBasicPersonalData,
            canSubmit: canSubmit,
            uploadedDocs: uploadedDocs,
            totalCriteriaSaved: totalCriteriaSaved,
            expectedCriteriaCount: expectedCriteriaCount
        });
        
        const submitBtn = $('#submitApplicationBtn');
        const submitHelp = $('#submitHelp');
        
        if (submitBtn.length) {
            if (canSubmit) {
                submitBtn.prop('disabled', false);
                submitBtn.removeClass('btn-secondary').addClass('btn-success');
                if (submitHelp.length) {
                    submitHelp.text('Semua data sudah lengkap. Silakan submit aplikasi!');
                }
            } else {
                submitBtn.prop('disabled', true);
                submitBtn.removeClass('btn-success').addClass('btn-secondary');
                
                const missingItems = [];
                if (!hasBasicPersonalData) {
                    missingItems.push('data pribadi');
                }
                if (!hasCriteriaValues) {
                    missingItems.push(`pilih kriteria (${totalCriteriaSaved}/${expectedCriteriaCount})`);
                }
                if (!allDocsUploaded) {
                    const missingDocs = requiredDocs.filter(type => !uploadedDocs.includes(type));
                    if (missingDocs.length > 0) {
                        missingItems.push(`upload ${missingDocs.length} dokumen`);
                    }
                }
                
                if (missingItems.length > 0 && submitHelp.length) {
                    submitHelp.text('Lengkapi: ' + missingItems.join(', '));
                }
            }
        }
    }
    
function showAlert(type, message) {
    // Remove existing alerts first
    $('.alert-dismissible').remove();
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas ${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert alert at top of content
    $('.steps-progress').closest('.card').after(alertHtml);
    
    // Auto-hide success and info alerts
    if (type === 'success' || type === 'info') {
        setTimeout(() => {
            $('.alert-dismissible').fadeOut();
        }, 5000);
    }
    
    // Scroll to top to show alert
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
});
</script>
@endpush
@endsection