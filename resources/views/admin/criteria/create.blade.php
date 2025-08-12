@extends('layouts.app')

@section('title', 'Tambah Kriteria')
@section('page-title', 'Tambah Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Tambah Kriteria</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.criteria.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" required 
                               placeholder="Contoh: C1, EKONOMI, dst">
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
                        <div class="form-text">Urutan tampil kriteria (1 = pertama)</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.criteria.index') }}" class="btn btn-secondary">
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
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Panduan</h6>
            </div>
            <div class="card-body">
                <h6>Contoh Kriteria Beasiswa:</h6>
                <ul class="small">
                    <li><strong>Kondisi Ekonomi</strong> - C1</li>
                    <li><strong>Kondisi Rumah</strong> - C2</li>
                    <li><strong>Kepemilikan Aset</strong> - C3</li>
                    <li><strong>Fasilitas Rumah</strong> - C4</li>
                    <li><strong>Status Penerimaan Bantuan</strong> - C5</li>
                </ul>
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Gunakan nama yang jelas dan mudah dipahami</li>
                    <li>Kode kriteria sebaiknya singkat dan konsisten</li>
                    <li>Urutan menentukan tampilan di form aplikasi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection