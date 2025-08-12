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
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.criteria.index') }}">
                                        <i class="ni ni-bullet-list-67 text-primary"></i> Kriteria
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.criteria.subcriteria.index', 1) }}">
                                        <i class="ni ni-collection text-orange"></i> Sub Kriteria
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.subcriteria.subsubcriteria.index', 1) }}">
                                        <i class="ni ni-archive-2 text-yellow"></i> Sub-Sub Kriteria
                                    </a>
                                </li>
                                <a class="nav-link {{ request()->routeIs('admin.pairwise*') ? 'active' : '' }}" href="{{ route('admin.pairwise.criteria') }}">
                                    <i class="fas fa-balance-scale me-2"></i> Perbandingan AHP
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.period*') ? 'active' : '' }}" href="{{ route('admin.period.index') }}">
                                    <i class="fas fa-calendar me-2"></i> Periode
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.student*') ? 'active' : '' }}" href="{{ route('admin.student.index') }}">
                                    <i class="fas fa-users me-2"></i> Siswa
                                </a>
                                <a class="nav-link {{ request()->routeIs('admin.report*') ? 'active' : '' }}" href="{{ route('admin.report.index') }}">
                                    <i class="fas fa-chart-bar me-2"></i> Laporan
                                </a>
                            @elseif(Auth::user()->isValidator())
                                <a class="nav-link {{ request()->routeIs('validator.dashboard') ? 'active' : '' }}" href="{{ route('validator.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="nav-link {{ request()->routeIs('validator.validation*') ? 'active' : '' }}" href="{{ route('validator.validation.index') }}">
                                    <i class="fas fa-check-circle me-2"></i> Validasi
                                </a>
                            @elseif(Auth::user()->isStudent())
                                <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="nav-link {{ request()->routeIs('student.application*') ? 'active' : '' }}" href="{{ route('student.application.create') }}">
                                    <i class="fas fa-file-alt me-2"></i> Permohonan
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
                            <div class="navbar-nav ms-auto">
                                <span class="navbar-text">
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ Auth::user()->name }}
                                </span>
                            </div>
                        </div>
                    </nav>
                    
                    <div class="container-fluid p-4">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
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
                                <ul class="mb-0">
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
            $('.table-datatable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                }
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>