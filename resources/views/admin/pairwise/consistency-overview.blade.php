{{-- resources/views/admin/pairwise/consistency-overview.blade.php --}}
@extends('layouts.app')

@section('title', 'Overview Konsistensi AHP')
@section('page-title', 'Overview Konsistensi AHP')

@push('styles')
<style>
    .consistency-card {
        border-left: 4px solid;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .consistency-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .consistent-card {
        border-left-color: #198754;
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    }
    .inconsistent-card {
        border-left-color: #dc3545;
        background: linear-gradient(135deg, #fff8f8 0%, #f8e8e8 100%);
    }
    .no-data-card {
        border-left-color: #6c757d;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    .metric-item {
        padding: 8px 12px;
        border-radius: 6px;
        margin: 2px 0;
    }
    .metric-consistent {
        background-color: rgba(25, 135, 84, 0.1);
        color: #0f5132;
    }
    .metric-inconsistent {
        background-color: rgba(220, 53, 69, 0.1);
        color: #842029;
    }
    .summary-stats {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .stats-card {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        backdrop-filter: blur(10px);
    }
    .level-section {
        margin-bottom: 40px;
    }
    .level-header {
        background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        border-bottom: 2px solid #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Summary Statistics --}}
        <div class="summary-stats">
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h4 class="mb-1">{{ count($data['criteria']) }}</h4>
                        <small>Total Matriks Kriteria</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h4 class="mb-1">{{ count($data['subcriteria']) }}</h4>
                        <small>Total Matriks Sub Kriteria</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h4 class="mb-1">{{ count($data['subsubcriteria']) }}</h4>
                        <small>Total Matriks Sub-Sub Kriteria</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        @php
                            $totalMatrices = count($data['criteria']) + count($data['subcriteria']) + count($data['subsubcriteria']);
                            $consistentMatrices = 0;
                            foreach ($data as $level) {
                                foreach ($level as $item) {
                                    if ($item['is_consistent']) $consistentMatrices++;
                                }
                            }
                            $consistencyPercentage = $totalMatrices > 0 ? round(($consistentMatrices / $totalMatrices) * 100, 1) : 0;
                        @endphp
                        <h4 class="mb-1">{{ $consistencyPercentage }}%</h4>
                        <small>Tingkat Konsistensi</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Navigasi Cepat</h6>
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.pairwise.criteria') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-layer-group me-1"></i> Kriteria
                        </a>
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-list me-1"></i> Sub Kriteria
                        </button>
                        <ul class="dropdown-menu">
                            @foreach(\App\Models\Criteria::active()->whereHas('subCriterias')->get() as $criteria)
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}">
                                        {{ $criteria->code }} - {{ $criteria->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <button class="btn btn-outline-success btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Criteria Level --}}
<div class="level-section">
    <div class="level-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-layer-group me-2 text-primary"></i>
                Level 1: Kriteria Utama
            </h5>
            <span class="badge bg-primary">{{ count($data['criteria']) }} matriks</span>
        </div>
    </div>
    
    @if(count($data['criteria']) > 0)
        <div class="row">
            @foreach($data['criteria'] as $item)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card consistency-card {{ $item['is_consistent'] ? 'consistent-card' : 'inconsistent-card' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="card-title mb-1">{{ $item['code'] }}</h6>
                                    <small class="badge bg-light text-dark">{{ $item['grandparent'] }} → {{ $item['parent'] }}</small>
                                </div>
                                <i class="fas {{ $item['is_consistent'] ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' }}"></i>
                            </div>
                            <p class="card-text small text-muted mb-3">{{ $item['name'] }}</p>
                            
                            <div class="metric-item {{ $item['is_consistent'] ? 'metric-consistent' : 'metric-inconsistent' }}">
                                <small><strong>CR:</strong> {{ number_format($item['cr'], 4) }}</small>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar {{ $item['is_consistent'] ? 'bg-success' : 'bg-danger' }}" 
                                         style="width: {{ min($item['cr'] * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted">λmax: {{ number_format($item['lambda_max'], 3) }}</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">CI: {{ number_format($item['ci'], 4) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card no-data-card">
            <div class="card-body text-center py-4">
                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                <h6>Belum Ada Data Sub-Sub Kriteria</h6>
                <p class="text-muted">Sub-sub kriteria akan muncul setelah dilakukan perbandingan berpasangan.</p>
            </div>
        </div>
    @endif
</div>

{{-- Summary and Recommendations --}}
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Rekomendasi
                </h6>
            </div>
            <div class="card-body">
                @php
                    $inconsistentCriteria = array_filter($data['criteria'], function($item) { return !$item['is_consistent']; });
                    $inconsistentSubCriteria = array_filter($data['subcriteria'], function($item) { return !$item['is_consistent']; });
                    $inconsistentSubSubCriteria = array_filter($data['subsubcriteria'], function($item) { return !$item['is_consistent']; });
                    
                    $hasInconsistencies = count($inconsistentCriteria) > 0 || count($inconsistentSubCriteria) > 0 || count($inconsistentSubSubCriteria) > 0;
                @endphp
                
                @if(!$hasInconsistencies && $totalMatrices > 0)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Selamat!</strong> Semua matriks perbandingan berpasangan sudah konsisten. 
                        Anda dapat melanjutkan ke tahap penilaian aplikasi.
                    </div>
                @elseif($hasInconsistencies)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Terdapat matriks yang tidak konsisten. Berikut adalah rekomendasi tindakan:
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        @if(count($inconsistentCriteria) > 0)
                            @foreach($inconsistentCriteria as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Kriteria {{ $item['code'] }}</strong> - CR: {{ number_format($item['cr'], 4) }}
                                        <br><small class="text-muted">{{ $item['name'] }}</small>
                                    </div>
                                    <a href="{{ route('admin.pairwise.criteria') }}" class="btn btn-sm btn-outline-primary">
                                        Perbaiki
                                    </a>
                                </li>
                            @endforeach
                        @endif
                        
                        @if(count($inconsistentSubCriteria) > 0)
                            @foreach($inconsistentSubCriteria as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Sub Kriteria {{ $item['code'] }}</strong> ({{ $item['parent'] }}) - CR: {{ number_format($item['cr'], 4) }}
                                        <br><small class="text-muted">{{ $item['name'] }}</small>
                                    </div>
                                    <a href="#" class="btn btn-sm btn-outline-info">
                                        Perbaiki
                                    </a>
                                </li>
                            @endforeach
                        @endif
                        
                        @if(count($inconsistentSubSubCriteria) > 0)
                            @foreach($inconsistentSubSubCriteria as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Sub-Sub Kriteria {{ $item['code'] }}</strong> ({{ $item['grandparent'] }} → {{ $item['parent'] }}) - CR: {{ number_format($item['cr'], 4) }}
                                        <br><small class="text-muted">{{ $item['name'] }}</small>
                                    </div>
                                    <a href="#" class="btn btn-sm btn-outline-success">
                                        Perbaiki
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Mulai Perbandingan</strong> Belum ada data perbandingan berpasangan. 
                        Silakan mulai dengan melakukan perbandingan kriteria.
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Tentang Konsistensi
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-success">Konsisten (CR ≤ 0.10)</h6>
                    <p class="small text-muted mb-0">
                        Matriks perbandingan dapat diterima dan dapat digunakan untuk pengambilan keputusan.
                    </p>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-danger">Tidak Konsisten (CR > 0.10)</h6>
                    <p class="small text-muted mb-0">
                        Perlu revisi nilai perbandingan untuk meningkatkan konsistensi.
                    </p>
                </div>
                
                <hr>
                
                <div class="text-muted small">
                    <p class="mb-2"><strong>Keterangan:</strong></p>
                    <ul class="mb-0 ps-3">
                        <li>CR = Consistency Ratio</li>
                        <li>CI = Consistency Index</li>
                        <li>λmax = Lambda Maximum</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Data
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Cetak Laporan
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="exportData()">
                        <i class="fas fa-download me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportData() {
    // Implementation for exporting consistency data
    alert('Fitur export akan diimplementasikan');
}

// Auto refresh setiap 5 menit jika ada perubahan
setInterval(function() {
    // Optional: Check for updates and refresh if needed
    // This can be implemented with AJAX calls
}, 300000);
</script>
@endpush

@endsectiond {{ $item['is_consistent'] ? 'consistent-card' : 'inconsistent-card' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-1">{{ $item['code'] }}</h6>
                                <i class="fas {{ $item['is_consistent'] ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' }}"></i>
                            </div>
                            <p class="card-text small text-muted mb-3">{{ $item['name'] }}</p>
                            
                            <div class="metric-item {{ $item['is_consistent'] ? 'metric-consistent' : 'metric-inconsistent' }}">
                                <small><strong>CR:</strong> {{ number_format($item['cr'], 4) }}</small>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar {{ $item['is_consistent'] ? 'bg-success' : 'bg-danger' }}" 
                                         style="width: {{ min($item['cr'] * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted">λmax: {{ number_format($item['lambda_max'], 3) }}</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">CI: {{ number_format($item['ci'], 4) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card no-data-card">
            <div class="card-body text-center py-5">
                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                <h6>Belum Ada Data Perbandingan</h6>
                <p class="text-muted">Silakan lakukan perbandingan berpasangan kriteria terlebih dahulu.</p>
                <a href="{{ route('admin.pairwise.criteria') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Mulai Perbandingan
                </a>
            </div>
        </div>
    @endif
</div>

{{-- Sub-Criteria Level --}}
<div class="level-section">
    <div class="level-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-info"></i>
                Level 2: Sub Kriteria
            </h5>
            <span class="badge bg-info">{{ count($data['subcriteria']) }} matriks</span>
        </div>
    </div>
    
    @if(count($data['subcriteria']) > 0)
        <div class="row">
            @foreach($data['subcriteria'] as $item)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card consistency-card {{ $item['is_consistent'] ? 'consistent-card' : 'inconsistent-card' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="card-title mb-1">{{ $item['code'] }}</h6>
                                    <small class="badge bg-light text-dark">{{ $item['parent'] }}</small>
                                </div>
                                <i class="fas {{ $item['is_consistent'] ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' }}"></i>
                            </div>
                            <p class="card-text small text-muted mb-3">{{ $item['name'] }}</p>
                            
                            <div class="metric-item {{ $item['is_consistent'] ? 'metric-consistent' : 'metric-inconsistent' }}">
                                <small><strong>CR:</strong> {{ number_format($item['cr'], 4) }}</small>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar {{ $item['is_consistent'] ? 'bg-success' : 'bg-danger' }}" 
                                         style="width: {{ min($item['cr'] * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted">λmax: {{ number_format($item['lambda_max'], 3) }}</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">CI: {{ number_format($item['ci'], 4) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card no-data-card">
            <div class="card-body text-center py-4">
                <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                <h6>Belum Ada Data Sub Kriteria</h6>
                <p class="text-muted">Sub kriteria akan muncul setelah dilakukan perbandingan berpasangan.</p>
            </div>
        </div>
    @endif
</div>

{{-- Sub-Sub-Criteria Level --}}
<div class="level-section">
    <div class="level-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-sitemap me-2 text-success"></i>
                Level 3: Sub-Sub Kriteria
            </h5>
            <span class="badge bg-success">{{ count($data['subsubcriteria']) }} matriks</span>
        </div>
    </div>
    
    @if(count($data['subsubcriteria']) > 0)
        <div class="row">
            @foreach($data['subsubcriteria'] as $item)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card consistency-car