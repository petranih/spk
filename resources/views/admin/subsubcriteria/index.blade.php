@extends('layouts.app')

@section('title', 'Kelola Sub Sub Kriteria')
@section('page-title', 'Sub Sub Kriteria - ' . $subCriteria->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4>Sub Sub Kriteria: {{ $subCriteria->name }}</h4>
        <small class="text-muted">{{ $subCriteria->criteria->name }} → {{ $subCriteria->code }}</small>
    </div>
    <div>
        <a href="{{ route('admin.criteria.subcriteria.index', $subCriteria->criteria_id) }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Sub Kriteria
        </a>
<a href="{{ route('admin.criteria.subcriteria.create', ['criteria' => $criteria->id]) }}" class="btn btn-primary">
    Tambah Sub Kriteria
</a>

    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($subSubCriterias->count() > 0)
            <div class="table-responsive">
                <table class="table table-datatable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Sub Sub Kriteria</th>
                            <th>Bobot</th>
                            <th>Skor</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subSubCriterias as $index => $subSubCriteria)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $subSubCriteria->code }}</code></td>
                            <td>{{ $subSubCriteria->name }}</td>
                            <td>{{ number_format($subSubCriteria->weight, 6) }}</td>
                            <td>
                                <span class="badge bg-info">{{ number_format($subSubCriteria->score, 3) }}</span>
                            </td>
                            <td>{{ $subSubCriteria->order }}</td>
                            <td>
                                @if($subSubCriteria->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.subcriteria.subsubcriteria.edit', [$subCriteria->id, $subSubCriteria->id]) }}" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.subcriteria.subsubcriteria.destroy', [$subCriteria->id, $subSubCriteria->id]) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus sub sub kriteria ini?')">
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

            @if($subSubCriterias->count() >= 2)
                <div class="mt-4">
                    <a href="{{ route('admin.pairwise.subsubcriteria', $subCriteria->id) }}" class="btn btn-success">
                        <i class="fas fa-balance-scale me-2"></i>
                        Perbandingan Berpasangan Sub Sub Kriteria
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada sub sub kriteria yang ditambahkan untuk <strong>{{ $subCriteria->name }}</strong></p>
                <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subCriteria->id) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Sub Sub Kriteria Pertama
                </a>
            </div>
        @endif
    </div>
</div>

@if($subSubCriterias->count() > 0)
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Preview Opsi untuk Siswa</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">Berikut adalah opsi yang akan muncul di form aplikasi siswa:</p>
            <div class="form-group">
                <label class="form-label"><strong>{{ $subCriteria->name }}</strong></label>
@if($subSubCriterias->count() > 0)
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Preview Opsi untuk Siswa</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">Berikut adalah opsi yang akan muncul di form aplikasi siswa:</p>
            <div class="form-group">
                <label class="form-label"><strong>{{ $subCriteria->name }}</strong></label>
                <select class="form-select" disabled>
                    <option value="">-- Pilih {{ $subCriteria->name }} --</option>
                    @foreach($subSubCriterias->sortBy('order') as $subSubCriteria)
                        <option value="{{ $subSubCriteria->id }}">
                            {{ $subSubCriteria->name }} (Skor: {{ number_format($subSubCriteria->score, 3) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Siswa akan memilih salah satu opsi ini, dan sistem akan otomatis menggunakan skor yang sudah ditetapkan.
            </small>
        </div>
    </div>
@endif
@endsection