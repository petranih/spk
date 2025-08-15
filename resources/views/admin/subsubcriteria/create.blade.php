@extends('layouts.app')

@section('title', 'Tambah Sub Sub Kriteria')
@section('page-title', 'Tambah Sub Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Tambah Sub Sub Kriteria</h5>
                <small class="text-muted">{{ $subcriterion->criteria->name }} → {{ $subcriterion->name }} ({{ $subcriterion->code }})</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.subcriteria.subsubcriteria.store', $subcriterion->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" required 
                               placeholder="Contoh: {{ $subcriterion->code }}_1, {{ $subcriterion->code }}_2, dst">
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
                        <div class="form-text">Urutan tampil sub sub kriteria (1 = pertama)</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Catatan:</strong> Skor dan bobot akan dihitung secara otomatis melalui perbandingan berpasangan setelah semua sub sub kriteria ditambahkan.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.subsubcriteria.index', $subcriterion->id) }}" class="btn btn-secondary">
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
                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Ganti Sub Kriteria</h6>
            </div>
            <div class="card-body">
                <p class="small mb-3">Ingin menambah sub sub kriteria untuk sub kriteria lain?</p>
                @php
                    $allSubCriterias = \App\Models\SubCriteria::with('criteria')->orderBy('order')->get();
                @endphp
                <div class="d-grid gap-2">
                    @foreach($allSubCriterias as $subCrit)
                        @if($subCrit->id != $subcriterion->id)
                            <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subCrit->id) }}" 
                               class="btn btn-outline-primary btn-sm text-start">
                                <small><strong>{{ $subCrit->code }}</strong> - {{ Str::limit($subCrit->name, 15) }}</small>
                                <br><small class="text-muted">{{ $subCrit->criteria->name }}</small>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Sub Kriteria</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Nama</td>
                        <td>: {{ $subcriterion->name }}</td>
                    </tr>
                    <tr>
                        <td>Kode</td>
                        <td>: {{ $subcriterion->code }}</td>
                    </tr>
                    <tr>
                        <td>Kriteria</td>
                        <td>: {{ $subcriterion->criteria->name }}</td>
                    </tr>
                    <tr>
                        <td>Sub Sub Kriteria</td>
                        <td>: {{ $subcriterion->subSubCriterias->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Panduan</h6>
            </div>
            <div class="card-body">
                <h6>Contoh Sub Sub Kriteria untuk {{ $subcriterion->name }}:</h6>
                @if(str_contains(strtolower($subcriterion->name), 'pekerjaan'))
                    <ul class="small">
                        <li><strong>PNS/ASN</strong> - {{ $subcriterion->code }}_1</li>
                        <li><strong>Pegawai Swasta</strong> - {{ $subcriterion->code }}_2</li>
                        <li><strong>Wirausaha</strong> - {{ $subcriterion->code }}_3</li>
                        <li><strong>Petani/Nelayan</strong> - {{ $subcriterion->code }}_4</li>
                        <li><strong>Buruh</strong> - {{ $subcriterion->code }}_5</li>
                        <li><strong>Tidak Bekerja</strong> - {{ $subcriterion->code }}_6</li>
                    </ul>
                @elseif(str_contains(strtolower($subcriterion->name), 'penghasilan'))
                    <ul class="small">
                        <li><strong>> 5 Juta</strong> - {{ $subcriterion->code }}_1</li>
                        <li><strong>3-5 Juta</strong> - {{ $subcriterion->code }}_2</li>
                        <li><strong>1-3 Juta</strong> - {{ $subcriterion->code }}_3</li>
                        <li><strong>< 1 Juta</strong> - {{ $subcriterion->code }}_4</li>
                        <li><strong>Tidak Ada</strong> - {{ $subcriterion->code }}_5</li>
                    </ul>
                @elseif(str_contains(strtolower($subcriterion->name), 'hutang'))
                    <ul class="small">
                        <li><strong>Tidak Ada Hutang</strong> - {{ $subcriterion->code }}_1</li>
                        <li><strong>Hutang Sedikit</strong> - {{ $subcriterion->code }}_2</li>
                        <li><strong>Hutang Banyak</strong> - {{ $subcriterion->code }}_3</li>
                    </ul>
                @elseif(str_contains(strtolower($subcriterion->name), 'dinding'))
                    <ul class="small">
                        <li><strong>Tembok</strong> - {{ $subcriterion->code }}_1</li>
                        <li><strong>Semi Tembok</strong> - {{ $subcriterion->code }}_2</li>
                        <li><strong>Papan</strong> - {{ $subcriterion->code }}_3</li>
                        <li><strong>Bambu</strong> - {{ $subcriterion->code }}_4</li>
                    </ul>
                @else
                    <ul class="small">
                        <li>Sesuaikan dengan karakteristik {{ $subcriterion->name }}</li>
                        <li>Gunakan pola kode: {{ $subcriterion->code }}_1, {{ $subcriterion->code }}_2, dst</li>
                    </ul>
                @endif
                
                <hr>
                
                <h6>Tips:</h6>
                <ul class="small">
                    <li>Gunakan nama yang jelas dan mudah dipahami siswa</li>
                    <li>Kode sebaiknya mengikuti pola sub kriteria induk</li>
                    <li>Urutan menentukan tampilan di form aplikasi</li>
                    <li>Skor akan dihitung otomatis melalui perbandingan berpasangan</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection