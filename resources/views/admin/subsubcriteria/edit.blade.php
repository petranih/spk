@extends('layouts.app')

@section('title', 'Edit Sub Sub Kriteria')
@section('page-title', 'Edit Sub Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Edit Sub Sub Kriteria</h5>
                <small class="text-muted">
                    {{ $subcriterion->criteria->name }} → {{ $subcriterion->name }} → {{ $subsubcriterion->name }}
                </small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.subcriteria.subsubcriteria.update', [$subcriterion->id, $subsubcriterion->id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $subsubcriterion->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Sub Sub Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $subsubcriterion->code) }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $subsubcriterion->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="order" class="form-label">Urutan</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" 
                               id="order" name="order" value="{{ old('order', $subsubcriterion->order) }}" min="1" required>
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $subsubcriterion->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktif
                            </label>
                        </div>
                        <div class="form-text">Sub sub kriteria tidak aktif tidak akan muncul sebagai opsi pilihan</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subcriterion->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update
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
                <p class="small mb-3">Edit sub sub kriteria untuk sub kriteria lain:</p>
                @php
                    $allSubCriterias = \App\Models\SubCriteria::with('criteria')->orderBy('order')->get();
                @endphp
                <div class="d-grid gap-2">
                    @foreach($allSubCriterias as $subCrit)
                        @if($subCrit->id != $subcriterion->id)
                            <a href="{{ route('admin.subcriteria.subsubcriteria.edit', [$subCrit->id, $subsubcriterion->id]) }}" 
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
                <h6 class="mb-0">Informasi Sub Sub Kriteria</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>ID</td>
                        <td>: {{ $subsubcriterion->id }}</td>
                    </tr>
                    <tr>
                        <td>Bobot Saat Ini</td>
                        <td>: {{ number_format($subsubcriterion->weight, 6) }}</td>
                    </tr>
                    <tr>
                        <td>Dibuat</td>
                        <td>: {{ $subsubcriterion->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td>Diperbarui</td>
                        <td>: {{ $subsubcriterion->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Referensi dari Jurnal</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">Contoh opsi berdasarkan penelitian:</p>
                
                @if(str_contains(strtolower($subcriterion->name), 'pekerjaan'))
                    <table class="table table-sm">
                        <tr><td>Petani</td></tr>
                        <tr><td>Buruh</td></tr>
                        <tr><td>PNS</td></tr>
                        <tr><td>Wirausaha</td></tr>
                        <tr><td>Tidak Bekerja</td></tr>
                    </table>
                @elseif(str_contains(strtolower($subcriterion->name), 'hutang'))
                    <table class="table table-sm">
                        <tr><td>Tidak Punya Hutang</td></tr>
                        <tr><td>Punya Sedikit Hutang</td></tr>
                        <tr><td>Punya Banyak Hutang</td></tr>
                    </table>
                @else
                    <p class="small">Gunakan perbandingan berpasangan untuk menentukan prioritas yang akurat.</p>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Navigasi</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.criteria.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list me-2"></i>Semua Kriteria
                    </a>
                    <a href="{{ route('admin.subcriteria.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-layer-group me-2"></i>Sub Kriteria
                    </a>
                    <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subcriterion->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection