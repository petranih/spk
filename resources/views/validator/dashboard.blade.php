{{-- resources/views/validator/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Validator')
@section('page-title', 'Dashboard Validator')

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Pending Validasi</h6>
                        <h2 class="mb-0">{{ $stats['pending'] }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Memerlukan perhatian segera
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Validasi Saya</h6>
                        <h2 class="mb-0">{{ $stats['validated'] }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-clipboard-check fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-chart-line me-1"></i>
                        Semua periode
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Disetujui</h6>
                        <h2 class="mb-0">{{ $stats['approved'] }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        @if($stats['validated'] > 0)
                            {{ number_format(($stats['approved'] / $stats['validated']) * 100, 1) }}% approval rate
                        @else
                            0% approval rate
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Ditolak</h6>
                        <h2 class="mb-0">{{ $stats['rejected'] }}</h2>
                    </div>
                    <div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        @if($stats['validated'] > 0)
                            {{ number_format(($stats['rejected'] / $stats['validated']) * 100, 1) }}% rejection rate
                        @else
                            0% rejection rate
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Pending Applications -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-hourglass-half me-2"></i>
                        Aplikasi Menunggu Validasi
                    </h5>
                    @if($pendingApplications->count() > 0)
                        <a href="{{ route('validator.validation.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Lihat Semua
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($pendingApplications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Aplikasi</th>
                                    <th>Nama Siswa</th>
                                    <th>Sekolah</th>
                                    <th>Submit</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApplications as $app)
                                <tr>
                                    <td>
                                        <code>APP-{{ $app->id }}</code>
                                        <br>
                                        <small class="text-muted">{{ $app->period->name }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $app->full_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $app->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $app->school }}
                                        <br>
                                        <small class="text-muted">{{ $app->class }}</small>
                                    </td>
                                    <td>
                                        {{ $app->updated_at->format('d/m/Y') }}
                                        <br>
                                        <small class="text-muted">{{ $app->updated_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('validator.validation.show', $app->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>Validasi
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="text-muted">Tidak Ada Aplikasi Pending</h5>
                        <p class="text-muted">Semua aplikasi sudah divalidasi atau belum ada yang disubmit</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Validations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Validasi Terbaru Saya
                </h5>
            </div>
            <div class="card-body">
                @if($myValidations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Aplikasi</th>
                                    <th>Nama Siswa</th>
                                    <th>Status</th>
                                    <th>Tanggal Validasi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myValidations as $validation)
                                <tr>
                                    <td>
                                        <code>APP-{{ $validation->application->id }}</code>
                                        <br>
                                        <small class="text-muted">{{ $validation->application->period->name }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $validation->application->full_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $validation->application->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($validation->status === 'approved')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Disetujui
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $validation->validated_at->format('d/m/Y H:i') }}
                                        <br>
                                        <small class="text-muted">{{ $validation->validated_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($validation->notes)
                                            <small>{{ Str::limit($validation->notes, 50) }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum Ada Validasi</h5>
                        <p class="text-muted">Anda belum melakukan validasi aplikasi apapun</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('validator.validation.index') }}" class="btn btn-primary">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Lihat Semua Aplikasi ({{ $stats['pending'] }})
                    </a>
                </div>
            </div>
        </div>

        <!-- Validation Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Statistik Validasi Saya
                </h6>
            </div>
            <div class="card-body">
                @if($stats['validated'] > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-success">Disetujui</span>
                            <span>{{ $stats['approved'] }}/{{ $stats['validated'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: {{ ($stats['approved'] / $stats['validated']) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-danger">Ditolak</span>
                            <span>{{ $stats['rejected'] }}/{{ $stats['validated'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-danger" 
                                 role="progressbar" 
                                 style="width: {{ ($stats['rejected'] / $stats['validated']) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="text-center">
                        <h4 class="mb-1">{{ $stats['validated'] }}</h4>
                        <small class="text-muted">Total Aplikasi Divalidasi</small>
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada data statistik</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tips & Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Tips Validasi
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Pastikan semua dokumen telah diupload dengan lengkap
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-eye text-info me-2"></i>
                        Periksa kevalidan dan kejelasan dokumen
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        Verifikasi semua kriteria telah diisi dengan benar
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-comment text-warning me-2"></i>
                        Berikan catatan yang jelas untuk setiap keputusan
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-balance-scale text-secondary me-2"></i>
                        Validasi berdasarkan objektifitas dan konsistensi
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection