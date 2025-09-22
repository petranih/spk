<?php
// routes/web.php - FIXED untuk ValidationController

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MultiAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CriteriaController;
use App\Http\Controllers\Admin\SubCriteriaController;
use App\Http\Controllers\Admin\SubSubCriteriaController;
use App\Http\Controllers\Admin\PairwiseComparisonController;
use App\Http\Controllers\Admin\ApplicationScoringController;
use App\Http\Controllers\Admin\PeriodController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Validator\ValidatorController;
use App\Http\Controllers\Validator\ValidationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AHPController;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

// Authentication routes
Route::get('/login', [MultiAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MultiAuthController::class, 'login']);
Route::get('/register', [MultiAuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [MultiAuthController::class, 'register']);
Route::post('/logout', [MultiAuthController::class, 'logout'])->name('logout');

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Criteria management
    Route::resource('criteria', CriteriaController::class)->parameters([
        'criteria' => 'criterion'
    ]);
    
    // Subcriteria management (nested resource)
    Route::resource('criteria.subcriteria', SubCriteriaController::class)->parameters([
        'criteria' => 'criterion',
        'subcriteria' => 'subcriterion'
    ]);
    
    // Alternative route for subcriteria index without criteria parameter (for selection)
    Route::get('/subcriteria/{criterion?}', [SubCriteriaController::class, 'index'])->name('subcriteria.index');
    
    // Sub-subcriteria management (nested resource) 
    Route::resource('subcriteria.subsubcriteria', SubSubCriteriaController::class)->parameters([
        'subcriteria' => 'subcriterion',
        'subsubcriteria' => 'subsubcriterion'
    ]);
    
    // Alternative route for sub-subcriteria index without subcriterion parameter (for selection)
    Route::get('/subsubcriteria/{subcriterion?}', [SubSubCriteriaController::class, 'index'])->name('subsubcriteria.index');
    
    // Pairwise Comparison Routes
    Route::prefix('pairwise')->name('pairwise.')->group(function () {
        Route::get('/', [PairwiseComparisonController::class, 'index'])->name('index');
        Route::get('/criteria', [PairwiseComparisonController::class, 'criteria'])->name('criteria');
        Route::post('/criteria', [PairwiseComparisonController::class, 'storeCriteria'])->name('criteria.store');
        Route::get('/subcriteria/{criterion}', [PairwiseComparisonController::class, 'subCriteria'])->name('subcriteria');
        Route::post('/subcriteria/{criterion}', [PairwiseComparisonController::class, 'storeSubCriteria'])->name('subcriteria.store');
        Route::get('/subsubcriteria/{subcriterion}', [PairwiseComparisonController::class, 'subSubCriteria'])->name('subsubcriteria');
        Route::post('/subsubcriteria/{subcriterion}', [PairwiseComparisonController::class, 'storeSubSubCriteria'])->name('subsubcriteria.store');
        Route::get('/consistency-overview', [PairwiseComparisonController::class, 'consistencyOverview'])->name('consistency.overview');
        Route::get('/export/{type}/{parentId?}', [PairwiseComparisonController::class, 'exportMatrix'])->name('export.matrix');
        Route::post('/reset', [PairwiseComparisonController::class, 'resetComparisons'])->name('reset');
    });

    // Application Scoring Routes
    Route::prefix('scoring')->name('scoring.')->group(function () {
        Route::get('/', [ApplicationScoringController::class, 'index'])->name('index');
        Route::get('/period/{period}', [ApplicationScoringController::class, 'showApplications'])->name('applications');
        Route::post('/calculate-single/{application}', [ApplicationScoringController::class, 'calculateSingleScore'])->name('calculate-single');
        Route::post('/calculate-all/{period}', [ApplicationScoringController::class, 'calculateAllScores'])->name('calculate-all');
        Route::get('/detail/{application}', [ApplicationScoringController::class, 'showCalculationDetail'])->name('detail');
        Route::get('/export/{period}/{format}', [ApplicationScoringController::class, 'export'])->name('export');
    });

    // AHP Calculation Routes
    Route::prefix('ahp')->name('ahp.')->group(function () {
        Route::post('/calculate-criteria', [AHPController::class, 'calculateCriteriaWeights'])->name('calculate.criteria');
        Route::post('/calculate-subcriteria/{criteriaId}', [AHPController::class, 'calculateSubCriteriaWeights'])->name('calculate.subcriteria');
        Route::post('/calculate-subsubcriteria/{subCriteriaId}', [AHPController::class, 'calculateSubSubCriteriaWeights'])->name('calculate.subsubcriteria');
    });
    
    // Period management
    Route::resource('period', PeriodController::class);
    Route::patch('/period/{period}/activate', [PeriodController::class, 'activate'])->name('period.activate');
    
    // Student management
    Route::resource('student', AdminStudentController::class);
    
    // Reports
    Route::resource('report', ReportController::class)->only(['index', 'show']);
    Route::post('/report/{period}/calculate', [ReportController::class, 'calculate'])->name('report.calculate');
    Route::get('/report/{period}/export-pdf', [ReportController::class, 'exportPdf'])->name('report.export.pdf');
    Route::get('/report/{period}/export-excel', [ReportController::class, 'exportExcel'])->name('report.export.excel');
});

// Student routes
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    
    // Application management routes
    Route::get('/application/create', [ApplicationController::class, 'create'])->name('application.create');
    Route::post('/application', [ApplicationController::class, 'store'])->name('application.store');
    Route::get('/application/{application}/edit', [ApplicationController::class, 'edit'])->name('application.edit');
    
    // Separate routes untuk berbagai fungsi dengan middleware yang tepat
    Route::put('/application/{application}', [ApplicationController::class, 'update'])
        ->name('application.update')
        ->middleware('throttle:20,1');
    
    Route::post('/application/{application}/save-criteria', [ApplicationController::class, 'saveCriteria'])
        ->name('application.save-criteria')
        ->middleware('throttle:60,1');
    
    Route::post('/application/{application}/submit', [ApplicationController::class, 'submit'])
        ->name('application.submit')
        ->middleware('throttle:3,1');
    
    // Document management routes dengan rate limiting
    Route::post('/application/{application}/upload', [ApplicationController::class, 'uploadDocument'])
        ->name('application.upload')
        ->middleware('throttle:15,1');
        
    Route::delete('/application/{application}/document/{document}', [ApplicationController::class, 'deleteDocument'])
        ->name('application.document.delete')
        ->middleware('throttle:10,1');
});

// Validator routes - FIXED
Route::middleware(['auth', 'validator'])->prefix('validator')->name('validator.')->group(function () {
    Route::get('/dashboard', [ValidatorController::class, 'dashboard'])->name('dashboard');
    
    // Validation routes - FIXED method names
    Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
    Route::get('/validation/{application}', [ValidationController::class, 'show'])->name('validation.show');
    Route::post('/validation/{application}', [ValidationController::class, 'store'])->name('validation.store');
    
    // Document viewing routes - FIXED direct controller methods
    Route::get('/document/{document}/show', [ValidationController::class, 'showDocument'])->name('document.show');
    Route::get('/document/{document}/download', [ValidationController::class, 'downloadDocument'])->name('document.download');
});

// API Routes
Route::prefix('api')->name('api.')->group(function () {
    
    // Admin API routes
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        
        // Consistency status check
        Route::get('/consistency-status/{level}/{parentId?}', function($level, $parentId = null) {
            try {
                $weight = \App\Models\CriteriaWeight::where('level', $level)
                    ->when($parentId, function($query, $parentId) {
                        return $query->where('parent_id', $parentId);
                    }, function($query) {
                        return $query->whereNull('parent_id');
                    })
                    ->first();
                
                if (!$weight) {
                    return response()->json([
                        'status' => 'no_data',
                        'cr' => null,
                        'ci' => null,
                        'lambda_max' => null,
                    ]);
                }
                
                // Handle values
                $cr = is_numeric($weight->cr) ? (float) $weight->cr : null;
                $ci = is_numeric($weight->ci) ? (float) $weight->ci : null;
                $lambdaMax = is_numeric($weight->lambda_max) ? (float) $weight->lambda_max : null;
                
                return response()->json([
                    'status' => $weight->is_consistent ? 'consistent' : 'inconsistent',
                    'cr' => $cr,
                    'ci' => $ci,
                    'lambda_max' => $lambdaMax,
                ]);
                
            } catch (\Exception $e) {
                \Log::error('API consistency-status error: ' . $e->getMessage());
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch consistency status'
                ], 500);
            }
        })->name('consistency.status');
    });
    
    // Student API routes
    Route::prefix('student')->name('student.')->middleware(['auth', 'student'])->group(function () {
        
        // Application status check
        Route::get('/application/{application}/status', function($applicationId) {
            try {
                $application = \App\Models\Application::findOrFail($applicationId);
                
                // Security check - user can only access their own application
                if ($application->user_id !== auth()->id()) {
                    return response()->json([
                        'error' => 'Unauthorized access'
                    ], 403);
                }
                
                $criteriaCount = \App\Models\ApplicationValue::where('application_id', $applicationId)->count();
                $documentCount = \App\Models\ApplicationDocument::where('application_id', $applicationId)->count();
                
                return response()->json([
                    'status' => $application->status,
                    'criteria_count' => $criteriaCount,
                    'document_count' => $documentCount,
                    'updated_at' => $application->updated_at->format('Y-m-d H:i:s')
                ]);
                
            } catch (\Exception $e) {
                \Log::error('API application status error: ' . $e->getMessage());
                
                return response()->json([
                    'error' => 'Failed to fetch application status'
                ], 500);
            }
        })->name('application.status')->middleware('throttle:60,1');
    });
});

// Fallback route
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'error' => 'API endpoint not found',
            'message' => 'The requested API endpoint does not exist.'
        ], 404);
    }
    
    return response()->view('errors.404', [], 404);
});