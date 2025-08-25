@extends('admin.layouts.app')

@section('title', 'Perhitungan Skor - ' . $period->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">Perhitungan Skor - {{ $period->name }}</h3>
                            <p class="text-muted mb-0">Periode: {{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.scoring.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Statistik Singkat -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info">
                                <div class="card-body text-center text-white">
                                    <h4>{{ $applications->count() }}</h4>
                                    <p class="mb-0">Total Aplikasi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success">
                                <div class="card-body text-center text-white">
                                    <h4>{{ $applications->where('final_score', '>', 0)->count() }}</h4>
                                    <p class="mb-0">Sudah Dihitung</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning">
                                <div class="card-body text-center text-white">
                                    <h4>{{ $applications->where('final_score', 0)->count() }}</h4>
                                    <p class="mb-0">Belum Dihitung</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary">
                                <div class="card-body text-center text-white">
                                    <h4>{{ $applications->where('rank', '>', 0)->count() }}</h4>
                                    <p class="mb-0">Ter-ranking</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aksi Bulk -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="btn-group" role="group">
                                        @if($applications->count() > 0)
                                            <button type="button" class="btn btn-primary" onclick="calculateAllApplications()">
                                                <i class="fas fa-calculator"></i> Hitung Semua Skor
                                            </button>
                                            @if($applications->where('final_score', '>', 0)->count() > 0)
                                                <button type="button" class="btn btn-success" onclick="exportResults('excel')">
                                                    <i class="fas fa-file-excel"></i> Export Excel
                                                </button>
                                                <button type="button" class="btn btn-danger" onclick="exportResults('pdf')">
                                                    <i class="fas fa-file-pdf"></i> Export PDF
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Aplikasi -->
                    @if($applications->count() == 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Belum ada aplikasi tervalidasi untuk periode ini.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="applicationsTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Nama Siswa</th>
                                        <th>NIS</th>
                                        <th>Kelas</th>
                                        <th>Skor Akhir</th>
                                        <th>Ranking</th>
                                        <th>Status Perhitungan</th>
                                        <th width="200">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sortedApplications = $applications->sortByDesc('final_score');
                                    @endphp
                                    @foreach($sortedApplications as $index => $application)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $application->student->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $application->student->email }}</small>
                                        </td>
                                        <td>{{ $application->student->student_id }}</td>
                                        <td>{{ $application->student->class ?? '-' }}</td>
                                        <td>
                                            @if($application->final_score > 0)
                                                <span class="badge badge-success">
                                                    {{ number_format($application->final_score, 6) }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">Belum dihitung</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($application->rank > 0)
                                                <span class="badge badge-primary">
                                                    Ranking {{ $application->rank }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($application->final_score > 0)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Sudah Dihitung
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Belum Dihitung
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.scoring.detail', $application->id) }}" 
                                                   class="btn btn-sm btn-info" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="calculateSingle({{ $application->id }})" title="Hitung Skor">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hitung Semua -->
<div class="modal fade" id="calculateAllModal" tabindex="-1" role="dialog" aria-labelledby="calculateAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calculateAllModalLabel">Konfirmasi Perhitungan Semua Skor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghitung ulang semua skor untuk <strong>{{ $applications->count() }}</strong> aplikasi?</p>
                <div class="alert alert-warning">
                    <strong>Peringatan:</strong> 
                    <ul class="mb-0">
                        <li>Proses ini akan menimpa perhitungan skor yang sudah ada sebelumnya</li>
                        <li>Ranking akan diperbarui secara otomatis</li>
                        <li>Proses mungkin membutuhkan waktu beberapa menit</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('admin.scoring.calculate-all', $period->id) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Ya, Hitung Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hitung Satu -->
<div class="modal fade" id="calculateSingleModal" tabindex="-1" role="dialog" aria-labelledby="calculateSingleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calculateSingleModalLabel">Konfirmasi Perhitungan Skor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghitung ulang skor untuk aplikasi ini?</p>
                <div id="studentInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form id="calculateSingleForm" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Ya, Hitung
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#applicationsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/Indonesian.json"
        },
        "order": [[ 4, "desc" ]], // Sort by score descending
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disable ordering on last column (actions)
        ]
    });
});

function calculateAllApplications() {
    $('#calculateAllModal').modal('show');
}

function calculateSingle(applicationId) {
    // Get student info for display
    let row = $(`button[onclick="calculateSingle(${applicationId})"]`).closest('tr');
    let studentName = row.find('td:nth-child(2) strong').text();
    let studentId = row.find('td:nth-child(3)').text();
    
    $('#studentInfo').html(`
        <div class="alert alert-info">
            <strong>Nama:</strong> ${studentName}<br>
            <strong>NIS:</strong> ${studentId}
        </div>
    `);
    
    $('#calculateSingleForm').attr('action', 
        '{{ route("admin.scoring.calculate-single", ":id") }}'.replace(':id', applicationId)
    );
    
    $('#calculateSingleModal').modal('show');
}

function exportResults(format) {
    let url = '{{ route("admin.scoring.export", ["period" => $period->id, "format" => ":format"]) }}'
        .replace(':format', format);
    
    window.open(url, '_blank');
}
</script>
@endpush