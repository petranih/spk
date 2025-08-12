@extends('layouts.app')

@section('title', 'Dashboard Validator')
@section('page-title', 'Dashboard Validator')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                <h3>{{ $stats['pending'] }}</h3>
                <p class="card-text">Menunggu Validasi</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                <h3>{{ $stats['approved'] }}</h3>
                <p class="card-text">Disetujui</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                <h3>{{ $stats['rejected'] }}</h3>
                <p class="card-text">Ditolak</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-list fa-2x text-info mb-3"></i>
                <h3>{{ $stats['validated'] }}</h3>
                <p class="card-text">Total Validasi</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Aplikasi Menunggu Validasi
                </h5>
            </div>
            <div class="card-body">
                @if($pendingApplications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Aplikasi</th>
                                    <th>Nama Siswa</th>
                                    <th>Tanggal Submit</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApplications as $app)
                                <tr>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->user->name }}</td>
                                    <td>{{ $app->updated_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('validator.validation.show', $app->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Validasi
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('validator.validation.index') }}" class="btn btn-outline-primary">
                            Lihat Semua
                        </a>
                    </div>
                @else
                    <p class="text-muted text-center">Tidak ada aplikasi yang menunggu validasi</p>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Validasi Terakhir Saya
                </h5>
            </div>
            <div class="card-body">
                @if($myValidations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Aplikasi</th>
                                    <th>Nama Siswa</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myValidations as $validation)
                                <tr>
                                    <td>{{ $validation->application->application_number }}</td>
                                    <td>{{ $validation->application->user->name }}</td>
                                    <td>
                                        @if($validation->status == 'approved')
                                            <span class="badge bg-success">Disetujui</span>
                                        @else
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>{{ $validation->validated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">Belum ada validasi yang dilakukan</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection