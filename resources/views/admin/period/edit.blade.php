{{-- resources/views/admin/period/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Periode')
@section('page-title', 'Edit Periode Pendaftaran')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-edit me-2"></i>
                    Edit Periode: {{ $period->name }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.period.update', $period->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $period->name) }}" 
                               placeholder="Contoh: Periode Beasiswa 2024/2025 Semester Ganjil"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Berikan nama yang jelas dan mudah diidentifikasi untuk periode ini
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Deskripsi periode pendaftaran beasiswa ini...">{{ old('description', $period->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Opsional. Berikan informasi tambahan tentang periode ini
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('start_date') is-invalid @enderror" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="{{ old('start_date', $period->start_date->format('Y-m-d')) }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('end_date') is-invalid @enderror" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="{{ old('end_date', $period->end_date->format('Y-m-d')) }}" 
                                       required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $period->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>Periode aktif</strong>
                            </label>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                            Jika dicentang, periode lain yang sedang aktif akan dinonaktifkan secara otomatis
                        </div>
                    </div>
                    
                    @if($period->applications()->count() > 0)
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Peringatan:
                            </h6>
                            <p class="mb-0">Periode ini sudah memiliki <strong>{{ $period->applications()->count() }} aplikasi</strong>. 
                            Berhati-hatilah saat mengubah tanggal periode karena dapat mempengaruhi aplikasi yang sudah ada.</p>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>
                                Informasi:
                            </h6>
                            <ul class="mb-0 small">
                                <li>Periode ini belum memiliki aplikasi, sehingga aman untuk diubah</li>
                                <li>Tanggal berakhir harus lebih besar dari tanggal mulai</li>
                                <li>Hanya satu periode yang bisa aktif pada satu waktu</li>
                            </ul>
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.period.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Periode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Statistik Periode
                </h6>
            </div>
            <div class="card-body">
                @php
                    $totalApps = $period->applications()->count();
                    $draftApps = $period->applications()->where('status', 'draft')->count();
                    $submittedApps = $period->applications()->where('status', 'submitted')->count();
                    $validatedApps = $period->applications()->where('status', 'validated')->count();
                    $rejectedApps = $period->applications()->where('status', 'rejected')->count();
                @endphp
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <div class="h4 text-primary mb-1">{{ $totalApps }}</div>
                            <div class="small text-muted">Total Aplikasi</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <div class="h4 text-success mb-1">{{ $validatedApps }}</div>
                            <div class="small text-muted">Tervalidasi</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <div class="h4 text-warning mb-1">{{ $submittedApps }}</div>
                            <div class="small text-muted">Menunggu</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <div class="h4 text-secondary mb-1">{{ $draftApps }}</div>
                            <div class="small text-muted">Draft</div>
                        </div>
                    </div>
                </div>
                
                @if($rejectedApps > 0)
                    <div class="text-center mb-3">
                        <div class="border rounded p-2">
                            <div class="h5 text-danger mb-1">{{ $rejectedApps }}</div>
                            <div class="small text-muted">Ditolak</div>
                        </div>
                    </div>
                @endif
                
                @if($totalApps > 0)
                    <hr>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Progress Validasi:</span>
                            <span>{{ $totalApps > 0 ? round(($validatedApps / $totalApps) * 100, 1) : 0 }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $totalApps > 0 ? ($validatedApps / $totalApps) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Status Waktu
                </h6>
            </div>
            <div class="card-body">
                @php
                    $now = now();
                    $isOngoing = $now->between($period->start_date, $period->end_date);
                    $isUpcoming = $now->lt($period->start_date);
                    $isExpired = $now->gt($period->end_date);
                    $daysToStart = $now->diffInDays($period->start_date, false);
                    $daysToEnd = $now->diffInDays($period->end_date, false);
                @endphp
                
                <div class="text-center">
                    @if($isUpcoming)
                        <div class="badge bg-primary fs-6 mb-2">Akan Datang</div>
                        <div class="small text-muted">
                            Dimulai dalam {{ abs($daysToStart) }} hari
                        </div>
                    @elseif($isOngoing)
                        <div class="badge bg-success fs-6 mb-2">Berlangsung</div>
                        <div class="small text-muted">
                            Berakhir dalam {{ $daysToEnd }} hari
                        </div>
                    @else
                        <div class="badge bg-danger fs-6 mb-2">Berakhir</div>
                        <div class="small text-muted">
                            Berakhir {{ abs($daysToEnd) }} hari yang lalu
                        </div>
                    @endif
                </div>
                
                <hr>
                
                <div class="small">
                    <div class="mb-2">
                        <strong>Mulai:</strong> {{ $period->start_date->format('d F Y') }}<br>
                        <strong>Berakhir:</strong> {{ $period->end_date->format('d F Y') }}
                    </div>
                    <div>
                        <strong>Durasi:</strong> {{ $period->start_date->diffInDays($period->end_date) }} hari
                    </div>
                </div>
            </div>
        </div>
        
        @if($totalApps > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Aplikasi Terbaru
                </h6>
            </div>
            <div class="card-body">
                @php
                    $recentApps = $period->applications()->with('user')->latest()->limit(3)->get();
                @endphp
                
                @foreach($recentApps as $app)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="small fw-bold">{{ $app->full_name }}</div>
                            <div class="small text-muted">{{ $app->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="badge bg-{{ 
                            $app->status == 'validated' ? 'success' : 
                            ($app->status == 'submitted' ? 'warning' : 
                            ($app->status == 'rejected' ? 'danger' : 'secondary'))
                        }}">
                            {{ ucfirst($app->status) }}
                        </span>
                    </div>
                    @if(!$loop->last)<hr class="my-2">@endif
                @endforeach
                
                @if($totalApps > 3)
                    <div class="text-center mt-2">
                        <small class="text-muted">Dan {{ $totalApps - 3 }} aplikasi lainnya...</small>
                    </div>
                @endif
            </div>
        </div>
        @endif
        
        @if($period->is_active)
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Periode Aktif
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <p class="mb-2">Periode ini sedang aktif dan siswa dapat melakukan pendaftaran.</p>
                    <div class="d-grid">
                        <a href="{{ route('admin.scoring.applications', $period->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-calculator me-2"></i>
                            Lihat Perhitungan AHP
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        // Set minimum end date to start date + 1 day
        const startDate = new Date(this.value);
        startDate.setDate(startDate.getDate() + 1);
        endDateInput.min = startDate.toISOString().split('T')[0];
        
        // If end date is before start date, clear it
        if (endDateInput.value && new Date(endDateInput.value) <= new Date(this.value)) {
            endDateInput.value = '';
        }
    });
    
    // Set initial minimum end date
    if (startDateInput.value) {
        const startDate = new Date(startDateInput.value);
        startDate.setDate(startDate.getDate() + 1);
        endDateInput.min = startDate.toISOString().split('T')[0];
    }
});
</script>
@endsection