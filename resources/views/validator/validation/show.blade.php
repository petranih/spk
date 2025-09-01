{{-- resources/views/validator/validation/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Validasi Aplikasi')
@section('page-title', 'Validasi Aplikasi')

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Application Info Card -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Informasi Aplikasi
                    </h5>
                    <div>
                        <span class="badge bg-warning fs-6 px-3 py-2">
                            <i class="fas fa-clock me-1"></i>Pending Validasi
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">ID Aplikasi:</th>
                                <td><code class="bg-primary text-white p-1 rounded">{{ $application->application_number }}</code></td>
                            </tr>
                            <tr>
                                <th>Nama Lengkap:</th>
                                <td><strong>{{ $application->full_name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $application->user->email }}</td>
                            </tr>
                            <tr>
                                <th>No. Telepon:</th>
                                <td>{{ $application->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Sekolah:</th>
                                <td>{{ $application->school }}</td>
                            </tr>
                            <tr>
                                <th>Kelas:</th>
                                <td>{{ $application->class }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">NISN:</th>
                                <td>{{ $application->nisn ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tempat Lahir:</th>
                                <td>{{ $application->birth_place ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal Lahir:</th>
                                <td>{{ $application->birth_date ? $application->birth_date->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin:</th>
                                <td>{{ $application->gender ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat:</th>
                                <td>{{ $application->address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Periode:</th>
                                <td>
                                    <span class="badge bg-info">{{ $application->period->name }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Tanggal Submit:</th>
                                <td>{{ $application->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Status Saat Ini:</th>
                                <td>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Values Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list-check me-2"></i>
                    Penilaian Kriteria
                </h5>
            </div>
            <div class="card-body">
                @if($criterias->count() > 0)
                    <div class="accordion" id="criteriaAccordion">
                        @foreach($criterias as $index => $criteria)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#criteria{{ $criteria->id }}" 
                                        aria-expanded="{{ $index == 0 ? 'true' : 'false' }}">
                                    <strong>{{ $criteria->code }} - {{ $criteria->name }}</strong>
                                    @if($criteria->weight)
                                        <span class="badge bg-secondary ms-2">Bobot: {{ number_format($criteria->weight * 100, 1) }}%</span>
                                    @endif
                                </button>
                            </h2>
                            <div id="criteria{{ $criteria->id }}" 
                                 class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" 
                                 data-bs-parent="#criteriaAccordion">
                                <div class="accordion-body">
                                    @if($criteria->subCriterias->count() > 0)
                                        @foreach($criteria->subCriterias as $subCriteria)
                                        <div class="border rounded p-3 mb-3">
                                            <h6 class="text-primary">
                                                {{ $subCriteria->code }} - {{ $subCriteria->name }}
                                                @if($subCriteria->weight)
                                                    <span class="badge bg-info ms-2">Bobot: {{ number_format($subCriteria->weight * 100, 1) }}%</span>
                                                @endif
                                            </h6>
                                            
                                            @if($subCriteria->subSubCriterias->count() > 0)
                                                @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                                <div class="ms-3 border-start ps-3 mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>
                                                            <strong>{{ $subSubCriteria->code }}</strong> - {{ $subSubCriteria->name }}
                                                            @if($subSubCriteria->weight)
                                                                <small class="text-muted">(Bobot: {{ number_format($subSubCriteria->weight * 100, 1) }}%)</small>
                                                            @endif
                                                        </span>
                                                        <div>
                                                            @php
                                                                $key = 'subsubcriteria_' . $subSubCriteria->id;
                                                                $score = $applicationValues[$key]->score ?? 0;
                                                            @endphp
                                                            <span class="badge {{ $score > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                                Nilai: {{ $score }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @else
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>Nilai Sub-Kriteria</span>
                                                    <div>
                                                        @php
                                                            $key = 'subcriteria_' . $subCriteria->id;
                                                            $score = $applicationValues[$key]->score ?? 0;
                                                        @endphp
                                                        <span class="badge {{ $score > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                            Nilai: {{ $score }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Nilai Kriteria</span>
                                            <div>
                                                @php
                                                    $key = 'criteria_' . $criteria->id;
                                                    $score = $applicationValues[$key]->score ?? 0;
                                                @endphp
                                                <span class="badge {{ $score > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                    Nilai: {{ $score }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">Belum Ada Kriteria</h6>
                        <p class="text-muted">Kriteria penilaian belum diatur oleh admin</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Documents Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i>
                    Dokumen Pendukung
                </h5>
            </div>
            <div class="card-body">
                @if($documents->count() > 0)
                    <div class="row">
                        @foreach($documents as $document)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $document->document_type }}</h6>
                                        <p class="text-muted small mb-2">{{ $document->document_name }}</p>
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $document->created_at->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-file me-1"></i>
                                            {{ $document->file_size_formatted }}
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ $document->url }}" 
                                           target="_blank" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Lihat
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak Ada Dokumen</h6>
                        <p class="text-muted">Siswa belum mengupload dokumen pendukung</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Validation Form -->
        <div class="card sticky-top">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-gavel me-2"></i>
                    Form Validasi
                </h6>
            </div>
            <form action="{{ route('validator.validation.store', $application->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Keputusan Validasi <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="">Pilih Keputusan...</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>
                                <i class="fas fa-check"></i> Setujui Aplikasi
                            </option>
                            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>
                                <i class="fas fa-times"></i> Tolak Aplikasi
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan Validasi</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="5" 
                                  placeholder="Berikan catatan atau alasan keputusan validasi...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Catatan akan dikirim ke siswa sebagai feedback
                        </div>
                    </div>

                    <div class="alert alert-info" id="approval-info" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Menyetujui aplikasi akan:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Mengubah status aplikasi menjadi "Validated"</li>
                            <li>Menghitung skor AHP secara otomatis</li>
                            <li>Menambahkan aplikasi ke dalam ranking</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning" id="rejection-info" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Menolak aplikasi akan:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Mengubah status aplikasi menjadi "Rejected"</li>
                            <li>Siswa dapat melihat catatan penolakan</li>
                            <li>Aplikasi tidak akan masuk ke dalam ranking</li>
                        </ul>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
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

        <!-- Application Summary -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Ringkasan Aplikasi
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-primary mb-1">{{ $criterias->count() }}</h5>
                            <small class="text-muted">Kriteria</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-info mb-1">{{ $documents->count() }}</h5>
                            <small class="text-muted">Dokumen</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h5 class="text-success mb-1">
                            {{ $applicationValues->where('score', '>', 0)->count() }}
                        </h5>
                        <small class="text-muted">Nilai > 0</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.sticky-top {
    top: 1rem;
}

.table th {
    border: none;
    padding: 0.25rem 0;
    font-weight: 600;
}

.table td {
    border: none;
    padding: 0.25rem 0;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: transparent;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    border-color: transparent;
}

code {
    font-size: 0.8em;
}

.badge {
    font-size: 0.75em;
}

.border-start {
    border-left: 3px solid #dee2e6 !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const approvalInfo = document.getElementById('approval-info');
    const rejectionInfo = document.getElementById('rejection-info');

    statusSelect.addEventListener('change', function() {
        const value = this.value;
        
        // Hide all info alerts
        approvalInfo.style.display = 'none';
        rejectionInfo.style.display = 'none';
        
        // Show relevant info
        if (value === 'approved') {
            approvalInfo.style.display = 'block';
        } else if (value === 'rejected') {
            rejectionInfo.style.display = 'block';
        }
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const status = statusSelect.value;
        const notes = document.getElementById('notes').value.trim();
        
        if (!status) {
            e.preventDefault();
            alert('Silakan pilih keputusan validasi terlebih dahulu!');
            statusSelect.focus();
            return false;
        }
        
        // Confirm submission
        const action = status === 'approved' ? 'menyetujui' : 'menolak';
        const confirmMessage = `Apakah Anda yakin ingin ${action} aplikasi ini?`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush