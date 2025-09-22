{{-- resources/views/validator/validation/show.blade.php - CLEANED VERSION --}}
@extends('layouts.app')

@section('title', 'Validasi Aplikasi')
@section('page-title', 'Validasi Aplikasi')

@section('content')

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Application Info Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-user-graduate me-2"></i>
                        Informasi Aplikasi
                    </h5>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill">
                        <i class="fas fa-hourglass-half me-1"></i>Menunggu Validasi
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-hashtag text-primary me-2"></i>ID Aplikasi
                            </div>
                            <div class="info-value">
                                <code class="bg-primary text-white px-3 py-2 rounded-pill fw-bold">
                                    {{ strtoupper($application->application_number) }}
                                </code>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user text-success me-2"></i>Nama Lengkap
                            </div>
                            <div class="info-value fw-bold text-dark">
                                {{ $application->full_name }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope text-info me-2"></i>Email
                            </div>
                            <div class="info-value text-muted">
                                {{ $application->user->email }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-school text-warning me-2"></i>Sekolah
                            </div>
                            <div class="info-value">
                                {{ $application->school }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-graduation-cap text-secondary me-2"></i>Kelas
                            </div>
                            <div class="info-value">
                                <span class="badge bg-light text-dark">{{ $application->class }}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-calendar-alt text-info me-2"></i>Periode
                            </div>
                            <div class="info-value">
                                <span class="badge bg-info text-white rounded-pill">
                                    {{ $application->period->name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Values Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient-success text-white">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Respon Siswa dan Penilaian
                </h5>
            </div>
            <div class="card-body p-0">
                @if($criterias->count() > 0)
                    <div class="accordion accordion-flush" id="criteriaAccordion">

@foreach($criterias as $index => $criteria)
<div class="accordion-item border-0">
    <h2 class="accordion-header">
        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }} fw-semibold" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#criteria{{ $criteria->id }}" 
                aria-expanded="{{ $index == 0 ? 'true' : 'false' }}">
            
            <div class="d-flex align-items-center w-100">
                <div class="flex-grow-1">
                    <div class="fw-bold text-primary">
                        {{ $criteria->code }} - {{ $criteria->name }}
                    </div>
                </div>
                
                <div class="me-3">
                    @php
                        $criteriaFilledCount = 0;
                        $criteriaTotalCount = $criteria->subCriterias->count();
                        
                        foreach($criteria->subCriterias as $sub) {
                            $key = 'subcriteria_' . $sub->id;
                            if ($applicationValues->has($key)) {
                                $data = $applicationValues->get($key);
                                if (!$data->is_empty) {
                                    $criteriaFilledCount++;
                                }
                            }
                        }
                    @endphp
                    
                    @if($criteriaFilledCount > 0)
                        <span class="badge bg-success rounded-pill px-3">
                            <i class="fas fa-check me-1"></i>DIISI ({{ $criteriaFilledCount }}/{{ $criteriaTotalCount }})
                        </span>
                    @else
                        <span class="badge bg-danger rounded-pill px-3">
                            <i class="fas fa-times me-1"></i>KOSONG
                        </span>
                    @endif
                </div>
            </div>
        </button>
    </h2>
    <div id="criteria{{ $criteria->id }}" 
         class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" 
         data-bs-parent="#criteriaAccordion">
        <div class="accordion-body bg-light p-4">
            
            @foreach($criteria->subCriterias as $subCriteria)
                @php
                    $key = 'subcriteria_' . $subCriteria->id;
                    $hasResponse = false;
                    $responseText = 'Tidak ada respon';
                    $responseScore = 0;
                    $createdAt = null;
                    
                    if ($applicationValues->has($key)) {
                        $data = $applicationValues->get($key);
                        if (!$data->is_empty) {
                            $hasResponse = true;
                            $responseText = $data->value;
                            $responseScore = $data->score;
                            $createdAt = $data->created_at;
                        }
                    }
                @endphp
                
                {{-- Only show subcriteria that have responses --}}
                @if($hasResponse)
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="status-icon bg-success rounded-circle p-2 me-3">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $subCriteria->code }}</h6>
                                        <div class="text-muted">{{ $subCriteria->name }}</div>
                                    </div>
                                </div>
                                
                                <div class="response-content">
                                    <div class="response-label mb-2">
                                        <i class="fas fa-comment-alt text-primary me-2"></i>
                                        <strong>RESPON SISWA:</strong>
                                    </div>
                                    <div class="response-value bg-white border rounded p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="text-primary">{{ $subCriteria->name }}:</strong>
                                                <span class="ms-2 fw-semibold text-success">{{ $responseText }}</span>
                                                @if($createdAt)
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ \Carbon\Carbon::parse($createdAt)->format('d/m/Y H:i') }}
                                                    </small>
                                                @endif
                                            </div>
                                            <span class="badge bg-success rounded-pill fs-6">
                                                {{ number_format($responseScore, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="score-display text-center">
                                    <div class="score-label text-muted small mb-1">SKOR</div>
                                    <div class="score-badge">
                                        <span class="badge bg-success fs-5 px-3 py-2 rounded-circle">
                                            {{ number_format($responseScore, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Detail pilihan untuk sub criteria yang punya sub-sub --}}
                @if($subCriteria->subSubCriterias->count() > 0)
                    <div class="ms-4 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h6 class="text-muted mb-0">
                                    <i class="fas fa-list me-2"></i>Detail Pilihan:
                                </h6>
                            </div>
                            <div class="card-body pt-2">
                                @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                    @php
                                        $subSubKey = 'subsubcriteria_' . $subCriteria->id . '_' . $subSubCriteria->id;
                                        $isSelected = false;
                                        $selectedScore = 0;
                                        
                                        if ($applicationValues->has($subSubKey)) {
                                            $data = $applicationValues->get($subSubKey);
                                            $isSelected = $data->is_selected;
                                            $selectedScore = $data->score;
                                        }
                                    @endphp
                                    
                                    <div class="d-flex justify-content-between align-items-center py-2 {{ $isSelected ? 'border-start border-success border-3 ps-3 bg-success bg-opacity-10' : '' }}">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                @if($isSelected)
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    <span class="fw-semibold text-success">{{ $subSubCriteria->name }}</span>
                                                    <span class="badge bg-success ms-2 rounded-pill">DIPILIH</span>
                                                @else
                                                    <i class="far fa-circle text-muted me-2"></i>
                                                    <span class="text-muted">{{ $subSubCriteria->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge {{ $isSelected ? 'bg-success' : 'bg-light text-muted' }} rounded-pill">
                                                {{ number_format($selectedScore, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                @endif {{-- End of hasResponse condition --}}
            @endforeach
        </div>
    </div>
</div>
@endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum Ada Kriteria</h5>
                        <p class="text-muted">Kriteria penilaian belum diatur oleh admin</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Documents Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient-info text-white">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-folder-open me-2"></i>
                    Dokumen Pendukung
                </h5>
            </div>
            <div class="card-body p-4">
                @if($documents->count() > 0)
                    <div class="row g-3">
                        @foreach($documents as $document)
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="document-icon me-3">
                                            @php
                                                $extension = strtolower(pathinfo($document->document_name, PATHINFO_EXTENSION));
                                            @endphp
                                            <div class="icon-container p-3 rounded-circle bg-light">
                                                @switch($extension)
                                                    @case('pdf')
                                                        <i class="fas fa-file-pdf text-danger fa-2x"></i>
                                                        @break
                                                    @case('jpg')
                                                    @case('jpeg')  
                                                    @case('png')
                                                        <i class="fas fa-file-image text-info fa-2x"></i>
                                                        @break
                                                    @case('doc')
                                                    @case('docx')
                                                        <i class="fas fa-file-word text-primary fa-2x"></i>
                                                        @break
                                                    @default
                                                        <i class="fas fa-file text-secondary fa-2x"></i>
                                                @endswitch
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="document-type mb-2 text-primary fw-bold">
                                                {{ str_replace('_', ' ', ucwords($document->document_type)) }}
                                            </h6>
                                            <div class="document-name text-dark mb-2">
                                                {{ strlen($document->document_name) > 30 ? substr($document->document_name, 0, 30) . '...' : $document->document_name }}
                                            </div>
                                            <div class="document-meta mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $document->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                            
                                            <div class="document-actions d-flex gap-2">
                                                @if($document->exists)
                                                    <a href="{{ route('validator.document.show', $document->id) }}" 
                                                       target="_blank" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>Lihat
                                                    </a>
                                                    <a href="{{ route('validator.document.download', $document->id) }}" 
                                                       class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-download me-1"></i>Download
                                                    </a>
                                                @else
                                                    <span class="badge bg-danger rounded-pill">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>File tidak ditemukan
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak Ada Dokumen</h5>
                        <p class="text-muted">Siswa belum mengunggah dokumen pendukung</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Validation Form -->
        <div class="card shadow sticky-top" style="top: 2rem;">
            <div class="card-header bg-gradient-warning text-dark">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-gavel me-2"></i>
                    Form Validasi
                </h6>
            </div>
            <form action="{{ route('validator.validation.store', $application->id) }}" method="POST" id="validationForm">
                @csrf
                <div class="card-body p-4">
                    <!-- Validation Recommendation -->
                    <div class="card border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <div class="card-body p-3">
                            <h6 class="mb-2">
                                <i class="fas fa-brain me-2"></i>Rekomendasi Sistem
                            </h6>
                            <div class="small">
                                <i class="fas fa-thumbs-up me-1"></i>
                                Aplikasi telah diisi dan siap untuk divalidasi.
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">
                            <i class="fas fa-balance-scale me-2 text-primary"></i>
                            Keputusan Validasi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="">Pilih Keputusan...</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>
                                ✅ Setujui Aplikasi
                            </option>
                            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>
                                ❌ Tolak Aplikasi
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">
                            <i class="fas fa-sticky-note me-2 text-info"></i>
                            Catatan Validasi
                        </label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="5" 
                                  placeholder="Berikan catatan validasi yang jelas dan konstruktif...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Catatan akan dikirim ke siswa melalui email
                        </small>
                    </div>
                </div>

                <div class="card-footer bg-light p-4">
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" id="submitBtn">
                            <i class="fas fa-save me-2"></i>
                            Simpan Validasi
                        </button>
                        <a href="{{ route('validator.validation.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.sticky-top {
    top: 1rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}

.info-value {
    font-size: 0.95rem;
    color: #212529;
}

.response-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.response-value {
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    min-height: 45px;
    word-wrap: break-word;
}

.status-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.score-display {
    min-width: 80px;
}

.score-badge .badge {
    font-size: 1.1rem;
    min-width: 50px;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.border-start.border-3 {
    border-left-width: 3px !important;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #28a745, #1e7e34);
}

.bg-gradient-warning {
    background: linear-gradient(45deg, #ffc107, #e0a800);
}

.bg-gradient-info {
    background: linear-gradient(45deg, #17a2b8, #138496);
}

.icon-container {
    background-color: #f8f9fa;
}

.document-icon {
    flex-shrink: 0;
}

.document-type {
    font-size: 1rem;
}

.document-name {
    font-size: 0.9rem;
    word-break: break-word;
}

.document-meta {
    font-size: 0.8rem;
}

.document-actions .btn {
    font-size: 0.8rem;
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

.bg-success.bg-opacity-10 {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#validationForm');
    const submitBtn = document.querySelector('#submitBtn');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            const status = document.getElementById('status').value;
            if (!status) {
                e.preventDefault();
                alert('Silakan pilih keputusan validasi!');
                return false;
            }
            
            const action = status === 'approved' ? 'menyetujui' : 'menolak';
            if (!confirm('Apakah Anda yakin ingin ' + action + ' aplikasi ini?')) {
                e.preventDefault();
                return false;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        });
    }
});
</script>
@endpush