@extends('layouts.app')

@section('title', 'Detail Perhitungan - ' . $application->full_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">Detail Perhitungan AHP</h3>
                            <p class="text-muted mb-0">{{ $application->full_name }} ({{ $application->nisn }})</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.scoring.applications', $application->period_id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            @if(config('app.debug'))
                                <a href="{{ route('admin.debug.application-data', $application->id) }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-bug"></i> Debug Data
                                </a>
                                <a href="{{ route('admin.debug.application-mapping', $application->id) }}" target="_blank" class="btn btn-warning btn-sm">
                                    <i class="fas fa-search"></i> Debug Mapping
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Informasi Siswa -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Informasi Siswa</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Nama:</strong></td>
                                            <td>{{ $application->full_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>NISN:</strong></td>
                                            <td>{{ $application->nisn }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sekolah:</strong></td>
                                            <td>{{ $application->school }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kelas:</strong></td>
                                            <td>{{ $application->class ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $application->student->email ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-{{ $ranking ? 'success' : 'warning' }}">
                                <div class="card-body text-white">
                                    <h6 class="card-title">Hasil Perhitungan</h6>
                                    <table class="table table-sm table-borderless text-white">
                                        <tr>
                                            <td width="50%"><strong>Skor Total:</strong></td>
                                            <td>{{ $ranking ? number_format($ranking->total_score, 6) : ($application->final_score ? number_format($application->final_score, 6) : 'Belum dihitung') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ranking:</strong></td>
                                            <td>{{ $ranking && $ranking->rank ? "Peringkat {$ranking->rank}" : 'Belum di-ranking' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Perhitungan:</strong></td>
                                            <td>{{ $ranking && $ranking->calculated_at ? $ranking->calculated_at->format('d/m/Y H:i') : '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Data -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Status Data (Total: {{ $applicationValues->count() }} items)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $dbValuesCount = $applicationValues->where('source', 'application_values_db')->count();
                                        $fromSubSubCount = $applicationValues->filter(function($value) {
                                            return str_contains($value->source ?? '', 'from_subsubcriteria');
                                        })->count();
                                        
                                        $totalCriteria = 0;
                                        foreach($criterias as $criteria) {
                                            $totalCriteria += $criteria->subCriterias->count();
                                        }
                                        $missingCount = $totalCriteria - $applicationValues->count();
                                        
                                        $correctWeightCount = $applicationValues->filter(function($value) {
                                            return isset($value->actual_weight) || isset($value->subsubcriteria_data);
                                        })->count();
                                        
                                        $completionPercentage = $totalCriteria > 0 ? round(($applicationValues->count() / $totalCriteria) * 100, 1) : 0;
                                    @endphp
                                    <div class="row text-center">
                                        <div class="col">
                                            <h4 class="mb-1"><span class="badge bg-success">{{ $dbValuesCount }}</span></h4>
                                            <small class="text-muted">Database Values</small>
                                        </div>
                                        <div class="col">
                                            <h4 class="mb-1"><span class="badge bg-primary">{{ $fromSubSubCount }}</span></h4>
                                            <small class="text-muted">From SubSubCriteria</small>
                                        </div>
                                        <div class="col">
                                            <h4 class="mb-1"><span class="badge bg-danger">{{ $missingCount > 0 ? $missingCount : 0 }}</span></h4>
                                            <small class="text-muted">Missing Data</small>
                                        </div>
                                        <div class="col">
                                            <h4 class="mb-1"><span class="badge bg-info">{{ $correctWeightCount }}</span></h4>
                                            <small class="text-muted">Correct Weights</small>
                                        </div>
                                        <div class="col">
                                            <h4 class="mb-1"><span class="badge bg-{{ $completionPercentage >= 80 ? 'success' : ($completionPercentage >= 60 ? 'warning' : 'danger') }}">{{ $completionPercentage }}%</span></h4>
                                            <small class="text-muted">Completion</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($ranking && $ranking->criteria_scores)
                        @php
                            $criteriaScores = [];
                            if ($ranking->criteria_scores) {
                                if (is_string($ranking->criteria_scores)) {
                                    try {
                                        $criteriaScores = json_decode($ranking->criteria_scores, true) ?: [];
                                    } catch (Exception $e) {
                                        $criteriaScores = [];
                                    }
                                } elseif (is_array($ranking->criteria_scores)) {
                                    $criteriaScores = $ranking->criteria_scores;
                                }
                            }
                            $totalScore = $ranking->total_score;
                        @endphp

                        <!-- Detail Skor per Kriteria -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Breakdown Skor per Kriteria Utama</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Kriteria</th>
                                                        <th class="text-center">Bobot</th>
                                                        <th class="text-center">Skor</th>
                                                        <th class="text-center">Kontribusi</th>
                                                        <th class="text-center">Persentase</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($criterias as $criteria)
                                                        @php
                                                            $score = isset($criteriaScores[$criteria->code]) ? $criteriaScores[$criteria->code] : 0;
                                                            $contribution = $criteria->weight * $score;
                                                            $percentage = $totalScore > 0 ? ($contribution / $totalScore) * 100 : 0;
                                                        @endphp
                                                        <tr>
                                                            <td><strong>{{ $criteria->name }}</strong></td>
                                                            <td class="text-center">{{ number_format($criteria->weight, 4) }}</td>
                                                            <td class="text-center">{{ number_format($score, 4) }}</td>
                                                            <td class="text-center fw-bold">{{ number_format($contribution, 4) }}</td>
                                                            <td class="text-center">{{ number_format($percentage, 2) }}%</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="3" class="text-end">TOTAL SKOR</th>
                                                        <th class="text-center">{{ number_format($totalScore, 4) }}</th>
                                                        <th class="text-center">100%</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visualisasi Kontribusi -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0">Visualisasi Kontribusi per Kriteria</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="contributionChart" height="80"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Data Aplikasi Siswa (IMPROVED) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-table"></i>
                                        Data Aplikasi Siswa - Detail per Sub Kriteria
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="20%">Kategori</th>
                                                    <th width="25%">Sub Kriteria</th>
                                                    <th width="25%">Nilai Siswa</th>
                                                    <th width="15%" class="text-center">Bobot</th>
                                                    <th width="15%" class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($criterias as $criteria)
                                                    @if($criteria->subCriterias->count() > 0)
                                                        @foreach($criteria->subCriterias as $index => $subCriteria)
                                                            @php
                                                                $appValue = $applicationValues->get('subcriteria_' . $subCriteria->id);
                                                                $hasValue = !empty($appValue) && !empty($appValue->value);
                                                                
                                                                // Dapatkan nama SubSubCriteria yang sesuai
                                                                $displayValue = '-';
                                                                $subSubName = '';
                                                                
                                                                if ($hasValue) {
                                                                    // Cari SubSubCriteria yang cocok dengan value
                                                                    $matchedSubSub = $subCriteria->subSubCriterias->firstWhere('id', $appValue->value);
                                                                    
                                                                    if ($matchedSubSub) {
                                                                        $displayValue = $matchedSubSub->name;
                                                                        $subSubName = $matchedSubSub->name;
                                                                    } else {
                                                                        // Jika tidak ada SubSubCriteria, tampilkan value langsung
                                                                        $displayValue = $appValue->value;
                                                                    }
                                                                }
                                                                
                                                                $hasSubSub = $subCriteria->subSubCriterias->count() > 0;
                                                                
                                                                // Tentukan bobot yang akan ditampilkan
                                                                $displayWeight = null;
                                                                $isCorrectWeight = false;
                                                                
                                                                if ($hasValue) {
                                                                    if (!$hasSubSub) {
                                                                        // Direct SubCriteria
                                                                        $displayWeight = $subCriteria->weight;
                                                                        $isCorrectWeight = true;
                                                                    } elseif (isset($appValue->actual_weight)) {
                                                                        $displayWeight = $appValue->actual_weight;
                                                                        $isCorrectWeight = true;
                                                                    } elseif (isset($appValue->subsubcriteria_data)) {
                                                                        $displayWeight = $appValue->subsubcriteria_data->weight;
                                                                        $isCorrectWeight = true;
                                                                    } else {
                                                                        $displayWeight = $subCriteria->weight;
                                                                        $isCorrectWeight = false;
                                                                    }
                                                                } else {
                                                                    $displayWeight = $subCriteria->weight;
                                                                }
                                                            @endphp
                                                            <tr class="{{ $isCorrectWeight && $hasValue ? 'table-success' : '' }}">
                                                                @if($index === 0)
                                                                    <td rowspan="{{ $criteria->subCriterias->count() }}" class="align-middle bg-light">
                                                                        <strong>{{ $criteria->name }}</strong>
                                                                        <br>
                                                                        <small class="text-muted">Bobot: {{ number_format($criteria->weight, 4) }}</small>
                                                                    </td>
                                                                @endif
                                                                <td>
                                                                    <div class="mb-1"><strong>{{ $subCriteria->name }}</strong></div>
                                                                    @if($subCriteria->code)
                                                                        <small class="text-muted">{{ $subCriteria->code }}</small>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($hasValue)
                                                                        <div class="mb-1">
                                                                            <span class="badge bg-primary">{{ $displayValue }}</span>
                                                                        </div>
                                                                        @if($hasSubSub && $subSubName)
                                                                            <small class="text-muted">
                                                                                <i class="fas fa-sitemap"></i> {{ $subSubName }}
                                                                            </small>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-muted">{{ $displayValue }}</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge {{ $isCorrectWeight ? 'bg-success' : 'bg-warning' }} fs-6">
                                                                        {{ number_format($displayWeight, 4) }}
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($hasValue)
                                                                        <span class="badge bg-success">
                                                                            <i class="fas fa-check"></i> OK
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-danger">
                                                                            <i class="fas fa-times"></i> Missing
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(config('app.debug'))
                    <!-- Weight Debugging Information -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-bug"></i>
                                        Weight Debugging Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>SubCriteria</th>
                                                    <th>Value (ID)</th>
                                                    <th>SubSubCriteria Name</th>
                                                    <th>SubSub Weight</th>
                                                    <th>SubCriteria Weight</th>
                                                    <th>Source</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($criterias as $criteria)
                                                    @foreach($criteria->subCriterias as $subCriteria)
                                                        @php
                                                            $appValue = $applicationValues->get('subcriteria_' . $subCriteria->id);
                                                            $hasValue = !empty($appValue) && !empty($appValue->value);
                                                            
                                                            $matchedSubSub = null;
                                                            if ($hasValue && $subCriteria->subSubCriterias->count() > 0) {
                                                                $matchedSubSub = $subCriteria->subSubCriterias->firstWhere('id', $appValue->value);
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $subCriteria->name }}</td>
                                                            <td>
                                                                @if($hasValue)
                                                                    <code>{{ $appValue->value }}</code>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($matchedSubSub)
                                                                    {{ $matchedSubSub->name }}
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($hasValue && isset($appValue->actual_weight))
                                                                    <strong>{{ number_format($appValue->actual_weight, 6) }}</strong>
                                                                @elseif($matchedSubSub)
                                                                    {{ number_format($matchedSubSub->weight, 6) }}
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ number_format($subCriteria->weight, 6) }}</td>
                                                            <td>
                                                                @if($hasValue)
                                                                    <span class="badge bg-secondary">{{ $appValue->source ?? 'unknown' }}</span>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($hasValue && isset($appValue->actual_weight))
                                                                    <span class="badge bg-success">Weight OK</span>
                                                                @elseif($hasValue)
                                                                    <span class="badge bg-warning">Fallback</span>
                                                                @else
                                                                    <span class="badge bg-danger">Missing</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Raw Application Data -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">
                                        <i class="fas fa-database"></i>
                                        Raw Application Data
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-dark sticky-top">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Criteria ID</th>
                                                    <th>Value</th>
                                                    <th>Source</th>
                                                    <th>Weight</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($applicationValues as $key => $value)
                                                <tr>
                                                    <td><code>{{ $value->criteria_type }}</code></td>
                                                    <td>{{ $value->criteria_id }}</td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            {{ Str::limit($value->value, 30) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge 
                                                            @if(str_contains($value->source, 'application_values_db')) bg-success
                                                            @elseif(str_contains($value->source, 'from_subsubcriteria')) bg-primary
                                                            @else bg-secondary
                                                            @endif">
                                                            {{ $value->source ?? 'unknown' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if(isset($value->actual_weight))
                                                            <strong class="text-success">{{ number_format($value->actual_weight, 6) }}</strong>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        No application values found
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            @if(!$ranking)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Aplikasi ini belum dihitung skornya.
                                </div>
                            @endif
                            
                            <form action="{{ route('admin.scoring.calculate-single', $application->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-{{ $ranking ? 'warning' : 'success' }} btn-lg" onclick="return confirm('Yakin ingin {{ $ranking ? 'menghitung ulang' : 'menghitung' }} skor?')">
                                    <i class="fas fa-{{ $ranking ? 'redo' : 'calculator' }} me-2"></i>
                                    {{ $ranking ? 'Hitung Ulang Skor' : 'Hitung Skor Sekarang' }}
                                </button>
                            </form>
                            
                            @if(config('app.debug'))
                            <form action="{{ route('admin.scoring.resync-weights', $application->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info btn-lg ms-2" onclick="return confirm('Yakin ingin re-sync bobot?')">
                                    <i class="fas fa-sync me-2"></i>
                                    Re-sync Weights
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.table-success {
    background-color: rgba(25, 135, 84, 0.075) !important;
}
</style>
@endpush

@push('scripts')
<script>
@if($ranking && !empty($criteriaScores))
const ctx = document.getElementById('contributionChart').getContext('2d');

const criteriaData = @json($criterias->pluck('name', 'code'));
const criteriaWeights = @json($criterias->pluck('weight', 'code'));
const criteriaScores = @json($criteriaScores);

const labels = Object.values(criteriaData);
const contributions = Object.keys(criteriaData).map(key => {
    const score = criteriaScores[key] || 0;
    const weight = criteriaWeights[key] || 0;
    return (weight * score);
});

const backgroundColors = [
    'rgba(255, 99, 132, 0.8)',
    'rgba(54, 162, 235, 0.8)', 
    'rgba(255, 205, 86, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(153, 102, 255, 0.8)',
    'rgba(255, 159, 64, 0.8)'
];

const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Kontribusi Skor',
            data: contributions,
            backgroundColor: backgroundColors,
            borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Kontribusi Skor'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const criteriaCode = Object.keys(criteriaData)[context.dataIndex];
                        const score = criteriaScores[criteriaCode] || 0;
                        const weight = criteriaWeights[criteriaCode] || 0;
                        return [
                            'Kontribusi: ' + context.parsed.y.toFixed(4),
                            'Skor: ' + score.toFixed(4),
                            'Bobot: ' + weight.toFixed(4)
                        ];
                    }
                }
            }
        }
    }
});
@endif
</script>
@endpush