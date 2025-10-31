{{-- resources/views/student/application/show.blade.php - Detail Aplikasi (Read-Only) FIXED --}}
@extends('layouts.app')

@section('title', 'Detail Aplikasi')
@section('page-title', 'Detail Aplikasi Beasiswa')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
    </div>
</div>

{{-- Info Status Aplikasi --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-{{ $application->status == 'validated' ? 'success' : ($application->status == 'rejected' ? 'danger' : 'warning') }}">
            <div class="card-header bg-{{ $application->status == 'validated' ? 'success' : ($application->status == 'rejected' ? 'danger' : 'warning') }} text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Status Aplikasi
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td width="40%"><strong>No. Aplikasi</strong></td>
                                <td>: <code>{{ $application->application_number ?? 'APP-' . $application->id }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>Periode</strong></td>
                                <td>: {{ $application->period->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Daftar</strong></td>
                                <td>: {{ $application->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Submit</strong></td>
                                <td>: {{ $application->submitted_at ? $application->submitted_at->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td width="40%"><strong>Status</strong></td>
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
                            @if($application->final_score)
                            <tr>
                                <td><strong>Skor Akhir</strong></td>
                                <td>: <span class="badge bg-primary fs-6">{{ number_format($application->final_score, 4) }}</span></td>
                            </tr>
                            @endif
                            @if($application->rank)
                            <tr>
                                <td><strong>Peringkat</strong></td>
                                <td>: <span class="badge bg-info fs-6">#{{ $application->rank }}</span></td>
                            </tr>
                            @endif
                            @if($application->validation && $application->validation->notes)
                            <tr>
                                <td colspan="2">
                                    <div class="alert alert-{{ $application->status == 'validated' ? 'success' : 'danger' }} mb-0 mt-2">
                                        <strong>Catatan Validator:</strong><br>
                                        {{ $application->validation->notes }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Data Pribadi --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Data Pribadi
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td width="40%"><strong>Nama Lengkap</strong></td>
                                <td>: {{ $application->full_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>NISN</strong></td>
                                <td>: {{ $application->nisn }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sekolah</strong></td>
                                <td>: {{ $application->school }}</td>
                            </tr>
                            <tr>
                                <td><strong>Kelas</strong></td>
                                <td>: {{ $application->class }}</td>
                            </tr>
                            <tr>
                                <td><strong>Jenis Kelamin</strong></td>
                                <td>: {{ $application->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td width="40%"><strong>Tempat Lahir</strong></td>
                                <td>: {{ $application->birth_place }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Lahir</strong></td>
                                <td>: {{ \Carbon\Carbon::parse($application->birth_date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>No. Telepon</strong></td>
                                <td>: {{ $application->phone }}</td>
                            </tr>
                            <tr>
                                <td><strong>Alamat</strong></td>
                                <td>: {{ $application->address }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Data Kriteria - PERBAIKAN untuk C5 --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Data Kriteria AHP
                </h5>
            </div>
            <div class="card-body">
                @foreach($criterias as $criteria)
                <div class="mb-4">
                    <h6 class="text-primary border-bottom pb-2">
                        <i class="fas fa-folder me-2"></i>
                        {{ $criteria->code }} - {{ $criteria->name }}
                    </h6>
                    
                    <div class="ms-3">
                        @foreach($criteria->subCriterias as $subCriteria)
                            <div class="mb-3">
                                <strong class="text-secondary">
                                    <i class="fas fa-angle-right me-1"></i>
                                    {{ $subCriteria->code }} - {{ $subCriteria->name }}
                                </strong>
                                
                                @if($subCriteria->subSubCriterias->count() > 0)
                                    {{-- Ada SubSubCriteria (C1, C2, C3, C4) --}}
                                    <div class="ms-4 mt-2">
                                        @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                            @php
                                                $key = 'subsubcriteria_' . $subSubCriteria->id;
                                                $isSelected = isset($existingValues[$key]);
                                            @endphp
                                            
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" disabled
                                                    {{ $isSelected ? 'checked' : '' }}>
                                                <label class="form-check-label {{ $isSelected ? 'fw-bold text-success' : '' }}">
                                                    {{ $subSubCriteria->name }}
                                                    @if($isSelected)
                                                        <i class="fas fa-check-circle text-success ms-2"></i>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    {{-- Direct SubCriteria (C5 - Status Penerimaan Bantuan) --}}
                                    @php
                                        $key = 'subcriteria_' . $subCriteria->id;
                                        $isSelected = isset($existingValues[$key]);
                                    @endphp
                                    
                                    <div class="ms-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled
                                                {{ $isSelected ? 'checked' : '' }}>
                                            <label class="form-check-label {{ $isSelected ? 'fw-bold text-success' : 'text-muted' }}">
                                                {{ $subCriteria->name }}
                                                @if($isSelected)
                                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                                    <span class="badge bg-success ms-2">Dipilih</span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                
                @if(!$loop->last)
                    <hr class="my-4">
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Dokumen - PERBAIKAN link view --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-file-upload me-2"></i>
                    Dokumen Pendukung
                </h5>
            </div>
            <div class="card-body">
                @if($documents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Jenis Dokumen</th>
                                    <th width="30%">Nama Dokumen</th>
                                    <th width="15%">Ukuran</th>
                                    <th width="20%">Tanggal Upload</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $index => $doc)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-primary">
                                            @switch($doc->document_type)
                                                @case('ktp') <i class="fas fa-id-card me-1"></i>KTP @break
                                                @case('kk') <i class="fas fa-users me-1"></i>KK @break
                                                @case('slip_gaji') <i class="fas fa-money-bill me-1"></i>Slip Gaji @break
                                                @case('surat_keterangan') <i class="fas fa-file-alt me-1"></i>Surat Keterangan @break
                                                @default {{ $doc->document_type }}
                                            @endswitch
                                        </span>
                                    </td>
                                    <td>{{ $doc->document_name }}</td>
                                    <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                    <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        {{-- PERBAIKAN: Gunakan route khusus untuk view dokumen --}}
                                        <a href="{{ route('document.view', $doc->id) }}" 
                                           class="btn btn-sm btn-info" 
                                           target="_blank" 
                                           title="Lihat Dokumen">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada dokumen yang diupload</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tombol Aksi --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center">
                <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
                
                @if($application->status == 'draft')
                    <a href="{{ route('student.application.edit', $application->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Lanjutkan Edit
                    </a>
                @endif
                
                @if($application->status == 'validated' || $application->status == 'rejected')
                    <button type="button" class="btn btn-info" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Cetak Detail
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    @media print {
        .btn, .card-header, nav, footer {
            display: none !important;
        }
        .card {
            border: 1px solid #000 !important;
            page-break-inside: avoid;
        }
    }
</style>
@endpush