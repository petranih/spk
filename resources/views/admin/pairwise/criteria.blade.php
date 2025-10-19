{{-- resources/views/admin/pairwise/criteria.blade.php --}}
@extends('layouts.app')

@section('title', 'Perbandingan Berpasangan Kriteria')
@section('page-title', 'Perbandingan Berpasangan Kriteria')

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
    .comparison-table {
        background-color: #fff;
    }
    
    .comparison-table thead {
        background-color: #2c3e50;
    }
    
    .comparison-table thead th {
        color: #ffffff;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
        padding: 12px 8px;
        border: 1px solid #1a252f;
    }
    
    .comparison-table tbody td,
    .comparison-table tbody th {
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px;
        border: 1px solid #dee2e6;
    }
    
    /* Row Header - Lebih gelap */
    .comparison-table tbody th.table-dark {
        background-color: #34495e;
        color: #ffffff;
        font-weight: 600;
    }
    
    .comparison-table tbody th.table-dark small {
        color: #ecf0f1;
        display: block;
        margin-top: 4px;
        font-weight: 400;
    }
    
    /* Diagonal Cell - Abu-abu gelap */
    .diagonal-cell {
        background-color: #ecf0f1;
        font-weight: bold;
        color: #2c3e50;
    }
    
    /* Matrix Cell - Putih dengan border */
    .matrix-cell {
        background-color: #ffffff;
        color: #2c3e50;
    }
    
    .matrix-cell .text-muted {
        color: #555 !important;
        font-weight: 500;
    }
    
    /* Input Select */
    .comparison-input {
        width: 85px;
        text-align: center;
        border: 2px solid #3498db;
        border-radius: 4px;
        padding: 6px 4px;
        font-weight: 500;
        background-color: #ecf0f1;
        color: #2c3e50;
    }
    
    .comparison-input:focus {
        background-color: #ffffff;
        border-color: #2980b9;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    .comparison-input option {
        background-color: #ffffff;
        color: #2c3e50;
        padding: 8px;
    }
    
    /* Hover effect untuk tbody */
    .comparison-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Badge */
    .badge.bg-info {
        background-color: #3498db !important;
        color: white;
    }
    
    /* Alert */
    .alert.alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
    /* Perbaikan Warna Tabel Comparison - LENGKAP */
    .comparison-table {
        background-color: #fff;
        border-collapse: collapse;
    }
    
    /* HEADER TABEL (bagian paling atas) */
    .comparison-table thead {
        background-color: #2c3e50;
    }
    
    .comparison-table thead tr {
        background-color: #2c3e50;
    }
    
    .comparison-table thead th {
        background-color: #2c3e50 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        text-align: center;
        vertical-align: middle;
        padding: 12px 8px !important;
        border: 2px solid #1a252f !important;
    }
    
    /* ROW HEADER (bagian kiri - row label) */
    .comparison-table tbody th {
        background-color: #34495e !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        text-align: center;
        vertical-align: middle;
        padding: 12px 8px !important;
        border: 2px solid #2c3e50 !important;
    }
    
    .comparison-table tbody th small {
        color: #ecf0f1 !important;
        display: block;
        margin-top: 6px;
        font-weight: 500;
        font-size: 12px;
    }
    
    .comparison-table tbody th.table-dark {
        background-color: #34495e !important;
        color: #ffffff !important;
    }
    
    /* DATA CELL - Diagonal (angka 1) */
    .diagonal-cell {
        background-color: #bdc3c7 !important;
        font-weight: bold !important;
        color: #2c3e50 !important;
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px !important;
        border: 1px solid #95a5a6 !important;
    }
    
    /* DATA CELL - Biasa (putih) */
    .matrix-cell {
        background-color: #ffffff !important;
        color: #2c3e50 !important;
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px !important;
        border: 1px solid #bdc3c7 !important;
    }
    
    .matrix-cell .text-muted {
        color: #34495e !important;
        font-weight: 600;
        font-size: 14px;
    }
    
    /* SELECT INPUT */
    .comparison-input {
        width: 85px !important;
        text-align: center;
        border: 2px solid #3498db !important;
        border-radius: 5px;
        padding: 8px 6px !important;
        font-weight: 600;
        background-color: #ecf0f1 !important;
        color: #2c3e50 !important;
        font-size: 13px;
    }
    
    .comparison-input:focus {
        background-color: #ffffff !important;
        border-color: #2980b9 !important;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25) !important;
        outline: none;
    }
    
    .comparison-input option {
        background-color: #ffffff;
        color: #2c3e50;
        padding: 8px;
    }
    
    /* HOVER EFFECT */
    .comparison-table tbody tr:hover {
        background-color: #f0f3f4;
    }
    
    .comparison-table tbody tr:hover th {
        background-color: #2c3e50 !important;
    }
    
    .comparison-table tbody tr:hover td {
        background-color: #f0f3f4;
    }
    
    .comparison-table tbody tr:hover .diagonal-cell {
        background-color: #95a5a6 !important;
    }
    
    /* TABLE RESPONSIVE */
    .table-responsive {
        border: 2px solid #bdc3c7;
        border-radius: 5px;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Consistency Indicator --}}
        @php
            $criteriaWeight = \App\Models\CriteriaWeight::where('level', 'criteria')
                ->where('parent_id', null)
                ->first();
        @endphp
        
        @if($criteriaWeight && $criteriaWeight->cr !== null)
            <div class="consistency-indicator {{ $criteriaWeight->is_consistent ? 'consistent' : 'inconsistent' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">
                            <i class="fas {{ $criteriaWeight->is_consistent ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                            Status Konsistensi: {{ $criteriaWeight->is_consistent ? 'KONSISTEN' : 'TIDAK KONSISTEN' }}
                        </h6>
                        <small>
                            CR = {{ number_format($criteriaWeight->cr, 4) }} 
                            ({{ $criteriaWeight->is_consistent ? '≤ 0.10' : '> 0.10' }})
                        </small>
                    </div>
                    <div class="text-end">
                        <small class="d-block">λmax = {{ number_format($criteriaWeight->lambda_max, 4) }}</small>
                        <small class="d-block">CI = {{ number_format($criteriaWeight->ci, 4) }}</small>
                    </div>
                </div>
                @if(!$criteriaWeight->is_consistent)
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
                    <h5 class="mb-0">Matriks Perbandingan Berpasangan Kriteria</h5>
                    <span class="badge bg-info">{{ $criterias->count() }} Kriteria</span>
                </div>
            </div>
            <div class="card-body">
                @if($criterias->count() >= 2)
                    <form action="{{ route('admin.pairwise.criteria.store') }}" method="POST">
                        @csrf
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Petunjuk:</strong> Berikan nilai perbandingan untuk setiap pasangan kriteria.
                            Skala: 1 = Sama penting, 3 = Sedikit lebih penting, 5 = Lebih penting, 7 = Sangat penting, 9 = Mutlak lebih penting.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered comparison-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Kriteria</th>
                                        @foreach($criterias as $criteria)
                                            <th>{{ $criteria->code }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $comparisonIndex = 0; @endphp
                                    @foreach($criterias as $i => $criteriaA)
                                        <tr>
                                            <th class="table-dark">{{ $criteriaA->code }}<br><small>{{ $criteriaA->name }}</small></th>
                                            @foreach($criterias as $j => $criteriaB)
                                                <td class="{{ $i == $j ? 'diagonal-cell' : 'matrix-cell' }}">
                                                    @if($i == $j)
                                                        1
                                                    @elseif($i < $j)
                                                        @php
                                                            $key = $criteriaA->id . '_' . $criteriaB->id;
                                                            $value = isset($comparisons[$key]) ? $comparisons[$key]->value : 1;
                                                        @endphp
                                                        <input type="hidden" name="comparisons[{{ $comparisonIndex }}][item_a_id]" value="{{ $criteriaA->id }}">
                                                        <input type="hidden" name="comparisons[{{ $comparisonIndex }}][item_b_id]" value="{{ $criteriaB->id }}">
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
                                                            $key = $criteriaA->id . '_' . $criteriaB->id;
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
                    @if($criterias->where('weight', '>', 0)->count() > 0)
                        <hr>
                        <h6>Bobot Kriteria Saat Ini:</h6>
                        <div class="row">
                            @foreach($criterias as $criteria)
                                <div class="col-md-3 mb-3">
                                    <div class="card weight-card">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">{{ $criteria->code }}</h6>
                                            <h4 class="text-primary mb-1">{{ number_format($criteria->weight, 4) }}</h4>
                                            <small class="text-muted">{{ number_format($criteria->weight * 100, 2) }}%</small>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: {{ $criteria->weight * 100 }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Sub-criteria navigation if available - FIXED --}}
                        @php
                            $criteriasWithSubCriteria = $criterias->filter(function($criteria) {
                                return $criteria->subCriterias && $criteria->subCriterias->count() > 0;
                            });
                        @endphp
                        
                        @if($criteriasWithSubCriteria->count() > 0)
                            <hr>
                            <h6>Lanjut ke Sub Kriteria:</h6>
                            <div class="row">
                                @foreach($criteriasWithSubCriteria as $criteria)
                                    <div class="col-md-4 mb-2">
                                        <a href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}" 
                                           class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-arrow-right me-1"></i>
                                            {{ $criteria->code }} ({{ $criteria->subCriterias->count() }} sub)
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Kriteria Tidak Cukup</h5>
                        <p class="text-muted">Minimal 2 kriteria diperlukan untuk melakukan perbandingan berpasangan.</p>
                        <a href="{{ route('admin.criteria.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Kriteria
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
                    @if($criteriaWeight && $criteriaWeight->cr !== null)
                        <div class="progress mb-2">
                            <div class="progress-bar {{ $criteriaWeight->is_consistent ? 'bg-success' : 'bg-danger' }}" 
                                 style="width: {{ min($criteriaWeight->cr * 100, 100) }}%"></div>
                        </div>
                        <small>{{ number_format($criteriaWeight->cr, 4) }}</small>
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
    </div>
</div>
@endsection