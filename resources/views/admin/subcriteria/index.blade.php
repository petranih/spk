@extends('layouts.app')

@section('title', 'Kelola Sub Kriteria')
@section('page-title', 'Sub Kriteria - ' . $criterion->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4>Sub Kriteria: {{ $criterion->name }}</h4>
        <small class="text-muted">Kode: {{ $criterion->code }}</small>
    </div>
    <div>
        <a href="{{ route('admin.criteria.index') }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kriteria
        </a>
 
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($subCriterias->count() > 0)
            <div class="table-responsive">
                <table class="table table-datatable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Sub Kriteria</th>
                            <th>Bobot</th>
                            <th>Urutan</th>
                            <th>Sub Sub Kriteria</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subCriterias as $index => $subCriteria)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $subCriteria->code }}</code></td>
                            <td>{{ $subCriteria->name }}</td>
                            <td>{{ number_format($subCriteria->weight, 6) }}</td>
                            <td>{{ $subCriteria->order }}</td>
                            <td>
                                <span class="badge bg-info">{{ $subCriteria->subSubCriterias->count() }} Sub</span>
                                @if($subCriteria->subSubCriterias->count() > 0)
                                    <a href="{{ route('admin.subcriteria.subsubcriteria.index', $subCriteria->id) }}" 
                                       class="btn btn-sm btn-outline-info ms-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if($subCriteria->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.admin.criteria.subcriteria.show', ['criterion' => $criteria->id, 'subcriterion' => $subCriteria->id]) }}" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.admin.criteria.subcriteria.edit', ['criterion' => $criteria->id, 'subcriterion' => $subCriteria->id]) }}" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.admin.criteria.subcriteria.destroy', ['criterion' => $criteria->id, 'subcriterion' => $subCriteria->id]) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus sub kriteria ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($subCriterias->count() >= 2)
                <div class="mt-4">
                    <a href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}" class="btn btn-success">
                        <i class="fas fa-balance-scale me-2"></i>
                        Perbandingan Berpasangan Sub Kriteria
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada sub kriteria yang ditambahkan untuk <strong>{{ $criterion->name }}</strong></p>
                
            </div>
        @endif
    </div>
</div>
@endsection