@extends('layouts.app')

@section('title', 'Tambah Sub Kriteria')
@section('page-title', 'Tambah Sub Kriteria')

@section('content')

<!-- Criteria Selection Card -->
@if(!isset($criterion))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Pilih Kriteria</h5>
    </div>
    <div class="card-body">
        @php
            $criterias = \App\Models\Criteria::orderBy('order')->get();
        @endphp
        @if($criterias->count() > 0)
            <div class="row">
                <div class="col-md-8">
                    <form method="GET" action="{{ route('admin.criteria.subcriteria.create', 'placeholder') }}" id="criteriaForm">
                        <div class="input-group">
                            <select class="form-select" name="criteria_id" id="criteriaSelect" onchange="changeCriteria()">
                                <option value="">-- Pilih Kriteria untuk Menambah Sub Kriteria --</option>
                                @foreach($criterias as $crit)
                                    <option value="{{ $crit->id }}">
                                        {{ $crit->code }} - {{ $crit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="text-center py-3">
                <p class="text-muted">Belum ada kriteria yang tersedia</p>
                <a href="{{ route('admin.criteria.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Kriteria
                </a>
            </div>
        @endif
    </div>
</div>
@endif

@if(isset($criterion))
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Tambah Sub Kriteria</h5>
                <small class="text-muted">Kriteria: {{ $criterion->name }} ({{ $criterion->code }})</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.criteria.subcriteria.store', $criterion->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Sub Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Sub Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" required 
                               placeholder="Contoh: {{ $criterion->code }}_1, {{ $criterion->code }}_2, dst">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Kode harus unik dan akan digunakan untuk identifikasi</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="order" class="form-label">Urutan</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" 
                               id="order" name="order" value="{{ old('order', 1) }}" min="1" required>
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Urutan tampil sub kriteria (1 = pertama)</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Catatan:</strong> Bobot akan dihitung secara otomatis melalui perbandingan berpasangan setelah semua sub kriteria ditambahkan.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.subcriteria.index', $criterion->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Navigation Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Ganti Kriteria</h6>
            </div>
            <div class="card-body">
                <p class="small mb-3">Ingin menambah sub kriteria untuk kriteria lain?</p>
                @php
                    $allCriterias = \App\Models\Criteria::orderBy('order')->get();
                @endphp
                <div class="d-grid gap-2">
                    @foreach($allCriterias as $crit)
                        @if($crit->id != $criterion->id)
                            <a href="{{ route('admin.criteria.subcriteria.create', $crit->id) }}" 
                               class="btn btn-outline-primary btn-sm text-start">
                                <small><strong>{{ $crit->code }}</strong> - {{ Str::limit($crit->name, 20) }}</small>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Kriteria</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Nama</td>
                        <td>: {{ $criterion->name }}</td>
                    </tr>
                    <tr>
                        <td>Kode</td>
                        <td>: {{ $criterion->code }}</td>
                    </tr>
                    <tr>
                        <td>Bobot</td>
                        <td>: {{ number_format($criterion->weight, 6) }}</td>
                    </tr>
                    <tr>
                        <td>Sub Kriteria</td>
                        <td>: {{ $criterion->subCriterias->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Panduan</h6>
            </div>
            <div class="card-body">
                <h6>Contoh Sub Kriteria untuk {{ $criterion->name }}:</h6>
                @if($criterion->code == 'C1')
                    <ul class="small">
                        <li><strong>Pekerjaan Ayah</strong> - {{ $criterion->code }}_1</li>
                        <li><strong>Pekerjaan Ibu</strong> - {{ $criterion->code }}_2</li>
                        <li><strong>Penghasilan Ayah</strong> - {{ $criterion->code }}_3</li>
                        <li><strong>Penghasilan Ibu</strong> - {{ $criterion->code }}_4</li>
                        <li><strong>Jumlah Tanggungan</strong> - {{ $criterion->code }}_5</li>
                        <li><strong>Kepemilikan Hutang</strong> - {{ $criterion->code }}_6</li>
                        <li><strong>Pendidikan Orang Tua</strong> - {{ $criterion->code }}_7</li>
                    </ul>
                @elseif($criterion->code == 'C2')
                    <ul class="small">
                        <li><strong>Dinding</strong> - {{ $criterion->code }}_1</li>
                        <li><strong>Lantai</strong> - {{ $criterion->code }}_2</li>
                        <li><strong>Atap</strong> - {{ $criterion->code }}_3</li>
                        <li><strong>Status Kepemilikan</strong> - {{ $criterion->code }}_4</li>
                        <li><strong>Luas Rumah</strong> - {{ $criterion->code }}_5</li>
                        <li><strong>Jumlah Kamar</strong> - {{ $criterion->code }}_6</li>
                        <li><strong>Orang per Kamar</strong> - {{ $criterion->code }}_7</li>
                    </ul>
                @else
                    <ul class="small">
                        <li>Sesuaikan dengan karakteristik kriteria</li>
                        <li>Gunakan pola kode: {{ $criterion->code }}_1, {{ $criterion->code }}_2, dst</li>
                    </ul>
                @endif
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Gunakan nama yang jelas dan spesifik</li>
                    <li>Kode sebaiknya mengikuti pola induk</li>
                    <li>Urutan menentukan tampilan di form</li>
                    <li>Bobot akan dihitung otomatis melalui perbandingan berpasangan</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-arrow-up fa-3x text-muted mb-3"></i>
        <p class="text-muted">Pilih kriteria terlebih dahulu untuk menambah sub kriteria</p>
        <a href="{{ route('admin.subcriteria.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kelola Sub Kriteria
        </a>
    </div>
</div>
@endif

<script>
function changeCriteria() {
    const select = document.getElementById('criteriaSelect');
    const criteriaId = select.value;
    
    if (criteriaId) {
        // Redirect to the create page for selected criteria
        window.location.href = `{{ route('admin.criteria.subcriteria.create', '') }}/${criteriaId}`;
    }
}
</script>

@endsection