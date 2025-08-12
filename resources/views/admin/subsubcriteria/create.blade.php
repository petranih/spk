@extends('layouts.app')

@section('title', 'Tambah Sub Sub Kriteria')
@section('page-title', 'Tambah Sub Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Tambah Sub Sub Kriteria</h5>
                <small class="text-muted">
                    {{ $subCriteria->criteria->name }} → {{ $subCriteria->name }} ({{ $subCriteria->code }})
                </small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.subcriteria.subsubcriteria.store', $subCriteria->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Contoh: "Petani", "PNS", "< Rp500.000", dll.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" required 
                               placeholder="Contoh: C1_1_1, PETANI, KURANG_500K, dst">
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order" class="form-label">Urutan</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror" 
                                       id="order" name="order" value="{{ old('order', 1) }}" min="1" required>
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Urutan tampil opsi (1 = pertama)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="score" class="form-label">Skor AHP</label>
                                <input type="number" class="form-control @error('score') is-invalid @enderror" 
                                       id="score" name="score" value="{{ old('score') }}" 
                                       step="0.000001" min="0" max="1" required>
                                @error('score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Skor prioritas AHP (0-1)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Catatan:</strong> Skor AHP akan otomatis dihitung berdasarkan perbandingan berpasangan. 
                        Anda bisa memasukkan skor sementara terlebih dahulu.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subCriteria->id) }}" class="btn btn-secondary">
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
                <h6 class="mb-0">Informasi Sub Kriteria</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Kriteria</td>
                        <td>: {{ $subCriteria->criteria->name }}</td>
                    </tr>
                    <tr>
                        <td>Sub Kriteria</td>
                        <td>: {{ $subCriteria->name }}</td>
                    </tr>
                    <tr>
                        <td>Kode</td>
                        <td>: {{ $subCriteria->code }}</td>
                    </tr>
                    <tr>
                        <td>Bobot</td>
                        <td>: {{ number_format($subCriteria->weight, 6) }}</td>
                    </tr>
                    <tr>
                        <td>Sub Sub Kriteria</td>
                        <td>: {{ $subCriteria->subSubCriterias->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Panduan</h6>
            </div>
            <div class="card-body">
                <h6>Contoh untuk "{{ $subCriteria->name }}":</h6>
                @if(str_contains(strtolower($subCriteria->name), 'pekerjaan'))
                    <ul class="small">
                        <li><strong>Petani</strong> - Skor: 0.406477</li>
                        <li><strong>Buruh</strong> - Skor: 0.25624</li>
                        <li><strong>PNS</strong> - Skor: 0.158276</li>
                        <li><strong>Wirausaha</strong> - Skor: 0.09695</li>
                        <li><strong>Tidak Bekerja</strong> - Skor: 0.082057</li>
                    </ul>
                @elseif(str_contains(strtolower($subCriteria->name), 'penghasilan'))
                    <ul class="small">
                        <li><strong>< Rp500.000</strong> - Skor: 0.513786</li>
                        <li><strong>Rp500.000-1.000.000</strong> - Skor: 0.297663</li>
                        <li><strong>Rp1.000.000-2.000.000</strong> - Skor: 0.118691</li>
                        <li><strong>> Rp2.000.000</strong> - Skor: 0.06986</li>
                    </ul>
                @elseif(str_contains(strtolower($subCriteria->name), 'tanggungan'))
                    <ul class="small">
                        <li><strong>1-2 orang</strong> - Skor: 0.607962</li>
                        <li><strong>3-4 orang</strong> - Skor: 0.272099</li>
                        <li><strong>5 orang atau lebih</strong> - Skor: 0.119939</li>
                    </ul>
                @else
                    <p class="small text-muted">Sesuaikan dengan opsi yang relevan untuk kriteria ini</p>
                @endif
                
                <hr>
                
                <h6>Tips Skor:</h6>
                <ul class="small">
                    <li>Skor tinggi = kondisi kurang mampu</li>
                    <li>Skor rendah = kondisi lebih mampu</li>
                    <li>Total skor semua opsi harus = 1.0</li>
                    <li>Gunakan perbandingan berpasangan untuk akurasi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection