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
            transition: all 0.3s ease;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1040;
            overflow-y: auto;
            overflow-x: hidden;
            width: 250px;
        }
        
        .sidebar.collapsed {
            left: -250px;
        }
        
        .sidebar-wrapper {
            transition: all 0.3s ease;
            padding: 0;
        }
        
        .sidebar-wrapper.collapsed {
            margin-left: 0 !important;
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
            transition: margin-left 0.3s ease;
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
            margin-left: 0;
            font-size: 1.25rem;
        }
        
        /* Toggle Button */
        .sidebar-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            margin-right: 20px;
            flex-shrink: 0;
            position: relative;
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #7c8ef5 0%, #8a5db8 100%);
        }
        
        .sidebar-toggle:active {
            transform: scale(0.95);
        }
        
        /* Icon animation */
        .sidebar-toggle i {
            transition: transform 0.3s ease;
            font-size: 18px;
        }
        
        .sidebar-collapsed .sidebar-toggle i {
            transform: rotate(0deg);
        }
        
        .sidebar-toggle i {
            transform: rotate(0deg);
        }
        
        /* Dropdown menu styling */
        .sidebar .dropdown-menu {
            background: rgba(255,255,255,0.95);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            min-width: 250px;
        }
        
        .sidebar .dropdown-item {
            color: #495057;
            padding: 8px 16px;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }
        
        .sidebar .dropdown-item:hover,
        .sidebar .dropdown-item:focus {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .sidebar .dropdown-item.active {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            font-weight: 500;
        }
        
        /* Active indicator badge */
        .sidebar .dropdown-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 70%;
            background: #667eea;
            border-radius: 0 3px 3px 0;
        }
        
        /* Active dropdown indicator */
        .sidebar .dropdown-toggle.has-active-child {
            background-color: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar .dropdown-toggle.has-active-child::after {
            border-top-color: white;
        }
        
        .sidebar .dropdown-header {
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 8px 16px 4px 16px;
        }
        
        .sidebar .dropdown-divider {
            border-color: rgba(108, 117, 125, 0.2);
            margin: 4px 0;
        }
        
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
        
        /* Overlay untuk mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Responsive */
        @media (min-width: 769px) {
            .sidebar-wrapper {
                width: 250px;
                flex: 0 0 250px;
            }
            
            .sidebar-wrapper.collapsed {
                width: 0;
                flex: 0 0 0;
            }
            
            .main-content {
                flex: 1;
                max-width: 100%;
            }
        }
        
                    @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .sidebar-wrapper {
                width: 0 !important;
            }
            
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
            
            .sidebar .dropdown-item.active {
                background: rgba(255,255,255,0.2);
                color: white;
            }
            
            .sidebar .dropdown-item.active::before {
                background: white;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    @auth
        <!-- Overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    @endauth
    
    <div class="container-fluid p-0">
        <div class="row g-0">
            @auth
                <!-- Sidebar -->
                <div class="sidebar-wrapper" id="sidebarWrapper">
                    <div class="sidebar p-3" id="sidebar">
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
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.criteria*') || request()->routeIs('admin.subcriteria*') || request()->routeIs('admin.subsubcriteria*') ? 'has-active-child active' : '' }}" 
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
                                @php
                                    $isPairwiseActive = request()->routeIs('admin.pairwise*');
                                    $currentRoute = request()->route()->getName();
                                    // Get route parameters with fallback
                                    $currentCriteriaId = request()->route()->parameter('criteria');
                                    $currentSubCriteriaId = request()->route()->parameter('subcriteria');
                                @endphp
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ $isPairwiseActive ? 'has-active-child active' : '' }}" 
                                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-balance-scale me-2"></i> Perbandingan AHP
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item {{ $currentRoute == 'admin.pairwise.index' ? 'active' : '' }}" href="{{ route('admin.pairwise.index') }}">
                                            <i class="fas fa-home me-2"></i> Overview AHP
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item {{ $currentRoute == 'admin.pairwise.criteria' ? 'active' : '' }}" href="{{ route('admin.pairwise.criteria') }}">
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
                                                    @php
                                                        $isActiveSubCriteria = ($currentRoute == 'admin.pairwise.subcriteria' && $currentCriteriaId == $criteria->id);
                                                    @endphp
                                                    <li><a class="dropdown-item {{ $isActiveSubCriteria ? 'active' : '' }}" 
                                                           href="{{ route('admin.pairwise.subcriteria', $criteria->id) }}">
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
                                                    @php
                                                        $isActiveSubSubCriteria = ($currentRoute == 'admin.pairwise.subsubcriteria' && $currentSubCriteriaId == $subCriteria->id);
                                                    @endphp
                                                    <li><a class="dropdown-item {{ $isActiveSubSubCriteria ? 'active' : '' }}" 
                                                           href="{{ route('admin.pairwise.subsubcriteria', $subCriteria->id) }}">
                                                        <i class="fas fa-list-ul me-2"></i> {{ $subCriteria->criteria->code }} → {{ $subCriteria->code }}
                                                    </a></li>
                                                @endif
                                            @endforeach
                                        @endif
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item {{ $currentRoute == 'admin.pairwise.consistency.overview' ? 'active' : '' }}" href="{{ route('admin.pairwise.consistency.overview') }}">
                                            <i class="fas fa-chart-line me-2"></i> Status Konsistensi
                                        </a></li>
                                    </ul>
                                </div>
                                
                                <a href="{{ route('admin.scoring.index') }}" class="nav-link {{ request()->routeIs('admin.scoring.*') ? 'active' : '' }}">
                                    <i class="fas fa-calculator me-2"></i> Perhitungan Skor
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
                <div class="main-content" id="mainContent">
                    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                        <div class="container-fluid px-4">
                            <div class="d-flex align-items-center gap-3">
                                <!-- Sidebar Toggle Button -->
                                <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                                    <i class="fas fa-bars" id="toggleIcon"></i>
                                </button>
                                <a class="navbar-brand mb-0" href="#">@yield('page-title', 'Dashboard')</a>
                            </div>
                            
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
                                <button type="button" class-"btn-close" data-bs-dismiss="alert"></button>
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
                <div class="w-100">
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
            
            // Sidebar Toggle Functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarWrapper = document.getElementById('sidebarWrapper');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const toggleIcon = document.getElementById('toggleIcon');
            
            // Check if elements exist (for auth users only)
            if (sidebarToggle && sidebar) {
                // Load sidebar state from localStorage
                const sidebarState = localStorage.getItem('sidebarCollapsed');
                const isMobile = window.innerWidth <= 768;
                
                if (sidebarState === 'true' && !isMobile) {
                    sidebar.classList.add('collapsed');
                    sidebarWrapper.classList.add('collapsed');
                    if (toggleIcon) {
                        toggleIcon.classList.remove('fa-bars');
                        toggleIcon.classList.add('fa-bars');
                    }
                }
                
                // Toggle sidebar
                sidebarToggle.addEventListener('click', function() {
                    const isMobile = window.innerWidth <= 768;
                    
                    if (isMobile) {
                        // Mobile behavior
                        sidebar.classList.toggle('show');
                        sidebarOverlay.classList.toggle('show');
                    } else {
                        // Desktop behavior
                        sidebar.classList.toggle('collapsed');
                        sidebarWrapper.classList.toggle('collapsed');
                        
                        // Icon animation
                        if (toggleIcon) {
                            if (sidebar.classList.contains('collapsed')) {
                                // Sidebar closed - show bars icon
                                toggleIcon.style.transform = 'rotate(180deg)';
                                setTimeout(() => {
                                    toggleIcon.style.transform = 'rotate(0deg)';
                                }, 150);
                            } else {
                                // Sidebar open - show bars icon
                                toggleIcon.style.transform = 'rotate(180deg)';
                                setTimeout(() => {
                                    toggleIcon.style.transform = 'rotate(0deg)';
                                }, 150);
                            }
                        }
                        
                        // Save state to localStorage
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        localStorage.setItem('sidebarCollapsed', isCollapsed);
                    }
                });
                
                // Close sidebar when clicking overlay (mobile)
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    });
                }
                
                // Handle window resize
                window.addEventListener('resize', function() {
                    const isMobile = window.innerWidth <= 768;
                    
                    if (!isMobile) {
                        // Remove mobile classes
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                        
                        // Restore desktop state from localStorage
                        const sidebarState = localStorage.getItem('sidebarCollapsed');
                        if (sidebarState === 'true') {
                            sidebar.classList.add('collapsed');
                            sidebarWrapper.classList.add('collapsed');
                        } else {
                            sidebar.classList.remove('collapsed');
                            sidebarWrapper.classList.remove('collapsed');
                        }
                    } else {
                        // Mobile: always start collapsed
                        sidebar.classList.remove('collapsed');
                        sidebarWrapper.classList.remove('collapsed');
                    }
                });
            }
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