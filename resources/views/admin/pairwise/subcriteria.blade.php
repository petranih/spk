{{-- resources/views/admin/pairwise/subcriteria.blade.php --}}
@extends('layouts.app')

@section('title', 'Perbandingan Berpasangan Sub Kriteria')
@section('page-title', 'Perbandingan Berpasangan Sub Kriteria')

@push('styles')
<style>
    .comparison-table th, .comparison-table td {
        text-align: center;
        vertical-align: middle;
    }
    .comparison-input {
        width: 80px;
        text-align: center;
    }
    .matrix-cell {
        background-color: #f8f9fa;
    }
    .diagonal-cell {
        background-color: #e9ecef;
        font-weight: bold;
    }
    .consistency-indicator {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .consistent {
        background-color: #d1edff;
        border: 2px solid #0dcaf0;
        color: #055160;
    }
    .inconsistent {
        background-color: #f8d7da;
        border: 2px solid #dc3545;
        color: #721c24;
    }
    .weight-card {
        transition: transform 0.2s;
    }
    .weight-card:hover {
        transform: translateY(-2px);
    }
    .breadcrumb-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        {{-- Breadcrumb Navigation --}}
        <div class="card breadcrumb-card">
            <div class="card-body">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb text-white mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.pairwise.criteria') }}" class="text-white">
                                <i class="fas fa-layer-group me-1"></i>Kriteria
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">
                            <i class="fas fa-list me-1"></i>
                            Sub Kriteria: {{ $criterion->code }} - {{ $criterion->name }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        {{-- Consistency Indicator --}}
        @php
            $subCriteriaWeight = \App\Models\CriteriaWeight::where('level', 'subcriteria')
                ->where('parent_id', $criterion->id)
                ->first();
        @endphp
        
        @if($subCriteriaWeight && $subCriteriaWeight->cr !== null)
            <div class="consistency-indicator {{ $subCriteriaWeight->is_consistent ? 'consistent' : 'inconsistent' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">
                            <i class="fas {{ $subCriteriaWeight->is_consistent ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                            Status Konsistensi: {{ $subCriteriaWeight->is_consistent ? 'KONSISTEN' : 'TIDAK KONSISTEN' }}
                        </h6>
                        <small>
                            CR = {{ number_format($subCriteriaWeight->cr, 4) }} 
                            ({{ $subCriteriaWeight->is_consistent ? '≤ 0.10' : '> 0.10' }})
                        </small>
                    </div>
                    <div class="text-end">
                        <small class="d-block">λmax = {{ number_format($subCriteriaWeight->lambda_max, 4) }}</small>
                        <small class="d-block">CI = {{ number_format($subCriteriaWeight->ci, 4) }}</small>
                    </div>
                </div>
                @if(!$subCriteriaWeight->is_consistent)
                    <div class="mt-2">
                        <small><i class="fas fa-info-circle me-1"></i> 
                        Silakan periksa kembali nilai perbandingan Anda. Konsistensi yang baik memiliki CR ≤ 0.10</small>
                    </div>
                @endif
            </div>
        @endif
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Matriks Perbandingan Berpasangan Sub Kriteria</h5>
                        <small class="text-muted">Kriteria: {{ $criterion->code }} - {{ $criterion->name }}</small>
                    </div>
                    <span class="badge bg-info">{{ $subCriterias->count() }} Sub Kriteria</span>
                </div>
            </div>
            <div class="card-body">
                @if($subCriterias->count() >= 2)
                    <form action="{{ route('admin.pairwise.subcriteria.store', $criterion->id) }}" method="POST">
                        @csrf
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Petunjuk:</strong> Berikan nilai perbandingan untuk setiap pasangan sub kriteria dalam kriteria <strong>{{ $criterion->name }}</strong>.
                            Skala: 1 = Sama penting, 3 = Sedikit lebih penting, 5 = Lebih penting, 7 = Sangat penting, 9 = Mutlak lebih penting.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered comparison-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Sub Kriteria</th>
                                        @foreach($subCriterias as $subCriteria)
                                            <th>{{ $subCriteria->code }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $comparisonIndex = 0; @endphp
                                    @foreach($subCriterias as $i => $subCriteriaA)
                                        <tr>
                                            <th class="table-dark">
                                                {{ $subCriteriaA->code }}<br>
                                                <small>{{ Str::limit($subCriteriaA->name, 30) }}</small>
                                            </th>
                                            @foreach($subCriterias as $j => $subCriteriaB)
                                                <td class="{{ $i == $j ? 'diagonal-cell' : 'matrix-cell' }}">
                                                    @if($i == $j)
                                                        1
                                                    @elseif($i < $j)
                                                        @php
                                                            $key = $subCriteriaA->id . '_' . $subCriteriaB->id;
                                                            $value = isset($comparisons[$key]) ? $comparisons[$key]->value : 1;
                                                        @endphp
                                                        <input type="hidden" name="comparisons[{{ $comparisonIndex }}][item_a_id]" value="{{ $subCriteriaA->id }}">
                                                        <input type="hidden" name="comparisons[{{ $comparisonIndex }}][item_b_id]" value="{{ $subCriteriaB->id }}">
                                                        <select name="comparisons[{{ $comparisonIndex }}][value]" class="form-select form-select-sm comparison-input">
                                                            <option value="0.111" {{ abs($value - 0.111) < 0.01 ? 'selected' : '' }}>1/9</option>
                                                            <option value="0.125" {{ abs($value - 0.125) < 0.01 ? 'selected' : '' }}>1/8</option>
                                                            <option value="0.143" {{ abs($value - 0.143) < 0.01 ? 'selected' : '' }}>1/7</option>
                                                            <option value="0.167" {{ abs($value - 0.167) < 0.01 ? 'selected' : '' }}>1/6</option>
                                                            <option value="0.2" {{ abs($value - 0.2) < 0.01 ? 'selected' : '' }}>1/5</option>
                                                            <option value="0.25" {{ abs($value - 0.25) < 0.01 ? 'selected' : '' }}>1/4</option>
                                                            <option value="0.333" {{ abs($value - 0.333) < 0.01 ? 'selected' : '' }}>1/3</option>
                                                            <option value="0.5" {{ abs($value - 0.5) < 0.01 ? 'selected' : '' }}>1/2</option>
                                                            <option value="1" {{ abs($value - 1) < 0.01 ? 'selected' : '' }}>1</option>
                                                            <option value="2" {{ abs($value - 2) < 0.01 ? 'selected' : '' }}>2</option>
                                                            <option value="3" {{ abs($value - 3) < 0.01 ? 'selected' : '' }}>3</option>
                                                            <option value="4" {{ abs($value - 4) < 0.01 ? 'selected' : '' }}>4</option>
                                                            <option value="5" {{ abs($value - 5) < 0.01 ? 'selected' : '' }}>5</option>
                                                            <option value="6" {{ abs($value - 6) < 0.01 ? 'selected' : '' }}>6</option>
                                                            <option value="7" {{ abs($value - 7) < 0.01 ? 'selected' : '' }}>7</option>
                                                            <option value="8" {{ abs($value - 8) < 0.01 ? 'selected' : '' }}>8</option>
                                                            <option value="9" {{ abs($value - 9) < 0.01 ? 'selected' : '' }}>9</option>
                                                        </select>
                                                        @php $comparisonIndex++; @endphp
                                                    @else
                                                        @php
                                                            $key = $subCriteriaA->id . '_' . $subCriteriaB->id;
                                                            $value = isset($comparisons[$key]) ? $comparisons[$key]->value : 1;
                                                            if($value < 1) {
                                                                $displayValue = '1/' . number_format(1/$value, 0);
                                                            } else {
                                                                $displayValue = number_format($value, 3);
                                                            }
                                                        @endphp
                                                        <span class="text-muted">{{ $displayValue }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-calculator me-2"></i>
                                Hitung Bobot AHP
                            </button>
                        </div>
                    </form>
                    
                    {{-- Display current weights if available --}}
                    @if($subCriterias->where('weight', '>', 0)->count() > 0)
                        <hr>
                        <h6>Bobot Sub Kriteria Saat Ini:</h6>
                        <div class="row">
                            @foreach($subCriterias as $subCriteria)
                                <div class="col-md-3 mb-3">
                                    <div class="card weight-card">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">{{ $subCriteria->code }}</h6>
                                            <h4 class="text-primary mb-1">{{ number_format($subCriteria->weight, 4) }}</h4>
                                            <small class="text-muted">{{ number_format($subCriteria->weight * 100, 2) }}%</small>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: {{ $subCriteria->weight * 100 }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Sub-Sub-Criteria navigation if available --}}
                        @php
                            $subCriteriasWithSubSubCriteria = $subCriterias->filter(function($subCriteria) {
                                return $subCriteria->subSubCriterias && $subCriteria->subSubCriterias->count() > 0;
                            });
                        @endphp
                        
                        @if($subCriteriasWithSubSubCriteria->count() > 0)
                            <hr>
                            <h6>Lanjut ke Sub-Sub Kriteria:</h6>
                            <div class="row">
                                @foreach($subCriteriasWithSubSubCriteria as $subCriteria)
                                    <div class="col-md-4 mb-2">
                                        <a href="{{ route('admin.pairwise.subsubcriteria', $subCriteria->id) }}" 
                                           class="btn btn-outline-success btn-sm w-100">
                                            <i class="fas fa-arrow-right me-1"></i>
                                            {{ $subCriteria->code }} ({{ $subCriteria->subSubCriterias->count() }} sub-sub)
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Sub Kriteria Tidak Cukup</h5>
                        <p class="text-muted">Minimal 2 sub kriteria diperlukan untuk melakukan perbandingan berpasangan.</p>
                        <a href="{{ route('admin.criteria.subcriteria.create', $criterion->id) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Sub Kriteria
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Skala Perbandingan Saaty</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Intensitas</th>
                                <th>Definisi</th>
                                <th>Penjelasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>1</strong></td>
                                <td>Sama penting</td>
                                <td>Kedua elemen mempunyai pengaruh yang sama besar</td>
                            </tr>
                            <tr>
                                <td><strong>3</strong></td>
                                <td>Sedikit lebih penting</td>
                                <td>Satu elemen sedikit lebih penting daripada elemen yang lainnya</td>
                            </tr>
                            <tr>
                                <td><strong>5</strong></td>
                                <td>Lebih penting</td>
                                <td>Satu elemen lebih penting daripada yang lainnya</td>
                            </tr>
                            <tr>
                                <td><strong>7</strong></td>
                                <td>Sangat penting</td>
                                <td>Satu elemen jelas lebih mutlak penting daripada elemen lainnya</td>
                            </tr>
                            <tr>
                                <td><strong>9</strong></td>
                                <td>Mutlak lebih penting</td>
                                <td>Satu elemen mutlak penting daripada elemen lainnya</td>
                            </tr>
                            <tr>
                                <td><strong>2,4,6,8</strong></td>
                                <td>Nilai antara</td>
                                <td>Nilai-nilai antara dua nilai pertimbangan yang berdekatan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Indeks Konsistensi</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>CR (Consistency Ratio)</strong>
                    <p class="small text-muted mb-1">Rasio konsistensi harus ≤ 0.10</p>
                    @if($subCriteriaWeight && $subCriteriaWeight->cr !== null)
                        <div class="progress mb-2">
                            <div class="progress-bar {{ $subCriteriaWeight->is_consistent ? 'bg-success' : 'bg-danger' }}" 
                                 style="width: {{ min($subCriteriaWeight->cr * 100, 100) }}%"></div>
                        </div>
                        <small>{{ number_format($subCriteriaWeight->cr, 4) }}</small>
                    @else
                        <small class="text-muted">Belum dihitung</small>
                    @endif
                </div>
                
                <div class="text-muted small">
                    <p><strong>Keterangan:</strong></p>
                    <ul class="mb-0">
                        <li>CR ≤ 0.10 = Konsisten</li>
                        <li>CR > 0.10 = Tidak konsisten</li>
                        <li>Revisi diperlukan jika tidak konsisten</li>
                    </ul>
                </div>
            </div>
        </div>
        
        {{-- Navigation Back --}}
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Navigasi</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.pairwise.criteria') }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Kriteria
                </a>
                
                @if($criterion->subCriterias->count() > 0)
                    <div class="dropdown-divider"></div>
                    <small class="text-muted d-block mb-2">Sub Kriteria Lainnya:</small>
                    @foreach($criterion->subCriterias as $otherSubCriteria)
                        @if($otherSubCriteria->subSubCriterias->count() >= 2)
                            <a href="{{ route('admin.pairwise.subsubcriteria', $otherSubCriteria->id) }}" 
                               class="btn btn-outline-info btn-sm w-100 mb-1">
                                <i class="fas fa-list me-1"></i>{{ $otherSubCriteria->code }}
                            </a>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection