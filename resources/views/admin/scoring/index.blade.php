@extends('layouts.app')

@section('title', 'Perhitungan Skor AHP')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Perhitungan Skor Akhir AHP</h3>
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

                    <!-- Informasi Prasyarat -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Prasyarat Perhitungan:</h5>
                        <ul class="mb-0">
                            <li>Pastikan semua kriteria, sub kriteria, dan sub-sub kriteria sudah memiliki bobot (weight > 0)</li>
                            <li>Semua perbandingan berpasangan harus konsisten (CR ≤ 0.1)</li>
                            <li>Aplikasi siswa harus berstatus "Tervalidasi"</li>
                        </ul>
                    </div>

                    <!-- Status Bobot Kriteria -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Status Bobot Kriteria</h5>
                                </div>
                                <div class="card-body">
                                    <div id="weights-status">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Memuat status bobot...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pilih Periode -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Pilih Periode untuk Perhitungan</h5>
                                </div>
                                <div class="card-body">
                                    @if($periods->count() == 0)
                                        <div class="alert alert-warning">
                                            Belum ada periode yang tersedia. 
                                            <a href="{{ route('admin.period.create') }}" class="btn btn-sm btn-warning">Buat Periode Baru</a>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Periode</th>
                                                        <th>Tanggal Mulai</th>
                                                        <th>Tanggal Selesai</th>
                                                        <th>Status</th>
                                                        <th>Jumlah Aplikasi</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($periods as $period)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $period->name }}</strong>
                                                            @if($period->is_active)
                                                                <span class="badge badge-success ml-2">Aktif</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $period->start_date->format('d/m/Y') }}</td>
                                                        <td>{{ $period->end_date->format('d/m/Y') }}</td>
                                                        <td>
                                                            @if($period->start_date <= now() && $period->end_date >= now())
                                                                <span class="badge badge-success">Berlangsung</span>
                                                            @elseif($period->start_date > now())
                                                                <span class="badge badge-warning">Belum Dimulai</span>
                                                            @else
                                                                <span class="badge badge-secondary">Selesai</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $appCount = \App\Models\Application::where('period_id', $period->id)
                                                                    ->where('status', 'validated')->count();
                                                            @endphp
                                                            <span class="badge badge-info">{{ $appCount }} aplikasi</span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="{{ route('admin.scoring.applications', $period->id) }}" 
                                                                   class="btn btn-sm btn-info">
                                                                    <i class="fas fa-eye"></i> Lihat Detail
                                                                </a>
                                                                @if($appCount > 0)
                                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                                            onclick="calculateAll({{ $period->id }})">
                                                                        <i class="fas fa-calculator"></i> Hitung Semua
                                                                    </button>
                                                                @endif
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
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="calculateModal" tabindex="-1" role="dialog" aria-labelledby="calculateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calculateModalLabel">Konfirmasi Perhitungan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghitung ulang semua skor untuk periode ini?</p>
                <div class="alert alert-warning">
                    <small><strong>Peringatan:</strong> Proses ini akan menimpa perhitungan skor yang sudah ada sebelumnya.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form id="calculateForm" method="POST" style="display: inline;">
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

@push('scripts')
<script>
$(document).ready(function() {
    loadWeightsStatus();
});

function loadWeightsStatus() {
    $.get('{{ route("api.admin.weights.summary") }}', function(data) {
        let html = '<div class="row">';
        
        // Status Kriteria
        html += '<div class="col-md-4"><div class="card bg-' + (data.criteria.consistent ? 'success' : 'warning') + '">';
        html += '<div class="card-body text-center text-white">';
        html += '<h6>Kriteria Utama</h6>';
        html += '<p>' + (data.criteria.consistent ? 'Konsisten' : 'Tidak Konsisten') + '</p>';
        html += '<small>CR: ' + (data.criteria.cr || 'N/A') + '</small>';
        html += '</div></div></div>';

        // Status Sub Kriteria
        html += '<div class="col-md-4"><div class="card bg-' + (data.subcriteria.all_consistent ? 'success' : 'warning') + '">';
        html += '<div class="card-body text-center text-white">';
        html += '<h6>Sub Kriteria</h6>';
        html += '<p>' + data.subcriteria.consistent_count + '/' + data.subcriteria.total_count + ' Konsisten</p>';
        html += '</div></div></div>';

        // Status Sub Sub Kriteria
        html += '<div class="col-md-4"><div class="card bg-' + (data.subsubcriteria.all_consistent ? 'success' : 'warning') + '">';
        html += '<div class="card-body text-center text-white">';
        html += '<h6>Sub Sub Kriteria</h6>';
        html += '<p>' + data.subsubcriteria.consistent_count + '/' + data.subsubcriteria.total_count + ' Konsisten</p>';
        html += '</div></div></div>';

        html += '</div>';

        if (!data.criteria.consistent || !data.subcriteria.all_consistent || !data.subsubcriteria.all_consistent) {
            html += '<div class="alert alert-warning mt-3">';
            html += '<strong>Perhatian:</strong> Ada kriteria yang belum konsisten. ';
            html += '<a href="{{ route("admin.pairwise.index") }}" class="btn btn-sm btn-warning">Kelola Perbandingan</a>';
            html += '</div>';
        }

        $('#weights-status').html(html);
    }).fail(function() {
        $('#weights-status').html('<div class="alert alert-danger">Error memuat status bobot kriteria</div>');
    });
}

function calculateAll(periodId) {
    $('#calculateForm').attr('action', '{{ route("admin.scoring.calculate-all", ":id") }}'.replace(':id', periodId));
    $('#calculateModal').modal('show');
}
</script>
@endpush