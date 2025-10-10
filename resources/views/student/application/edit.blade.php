{{-- resources/views/student/application/edit.blade.php - FULL FIXED VERSION --}}
@extends('layouts.app')

@section('title', 'Edit Aplikasi Beasiswa')
@section('page-title', 'Edit Aplikasi Beasiswa')

@section('content')
@php
    // CRITICAL FIX: Hitung TOTAL CRITERIA UTAMA (C1, C2, C3...), BUKAN SubCriteria
    $totalCriteriasNeeded = \App\Models\Criteria::where('is_active', true)->count();
    
    // Hitung yang sudah diisi (saved) - CARA YANG BENAR
    $savedDirectSubIds = \App\Models\ApplicationValue::where('application_id', $application->id)
        ->where('criteria_type', 'subcriteria')
        ->pluck('criteria_id')
        ->toArray();
    
    $savedSubSubIds = \App\Models\ApplicationValue::where('application_id', $application->id)
        ->where('criteria_type', 'subsubcriteria')
        ->pluck('criteria_id')
        ->toArray();
    
    $parentIdsFromSubSub = [];
    if (!empty($savedSubSubIds)) {
        $parentIdsFromSubSub = \App\Models\SubSubCriteria::whereIn('id', $savedSubSubIds)
            ->distinct()
            ->pluck('sub_criteria_id')
            ->toArray();
    }
    
    $allUniqueParentIds = array_unique(array_merge($savedDirectSubIds, $parentIdsFromSubSub));
    
    // CRITICAL: Ambil MAIN CRITERIA yang sudah terisi
    $filledMainCriteriaIds = \App\Models\SubCriteria::whereIn('id', $allUniqueParentIds)
        ->pluck('criteria_id')
        ->unique()
        ->toArray();
    
    $totalFilled = count($filledMainCriteriaIds);
@endphp

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="steps-progress">
                    <div class="step {{ $application->status == 'draft' ? 'active' : 'completed' }}">
                        <div class="step-icon">1</div>
                        <div class="step-label">Data Pribadi</div>
                    </div>
                    <div class="step {{ $totalFilled > 0 ? 'active' : '' }}">
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
        {{-- FORM DATA PRIBADI --}}
        <form id="personalDataForm" action="{{ route('student.application.update', $application->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Data Pribadi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                                       id="full_name" name="full_name" value="{{ old('full_name', $application->full_name) }}" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nisn" class="form-label">NISN</label>
                                <input type="text" class="form-control @error('nisn') is-invalid @enderror" 
                                       id="nisn" name="nisn" value="{{ old('nisn', $application->nisn) }}" required>
                                @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school" class="form-label">Sekolah</label>
                                <input type="text" class="form-control @error('school') is-invalid @enderror" 
                                       id="school" name="school" value="{{ old('school', $application->school) }}" required>
                                @error('school')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class" class="form-label">Kelas</label>
                                <input type="text" class="form-control @error('class') is-invalid @enderror" 
                                       id="class" name="class" value="{{ old('class', $application->class) }}" required placeholder="Contoh: XII IPA 1">
                                @error('class')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_place" class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control @error('birth_place') is-invalid @enderror" 
                                       id="birth_place" name="birth_place" value="{{ old('birth_place', $application->birth_place) }}" required>
                                @error('birth_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                       id="birth_date" name="birth_date" 
                                       value="{{ old('birth_date', $application->birth_date ? \Carbon\Carbon::parse($application->birth_date)->format('Y-m-d') : '') }}" required>
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Jenis Kelamin</label>
                                <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L" {{ old('gender', $application->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $application->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">No. Telepon</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $application->phone) }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3" required>{{ old('address', $application->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="savePersonalDataBtn">
                            <i class="fas fa-save me-2"></i>Simpan Data Pribadi
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        {{-- DATA KRITERIA AHP - FIXED RADIO GROUP --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Data Kriteria AHP</h5>
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
                            <h6 class="fw-bold text-primary mb-3">{{ $criteria->code }} - {{ $criteria->name }}</h6>
                            
                            @if($criteria->subCriterias->count() > 0)
                                @php
                                    // Cek apakah ada SubSubCriteria
                                    $hasSubSubCriteria = $criteria->subCriterias->some(function($sub) {
                                        return $sub->subSubCriterias->count() > 0;
                                    });
                                @endphp
                                
                                @if($hasSubSubCriteria)
                                    {{-- CASE 1: Ada SubSubCriteria - Radio per SubCriteria Group --}}
                                    @foreach($criteria->subCriterias as $subCriteria)
                                        @if($subCriteria->subSubCriterias->count() > 0)
                                            <div class="subcriteria-section mb-3">
                                                <label class="form-label fw-semibold">{{ $subCriteria->code }} - {{ $subCriteria->name }}</label>
                                                <div class="row">
                                                    @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                                        <div class="col-md-6">
                                                            <div class="form-check mb-2">
                                                                @php
                                                                    $existingKey = 'subsubcriteria_' . $subSubCriteria->id;
                                                                    $isChecked = isset($existingValues[$existingKey]);
                                                                @endphp
                                                                <input class="form-check-input criteria-input" type="radio" 
                                                                       name="subsubcriteria_{{ $subCriteria->id }}" 
                                                                       id="subsubcriteria_{{ $subSubCriteria->id }}"
                                                                       value="{{ $subSubCriteria->id }}"
                                                                       data-criteria-type="subsubcriteria"
                                                                       data-criteria-id="{{ $subSubCriteria->id }}"
                                                                       data-parent-id="{{ $subCriteria->id }}"
                                                                       data-criteria-name="{{ $subCriteria->name }}"
                                                                       {{ $isChecked ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="subsubcriteria_{{ $subSubCriteria->id }}">
                                                                    {{ $subSubCriteria->name }}
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
                                    {{-- CASE 2: TIDAK ada SubSubCriteria - Radio LANGSUNG SubCriteria --}}
                                    <div class="subcriteria-section">
                                        <label class="form-label fw-semibold mb-3">Pilih {{ $criteria->name }}:</label>
                                        <div class="row">
                                            @foreach($criteria->subCriterias as $subCriteria)
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        @php
                                                            $existingKey = 'subcriteria_' . $subCriteria->id;
                                                            $isChecked = isset($existingValues[$existingKey]);
                                                        @endphp
                                                        <input class="form-check-input criteria-input" type="radio" 
                                                               name="subcriteria_group_{{ $criteria->id }}" 
                                                               id="subcriteria_{{ $subCriteria->id }}"
                                                               value="{{ $subCriteria->id }}"
                                                               data-criteria-type="subcriteria"
                                                               data-criteria-id="{{ $subCriteria->id }}"
                                                               data-parent-id="{{ $subCriteria->id }}"
                                                               data-parent-criteria-id="{{ $criteria->id }}"
                                                               data-criteria-name="{{ $criteria->name }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="subcriteria_{{ $subCriteria->id }}">
                                                            {{ $subCriteria->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-warning"><small>Belum ada sub-kriteria untuk kriteria ini</small></div>
                            @endif
                        </div>
                    @endforeach
                    
                    <div class="alert alert-info" id="criteriaSummary">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Status Kriteria:</strong> 
                        <span id="criteriaStatusText">{{ $totalFilled }} dari {{ $totalCriteriasNeeded }} kriteria telah dipilih</span>
                    </div>
                @else
                    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Kriteria belum dikonfigurasi oleh admin.</div>
                @endif
            </div>
        </div>
        
        {{-- UPLOAD DOKUMEN --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Upload Dokumen</h5>
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
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                        <small class="text-muted">Uploading...</small>
                    </div>
                </div>
                
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
                                                @case('ktp')<span class="badge bg-primary">KTP</span>@break
                                                @case('kk')<span class="badge bg-info">KK</span>@break
                                                @case('slip_gaji')<span class="badge bg-success">Slip Gaji</span>@break
                                                @case('surat_keterangan')<span class="badge bg-warning">Surat Keterangan</span>@break
                                                @default<span class="badge bg-secondary">{{ $doc->document_type }}</span>
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
    
    {{-- SIDEBAR --}}
    <div class="col-md-4">
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
                            @if($application->status == 'draft')<span class="badge bg-secondary">Draft</span>
                            @elseif($application->status == 'submitted')<span class="badge bg-warning">Menunggu Validasi</span>
                            @elseif($application->status == 'validated')<span class="badge bg-success">Tervalidasi</span>
                            @elseif($application->status == 'rejected')<span class="badge bg-danger">Ditolak</span>
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
                    <div class="checklist-item {{ $totalFilled >= $totalCriteriasNeeded ? 'completed' : '' }}" id="criteria-check">
                        <i class="fas {{ $totalFilled >= $totalCriteriasNeeded ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}"></i>
                        <span id="criteria-check-text">Data kriteria terisi ({{ $totalFilled }}/{{ $totalCriteriasNeeded }})</span>
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
        
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($application->status == 'draft')
                        @php
                            $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
                            $uploadedDocs = $documents->pluck('document_type')->toArray();
                            $hasPersonalData = !empty($application->full_name) && !empty($application->nisn) &&
                                             !empty($application->school) && !empty($application->class) &&
                                             !empty($application->birth_date) && !empty($application->birth_place) &&
                                             !empty($application->gender) && !empty($application->address) &&
                                             !empty($application->phone);
                            $hasCriteriaValues = $totalFilled >= $totalCriteriasNeeded;
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
                                        if (!$hasCriteriaValues) $missingItems[] = "kriteria ({$totalFilled}/{$totalCriteriasNeeded})";
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
.steps-progress {display: flex; justify-content: space-between; align-items: center; position: relative;}
.steps-progress::before {content: ''; position: absolute; top: 20px; left: 50px; right: 50px; height: 2px; background: #dee2e6; z-index: 1;}
.step {display: flex; flex-direction: column; align-items: center; z-index: 2; background: white; padding: 0 10px;}
.step-icon {width: 40px; height: 40px; border-radius: 50%; background: #dee2e6; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 5px;}
.step.active .step-icon {background: #007bff; color: white;}
.step.completed .step-icon {background: #28a745; color: white;}
.step-label {font-size: 12px; text-align: center; color: #6c757d;}
.step.active .step-label, .step.completed .step-label {color: #495057; font-weight: 500;}
.criteria-section {border-left: 3px solid #007bff; padding-left: 15px;}
.subcriteria-section {background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;}
.checklist-item {display: flex; align-items: center; padding: 5px 0; transition: all 0.3s ease;}
.checklist-item i {margin-right: 10px;}
.checklist-item.completed {color: #28a745;}
.criteria-input:checked + label {font-weight: 500; color: #007bff;}
.criteria-input {margin-right: 8px;}
.form-check-label {cursor: pointer;}
.auto-saving {border-left: 3px solid #28a745 !important; background-color: #d4edda;}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    console.log('=== APPLICATION EDIT PAGE INIT ===');
    const expectedCriteriaCount = {{ $totalCriteriasNeeded }};
    let currentCriteriaSaved = {{ $totalFilled }};
    console.log('Expected MAIN CRITERIA Count:', expectedCriteriaCount);
    console.log('Initial Saved MAIN CRITERIA Count:', currentCriteriaSaved);
    
    // Auto-fill document name
    $('#document_type').on('change', function() {
        const names = {
            ktp: 'KTP Orang Tua', 
            kk: 'Kartu Keluarga', 
            slip_gaji: 'Slip Gaji Orang Tua', 
            surat_keterangan: 'Surat Keterangan Tidak Mampu'
        };
        if (names[$(this).val()]) {
            $('#document_name').val(names[$(this).val()]);
        }
    });

    // Personal Data Form Submit
    $('#personalDataForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = $('#savePersonalDataBtn');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
        
        $.ajax({
            url: this.action,
            type: this.method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                showAlert('success', response.message || 'Data pribadi berhasil disimpan');
                updatePersonalDataCheck();
                
                if (typeof response.saved_criteria_count === 'number') {
                    currentCriteriaSaved = response.saved_criteria_count;
                    updateCriteriaCheck();
                    updateCriteriaSummary();
                }
                
                updateSubmitButton();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Gagal menyimpan data pribadi';
                showAlert('danger', msg);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // CRITICAL FIX: Criteria Auto-Save dengan Update Realtime
    let saveTimeout;
    $('.criteria-input').on('change', function() {
        const input = $(this);
        const criteriaType = input.data('criteria-type');
        const criteriaId = input.data('criteria-id');
        const parentId = input.data('parent-id');
        const criteriaName = input.data('criteria-name');
        const value = input.val();
        const parentCriteriaId = input.data('parent-criteria-id');
        
        console.log('=== CRITERIA CHANGED ===', {
            type: criteriaType,
            id: criteriaId,
            parent: parentId,
            parentCriteriaId: parentCriteriaId,
            value: value,
            name: criteriaName,
            radioName: input.attr('name')
        });
        
        if (saveTimeout) clearTimeout(saveTimeout);
        
        const section = input.closest('.criteria-section');
        section.addClass('auto-saving');
        showSaveIndicator('Menyimpan ' + criteriaName + '...', 'info');
        
        saveTimeout = setTimeout(() => {
            saveCriteria(criteriaType, criteriaId, value, criteriaName, section, parentCriteriaId);
        }, 500);
    });
    
    function saveCriteria(type, id, val, name, section, parentCriteriaId) {
        console.log('=== SAVING CRITERIA TO SERVER ===', {type, id, val, name, parentCriteriaId});
        
        $.ajax({
            url: '{{ route("student.application.save-criteria", $application->id) }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                criteria_type: String(type),
                criteria_id: parseInt(id, 10),
                value: String(val),
                parent_criteria_id: parentCriteriaId ? parseInt(parentCriteriaId, 10) : null
            }),
            success: function(response) {
                console.log('=== SAVE SUCCESS ===', response);
                
                if (response && response.success) {
                    if (typeof response.total_criteria_saved === 'number') {
                        currentCriteriaSaved = response.total_criteria_saved;
                        console.log('Counter updated from server:', currentCriteriaSaved, '/', expectedCriteriaCount);
                    }
                    
                    section.removeClass('auto-saving');
                    showSaveIndicator(name + ' tersimpan!', 'success');
                    
                    updateCriteriaCheck();
                    updateCriteriaSummary();
                    updateSubmitButton();
                    
                    setTimeout(() => hideSaveIndicator(), 3000);
                } else {
                    handleSaveError(section, response?.message || 'Gagal menyimpan');
                }
            },
            error: function(xhr) {
                console.error('=== SAVE ERROR ===', xhr);
                let msg = 'Gagal menyimpan kriteria';
                
                if (xhr.responseJSON?.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    msg = 'Data tidak valid';
                } else if (xhr.status === 500) {
                    msg = 'Server error';
                }
                
                handleSaveError(section, msg);
            }
        });
    }
    
    function handleSaveError(section, msg) {
        section.removeClass('auto-saving');
        showAlert('warning', msg);
        hideSaveIndicator();
    }

    // Document Upload
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
                    updateDocumentCheck(response.document.document_type);
                    updateSubmitButton();
                    showAlert('success', response.message || 'Dokumen berhasil diupload');
                } else {
                    showAlert('danger', response?.message || 'Upload gagal');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Gagal upload dokumen';
                showAlert('danger', msg);
            },
            complete: function() {
                setUploadLoading(false);
            }
        });
    });
    
    // Submit Application
    $('#submitApplicationBtn').on('click', function(e) {
        e.preventDefault();
        
        if ($(this).prop('disabled')) {
            showAlert('warning', 'Data belum lengkap untuk disubmit');
            return;
        }
        
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const uploadedDocs = $('tr[data-doc-type]').map(function() {
            return $(this).data('doc-type');
        }).get();
        
        const hasAllDocs = requiredDocs.every(doc => uploadedDocs.includes(doc));
        const hasAllCriteria = currentCriteriaSaved >= expectedCriteriaCount;
        
        console.log('=== SUBMIT VALIDATION ===', {
            hasAllDocs,
            hasAllCriteria,
            currentSaved: currentCriteriaSaved,
            expected: expectedCriteriaCount,
            uploadedDocs
        });
        
        if (!hasAllDocs || !hasAllCriteria) {
            const missing = [];
            if (!hasAllCriteria) {
                missing.push(`kriteria (${currentCriteriaSaved}/${expectedCriteriaCount})`);
            }
            if (!hasAllDocs) {
                const missingDocs = requiredDocs.filter(d => !uploadedDocs.includes(d));
                missing.push(`dokumen (${missingDocs.join(', ')})`);
            }
            
            showAlert('warning', 'Belum lengkap: ' + missing.join(', '));
            return;
        }
        
        if (!confirm('Yakin ingin submit aplikasi? Data tidak bisa diubah setelah disubmit.')) {
            return;
        }
        
        submitApplication();
    });

    function submitApplication() {
        const btn = $('#submitApplicationBtn');
        const url = btn.data('url');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...');
        
        $.ajax({
            url: url,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({}),
            success: function(response) {
                if (response && response.success) {
                    showAlert('success', response.message + ' Redirecting...');
                    
                    $('input, select, textarea, button').prop('disabled', true);
                    
                    setTimeout(() => {
                        const redirectUrl = response.redirect_url || '{{ route("student.dashboard") }}';
                        window.location.href = redirectUrl;
                    }, 2000);
                } else {
                    showAlert('danger', response?.message || 'Gagal submit aplikasi');
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                console.error('Submit error:', xhr);
                const msg = xhr.responseJSON?.detailed_message || xhr.responseJSON?.message || 'Gagal submit aplikasi';
                showAlert('danger', msg);
                btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // Delete Document
    $(document).on('click', '.delete-doc-btn', function(e) {
        e.preventDefault();
        
        if (!confirm('Yakin ingin menghapus dokumen ini?')) return;
        
        const docId = $(this).data('doc-id');
        const deleteUrl = $(this).data('delete-url');
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: deleteUrl,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response && response.success) {
                    handleDocumentDeleted(docId, response.message);
                } else {
                    showAlert('danger', 'Gagal menghapus dokumen');
                }
            },
            error: function() {
                showAlert('danger', 'Gagal menghapus dokumen');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Helper Functions
    function showSaveIndicator(text, type) {
        const indicator = $('#autoSaveIndicator');
        const textEl = $('#autoSaveText');
        
        if (!indicator.length) return;
        
        indicator.removeClass('text-success text-info');
        
        if (type === 'success') {
            indicator.addClass('text-success');
            textEl.html('<i class="fas fa-check-circle me-1"></i>' + text);
        } else {
            indicator.addClass('text-info');
            textEl.html('<i class="fas fa-spinner fa-spin me-1"></i>' + text);
        }
        
        indicator.show();
    }
    
    function hideSaveIndicator() {
        $('#autoSaveIndicator').fadeOut();
    }
    
    function updateCriteriaCheck() {
        const checkItem = $('#criteria-check');
        const checkText = $('#criteria-check-text');
        
        if (checkText.length) {
            checkText.text(`Data kriteria terisi (${currentCriteriaSaved}/${expectedCriteriaCount})`);
        }
        
        if (checkItem.length) {
            if (currentCriteriaSaved >= expectedCriteriaCount) {
                checkItem.addClass('completed');
                checkItem.find('i')
                    .removeClass('fa-circle text-muted')
                    .addClass('fa-check-circle text-success');
            } else {
                checkItem.removeClass('completed');
                checkItem.find('i')
                    .removeClass('fa-check-circle text-success')
                    .addClass('fa-circle text-muted');
            }
        }
        
        console.log('Criteria check updated:', currentCriteriaSaved, '/', expectedCriteriaCount);
    }
    
    function updateCriteriaSummary() {
        const summaryText = $('#criteriaStatusText');
        if (summaryText.length) {
            summaryText.text(`${currentCriteriaSaved} dari ${expectedCriteriaCount} kriteria telah dipilih`);
        }
    }
    
    function updatePersonalDataCheck() {
        const checkItem = $('.checklist-item').first();
        if (checkItem.length) {
            checkItem.addClass('completed');
            checkItem.find('i')
                .removeClass('fa-circle text-muted')
                .addClass('fa-check-circle text-success');
        }
    }
    
    function validateUpload() {
        const docType = $('#document_type').val();
        const docName = $('#document_name').val();
        const file = $('#file')[0].files[0];
        
        if (!docType || !docName || !file) {
            showAlert('danger', 'Lengkapi semua field upload');
            return false;
        }
        
        if (file.size > 2048 * 1024) {
            showAlert('danger', 'Ukuran file maksimal 2MB');
            return false;
        }
        
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('danger', 'Format file harus PDF, JPG, atau PNG');
            return false;
        }
        
        return true;
    }
    
    function setUploadLoading(loading) {
        const btn = $('#uploadBtn');
        const progress = $('#uploadProgress');
        
        if (loading) {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Uploading...');
            progress.show();
        } else {
            btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Upload');
            progress.hide();
        }
    }
    
    function resetUploadForm() {
        $('#document_type, #document_name, #file').val('');
    }
    
    function updateDocumentsTable(doc, responseData) {
        let table = $('#documentsTable');
        
        if (!table.length) {
            const tableHtml = `
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
            `;
            $('#documentsContainer').html(tableHtml);
        }
        
        $(`tr[data-doc-type="${doc.document_type}"]`).remove();
        
        const badges = {
            ktp: 'bg-primary',
            kk: 'bg-info',
            slip_gaji: 'bg-success',
            surat_keterangan: 'bg-warning'
        };
        
        const rowHtml = `
            <tr id="doc_${doc.id}" data-doc-type="${doc.document_type}">
                <td><span class="badge ${badges[doc.document_type]}">${responseData.document_type_display}</span></td>
                <td>${doc.document_name}</td>
                <td>${responseData.file_size_display}</td>
                <td>${responseData.created_at_display}</td>
                <td>
                    <a href="${responseData.view_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" 
                            data-doc-id="${doc.id}" 
                            data-delete-url="${responseData.delete_url}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#documentsTableBody').append(rowHtml);
        $('#emptyDocuments').remove();
    }
    
    function updateDocumentCheck(docType) {
        const checkItem = $(`#${docType}-check`);
        if (checkItem.length) {
            checkItem.addClass('completed');
            checkItem.find('i')
                .removeClass('fa-circle text-muted')
                .addClass('fa-check-circle text-success');
        }
    }
    
    function handleDocumentDeleted(docId, message) {
        const row = $(`#doc_${docId}`);
        const docType = row.data('doc-type');
        
        row.fadeOut(300, function() {
            $(this).remove();
            
            if ($('#documentsTableBody tr').length === 0) {
                const emptyHtml = `
                    <div class="text-center text-muted" id="emptyDocuments">
                        <i class="fas fa-file-upload fa-3x mb-2"></i>
                        <p>Belum ada dokumen yang diupload</p>
                    </div>
                `;
                $('#documentsContainer').html(emptyHtml);
            }
        });
        
        const checkItem = $(`#${docType}-check`);
        if (checkItem.length) {
            checkItem.removeClass('completed');
            checkItem.find('i')
                .removeClass('fa-check-circle text-success')
                .addClass('fa-circle text-muted');
        }
        
        updateSubmitButton();
        showAlert('success', message);
    }
    
    function updateSubmitButton() {
        const requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        const uploadedDocs = $('tr[data-doc-type]').map(function() {
            return $(this).data('doc-type');
        }).get();
        
        const hasAllDocs = requiredDocs.every(type => uploadedDocs.includes(type));
        const hasAllCriteria = currentCriteriaSaved >= expectedCriteriaCount;
        const hasPersonalData = $('#full_name').val() && $('#nisn').val() && 
                               $('#school').val() && $('#class').val() && 
                               $('#birth_date').val() && $('#birth_place').val() && 
                               $('#gender').val() && $('#address').val() && 
                               $('#phone').val();
        
        const canSubmit = hasAllDocs && hasAllCriteria && hasPersonalData;
        
        console.log('=== UPDATE SUBMIT BUTTON ===', {
            hasAllDocs,
            hasAllCriteria,
            hasPersonalData,
            canSubmit,
            currentSaved: currentCriteriaSaved,
            expected: expectedCriteriaCount
        });
        
        const btn = $('#submitApplicationBtn');
        const helpText = $('#submitHelp');
        
        if (btn.length) {
            if (canSubmit) {
                btn.prop('disabled', false)
                   .removeClass('btn-secondary')
                   .addClass('btn-success');
                
                if (helpText.length) {
                    helpText.text('Semua data sudah lengkap. Silakan submit aplikasi!');
                }
            } else {
                btn.prop('disabled', true)
                   .removeClass('btn-success')
                   .addClass('btn-secondary');
                
                const missing = [];
                if (!hasPersonalData) missing.push('data pribadi');
                if (!hasAllCriteria) missing.push(`kriteria (${currentCriteriaSaved}/${expectedCriteriaCount})`);
                if (!hasAllDocs) {
                    const missingDocs = requiredDocs.filter(type => !uploadedDocs.includes(type));
                    missing.push(`upload ${missingDocs.length} dokumen`);
                }
                
                if (missing.length && helpText.length) {
                    helpText.text('Lengkapi: ' + missing.join(', '));
                }
            }
        }
    }
    
    function showAlert(type, message) {
        $('.alert-dismissible').remove();
        
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-triangle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${icons[type]} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.steps-progress').closest('.card').after(alertHtml);
        
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                $('.alert-dismissible').fadeOut();
            }, 5000);
        }
        
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    updateCriteriaCheck();
    updateCriteriaSummary();
    updateSubmitButton();
    
    console.log('=== INIT COMPLETE ===');
});
</script>
@endpush
@endsection