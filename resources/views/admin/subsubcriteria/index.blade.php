@extends('layouts.app')

@section('title', 'Kelola Sub Sub Kriteria')
@section('page-title', 'Sub Sub Kriteria' . ($subcriterion ? ' - ' . $subcriterion->name : ''))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4>Kelola Sub Sub Kriteria</h4>
        @if($subcriterion)
            <small class="text-muted">{{ $subcriterion->criteria->name }} → {{ $subcriterion->name }} ({{ $subcriterion->code }})</small>
        @endif
    </div>
    <div>
        <a href="{{ route('admin.subcriteria.index') }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Sub Kriteria
        </a>
        @if($subcriterion)
            <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subcriterion->id) }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Sub Sub Kriteria
            </a>
        @endif
    </div>
</div>

<!-- Sub Criteria Selection Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Pilih Sub Kriteria</h5>
    </div>
    <div class="card-body">
        @if($subCriterias->count() > 0)
            <div class="row">
                <div class="col-md-8">
                    <form method="GET" action="{{ route('admin.subsubcriteria.index') }}" id="subCriteriaForm">
                        <div class="input-group">
                            <select class="form-select" name="subcriteria_id" id="subCriteriaSelect" onchange="changeSubCriteria()">
                                <option value="">-- Pilih Sub Kriteria --</option>
                                @foreach($subCriterias as $subCrit)
                                    <option value="{{ $subCrit->id }}" 
                                            {{ $subcriterion && $subcriterion->id == $subCrit->id ? 'selected' : '' }}>
                                        {{ $subCrit->criteria->code }} → {{ $subCrit->code }} - {{ $subCrit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    @if($subcriterion)
                        <div class="bg-light p-2 rounded">
                            <strong>Kriteria:</strong> {{ $subcriterion->criteria->name }}<br>
                            <strong>Bobot:</strong> {{ number_format($subcriterion->weight, 6) }}<br>
                            <strong>Sub Sub Kriteria:</strong> {{ $subcriterion->subSubCriterias->count() }}
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Sub Criteria Cards (Alternative view) -->
            <div class="row mt-3">
                @foreach($subCriterias->groupBy('criteria.name') as $criteriaName => $subCritsByGroup)
                    <div class="col-12">
                        <h6 class="text-muted mb-2">{{ $criteriaName }}</h6>
                        <div class="row mb-3">
                            @foreach($subCritsByGroup as $subCrit)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card {{ $subcriterion && $subcriterion->id == $subCrit->id ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <code>{{ $subCrit->code }}</code>
                                                @if($subcriterion && $subcriterion->id == $subCrit->id)
                                                    <span class="badge bg-primary ms-2">Aktif</span>
                                                @endif
                                            </h6>
                                            <p class="card-text">{{ $subCrit->name }}</p>
                                            <small class="text-muted">
                                                Bobot: {{ number_format($subCrit->weight, 6) }} | 
                                                {{ $subCrit->subSubCriterias->count() }} sub sub kriteria
                                            </small>
                                            <div class="mt-2">
                                                <a href="{{ route('admin.subsubcriteria.index', $subCrit->id) }}" 
                                                   class="btn btn-sm {{ $subcriterion && $subcriterion->id == $subCrit->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                                    {{ $subcriterion && $subcriterion->id == $subCrit->id ? 'Aktif' : 'Pilih' }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-3">
                <p class="text-muted">Belum ada sub kriteria yang tersedia</p>
                <a href="{{ route('admin.criteria.subcriteria.index') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Kelola Sub Kriteria
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Sub Sub Criteria Table -->
@if($subcriterion)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sub Sub Kriteria: {{ $subcriterion->name }}</h5>
            <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subcriterion->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Tambah
            </a>
        </div>
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
                                        <a href="{{ route('admin.subcriteria.subsubcriteria.show', ['subcriterion' => $subcriterion->id, 'subsubcriterion' => $subSubCriteria->id]) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.subcriteria.subsubcriteria.edit', ['subcriterion' => $subcriterion->id, 'subsubcriterion' => $subSubCriteria->id]) }}" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.subcriteria.subsubcriteria.destroy', ['subcriterion' => $subcriterion->id, 'subsubcriterion' => $subSubCriteria->id]) }}" 
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
                        <a href="{{ route('admin.pairwise.subsubcriteria', $subcriterion->id) }}" class="btn btn-success">
                            <i class="fas fa-balance-scale me-2"></i>
                            Perbandingan Berpasangan Sub Sub Kriteria
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada sub sub kriteria yang ditambahkan untuk <strong>{{ $subcriterion->name }}</strong></p>
                    <a href="{{ route('admin.subcriteria.subsubcriteria.create', $subcriterion->id) }}" class="btn btn-primary">
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
                    <label class="form-label"><strong>{{ $subcriterion->name }}</strong></label>
                    <select class="form-select" disabled>
                        <option value="">-- Pilih {{ $subcriterion->name }} --</option>
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
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-arrow-up fa-3x text-muted mb-3"></i>
            <p class="text-muted">Pilih sub kriteria terlebih dahulu untuk mengelola sub sub kriteria</p>
        </div>
    </div>
@endif

<script>
function changeSubCriteria() {
    const select = document.getElementById('subCriteriaSelect');
    const subCriteriaId = select.value;
    
    if (subCriteriaId) {
        // Redirect to the selected sub criteria
        window.location.href = `{{ route('admin.subsubcriteria.index') }}/${subCriteriaId}`;
    } else {
        // Redirect to index without sub criteria
        window.location.href = `{{ route('admin.subsubcriteria.index') }}`;
    }
}
</script>

@endsection