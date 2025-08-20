{{-- resources/views/admin/pairwise/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Perbandingan Berpasangan AHP')
@section('page-title', 'Perbandingan Berpasangan AHP')

@push('styles')
<style>
    .hierarchy-card {
        border: none;
        transition: all 0.3s ease;
        border-radius: 12px;
    }
    .hierarchy-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .criteria-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .subcriteria-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    .subsubcriteria-card {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    .consistency-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.75rem;
    }
    .level-indicator {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
    }
    .progress-ring {
        width: 60px;
        height: 60px;
    }
    .progress-ring circle {
        fill: transparent;
        stroke-width: 4;
        stroke-dasharray: 188.4;
        stroke-dashoffset: 188.4;
        stroke-linecap: round;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset 0.3s ease;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h6 class="mb-1">Petunjuk Perbandingan Berpasangan AHP</h6>
                    <p class="mb-0">
                        Lakukan perbandingan berpasangan untuk setiap level hierarki secara berurutan: 
                        <strong>Kriteria → Sub Kriteria → Sub-Sub Kriteria</strong>. 
                        Pastikan setiap level memiliki tingkat konsistensi yang baik (CR ≤ 0.10).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Statistics Overview --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-layer-group fa-2x mb-2"></i>
                <h4>{{ $criterias->count() }}</h4>
                <small>Kriteria Utama</small>
                <div class="mt-2">
                    @if($consistencyData['criteria_consistent'])
                        <span class="badge bg-success">Konsisten</span>
                    @else
                        <span class="badge bg-warning">Perlu Perhitungan</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-list fa-2x mb-2"></i>
                <h4>{{ $consistencyData['subcriteria_total'] }}</h4>
                <small>Sub Kriteria</small>
                <div class="mt-2">
                    <span class="badge bg-light text-dark">
                        {{ $consistencyData['subcriteria_consistent'] }}/{{ $consistencyData['subcriteria_total'] }} Konsisten
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-list-ul fa-2x mb-2"></i>
                <h4>{{ $consistencyData['subsubcriteria_total'] }}</h4>
                <small>Sub-Sub Kriteria</small>
                <div class="mt-2">
                    <span class="badge bg-light text-dark">
                        {{ $consistencyData['subsubcriteria_consistent'] }}/{{ $consistencyData['subsubcriteria_total'] }} Konsisten
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calculator fa-2x mb-2"></i>
                <h4>AHP</h4>
                <small>Siap Perhitungan</small>
                <div class="mt-2">
                    @php
                        $allConsistent = $consistencyData['criteria_consistent'] && 
                                       ($consistencyData['subcriteria_total'] == 0 || $consistencyData['subcriteria_consistent'] == $consistencyData['subcriteria_total']) &&
                                       ($consistencyData['subsubcriteria_total'] == 0 || $consistencyData['subsubcriteria_consistent'] == $consistencyData['subsubcriteria_total']);
                    @endphp
                    @if($allConsistent)
                        <span class="badge bg-success">Siap</span>
                    @else
                        <span class="badge bg-warning">Belum Siap</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Level 1: Criteria --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card hierarchy-card criteria-card position-relative">
            <div class="level-indicator bg-white text-dark">1</div>
            @php
                $criteriaWeight = \App\Models\CriteriaWeight::where('level', 'criteria')
                    ->where('parent_id', null)
                    ->first();
            @endphp
            @if($criteriaWeight)
                <span class="consistency-badge badge {{ $criteriaWeight->is_consistent ? 'bg-success' : 'bg-danger' }}">
                    {{ $criteriaWeight->is_consistent ? 'Konsisten' : 'Tidak Konsisten' }}
                </span>
            @endif
            
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">
                            <i class="fas fa-layer-group me-2"></i>
                            Level 1: Perbandingan Kriteria Utama
                        </h5>
                        <p class="mb-2">
                            {{ $criterias->count() }} kriteria utama tersedia untuk dibandingkan
                        </p>
                        @if($criteriaWeight && $criteriaWeight->cr !== null)
                            <small class="d-block">
                                Consistency Ratio: {{ number_format($criteriaWeight->cr, 4) }}
                                ({{ $criteriaWeight->is_consistent ? 'Konsisten' : 'Tidak Konsisten' }})
                            </small>
                        @endif
                    </div>
                    <div class="col-md-4 text-end">
                        @if($criterias->count() >= 2)
                            <a href="{{ route('admin.pairwise.criteria') }}" class="btn btn-light btn-lg">
                                <i class="fas fa-calculator me-2"></i>
                                Mulai Perbandingan
                            </a>
                        @else
                            <span class="text-light">Minimal 2 kriteria diperlukan</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Level 2: Sub Criteria --}}
@if($criterias->whereNotNull('subCriterias')->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="mb-3">
                <i class="fas fa-list me-2"></i>
                Level 2: Perbandingan Sub Kriteria
            </h6>
        </div>
        @foreach($criterias as $criteria)
            @if($criteria->subCriterias->count() >= 2)
                <div class="col-md-6 mb-3">
                    <div class="card hierarchy-card subcriteria-card position-relative">
                        <div class="level-indicator bg-white text-dark">2</div>
                        @php
                            $subCriteriaWeight = \App\Models\CriteriaWeight::where('level', 'subcriteria')
                                ->where('parent_id', $criteria->id)
                                ->first();
                        @endphp
                        @if($subCriteriaWeight)
                            <span class="consistency-badge badge {{ $subCriteriaWeight->is_consistent ? 'bg-success' : 'bg-danger' }}">
                                {{ $subCriteriaWeight->is_consistent ? 'Konsisten' : 'Tidak Konsisten' }}
                            </span>
                        @endif
                        
                        <div class="card-body">
                            <h6 class="mb-1">{{ $criteria->code }} - {{ $criteria->name }}</h6>
                            <p class="mb-2">{{ $criteria->subCriterias->count() }} sub kriteria</p>
                            @if($subCriteriaWeight && $subCriteriaWeight->cr !== null)
                                <small class="d-block mb-3">
                                    CR: {{ number_format($subCriteriaWeight->cr, 4) }}
                                </small>
                            @endif
                            <a href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}" 
                               class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right me-1"></i>Bandingkan
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif

{{-- Level 3: Sub-Sub Criteria --}}
@php
    $hasSubSubCriteria = false;
    foreach($criterias as $criteria) {
        foreach($criteria->subCriterias as $subCriteria) {
            if($subCriteria->subSubCriterias->count() >= 2) {
                $hasSubSubCriteria = true;
                break 2;
            }
        }
    }
@endphp

@if($hasSubSubCriteria)
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="mb-3">
                <i class="fas fa-list-ul me-2"></i>
                Level 3: Perbandingan Sub-Sub Kriteria
            </h6>
        </div>
        @foreach($criterias as $criteria)
            @foreach($criteria->subCriterias as $subCriteria)
                @if($subCriteria->subSubCriterias->count() >= 2)
                    <div class="col-md-4 mb-3">
                        <div class="card hierarchy-card subsubcriteria-card position-relative">
                            <div class="level-indicator bg-white text-dark">3</div>
                            @php
                                $subSubCriteriaWeight = \App\Models\CriteriaWeight::where('level', 'subsubcriteria')
                                    ->where('parent_id', $subCriteria->id)
                                    ->first();
                            @endphp
                            @if($subSubCriteriaWeight)
                                <span class="consistency-badge badge {{ $subSubCriteriaWeight->is_consistent ? 'bg-success' : 'bg-danger' }}">
                                    {{ $subSubCriteriaWeight->is_consistent ? 'Konsisten' : 'Tidak Konsisten' }}
                                </span>
                            @endif
                            
                            <div class="card-body">
                                <h6 class="mb-1">{{ $subCriteria->code }}</h6>
                                <small class="d-block text-light mb-1">{{ $criteria->code }} → {{ $subCriteria->code }}</small>
                                <p class="mb-2">{{ $subCriteria->subSubCriterias->count() }} sub-sub kriteria</p>
                                @if($subSubCriteriaWeight && $subSubCriteriaWeight->cr !== null)
                                    <small class="d-block mb-3">
                                        CR: {{ number_format($subSubCriteriaWeight->cr, 4) }}
                                    </small>
                                @endif
                                <a href="{{ route('admin.pairwise.subsubcriteria', $subCriteria->id) }}" 
                                   class="btn btn-light btn-sm">
                                    <i class="fas fa-arrow-right me-1"></i>Bandingkan
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
@endif

{{-- Quick Actions --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('admin.pairwise.consistency.overview') }}" class="btn btn-outline-info btn-sm w-100">
                            <i class="fas fa-chart-line me-1"></i>
                            Overview Konsistensi
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-warning btn-sm w-100" onclick="resetAllComparisons()">
                            <i class="fas fa-undo me-1"></i>
                            Reset Semua
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('admin.pairwise.export.matrix', ['type' => 'all']) }}" class="btn btn-outline-success btn-sm w-100">
                            <i class="fas fa-download me-1"></i>
                            Export Matrix
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="calculateAllWeights()">
                            <i class="fas fa-calculator me-1"></i>
                            Hitung Semua Bobot
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Progress Summary --}}
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>
                    Progress Perbandingan Berpasangan
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Kriteria Utama</span>
                        <span>{{ $consistencyData['criteria_consistent'] ? '1/1' : '0/1' }}</span>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar {{ $consistencyData['criteria_consistent'] ? 'bg-success' : 'bg-warning' }}" 
                             style="width: {{ $consistencyData['criteria_consistent'] ? '100' : '0' }}%"></div>
                    </div>
                </div>
                
                @if($consistencyData['subcriteria_total'] > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Sub Kriteria</span>
                            <span>{{ $consistencyData['subcriteria_consistent'] }}/{{ $consistencyData['subcriteria_total'] }}</span>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-info" 
                                 style="width: {{ $consistencyData['subcriteria_total'] > 0 ? ($consistencyData['subcriteria_consistent'] / $consistencyData['subcriteria_total']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endif
                
                @if($consistencyData['subsubcriteria_total'] > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Sub-Sub Kriteria</span>
                            <span>{{ $consistencyData['subsubcriteria_consistent'] }}/{{ $consistencyData['subsubcriteria_total'] }}</span>
                        </div>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-primary" 
                                 style="width: {{ $consistencyData['subsubcriteria_total'] > 0 ? ($consistencyData['subsubcriteria_consistent'] / $consistencyData['subsubcriteria_total']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Tips & Panduan
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <p><strong>Urutan Perbandingan:</strong></p>
                    <ol class="mb-3">
                        <li>Mulai dari kriteria utama</li>
                        <li>Lanjutkan ke sub kriteria</li>
                        <li>Terakhir sub-sub kriteria</li>
                    </ol>
                    
                    <p><strong>Konsistensi:</strong></p>
                    <ul class="mb-3">
                        <li>CR ≤ 0.10 = Konsisten</li>
                        <li>CR > 0.10 = Perlu revisi</li>
                    </ul>
                    
                    <p><strong>Skala Saaty:</strong></p>
                    <ul class="mb-0">
                        <li>1 = Sama penting</li>
                        <li>3 = Sedikit lebih penting</li>
                        <li>5 = Lebih penting</li>
                        <li>7 = Sangat penting</li>
                        <li>9 = Mutlak lebih penting</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals and Scripts --}}
@push('scripts')
<script>
function resetAllComparisons() {
    if (confirm('Apakah Anda yakin ingin mereset semua perbandingan berpasangan? Tindakan ini tidak dapat dibatalkan.')) {
        // Implementation for resetting all comparisons
        $.ajax({
            url: '{{ route("admin.pairwise.reset") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                type: 'all'
            },
            success: function(response) {
                alert('Semua perbandingan berhasil direset');
                location.reload();
            },
            error: function() {
                alert('Terjadi kesalahan saat mereset perbandingan');
            }
        });
    }
}

function calculateAllWeights() {
    if (confirm('Hitung ulang semua bobot AHP?')) {
        // Show loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Menghitung...';
        btn.disabled = true;
        
        // Implementation for calculating all weights
        $.ajax({
            url: '{{ route("admin.ahp.calculate.criteria") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert('Semua bobot berhasil dihitung');
                location.reload();
            },
            error: function() {
                alert('Terjadi kesalahan saat menghitung bobot');
            },
            complete: function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }
}

// Auto-refresh consistency indicators every 30 seconds
setInterval(function() {
    $.ajax({
        url: '{{ route("api.admin.weights.summary") }}',
        method: 'GET',
        success: function(data) {
            // Update consistency indicators dynamically
            // Implementation depends on the response structure
        }
    });
}, 30000);
</script>
@endpush
@endsection