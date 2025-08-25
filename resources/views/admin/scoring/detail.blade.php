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
                            <h3 class="card-title">Detail Perhitungan Skor AHP</h3>
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
                            <div class="card bg-primary">
                                <div class="card-body text-white">
                                    <h5>Informasi Siswa</h5>
                                    <p class="mb-1"><strong>Nama:</strong> {{ $application->student->name }}</p>
                                    <p class="mb-1"><strong>NIS:</strong> {{ $application->student->student_id }}</p>
                                    <p class="mb-1"><strong>Kelas:</strong> {{ $application->student->class ?? '-' }}</p>
                                    <p class="mb-0"><strong>Email:</strong> {{ $application->student->email }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success">
                                <div class="card-body text-white">
                                    <h5>Hasil Perhitungan</h5>
                                    <p class="mb-1"><strong>Skor Total:</strong> {{ number_format($application->final_score, 6) }}</p>
                                    <p class="mb-1"><strong>Ranking:</strong> {{ $application->rank ?? 'Belum diranking' }}</p>
                                    <p class="mb-0"><strong>Status:</strong> 
                                        @if($application->final_score > 0)
                                            <span class="badge badge-light">Sudah Dihitung</span>
                                        @else
                                            <span class="badge badge-warning">Belum Dihitung</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Perhitungan per Kriteria -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Breakdown Perhitungan per Kriteria</h5>
                        </div>
                        <div class="card-body">
                            @if($ranking && $ranking->criteria_scores)
                                @php
                                    $criteriaScores = json_decode($ranking->criteria_scores, true);
                                @endphp

                                @foreach($criterias as $criteria)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-0">
                                                    <span class="badge badge-primary">{{ $criteria->code }}</span>
                                                    {{ $criteria->name }}
                                                </h6>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <span class="badge badge-info">
                                                    Bobot: {{ number_format($criteria->weight, 4) }}
                                                </span>
                                                <span class="badge badge-success">
                                                    Skor: {{ number_format($criteriaScores[$criteria->code] ?? 0, 6) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($criteria->subCriterias->count() > 0)
                                            <!-- Sub Kriteria -->
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Sub Kriteria</th>
                                                            <th>Nilai Aplikasi</th>
                                                            <th>Bobot</th>
                                                            <th>Kontribusi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($criteria->subCriterias as $subCriteria)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $subCriteria->name }}</strong>
                                                                <br><small class="text-muted">{{ $subCriteria->code }}</small>
                                                            </td>
                                                            <td>
                                                                @php
                                                                    $appValueKey = 'subcriteria_' . $subCriteria->id;
                                                                    $appValue = $applicationValues[$appValueKey] ?? null;
                                                                @endphp
                                                                @if($appValue)
                                                                    <span class="badge badge-info">{{ $appValue->value }}</span>
                                                                @else
                                                                    <span class="badge badge-secondary">Tidak ada data</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-primary">{{ number_format($subCriteria->weight, 4) }}</span>
                                                            </td>
                                                            <td>
                                                                @php
                                                                    $subScore = 0;
                                                                    if ($appValue && $subCriteria->subSubCriterias->count() > 0) {
                                                                        $matchingSubSub = $subCriteria->subSubCriterias()
                                                                            ->where('code', $appValue->value)->first();
                                                                        if ($matchingSubSub) {
                                                                            $subScore = $matchingSubSub->weight * $subCriteria->weight;
                                                                        }
                                                                    }
                                                                @endphp
                                                                <span class="badge badge-success">{{ number_format($subScore, 6) }}</span>
                                                            </td>
                                                        </tr>

                                                        @if($subCriteria->subSubCriterias->count() > 0)
                                                        <tr>
                                                            <td colspan="4">
                                                                <div class="ml-3">
                                                                    <small class="text-muted">Sub-Sub Kriteria:</small>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-borderless">
                                                                            @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                                                                            <tr>
                                                                                <td width="40%">
                                                                                    <small>{{ $subSubCriteria->name }}</small>
                                                                                </td>
                                                                                <td width="20%">
                                                                                    <small class="badge badge-light">{{ $subSubCriteria->code }}</small>
                                                                                </td>
                                                                                <td width="20%">
                                                                                    <small>Bobot: {{ number_format($subSubCriteria->weight, 4) }}</small>
                                                                                </td>
                                                                                <td width="20%">
                                                                                    @if($appValue && $appValue->value == $subSubCriteria->code)
                                                                                        <small class="badge badge-success">Dipilih</small>
                                                                                    @else
                                                                                        <small class="text-muted">-</small>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <!-- Tidak ada sub kriteria -->
                                            <div class="alert alert-info">
                                                <small>Kriteria ini tidak memiliki sub kriteria.</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach

                                <!-- Ringkasan Perhitungan -->
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Ringkasan Perhitungan</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Kriteria</th>
                                                        <th>Skor Kriteria</th>
                                                        <th>Bobot Kriteria</th>
                                                        <th>Kontribusi ke Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $totalContribution = 0; @endphp
                                                    @foreach($criterias as $criteria)
                                                    @php
                                                        $criteriaScore = $criteriaScores[$criteria->code] ?? 0;
                                                        $contribution = $criteriaScore * $criteria->weight;
                                                        $totalContribution += $contribution;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $criteria->name }}</td>
                                                        <td>{{ number_format($criteriaScore, 6) }}</td>
                                                        <td>{{ number_format($criteria->weight, 4) }}</td>
                                                        <td>{{ number_format($contribution, 6) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="font-weight-bold">
                                                        <td>Total Skor Akhir</td>
                                                        <td colspan="2"></td>
                                                        <td>{{ number_format($totalContribution, 6) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Belum Ada Perhitungan</strong><br>
                                    Aplikasi ini belum dihitung skornya. Silakan lakukan perhitungan terlebih dahulu.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Aksi -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <form method="POST" action="{{ route('admin.scoring.calculate-single', $application->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-calculator"></i> Hitung Ulang Skor
                                    </button>
                                </form>
                                @if($application->final_score > 0)
                                    <button type="button" class="btn btn-success" onclick="printDetail()">
                                        <i class="fas fa-print"></i> Print Detail
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function printDetail() {
    window.print();
}
</script>
@endpush