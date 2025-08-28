{{-- resources/views/admin/period/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kelola Periode')
@section('page-title', 'Kelola Periode Pendaftaran')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0">Kelola periode pendaftaran beasiswa dan status aktifnya</p>
            </div>
            <a href="{{ route('admin.period.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Buat Periode Baru
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>
                    Daftar Periode
                </h5>
            </div>
            <div class="card-body">
                @if($periods->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Periode</th>
                                    <th>Deskripsi</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Berakhir</th>
                                    <th>Status</th>
                                    <th>Aplikasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($periods as $period)
                                <tr class="{{ $period->is_active ? 'table-success' : '' }}">
                                    <td>
                                        <strong>{{ $period->name }}</strong>
                                        @if($period->is_active)
                                            <span class="badge bg-success ms-2">AKTIF</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($period->description)
                                            <span class="text-muted">{{ Str::limit($period->description, 50) }}</span>
                                        @else
                                            <span class="text-muted fst-italic">Tidak ada deskripsi</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $period->start_date->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $period->end_date->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $now = now();
                                            $isOngoing = $now->between($period->start_date, $period->end_date);
                                            $isUpcoming = $now->lt($period->start_date);
                                            $isExpired = $now->gt($period->end_date);
                                        @endphp
                                        
                                        @if($period->is_active)
                                            @if($isOngoing)
                                                <span class="badge bg-success">Berlangsung</span>
                                            @elseif($isUpcoming)
                                                <span class="badge bg-primary">Akan Datang</span>
                                            @else
                                                <span class="badge bg-danger">Berakhir</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $totalApps = $period->applications()->count();
                                            $validatedApps = $period->applications()->where('status', 'validated')->count();
                                            $submittedApps = $period->applications()->where('status', 'submitted')->count();
                                        @endphp
                                        
                                        <div class="small">
                                            <div>Total: <span class="badge bg-info">{{ $totalApps }}</span></div>
                                            <div>Tervalidasi: <span class="badge bg-success">{{ $validatedApps }}</span></div>
                                            <div>Menunggu: <span class="badge bg-warning">{{ $submittedApps }}</span></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.period.edit', $period->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            @if(!$period->is_active)
                                                <form action="{{ route('admin.period.activate', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Yakin ingin mengaktifkan periode ini? Periode lain akan dinonaktifkan.')"
                                                            title="Aktifkan">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if($totalApps == 0)
                                                <form action="{{ route('admin.period.destroy', $period->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Yakin ingin menghapus periode ini?')"
                                                            title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-sm btn-outline-danger" disabled title="Tidak dapat dihapus (ada aplikasi)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada periode pendaftaran</p>
                        <a href="{{ route('admin.period.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Buat Periode Pertama
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($periods->count() > 0)
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informasi Periode
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Status Periode:</h6>
                        <ul class="small">
                            <li><span class="badge bg-success">Berlangsung</span> - Periode aktif dan dalam rentang tanggal</li>
                            <li><span class="badge bg-primary">Akan Datang</span> - Periode aktif tapi belum dimulai</li>
                            <li><span class="badge bg-danger">Berakhir</span> - Periode aktif tapi sudah lewat tanggal</li>
                            <li><span class="badge bg-secondary">Tidak Aktif</span> - Periode dinonaktifkan</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Catatan:</h6>
                        <ul class="small">
                            <li>Hanya satu periode yang bisa aktif pada satu waktu</li>
                            <li>Siswa hanya bisa mendaftar pada periode aktif</li>
                            <li>Periode tidak dapat dihapus jika sudah ada aplikasi</li>
                            <li>Mengaktifkan periode akan menonaktifkan periode lainnya</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Statistik
                </h6>
            </div>
            <div class="card-body">
                @php
                    $activePeriod = $periods->where('is_active', true)->first();
                    $totalPeriods = $periods->count();
                    $totalApplications = $periods->sum(fn($p) => $p->applications()->count());
                @endphp
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Total Periode:</span>
                        <strong>{{ $totalPeriods }}</strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Periode Aktif:</span>
                        <strong>{{ $activePeriod ? 1 : 0 }}</strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Total Aplikasi:</span>
                        <strong>{{ $totalApplications }}</strong>
                    </div>
                </div>
                
                @if($activePeriod)
                    <hr>
                    <h6 class="small text-muted">Periode Aktif:</h6>
                    <div class="small">
                        <strong>{{ $activePeriod->name }}</strong><br>
                        {{ $activePeriod->start_date->format('d/m/Y') }} - {{ $activePeriod->end_date->format('d/m/Y') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection