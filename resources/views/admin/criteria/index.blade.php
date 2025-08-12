{{-- resources/views/admin/criteria/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kelola Kriteria')
@section('page-title', 'Kelola Kriteria')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Daftar Kriteria</h4>
    <a href="{{ route('admin.criteria.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Kriteria
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($criterias->count() > 0)
            <div class="table-responsive">
                <table class="table table-datatable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Kriteria</th>
                            <th>Bobot</th>
                            <th>Urutan</th>
                            <th>Sub Kriteria</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criterias as $index => $criteria)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $criteria->code }}</code></td>
                            <td>{{ $criteria->name }}</td>
                            <td>{{ number_format($criteria->weight, 6) }}</td>
                            <td>{{ $criteria->order }}</td>
                            <td>
                                <span class="badge bg-info">{{ $criteria->subCriterias->count() }} Sub</span>
                            </td>
                            <td>
                                @if($criteria->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.criteria.show', $criteria->id) }}" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.criteria.edit', $criteria->id) }}" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.criteria.destroy', $criteria->id) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
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
        @else
            <div class="text-center py-5">
                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada kriteria yang ditambahkan</p>
                <a href="{{ route('admin.criteria.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Kriteria Pertama
                </a>
            </div>
        @endif
    </div>
</div>
@endsection