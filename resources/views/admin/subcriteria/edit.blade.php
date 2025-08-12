@extends('layouts.app')

@section('title', 'Edit Sub Kriteria')
@section('page-title', 'Edit Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Edit Sub Kriteria</h5>
                <small class="text-muted">Kriteria: {{ $criteria->name }} ({{ $criteria->code }})</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.criteria.subcriteria.update', [$criteria->id, $subCriteria->id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Sub Kriteria</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $subCriteria->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Sub Kriteria</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $subCriteria->code) }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $subCriteria->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="order" class="form-label">Urutan</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" 
                               id="order" name="order" value="{{ old('order', $subCriteria->order) }}" min="1" required>
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $subCriteria->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktif
                            </label>
                        </div>
                        <div class="form-text">Sub kriteria tidak aktif tidak akan muncul dalam form aplikasi</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.criteria.subcriteria.index', $criteria->id) }}" class="btn btn-secondary">
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
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Sub Kriteria</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>ID</td>
                        <td>: {{ $subCriteria->id }}</td>
                    </tr>
                    <tr>
                        <td>Bobot Saat Ini</td>
                        <td>: {{ number_format($subCriteria->weight, 6) }}</td>
                    </tr>
                    <tr>
                        <td>Sub Sub Kriteria</td>
                        <td>: {{ $subCriteria->subSubCriterias->count() }}</td>
                    </tr>
                    <tr>
                        <td>Dibuat</td>
                        <td>: {{ $subCriteria->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td>Diperbarui</td>
                        <td>: {{ $subCriteria->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($subCriteria->subSubCriterias->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Sub Sub Kriteria</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid">
                        <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subCriteria->id) }}" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Lihat {{ $subCriteria->subSubCriterias->count() }} Sub Sub Kriteria
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection