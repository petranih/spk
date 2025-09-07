{{-- resources/views/student/dashboard.blade.php - PERBAIKAN untuk Multiple Periods --}}
@extends('layouts.app')

@section('title', 'Dashboard Siswa')
@section('page-title', 'Dashboard Siswa')

@section('content')

{{-- Debug Information (bisa dihapus setelah testing) --}}
@if(config('app.debug'))
<div class="row mb-2">
    <div class="col-12">
        <div class="alert alert-info">
            <small>
                <strong>Debug Info:</strong><br>
                Active Period: {{ $activePeriod ? $activePeriod->name : 'None' }}<br>
                Available Periods: {{ isset($availablePeriods) ? $availablePeriods->count() : 0 }}<br>
                Current Application: {{ $currentApplication ? 'Yes (ID: '.$currentApplication->id.')' : 'None' }}
            </small>
        </div>
    </div>
</div>
@endif

<div class="row mb-4">
    <div class="col-12">
        @if($activePeriod)
            <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-calendar-check me-2"></i>
                        <strong>{{ $activePeriod->name }}</strong> sedang berlangsung
                        <br><small>{{ $activePeriod->start_date->format('d/m/Y') }} - {{ $activePeriod->end_date->format('d/m/Y') }}</small>
                        @if($activePeriod->description)
                            <br><small class="text-muted">{{ $activePeriod->description }}</small>
                        @endif
                    </div>
                    @if(!$currentApplication)
                        @if($activePeriod->canAcceptApplications())
                            <a href="{{ route('student.application.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Daftar Sekarang
                            </a>
                        @else
                            <span class="badge bg-warning fs-6">Periode Penuh / Berakhir</span>
                        @endif
                    @endif
                </div>
            </div>
        @else
            {{-- PERBAIKAN: Tampilkan periode yang akan datang jika ada --}}
            @if(isset($upcomingPeriods) && $upcomingPeriods->count() > 0)
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-plus fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Periode Akan Datang</h5>
                            @foreach($upcomingPeriods as $period)
                                <p class="mb-0">
                                    <strong>{{ $period->name }}</strong> - 
                                    Dimulai {{ $period->start_date->format('d/m/Y') }}
                                    <span class="badge bg-info">{{ $period->remaining_days }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Tidak Ada Periode Aktif</h5>
                            <p class="mb-0">Saat ini tidak ada periode pendaftaran beasiswa yang aktif. Silakan tunggu pengumuman periode berikutnya.</p>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- PERBAIKAN: Tampilkan periode yang tersedia untuk pendaftaran --}}
@if(isset($availablePeriods) && $availablePeriods->count() > 0 && !$activePeriod)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Periode Tersedia
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($availablePeriods as $period)
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h6 class="card-title">{{ $period->name }}</h6>
                                <p class="card-text small">
                                    {{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}<br>
                                    <span class="badge bg-{{ $period->is_ongoing ? 'success' : 'info' }}">
                                        {{ $period->remaining_days }}
                                    </span>
                                </p>
                                @php
                                    $hasApplication = $applications->where('period_id', $period->id)->first();
                                @endphp
                                @if($hasApplication)
                                    <span class="badge bg-secondary">Sudah Mendaftar</span>
                                @elseif($period->is_ongoing)
                                    <a href="{{ route('student.application.create') }}" class="btn btn-sm btn-primary">
                                        Daftar
                                    </a>
                                @else
                                    <span class="badge bg-warning">Belum Dimulai</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

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
                                    <th>Tanggal Daftar</th>
                                    <th>Status</th>
                                    <th>Skor</th>
                                    <th>Rank</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $app)
                                <tr class="{{ $app->id === optional($currentApplication)->id ? 'table-warning' : '' }}">
                                    <td>
                                        <code>{{ $app->application_number ?? 'APP-' . $app->id }}</code>
                                        @if($app->id === optional($currentApplication)->id)
                                            <span class="badge bg-warning">Aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $app->period->name }}</strong>
                                        <br><small class="text-muted">{{ $app->period->start_date->format('d/m/Y') }} - {{ $app->period->end_date->format('d/m/Y') }}</small>
                                        @if($app->period->is_ongoing)
                                            <span class="badge bg-success">Sedang Berlangsung</span>
                                        @elseif($app->period->is_expired)
                                            <span class="badge bg-secondary">Berakhir</span>
                                        @else
                                            <span class="badge bg-info">Akan Datang</span>
                                        @endif
                                    </td>
                                    <td>{{ $app->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($app->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($app->status == 'submitted')
                                            <span class="badge bg-warning">Menunggu Validasi</span>
                                        @elseif($app->status == 'validated')
                                            <span class="badge bg-success">Tervalidasi</span>
                                        @elseif($app->status == 'rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($app->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->final_score)
                                            <span class="badge bg-primary">{{ number_format($app->final_score, 4) }}</span>
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
                        <h5 class="text-muted">Belum Ada Aplikasi</h5>
                        <p class="text-muted">Anda belum pernah mengajukan aplikasi beasiswa</p>
                        @if($activePeriod && $activePeriod->canAcceptApplications())
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
                    <h6 class="mb-0">
                        <i class="fas fa-file-text me-2"></i>
                        Aplikasi Saat Ini
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>No. Aplikasi</td>
                            <td>: <code>{{ $currentApplication->application_number ?? 'APP-' . $currentApplication->id }}</code></td>
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
                                <td>: <span class="badge bg-primary">{{ number_format($currentApplication->final_score, 4) }}</span></td>
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
                        <div class="alert alert-info">
                            <small><i class="fas fa-info-circle me-1"></i>Aplikasi masih dalam tahap draft. Lengkapi semua data dan submit untuk validasi.</small>
                        </div>
                        <div class="d-grid">
                            <a href="{{ route('student.application.edit', $currentApplication->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Lengkapi Data
                            </a>
                        </div>
                    @elseif($currentApplication->status == 'submitted')
                        <div class="alert alert-warning">
                            <small><i class="fas fa-clock me-1"></i>Aplikasi sedang menunggu validasi dari admin.</small>
                        </div>
                    @elseif($currentApplication->status == 'validated')
                        <div class="alert alert-success">
                            <small><i class="fas fa-check-circle me-1"></i>Aplikasi telah divalidasi dan siap untuk perhitungan skor.</small>
                        </div>
                    @elseif($currentApplication->status == 'rejected')
                        <div class="alert alert-danger">
                            <small><i class="fas fa-times-circle me-1"></i>Aplikasi ditolak. Lihat catatan dari validator.</small>
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
        
        {{-- Informasi Panduan --}}
        <div class="card {{ $currentApplication ? 'mt-3' : '' }}">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>
                    Panduan Pendaftaran
                </h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Tunggu periode pendaftaran aktif</li>
                    <li>Klik "Daftar Sekarang" atau "Buat Aplikasi"</li>
                    <li>Isi data pribadi dengan lengkap</li>
                    <li>Lengkapi data kriteria AHP</li>
                    <li>Upload semua dokumen yang diperlukan</li>
                    <li>Review semua data yang telah diisi</li>
                    <li>Submit aplikasi untuk validasi</li>
                    <li>Tunggu hasil validasi dari admin</li>
                    <li>Lihat hasil perhitungan dan ranking</li>
                </ol>
                
                <div class="alert alert-info mt-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Catatan:</strong> Anda dapat mendaftar untuk setiap periode yang berbeda. Satu siswa dapat memiliki multiple aplikasi untuk periode yang berbeda.
                    </small>
                </div>
            </div>
        </div>
        
        {{-- Info periode aktif jika ada --}}
        @if($activePeriod)
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Info Periode Aktif
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Nama</td>
                        <td>: {{ $activePeriod->name }}</td>
                    </tr>
                    <tr>
                        <td>Mulai</td>
                        <td>: {{ $activePeriod->start_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Berakhir</td>
                        <td>: {{ $activePeriod->end_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Sisa Waktu</td>
                        <td>: <span class="badge bg-success">{{ $activePeriod->remaining_days }}</span></td>
                    </tr>
                    @if($activePeriod->max_applications)
                    <tr>
                        <td>Kuota</td>
                        <td>: {{ $activePeriod->applications_count }}/{{ $activePeriod->max_applications }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        @endif
        
        {{-- Dokumen yang diperlukan --}}
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Dokumen Yang Diperlukan
                </h6>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li><i class="fas fa-id-card text-primary me-1"></i> Kartu Keluarga (KK)</li>
                    <li><i class="fas fa-id-card text-primary me-1"></i> KTP Orang Tua</li>
                    <li><i class="fas fa-file-invoice-dollar text-success me-1"></i> Slip Gaji / Surat Keterangan Penghasilan</li>
                    <li><i class="fas fa-file-alt text-info me-1"></i> Surat Keterangan Tidak Mampu (SKTM)</li>
                </ul>
                
                <div class="alert alert-light mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Pastikan semua dokumen dalam format PDF/JPG dan ukuran maksimal 2MB
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection