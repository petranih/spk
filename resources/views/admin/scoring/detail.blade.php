@extends('layouts.app')

@section('title', 'Detail Perhitungan - ' . $application->full_name)

@section('content')
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.table-success {
    background-color: rgba(25, 135, 84, 0.075) !important;
}
.bg-dark {
    background-color: #343a40 !important;
}
.bg-dark th {
    color: white !important;
}
thead.bg-dark th {
    background-color: #343a40 !important;
    color: #ffffff !important;
}
</style>
@endpush
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
                            
                            // Calculate sum of all contributions for percentage calculation
                            $totalContribution = 0;
                            foreach($criterias as $criteria) {
                                $score = isset($criteriaScores[$criteria->code]) ? $criteriaScores[$criteria->code] : 0;
                                $contribution = $criteria->weight * $score;
                                $totalContribution += $contribution;
                            }
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
                                                <thead class="bg-dark">
                                                    <tr>
                                                        <th class="text-white">Kriteria</th>
                                                        <th class="text-center text-white">Bobot</th>
                                                        <th class="text-center text-white">Skor</th>
                                                        <th class="text-center text-white">Kontribusi</th>
                                                        <th class="text-center text-white">Persentase</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $totalPercentage = 0; @endphp
                                                    @foreach($criterias as $criteria)
                                                        @php
                                                            $score = isset($criteriaScores[$criteria->code]) ? $criteriaScores[$criteria->code] : 0;
                                                            $contribution = $criteria->weight * $score;
                                                            // Calculate percentage based on total contribution, not total score
                                                            $percentage = $totalContribution > 0 ? ($contribution / $totalContribution) * 100 : 0;
                                                            $totalPercentage += $percentage;
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
                                                        <th class="text-center">{{ number_format($totalPercentage, 2) }}%</th>
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