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
</style>
@endpush

@section('content')
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
                                                <select name="comparisons[{{ $loop->parent->index }}][item_a_id]" style="display:none">
                                                    <option value="{{ $criteriaA->id }}" selected>{{ $criteriaA->id }}</option>
                                                </select>
                                                <select name="comparisons[{{ $loop->parent->index }}][item_b_id]" style="display:none">
                                                    <option value="{{ $criteriaB->id }}" selected>{{ $criteriaB->id }}</option>
                                                </select>
                                                <select name="comparisons[{{ $loop->parent->index }}][value]" class="form-select form-select-sm comparison-input">
                                                    <option value="0.111" {{ $value == 0.111 ? 'selected' : '' }}>1/9</option>
                                                    <option value="0.125" {{ $value == 0.125 ? 'selected' : '' }}>1/8</option>
                                                    <option value="0.143" {{ $value == 0.143 ? 'selected' : '' }}>1/7</option>
                                                    <option value="0.167" {{ $value == 0.167 ? 'selected' : '' }}>1/6</option>
                                                    <option value="0.2" {{ $value == 0.2 ? 'selected' : '' }}>1/5</option>
                                                    <option value="0.25" {{ $value == 0.25 ? 'selected' : '' }}>1/4</option>
                                                    <option value="0.333" {{ $value == 0.333 ? 'selected' : '' }}>1/3</option>
                                                    <option value="0.5" {{ $value == 0.5 ? 'selected' : '' }}>1/2</option>
                                                    <option value="1" {{ $value == 1 ? 'selected' : '' }}>1</option>
                                                    <option value="2" {{ $value == 2 ? 'selected' : '' }}>2</option>
                                                    <option value="3" {{ $value == 3 ? 'selected' : '' }}>3</option>
                                                    <option value="4" {{ $value == 4 ? 'selected' : '' }}>4</option>
                                                    <option value="5" {{ $value == 5 ? 'selected' : '' }}>5</option>
                                                    <option value="6" {{ $value == 6 ? 'selected' : '' }}>6</option>
                                                    <option value="7" {{ $value == 7 ? 'selected' : '' }}>7</option>
                                                    <option value="8" {{ $value == 8 ? 'selected' : '' }}>8</option>
                                                    <option value="9" {{ $value == 9 ? 'selected' : '' }}>9</option>
                                                </select>
                                            @else
                                                @php
                                                    $key = $criteriaA->id . '_' . $criteriaB->id;
                                                    $value = isset($comparisons[$key]) ? $comparisons[$key]->value : 1;
                                                    $displayValue = $value < 1 ? '1/' . number_format(1/$value, 0) : number_format($value, 3);
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
                        <div class="col-md-3 mb-2">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6>{{ $criteria->code }}</h6>
                                    <h4 class="text-primary">{{ number_format($criteria->weight, 4) }}</h4>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
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

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">Skala Perbandingan Saaty</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Intensitas Kepentingan</th>
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
                        <td>Nilai-nilai antara</td>
                        <td>Nilai-nilai antara dua nilai pertimbangan yang berdekatan</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection