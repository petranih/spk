{{-- resources/views/admin/scoring/applications.blade.php --}}
@extends('layouts.app')

@section('title', 'Aplikasi Periode ' . $period->name)
@section('page-title', 'Aplikasi untuk Periode: ' . $period->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.scoring.index') }}">Scoring</a></li>
                        <li class="breadcrumb-item active">{{ $period->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                @if($applications->count() > 0)
                    <form action="{{ route('admin.scoring.calculate-all', $period->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Yakin ingin menghitung semua aplikasi pada periode ini?')">
                            <i class="fas fa-calculator me-2"></i>
                            Hitung Semua ({{ $applications->count() }})
                        </button>
                    </form>
                    
                    @php
                        $scoredCount = $applications->whereNotNull('final_score')->count();
                    @endphp
                    @if($scoredCount > 0)
                        <a href="{{ route('admin.scoring.export', [$period->id, 'excel']) }}" class="btn btn-outline-success">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </a>
                        <a href="{{ route('admin.scoring.export', [$period->id, 'pdf']) }}" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                <h4 class="text-primary">{{ $applications->count() }}</h4>
                <small class="text-muted">Total Aplikasi Tervalidasi</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-calculator fa-2x text-success mb-2"></i>
                <h4 class="text-success">{{ $applications->whereNotNull('final_score')->count() }}</h4>
                <small class="text-muted">Sudah Dihitung Skor</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <h4 class="text-warning">{{ $applications->whereNull('final_score')->count() }}</h4>
                <small class="text-muted">Belum Dihitung</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-trophy fa-2x text-info mb-2"></i>
                <h4 class="text-info">{{ $applications->whereNotNull('rank')->count() }}</h4>
                <small class="text-muted">Sudah Ada Ranking</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Daftar Aplikasi - {{ $period->name }}
                </h5>
                <small class="text-muted">Periode: {{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}</small>
            </div>
            <div class="card-body">
                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>Sekolah</th>
                                    <th>Status Skor</th>
                                    <th>Skor Final</th>
                                    <th>Ranking</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $index => $application)
                                <tr class="{{ $application->final_score ? '' : 'table-warning' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $application->full_name }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $application->user->email ?? 'No email' }}</small>
                                    </td>
                                    <td>
                                        <code>{{ $application->nisn }}</code>
                                    </td>
                                    <td>
                                        <div>{{ $application->school }}</div>
                                        <small class="text-muted">Kelas {{ $application->class }}</small>
                                    </td>
                                    <td>
                                        @if($application->final_score)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Sudah Dihitung
                                            </span>
                                            @if($application->calculated_at)
                                                <br><small class="text-muted">{{ $application->calculated_at->diffForHumans() }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Belum Dihitung
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($application->final_score)
                                            <span class="badge bg-primary fs-6">
                                                {{ number_format($application->final_score, 4) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($application->rank)
                                            <span class="badge bg-info fs-6">
                                                #{{ $application->rank }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.scoring.detail', $application->id) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="Lihat Detail Perhitungan">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <form action="{{ route('admin.scoring.calculate-single', $application->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Yakin ingin {{ $application->final_score ? 'menghitung ulang' : 'menghitung' }} skor untuk {{ $application->full_name }}?')"
                                                        title="{{ $application->final_score ? 'Hitung Ulang' : 'Hitung Skor' }}">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($applications->whereNotNull('final_score')->count() > 0)
                        <div class="mt-4">
                            <h6>Ranking Sementara:</h6>
                            <div class="row">
                                @php
                                    $rankedApps = $applications->whereNotNull('final_score')->sortBy('rank')->take(5);
                                @endphp
                                @foreach($rankedApps as $app)
                                    <div class="col-md-2 mb-2">
                                        <div class="card border-{{ $app->rank == 1 ? 'warning' : ($app->rank <= 3 ? 'success' : 'info') }}">
                                            <div class="card-body text-center p-2">
                                                <div class="h5 text-{{ $app->rank == 1 ? 'warning' : ($app->rank <= 3 ? 'success' : 'info') }}">
                                                    #{{ $app->rank }}
                                                </div>
                                                <div class="small">
                                                    <strong>{{ Str::limit($app->full_name, 15) }}</strong>
                                                </div>
                                                <div class="small text-muted">
                                                    {{ number_format($app->final_score, 3) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak Ada Aplikasi Tervalidasi</h5>
                        <p class="text-muted">Belum ada aplikasi yang tervalidasi untuk periode ini.</p>
                        <a href="{{ route('admin.period.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Periode
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($applications->count() > 0)
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informasi Perhitungan AHP
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Langkah Perhitungan:</h6>
                        <ol class="small">
                            <li>Ambil data kriteria dari aplikasi siswa</li>
                            <li>Gunakan bobot dari pairwise comparison</li>
                            <li>Hitung skor untuk setiap kriteria</li>
                            <li>Kalikan dengan bobot masing-masing</li>
                            <li>Jumlahkan untuk mendapat skor final</li>
                            <li>Urutkan berdasarkan skor tertinggi</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6>Status Perhitungan:</h6>
                        <div class="progress mb-2">
                            @php
                                $progress = $applications->count() > 0 ? ($applications->whereNotNull('final_score')->count() / $applications->count()) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $progress }}%">
                                {{ round($progress, 1) }}%
                            </div>
                        </div>
                        <div class="small text-muted">
                            {{ $applications->whereNotNull('final_score')->count() }} dari {{ $applications->count() }} aplikasi sudah dihitung
                        </div>
                        
                        @if($progress < 100)
                            <div class="mt-3">
                                <form action="{{ route('admin.scoring.calculate-all', $period->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100" 
                                            onclick="return confirm('Hitung semua aplikasi yang belum dihitung?')">
                                        <i class="fas fa-calculator me-2"></i>
                                        Hitung Sisanya ({{ $applications->whereNull('final_score')->count() }})
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Statistik Skor
                </h6>
            </div>
            <div class="card-body">
                @php
                    $scoredApps = $applications->whereNotNull('final_score');
                    $avgScore = $scoredApps->count() > 0 ? $scoredApps->avg('final_score') : 0;
                    $maxScore = $scoredApps->count() > 0 ? $scoredApps->max('final_score') : 0;
                    $minScore = $scoredApps->count() > 0 ? $scoredApps->min('final_score') : 0;
                @endphp
                
                @if($scoredApps->count() > 0)
                    <table class="table table-sm">
                        <tr>
                            <td>Skor Tertinggi:</td>
                            <td><span class="badge bg-success">{{ number_format($maxScore, 4) }}</span></td>
                        </tr>
                        <tr>
                            <td>Skor Terendah:</td>
                            <td><span class="badge bg-danger">{{ number_format($minScore, 4) }}</span></td>
                        </tr>
                        <tr>
                            <td>Rata-rata:</td>
                            <td><span class="badge bg-info">{{ number_format($avgScore, 4) }}</span></td>
                        </tr>
                        <tr>
                            <td>Range:</td>
                            <td><span class="badge bg-secondary">{{ number_format($maxScore - $minScore, 4) }}</span></td>
                        </tr>
                    </table>
                @else
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <p class="small">Belum ada data skor untuk ditampilkan</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.pairwise.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-balance-scale me-2"></i>
                        Kelola Bobot AHP
                    </a>
                    
                    @if($applications->whereNotNull('final_score')->count() > 0)
                        <a href="{{ route('admin.report.show', $period->id) }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-alt me-2"></i>
                            Lihat Laporan
                        </a>
                    @endif
                    
                    <a href="{{ route('admin.period.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-calendar me-2"></i>
                        Kelola Periode
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection