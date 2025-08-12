{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                <h3>{{ $stats['total_students'] }}</h3>
                <p class="card-text">Total Siswa</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-file-alt fa-2x text-success mb-3"></i>
                <h3>{{ $stats['total_applications'] }}</h3>
                <p class="card-text">Total Aplikasi</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar fa-2x text-info mb-3"></i>
                <h3>{{ $stats['active_periods'] }}</h3>
                <p class="card-text">Periode Aktif</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-list fa-2x text-warning mb-3"></i>
                <h3>{{ $stats['total_criterias'] }}</h3>
                <p class="card-text">Total Kriteria</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Aplikasi Terbaru
                </h5>
            </div>
            <div class="card-body">
                @if($recentApplications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Aplikasi</th>
                                    <th>Nama Siswa</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentApplications as $app)
                                <tr>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->user->name }}</td>
                                    <td>{{ $app->period->name }}</td>
                                    <td>
                                        @if($app->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($app->status == 'submitted')
                                            <span class="badge bg-warning">Menunggu Validasi</span>
                                        @elseif($app->status == 'validated')
                                            <span class="badge bg-success">Tervalidasi</span>
                                        @elseif($app->status == 'rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>{{ $app->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">Belum ada aplikasi</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection