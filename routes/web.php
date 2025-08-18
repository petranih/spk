<?php
// routes/web.php - Updated version

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MultiAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CriteriaController;
use App\Http\Controllers\Admin\SubCriteriaController;
use App\Http\Controllers\Admin\SubSubCriteriaController;
use App\Http\Controllers\Admin\PairwiseComparisonController;
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
    
    // Pairwise Comparison Routes - Updated to match controller methods
    Route::prefix('pairwise')->name('pairwise.')->group(function () {
        
        // Criteria Level
        Route::get('/criteria', [PairwiseComparisonController::class, 'criteria'])
            ->name('criteria');
        Route::post('/criteria', [PairwiseComparisonController::class, 'storeCriteria'])
            ->name('criteria.store');
        
        // Sub-Criteria Level - Updated routes to match controller
        Route::get('/subcriteria/{criterion}', [PairwiseComparisonController::class, 'subCriteria'])
            ->name('subcriteria');
        Route::post('/subcriteria/{criterion}', [PairwiseComparisonController::class, 'storeSubCriteria'])
            ->name('subcriteria.store');
        
        // Sub-Sub-Criteria Level - Updated routes to match controller
        Route::get('/subsubcriteria/{subcriterion}', [PairwiseComparisonController::class, 'subSubCriteria'])
            ->name('subsubcriteria');
        Route::post('/subsubcriteria/{subcriterion}', [PairwiseComparisonController::class, 'storeSubSubCriteria'])
            ->name('subsubcriteria.store');
        
        // Consistency Overview - New route
        Route::get('/consistency-overview', [PairwiseComparisonController::class, 'consistencyOverview'])
            ->name('consistency.overview');
        
        // Export and Utility Routes - New routes
        Route::get('/export/{type}/{parentId?}', [PairwiseComparisonController::class, 'exportMatrix'])
            ->name('export.matrix');
        Route::post('/reset', [PairwiseComparisonController::class, 'resetComparisons'])
            ->name('reset');
    });

    // AHP Calculation Routes - New section for manual calculations
    Route::prefix('ahp')->name('ahp.')->group(function () {
        Route::post('/calculate-criteria', [AHPController::class, 'calculateCriteriaWeights'])
            ->name('calculate.criteria');
        Route::post('/calculate-subcriteria/{criteriaId}', [AHPController::class, 'calculateSubCriteriaWeights'])
            ->name('calculate.subcriteria');
        Route::post('/calculate-subsubcriteria/{subCriteriaId}', [AHPController::class, 'calculateSubSubCriteriaWeights'])
            ->name('calculate.subsubcriteria');
        Route::post('/calculate-all/{periodId}', [AHPController::class, 'calculateAllApplicationsScores'])
            ->name('calculate.all');
        Route::post('/calculate-application/{applicationId}', [AHPController::class, 'calculateApplicationScore'])
            ->name('calculate.application');
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
    
    // Application management
    Route::get('/application/create', [ApplicationController::class, 'create'])->name('application.create');
    Route::post('/application', [ApplicationController::class, 'store'])->name('application.store');
    Route::get('/application/{application}/edit', [ApplicationController::class, 'edit'])->name('application.edit');
    Route::put('/application/{application}', [ApplicationController::class, 'update'])->name('application.update');
    Route::post('/application/{application}/submit', [ApplicationController::class, 'submit'])->name('application.submit');
    
    // Document upload
    Route::post('/application/{application}/upload', [ApplicationController::class, 'uploadDocument'])->name('application.upload');
    Route::delete('/application/{application}/document/{document}', [ApplicationController::class, 'deleteDocument'])->name('application.document.delete');
});

// Validator routes
Route::middleware(['auth', 'validator'])->prefix('validator')->name('validator.')->group(function () {
    Route::get('/dashboard', [ValidatorController::class, 'dashboard'])->name('dashboard');
    
    // Validation
    Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
    Route::get('/validation/{application}', [ValidationController::class, 'show'])->name('validation.show');
    Route::post('/validation/{application}', [ValidationController::class, 'validate'])->name('validation.store');
});

// API Routes for AJAX calls - New section for dynamic updates
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
        return response()->json(\App\Models\CriteriaWeight::getConsistencySummary());
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