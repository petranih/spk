{{-- resources/views/student/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Siswa')
@section('page-title', 'Dashboard Siswa')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        @if($activePeriod)
            <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-calendar-check me-2"></i>
                        <strong>{{ $activePeriod->name }}</strong> sedang berlangsung
                        <br><small>{{ $activePeriod->start_date->format('d/m/Y') }} - {{ $activePeriod->end_date->format('d/m/Y') }}</small>
                    </div>
                    @if(!$currentApplication)
                        <a href="{{ route('student.application.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Daftar Sekarang
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Saat ini tidak ada periode pendaftaran beasiswa yang aktif.
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Riwayat Aplikasi Saya
                </h5>
            </div>
            <div class="card-body">
                @if($applications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Aplikasi</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Skor</th>
                                    <th>Rank</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $app)
                                <tr>
                                    <td>{{ $app->application_number }}</td>
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
                                    <td>
                                        @if($app->final_score)
                                            {{ number_format($app->final_score, 4) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->rank)
                                            <span class="badge bg-info">#{{ $app->rank }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->status == 'draft')
                                            <a href="{{ route('student.application.edit', $app->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Lengkapi
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-eye"></i> Lihat
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Anda belum memiliki aplikasi beasiswa</p>
                        @if($activePeriod)
                            <a href="{{ route('student.application.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Aplikasi Pertama
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        @if($currentApplication)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aplikasi Saat Ini</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>No. Aplikasi</td>
                            <td>: {{ $currentApplication->application_number }}</td>
                        </tr>
                        <tr>
                            <td>Periode</td>
                            <td>: {{ $currentApplication->period->name }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>: 
                                @if($currentApplication->status == 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($currentApplication->status == 'submitted')
                                    <span class="badge bg-warning">Menunggu Validasi</span>
                                @elseif($currentApplication->status == 'validated')
                                    <span class="badge bg-success">Tervalidasi</span>
                                @elseif($currentApplication->status == 'rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                @endif
                            </td>
                        </tr>
                        @if($currentApplication->final_score)
                            <tr>
                                <td>Skor</td>
                                <td>: {{ number_format($currentApplication->final_score, 4) }}</td>
                            </tr>
                        @endif
                        @if($currentApplication->rank)
                            <tr>
                                <td>Peringkat</td>
                                <td>: <span class="badge bg-info">#{{ $currentApplication->rank }}</span></td>
                            </tr>
                        @endif
                    </table>
                    
                    @if($currentApplication->status == 'draft')
                        <div class="d-grid">
                            <a href="{{ route('student.application.edit', $currentApplication->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Lengkapi Data
                            </a>
                        </div>
                    @endif
                    
                    @if($currentApplication->validation && $currentApplication->validation->notes)
                        <hr>
                        <h6>Catatan Validator:</h6>
                        <p class="small text-muted">{{ $currentApplication->validation->notes }}</p>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Panduan</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Tunggu periode pendaftaran aktif</li>
                    <li>Buat aplikasi baru</li>
                    <li>Lengkapi semua data dan dokumen</li>
                    <li>Submit aplikasi untuk validasi</li>
                    <li>Tunggu hasil validasi</li>
                    <li>Lihat ranking dan hasil akhir</li>
                </ol>
                
                <hr>
                
                <h6>Dokumen Yang Diperlukan:</h6>
                <ul class="small">
                    <li>Kartu Keluarga (KK)</li>
                    <li>KTP Orang Tua</li>
                    <li>Slip Gaji / Surat Keterangan Penghasilan</li>
                    <li>Surat Keterangan Tidak Mampu (SKTM)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection