{{-- resources/views/validator/validation/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Validasi Aplikasi')
@section('page-title', 'Validasi Aplikasi')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Aplikasi Pending</h6>
                        <h2 class="mb-0">{{ $applications->total() }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Memerlukan validasi segera
                    </small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Halaman Ini</h6>
                        <h2 class="mb-0">{{ $applications->count() }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-list fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Dari {{ $applications->total() }} total aplikasi
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-check me-2"></i>
                Daftar Aplikasi Menunggu Validasi
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-refresh me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($applications->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">ID Aplikasi</th>
                            <th width="20%">Data Siswa</th>
                            <th width="20%">Sekolah & Kelas</th>
                            <th width="15%">Periode</th>
                            <th width="15%">Tanggal Submit</th>
                            <th width="10%">Status</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $app)
                        <tr>
                            <td>
                                <code class="bg-primary text-white p-1 rounded">{{ $app->application_number }}</code>
                            </td>
                            <td>
                                <div>
                                    <strong class="text-dark">{{ $app->full_name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>{{ $app->user->email }}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>{{ $app->phone ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $app->school }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-graduation-cap me-1"></i>{{ $app->class }}
                                    </small>
                                    @if($app->nisn)
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-1"></i>NISN: {{ $app->nisn }}
                                    </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $app->period->name }}</span>
                                <br>
                                <small class="text-muted">
                                    {{ $app->period->start_date->format('d/m/Y') }} - {{ $app->period->end_date->format('d/m/Y') }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ $app->updated_at->format('d/m/Y') }}</strong>
                                <br>
                                <small class="text-muted">{{ $app->updated_at->format('H:i') }}</small>
                                <br>
                                <small class="text-success">{{ $app->updated_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </span>
                            </td>
                            <td>
                                <div class="d-grid">
                                    <a href="{{ route('validator.validation.show', $app->id) }}" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        Validasi
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <small class="text-muted">
                        Menampilkan {{ $applications->firstItem() }} - {{ $applications->lastItem() }} 
                        dari {{ $applications->total() }} aplikasi
                    </small>
                </div>
                <div>
                    {{ $applications->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                <h4 class="text-muted mb-2">Semua Aplikasi Sudah Divalidasi</h4>
                <p class="text-muted mb-4">
                    Saat ini tidak ada aplikasi yang memerlukan validasi. 
                    Semua aplikasi yang disubmit sudah diproses.
                </p>
                <a href="{{ route('validator.dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Kembali ke Dashboard
                </a>
            </div>
        @endif
    </div>
</div>

@if($applications->count() > 0)
<!-- Quick Actions Card -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Panduan Validasi
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Hal yang Perlu Diperiksa:</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Kelengkapan data pribadi</li>
                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Kevalidan dokumen yang diupload</li>
                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Kesesuaian dengan kriteria beasiswa</li>
                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Format dan kualitas dokumen</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">Tips Validasi:</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><i class="fas fa-lightbulb text-warning me-2"></i>Baca semua dokumen dengan teliti</li>
                    <li class="mb-1"><i class="fas fa-lightbulb text-warning me-2"></i>Berikan catatan yang jelas dan konstruktif</li>
                    <li class="mb-1"><i class="fas fa-lightbulb text-warning me-2"></i>Konsisten dalam penilaian</li>
                    <li class="mb-1"><i class="fas fa-lightbulb text-warning me-2"></i>Pertimbangkan secara objektif</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table-hover tbody tr:hover {
    background-color: #f5f5f5;
}

code {
    font-size: 0.8em;
}

.badge {
    font-size: 0.75em;
}

.btn-sm {
    font-size: 0.8em;
}
</style>
@endpush