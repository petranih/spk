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
    Route::get('/subcriteria', [SubCriteriaController::class, 'index'])->name('subcriteria.index');
    
    // Sub-subcriteria management (nested resource) 
    Route::resource('subcriteria.subsubcriteria', SubSubCriteriaController::class)->parameters([
        'subcriteria' => 'subcriterion',
        'subsubcriteria' => 'subsubcriterion'
    ]);
    
    // Pairwise comparison
    Route::prefix('pairwise')->name('pairwise.')->group(function () {
        Route::get('/criteria', [PairwiseComparisonController::class, 'criteria'])->name('criteria');
        Route::post('/criteria', [PairwiseComparisonController::class, 'storeCriteria'])->name('criteria.store');
        Route::get('/criteria/{criterion}/subcriteria', [PairwiseComparisonController::class, 'subCriteria'])->name('subcriteria');
        Route::post('/criteria/{criterion}/subcriteria', [PairwiseComparisonController::class, 'storeSubCriteria'])->name('subcriteria.store');
        Route::get('/subcriteria/{subcriterion}/subsubcriteria', [PairwiseComparisonController::class, 'subSubCriteria'])->name('subsubcriteria');
        Route::post('/subcriteria/{subcriterion}/subsubcriteria', [PairwiseComparisonController::class, 'storeSubSubCriteria'])->name('subsubcriteria.store');
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