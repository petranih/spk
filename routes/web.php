<?php
// routes/web.php - PERBAIKAN untuk handle upload dengan benar

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
use App\Http\Controllers\AHPController;

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

// Student routes - PERBAIKAN: Rute upload yang lebih spesifik
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    
    // Application management
    Route::get('/application/create', [ApplicationController::class, 'create'])->name('application.create');
    Route::post('/application', [ApplicationController::class, 'store'])->name('application.store');
    Route::get('/application/{application}/edit', [ApplicationController::class, 'edit'])->name('application.edit');
    Route::put('/application/{application}', [ApplicationController::class, 'update'])->name('application.update');
    Route::post('/application/{application}/submit', [ApplicationController::class, 'submit'])->name('application.submit');
    Route::post('/student/application/{application}/save-criteria', [ApplicationController::class, 'saveCriteria'])->name('student.application.save-criteria');
    
    // PERBAIKAN: Document upload routes yang lebih jelas
    Route::post('/application/{application}/upload', [ApplicationController::class, 'uploadDocument'])
        ->name('application.upload');
    Route::delete('/application/{application}/document/{document}', [ApplicationController::class, 'deleteDocument'])
        ->name('application.document.delete');
});

// Validator routes
Route::middleware(['auth', 'validator'])->prefix('validator')->name('validator.')->group(function () {
    Route::get('/dashboard', [ValidatorController::class, 'dashboard'])->name('dashboard');
    
    // Validation
    Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
    Route::get('/validation/{application}', [ValidationController::class, 'show'])->name('validation.show');
    Route::post('/validation/{application}', [ValidationController::class, 'processValidation'])->name('validation.store');
    Route::get('/document/{document}', [App\Http\Controllers\FileController::class, 'showDocument'])
        ->name('document.show');
    Route::get('/document/{document}/download', [App\Http\Controllers\FileController::class, 'downloadDocument'])
        ->name('document.download');
});

// API Routes for AJAX calls
Route::prefix('api/admin')->name('api.admin.')->middleware(['auth', 'admin'])->group(function () {
    // Consistency status check
    Route::get('/consistency-status/{level}/{parentId?}', function($level, $parentId = null) {
        $weight = \App\Models\CriteriaWeight::where('level', $level)
            ->where('parent_id', $parentId)
            ->first();
        
        return response()->json([
            'status' => $weight ? ($weight->is_consistent ? 'consistent' : 'inconsistent') : 'no_data',
            'cr' => $weight ? $weight->cr : null,
            'ci' => $weight ? $weight->ci : null,
            'lambda_max' => $weight ? $weight->lambda_max : null,
        ]);
    })->name('consistency.status');
    
    // Weights summary
    Route::get('/weights-summary', function() {
        $data = [
            'criteria' => [
                'consistent' => false,
                'cr' => null,
                'total_count' => 0
            ],
            'subcriteria' => [
                'consistent_count' => 0,
                'total_count' => 0,
                'all_consistent' => false
            ],
            'subsubcriteria' => [
                'consistent_count' => 0,
                'total_count' => 0,
                'all_consistent' => false
            ]
        ];

        // Check criteria consistency
        $criteriaWeight = \App\Models\CriteriaWeight::where('level', 'criteria')
            ->where('parent_id', null)
            ->first();
        
        if ($criteriaWeight) {
            $data['criteria']['consistent'] = $criteriaWeight->is_consistent;
            $data['criteria']['cr'] = $criteriaWeight->cr;
        }

        // Check subcriteria consistency
        $subCriteriaWeights = \App\Models\CriteriaWeight::where('level', 'subcriteria')
            ->whereNotNull('parent_id')
            ->get();
        
        $data['subcriteria']['total_count'] = $subCriteriaWeights->count();
        $data['subcriteria']['consistent_count'] = $subCriteriaWeights->where('is_consistent', true)->count();
        $data['subcriteria']['all_consistent'] = $data['subcriteria']['total_count'] > 0 && 
            $data['subcriteria']['consistent_count'] == $data['subcriteria']['total_count'];

        // Check subsubcriteria consistency
        $subSubCriteriaWeights = \App\Models\CriteriaWeight::where('level', 'subsubcriteria')
            ->whereNotNull('parent_id')
            ->get();
        
        $data['subsubcriteria']['total_count'] = $subSubCriteriaWeights->count();
        $data['subsubcriteria']['consistent_count'] = $subSubCriteriaWeights->where('is_consistent', true)->count();
        $data['subsubcriteria']['all_consistent'] = $data['subsubcriteria']['total_count'] > 0 && 
            $data['subsubcriteria']['consistent_count'] == $data['subsubcriteria']['total_count'];

        return response()->json($data);
    })->name('weights.summary');
    
    // Get criteria with subcriteria for dropdown
    Route::get('/criteria-with-subcriteria', function() {
        $criterias = \App\Models\Criteria::active()
            ->with('subCriterias:id,criteria_id,code,name')
            ->get(['id', 'code', 'name']);
        
        return response()->json($criterias);
    })->name('criteria.with.subcriteria');
    
    // Get subcriteria with sub-subcriteria for dropdown
    Route::get('/subcriteria-with-subsubcriteria/{criteriaId}', function($criteriaId) {
        $subCriterias = \App\Models\SubCriteria::where('criteria_id', $criteriaId)
            ->active()
            ->with('subSubCriterias:id,sub_criteria_id,code,name')
            ->get(['id', 'code', 'name']);
        
        return response()->json($subCriterias);
    })->name('subcriteria.with.subsubcriteria');
});