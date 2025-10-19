{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Beasiswa AHP')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .main-content {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .navbar-brand {
            font-weight: bold;
            color: #667eea !important;
        }
        
        /* Dropdown menu styling - FIXED */
        .sidebar .dropdown-menu {
            background: rgba(255,255,255,0.95); /* Lebih opaque untuk kontras yang lebih baik */
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            min-width: 250px; /* Lebar minimum untuk teks yang panjang */
        }
        
        .sidebar .dropdown-item {
            color: #495057; /* Warna gelap untuk kontras yang baik */
            padding: 8px 16px;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar .dropdown-item:hover,
        .sidebar .dropdown-item:focus {
            background: rgba(102, 126, 234, 0.1); /* Background hover dengan warna primary */
            color: #667eea; /* Warna primary untuk hover */
        }
        
        .sidebar .dropdown-item.active {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            font-weight: 500;
        }
        
        /* Styling untuk dropdown header */
        .sidebar .dropdown-header {
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 8px 16px 4px 16px;
        }
        
        /* Styling untuk dropdown divider */
        .sidebar .dropdown-divider {
            border-color: rgba(108, 117, 125, 0.2);
            margin: 4px 0;
        }
        
        /* Perbaikan untuk dropdown toggle */
        .sidebar .dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .sidebar .dropdown-toggle::after {
            margin-left: auto;
            margin-top: 0;
        }
        
        /* Untuk mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar .dropdown-menu {
                position: static !important;
                float: none !important;
                width: 100% !important;
                margin-top: 0 !important;
                background: rgba(255,255,255,0.1);
                border: none;
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);
            }
            
            .sidebar .dropdown-item {
                color: rgba(255,255,255,0.9);
                padding-left: 2rem;
            }
            
            .sidebar .dropdown-item:hover {
                background: rgba(255,255,255,0.1);
                color: white;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            @auth
                <!-- Sidebar -->
                <div class="col-md-2 px-0">
                    <div class="sidebar p-3">
                        <div class="text-center mb-4">
                            <h4 class="text-white">Sistem Beasiswa</h4>
                            <small class="text-white-50">AHP Method</small>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-white-50 text-uppercase">{{ Auth::user()->role }}</small>
                            <div class="text-white">{{ Auth::user()->name }}</div>
                        </div>
                        
                        <hr class="text-white-50">
                        
                        <nav class="nav flex-column">
                            @if(Auth::user()->isAdmin())
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                
                                {{-- Criteria Management Dropdown --}}
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.criteria*') || request()->routeIs('admin.subcriteria*') || request()->routeIs('admin.subsubcriteria*') ? 'active' : '' }}" 
                                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-layer-group me-2"></i> Kelola Kriteria
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item {{ request()->routeIs('admin.criteria*') ? 'active' : '' }}" href="{{ route('admin.criteria.index') }}">
                                            <i class="fas fa-list me-2"></i> Kriteria Utama
                                        </a></li>
                                        <li><a class="dropdown-item {{ request()->routeIs('admin.subcriteria*') ? 'active' : '' }}" href="{{ route('admin.subcriteria.index') }}">
                                            <i class="fas fa-list-ul me-2"></i> Sub Kriteria
                                        </a></li>
                                        <li><a class="dropdown-item {{ request()->routeIs('admin.subsubcriteria*') ? 'active' : '' }}" href="{{ route('admin.subsubcriteria.index') }}">
                                            <i class="fas fa-indent me-2"></i> Sub-Sub Kriteria
                                        </a></li>
                                    </ul>
                                </div>
                                
                                {{-- AHP Pairwise Comparison Dropdown --}}
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.pairwise*') ? 'active' : '' }}" 
                                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-balance-scale me-2"></i> Perbandingan AHP
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.pairwise.index') }}">
                                            <i class="fas fa-home me-2"></i> Overview AHP
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.pairwise.criteria') }}">
                                            <i class="fas fa-layer-group me-2"></i> Kriteria Utama
                                        </a></li>
                                        
                                        {{-- Dynamic Sub Criteria Links --}}
                                        @php
                                            $criteriaWithSubs = \App\Models\Criteria::active()
                                                ->whereHas('subCriterias', function($query) {
                                                    $query->active();
                                                })
                                                ->with(['subCriterias' => function($query) {
                                                    $query->active();
                                                }])
                                                ->get();
                                        @endphp
                                        
                                        @if($criteriaWithSubs->count() > 0)
                                            <li><hr class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header">Sub Kriteria</h6></li>
                                            @foreach($criteriaWithSubs as $criteria)
                                                @if($criteria->subCriterias->count() >= 2)
                                                    <li><a class="dropdown-item" href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}">
                                                        <i class="fas fa-list me-2"></i> {{ $criteria->code }} - Sub Kriteria
                                                    </a></li>
                                                @endif
                                            @endforeach
                                        @endif
                                        
                                        {{-- Dynamic Sub-Sub Criteria Links --}}
                                        @php
                                            $subCriteriaWithSubSubs = \App\Models\SubCriteria::active()
                                                ->whereHas('subSubCriterias', function($query) {
                                                    $query->active();
                                                })
                                                ->whereHas('criteria', function($query) {
                                                    $query->active();
                                                })
                                                ->with(['subSubCriterias' => function($query) {
                                                    $query->active();
                                                }, 'criteria'])
                                                ->get();
                                        @endphp
                                        
                                        @if($subCriteriaWithSubSubs->count() > 0)
                                            <li><hr class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header">Sub-Sub Kriteria</h6></li>
                                            @foreach($subCriteriaWithSubSubs as $subCriteria)
                                                @if($subCriteria->subSubCriterias->count() >= 2)
                                                    <li><a class="dropdown-item" href="{{ route('admin.pairwise.subsubcriteria', $subCriteria->id) }}">
                                                        <i class="fas fa-list-ul me-2"></i> {{ $subCriteria->criteria->code }} → {{ $subCriteria->code }}
                                                    </a></li>
                                                @endif
                                            @endforeach
                                        @endif
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.pairwise.consistency.overview') }}">
                                            <i class="fas fa-chart-line me-2"></i> Status Konsistensi
                                        </a></li>
                                    </ul>
                                </div>
                                <a href="{{ route('admin.scoring.index') }}" class="nav-link {{ request()->routeIs('admin.scoring.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Perhitungan Skor</p>
                    </a>
                                
                                <a class="nav-link {{ request()->routeIs('admin.period*') ? 'active' : '' }}" href="{{ route('admin.period.index') }}">
                                    <i class="fas fa-calendar me-2"></i> Periode Beasiswa
                                </a>
                                
                            @elseif(Auth::user()->isValidator())
                                <a class="nav-link {{ request()->routeIs('validator.dashboard') ? 'active' : '' }}" href="{{ route('validator.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="nav-link {{ request()->routeIs('validator.validation*') ? 'active' : '' }}" href="{{ route('validator.validation.index') }}">
                                    <i class="fas fa-check-circle me-2"></i> Validasi Berkas
                                </a>
                                
                            @elseif(Auth::user()->isStudent())
                                <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="nav-link {{ request()->routeIs('student.application*') ? 'active' : '' }}" href="{{ route('student.application.create') }}">
                                    <i class="fas fa-file-alt me-2"></i> Ajukan Beasiswa
                                </a>
                            @endif
                        </nav>
                        
                        <hr class="text-white-50 mt-auto">
                        
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm w-100">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-md-10 main-content">
                    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="#">@yield('page-title', 'Dashboard')</a>
                            
                            {{-- Breadcrumb Navigation if exists --}}
                            @if(isset($breadcrumbs) && !empty($breadcrumbs))
                                <nav aria-label="breadcrumb" class="me-auto ms-3">
                                    <ol class="breadcrumb mb-0">
                                        @foreach($breadcrumbs as $breadcrumb)
                                            @if($loop->last)
                                                <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
                                            @else
                                                <li class="breadcrumb-item">
                                                    <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ol>
                                </nav>
                            @endif
                            
                            <div class="navbar-nav ms-auto">
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user-circle me-1"></i>
                                        {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><h6 class="dropdown-header">{{ Auth::user()->email }}</h6></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                    
                    <div class="container-fluid p-4">
                        {{-- Alert Messages --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @if(session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ session('warning') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @if(session('info'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ session('info') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Terdapat kesalahan:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @yield('content')
                    </div>
                </div>
            @else
                <!-- Guest Content -->
                <div class="col-12">
                    @yield('content')
                </div>
            @endauth
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('.table-datatable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            });
            
            // Auto-close alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // CSRF Token setup for AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        });
        
        // Confirmation dialogs
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus item ini?') {
            return confirm(message);
        }
        
        function confirmAction(message = 'Apakah Anda yakin ingin melakukan tindakan ini?') {
            return confirm(message);
        }
    </script>
    
    @stack('scripts')
</body>
</html>