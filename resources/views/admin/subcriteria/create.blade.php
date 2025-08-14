@extends('layouts.app')

@section('title', 'Tambah Sub Kriteria')
@section('page-title', 'Tambah Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Tambah Sub Kriteria</h5>
                <small class="text-muted">Kriteria: {{ $criterion->name }} ({{ $criterion->code }})</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.criteria.subcriteria.create', $criterion->id) }}" method="POST">
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
                               placeholder="Contoh: C1_1, PEKERJAAN_AYAH, dst">
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
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.criteria.subcriteria.index', $criterion->id) }}" class="btn btn-secondary">
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
                        <li><strong>Pekerjaan Ayah</strong> - C1_1</li>
                        <li><strong>Pekerjaan Ibu</strong> - C1_2</li>
                        <li><strong>Penghasilan Ayah</strong> - C1_3</li>
                        <li><strong>Penghasilan Ibu</strong> - C1_4</li>
                        <li><strong>Jumlah Tanggungan</strong> - C1_5</li>
                        <li><strong>Kepemilikan Hutang</strong> - C1_6</li>
                        <li><strong>Pendidikan Orang Tua</strong> - C1_7</li>
                    </ul>
                @elseif($criterion->code == 'C2')
                    <ul class="small">
                        <li><strong>Dinding</strong> - C2_1</li>
                        <li><strong>Lantai</strong> - C2_2</li>
                        <li><strong>Atap</strong> - C2_3</li>
                        <li><strong>Status Kepemilikan</strong> - C2_4</li>
                        <li><strong>Luas Rumah</strong> - C2_5</li>
                        <li><strong>Jumlah Kamar</strong> - C2_6</li>
                        <li><strong>Orang per Kamar</strong> - C2_7</li>
                    </ul>
                @else
                    <p class="small text-muted">Sesuaikan dengan kebutuhan kriteria</p>
                @endif
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Gunakan nama yang jelas dan spesifik</li>
                    <li>Kode sebaiknya mengikuti pola induk</li>
                    <li>Urutan menentukan tampilan di form</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection