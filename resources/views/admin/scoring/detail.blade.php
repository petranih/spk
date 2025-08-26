@extends('admin.layouts.app')

@section('title', 'Detail Perhitungan - ' . $application->student->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">Detail Perhitungan AHP</h3>
                            <p class="text-muted mb-0">{{ $application->student->name }} ({{ $application->student->student_id }})</p>
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
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Nama:</strong></td>
                                            <td>{{ $application->student->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>NIS:</strong></td>
                                            <td>{{ $application->student->student_id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kelas:</strong></td>
                                            <td>{{ $application->student->class ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $application->student->email }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-{{ $ranking ? 'success' : 'warning' }}">
                                <div class="card-body text-white">
                                    <h6 class="card-title">Hasil Perhitungan</h6>
                                    <table class="table table-sm text-white">
                                        <tr>
                                            <td><strong>Skor Total:</strong></td>
                                            <td>{{ $ranking ? number_format($ranking->total_score, 6) : 'Belum dihitung' }}</td>
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

                    @if($ranking && $ranking->criteria_scores)
                        <!-- Detail Skor per Kriteria -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Breakdown Skor per Kriteria Utama</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Kriteria</th>
                                                        <th>Bobot Kriteria</th>
                                                        <th>Skor Kriteria</th>
                                                        <th>Kontribusi (Bobot × Skor)</th>
                                                        <th>Persentase</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $criteriaWeights = [
                                                            'ekonomi' => 0.406477,
                                                            'rumah' => 0.25624,
                                                            'aset' => 0.158276,
                                                            'fasilitas' => 0.09695,
                                                            'bantuan' => 0.082057
                                                        ];
                                                        $criteriaNames = [
                                                            'ekonomi' => 'Kondisi Ekonomi',
                                                            'rumah' => 'Kondisi Rumah',
                                                            'aset' => 'Kepemilikan Aset',
                                                            'fasilitas' => 'Fasilitas Rumah',
                                                            'bantuan' => 'Status Bantuan Sosial'
                                                        ];
                                                        $criteriaScores = json_decode($ranking->criteria_scores, true);
                                                    @endphp
                                                    
                                                    @foreach($criteriaWeights as $code => $weight)
                                                        @php
                                                            $score = $criteriaScores[$code] ?? 0;
                                                            $contribution = $weight * $score;
                                                            $percentage = $ranking->total_score > 0 ? ($contribution / $ranking->total_score) * 100 : 0;
                                                        @endphp
                                                        <tr>
                                                            <td><strong>{{ $criteriaNames[$code] }}</strong></td>
                                                            <td>{{ number_format($weight, 6) }}</td>
                                                            <td>{{ number_format($score, 6) }}</td>
                                                            <td class="font-weight-bold">{{ number_format($contribution, 6) }}</td>
                                                            <td>{{ number_format($percentage, 2) }}%</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th colspan="3">TOTAL SKOR</th>
                                                        <th>{{ number_format($ranking->total_score, 6) }}</th>
                                                        <th>100%</th>
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
                                    <div class="card-header">
                                        <h5>Visualisasi Kontribusi per Kriteria</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="contributionChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Data Aplikasi Siswa -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Data Aplikasi Siswa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Kategori</th>
                                                    <th>Sub Kriteria</th>
                                                    <th>Nilai Siswa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($criterias as $criteria)
                                                    @foreach($criteria->subCriterias as $subCriteria)
                                                        @php
                                                            $appValue = $applicationValues->get('subcriteria_' . $subCriteria->id);
                                                        @endphp
                                                        <tr>
                                                            <td><strong>{{ $criteria->name }}</strong></td>
                                                            <td>{{ $subCriteria->name }}</td>
                                                            <td>
                                                                @if($appValue)
                                                                    <span class="badge badge-info">{{ $appValue->value }}</span>
                                                                @else
                                                                    <span class="text-muted">Tidak ada data</span>
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

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('scripts')
<script>
@if($ranking && $ranking->criteria_scores)
// Chart untuk visualisasi kontribusi
const ctx = document.getElementById('contributionChart').getContext('2d');

const criteriaNames = {
    'ekonomi': 'Kondisi Ekonomi',
    'rumah': 'Kondisi Rumah', 
    'aset': 'Kepemilikan Aset',
    'fasilitas': 'Fasilitas Rumah',
    'bantuan': 'Status Bantuan Sosial'
};

const criteriaWeights = {
    'ekonomi': 0.406477,
    'rumah': 0.25624,
    'aset': 0.158276,
    'fasilitas': 0.09695,
    'bantuan': 0.082057
};

const criteriaScores = @json($criteriaScores);

const labels = Object.keys(criteriaNames).map(key => criteriaNames[key]);
const contributions = Object.keys(criteriaWeights).map(key => {
    const score = criteriaScores[key] || 0;
    return (criteriaWeights[key] * score);
});

const backgroundColors = [
    'rgba(255, 99, 132, 0.8)',
    'rgba(54, 162, 235, 0.8)', 
    'rgba(255, 205, 86, 0.8)',
    'rgba(75, 192, 192, 0.8)',
    'rgba(153, 102, 255, 0.8)'
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
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Kontribusi Skor'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Kriteria'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Kontribusi Skor per Kriteria'
            },
            legend: {
                display: false
            }
        }
    }
});
@endif
</script>
@endpush