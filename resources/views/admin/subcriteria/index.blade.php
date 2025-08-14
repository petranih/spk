@extends('layouts.app')

@section('title', 'Kelola Sub Kriteria')
@section('page-title', 'Sub Kriteria' . ($criterion ? ' - ' . $criterion->name : ''))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4>Kelola Sub Kriteria</h4>
        @if($criterion)
            <small class="text-muted">Kriteria: {{ $criterion->name }} ({{ $criterion->code }})</small>
        @endif
    </div>
    <div>
        <a href="{{ route('admin.criteria.index') }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Kriteria
        </a>
        @if($criterion)
            <a href="{{ route('admin.criteria.subcriteria.create', $criterion->id) }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Sub Kriteria
            </a>
        @endif
    </div>
</div>

<!-- Criteria Selection Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Pilih Kriteria</h5>
    </div>
    <div class="card-body">
        @if($criterias->count() > 0)
            <div class="row">
                <div class="col-md-8">
                    <form method="GET" action="{{ route('admin.criteria.subcriteria.index', 'placeholder') }}" id="criteriaForm">
                        <div class="input-group">
                            <select class="form-select" name="criteria_id" id="criteriaSelect" onchange="changeCriteria()">
                                <option value="">-- Pilih Kriteria --</option>
                                @foreach($criterias as $crit)
                                    <option value="{{ $crit->id }}" 
                                            {{ $criterion && $criterion->id == $crit->id ? 'selected' : '' }}>
                                        {{ $crit->code }} - {{ $crit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    @if($criterion)
                        <div class="bg-light p-2 rounded">
                            <strong>Bobot:</strong> {{ number_format($criterion->weight, 6) }}<br>
                            <strong>Sub Kriteria:</strong> {{ $criterion->subCriterias->count() }}
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Kriteria Cards (Alternative view) -->
            <div class="row mt-3">
                @foreach($criterias as $crit)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card {{ $criterion && $criterion->id == $crit->id ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <code>{{ $crit->code }}</code>
                                    @if($criterion && $criterion->id == $crit->id)
                                        <span class="badge bg-primary ms-2">Aktif</span>
                                    @endif
                                </h6>
                                <p class="card-text">{{ $crit->name }}</p>
                                <small class="text-muted">
                                    Bobot: {{ number_format($crit->weight, 6) }} | 
                                    {{ $crit->subCriterias->count() }} sub kriteria
                                </small>
                                <div class="mt-2">
                                    <a href="{{ route('admin.criteria.subcriteria.index', $crit->id) }}" 
                                       class="btn btn-sm {{ $criterion && $criterion->id == $crit->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $criterion && $criterion->id == $crit->id ? 'Aktif' : 'Pilih' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-3">
                <p class="text-muted">Belum ada kriteria yang tersedia</p>
                <a href="{{ route('admin.criteria.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Kriteria
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Subcriteria Table -->
@if($criterion)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sub Kriteria: {{ $criterion->name }}</h5>
            <a href="{{ route('admin.criteria.subcriteria.create', $criterion->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Tambah
            </a>
        </div>
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
                                        <a href="{{ route('admin.criteria.subcriteria.show', ['criterion' => $criterion->id, 'subcriterion' => $subCriteria->id]) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.criteria.subcriteria.edit', ['criterion' => $criterion->id, 'subcriterion' => $subCriteria->id]) }}" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.criteria.subcriteria.destroy', ['criterion' => $criterion->id, 'subcriterion' => $subCriteria->id]) }}" 
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
                        <a href="{{ route('admin.pairwise.subcriteria', $criterion->id) }}" class="btn btn-success">
                            <i class="fas fa-balance-scale me-2"></i>
                            Perbandingan Berpasangan Sub Kriteria
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada sub kriteria yang ditambahkan untuk <strong>{{ $criterion->name }}</strong></p>
                    <a href="{{ route('admin.criteria.subcriteria.create', $criterion->id) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Sub Kriteria Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-arrow-up fa-3x text-muted mb-3"></i>
            <p class="text-muted">Pilih kriteria terlebih dahulu untuk mengelola sub kriteria</p>
        </div>
    </div>
@endif

<script>
function changeCriteria() {
    const select = document.getElementById('criteriaSelect');
    const criteriaId = select.value;
    
    if (criteriaId) {
        // Redirect to the selected criteria
        window.location.href = `{{ route('admin.criteria.subcriteria.index', '') }}/${criteriaId}`;
    } else {
        // Redirect to index without criteria
        window.location.href = `{{ route('admin.criteria.subcriteria.index', 'placeholder') }}`.replace('/placeholder', '');
    }
}
</script>

@endsection