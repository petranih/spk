@extends('layouts.app')

@section('title', 'Detail Sub Kriteria')
@section('page-title', 'Detail Sub Kriteria')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $subCriteria->name }}</h5>
                <small class="text-muted">{{ $criteria->name }} → {{ $subCriteria->code }}</small>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Nama Sub Kriteria</th>
                        <td>{{ $subCriteria->name }}</td>
                    </tr>
                    <tr>
                        <th>Kode</th>
                        <td><code>{{ $subCriteria->code }}</code></td>
                    </tr>
                    <tr>
                        <th>Deskripsi</th>
                        <td>{{ $subCriteria->description ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Bobot</th>
                        <td>{{ number_format($subCriteria->weight, 6) }}</td>
                    </tr>
                    <tr>
                        <th>Urutan</th>
                        <td>{{ $subCriteria->order }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($subCriteria->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Tidak Aktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Sub Sub Kriteria</th>
                        <td>{{ $subCriteria->subSubCriterias->count() }} item</td>
                    </tr>
                </table>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.criteria.subcriteria.edit', [$criteria->id, $subCriteria->id]) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subCriteria->id) }}" class="btn btn-info">
                        <i class="fas fa-list me-2"></i>Kelola Sub Sub Kriteria
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Sub Sub Kriteria</h6>
            </div>
            <div class="card-body">
                @if($subCriteria->subSubCriterias->count() > 0)
                    <div class="list-group">
                        @foreach($subCriteria->subSubCriterias as $subSubCriteria)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $subSubCriteria->name }}</strong>
                                <br><small class="text-muted">{{ $subSubCriteria->code }}</small>
                            </div>
                            <span class="badge bg-primary">{{ number_format($subSubCriteria->score, 3) }}</span>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-3 d-grid">
                        <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subCriteria->id) }}" class="btn btn-info">
                            <i class="fas fa-cogs me-2"></i>Kelola Semua
                        </a>
                    </div>
                @else
                    <p class="text-muted text-center">Belum ada sub sub kriteria</p>
                    <div class="d-grid">
                        <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subCriteria->id) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Sub Sub Kriteria
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Navigasi</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.criteria.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>Semua Kriteria
                    </a>
                    <a href="{{ route('admin.criteria.subcriteria.index', $criteria->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Sub Kriteria
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection