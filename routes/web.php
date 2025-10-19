<?php
// routes/web.php - FIXED tanpa Excel export

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
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Models\ApplicationDocument;
use App\Models\CriteriaWeight;
use Illuminate\Support\Facades\Storage;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication routes
Route::get('/login', [MultiAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MultiAuthController::class, 'login']);
Route::get('/register', [MultiAuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [MultiAuthController::class, 'register']);
Route::post('/logout', [MultiAuthController::class, 'logout'])->name('logout');
// Forgot Password Routes
Route::get('/password/forgot', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendOtp'])->name('password.email');
Route::get('/password/verify-otp', [ForgotPasswordController::class, 'showVerifyOtpForm'])->name('password.verify-otp');
Route::post('/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.verify-otp.post');
Route::get('/password/reset/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

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

    // Application Scoring Routes - FIXED tanpa Excel
    Route::prefix('scoring')->name('scoring.')->group(function () {
        Route::get('/', [ApplicationScoringController::class, 'index'])->name('index');
        Route::get('/period/{period}', [ApplicationScoringController::class, 'showApplications'])->name('applications');
        Route::post('/calculate-single/{application}', [ApplicationScoringController::class, 'calculateSingleScore'])->name('calculate-single');
        Route::post('/calculate-all/{period}', [ApplicationScoringController::class, 'calculateAllScores'])->name('calculate-all');
        Route::get('/detail/{application}', [ApplicationScoringController::class, 'showCalculationDetail'])->name('detail');
        
        // HANYA PDF - Route baru yang benar
        Route::get('/export-pdf/{period}', [ApplicationScoringController::class, 'exportPdf'])->name('export-pdf');
        
        Route::post('/resync-weights/{application}', [ApplicationScoringController::class, 'resyncWeights'])->name('resync-weights');
    });

    // Debug routes
    if (config('app.debug')) {
        Route::prefix('debug')->name('debug.')->group(function () {
            Route::get('/application/{application}', [ApplicationScoringController::class, 'debugApplicationData'])
                ->name('application-data');
            
            Route::get('/application-mapping/{application}', [ApplicationScoringController::class, 'debugApplicationMapping'])
                ->name('application-mapping');
            
            Route::get('/application-data-enhanced/{application}', 
                [ApplicationScoringController::class, 'debugApplicationDataEnhanced'])
                ->name('application-data-enhanced');

            Route::get('/weight-consistency', [ApplicationScoringController::class, 'debugWeightConsistency'])
                ->name('weight-consistency');
            
            Route::get('/application-values/{application}', function(\App\Models\Application $application) {
                $values = \App\Models\ApplicationValue::where('application_id', $application->id)->get();
                $criterias = \App\Models\Criteria::with(['subCriterias.subSubCriterias'])->get();
                
                return response()->json([
                    'application_id' => $application->id,
                    'application_name' => $application->full_name,
                    'application_data' => $application->toArray(),
                    'application_values_count' => $values->count(),
                    'application_values' => $values,
                    'criterias' => $criterias
                ]);
            })->name('application-values');
            
            Route::get('/criteria-mapping', function() {
                $criterias = \App\Models\Criteria::with(['subCriterias.subSubCriterias'])->get();
                $sampleApp = \App\Models\Application::with(['applicationValues'])->first();
                
                return response()->json([
                    'criterias_structure' => $criterias,
                    'sample_application' => $sampleApp ? $sampleApp->toArray() : null,
                    'sample_application_values' => $sampleApp ? $sampleApp->applicationValues : []
                ]);
            })->name('criteria-mapping');
            
            Route::get('/test-data/{application}', function(\App\Models\Application $application) {
                $appData = $application->toArray();
                $nonNullFields = [];
                
                foreach ($appData as $key => $value) {
                    if (!is_null($value) && $value !== '' && !in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        $nonNullFields[$key] = $value;
                    }
                }
                
                $appValues = \App\Models\ApplicationValue::where('application_id', $application->id)
                    ->get()
                    ->map(function($val) {
                        return [
                            'criteria_type' => $val->criteria_type,
                            'criteria_id' => $val->criteria_id,
                            'value' => $val->value
                        ];
                    });
                
                return response()->json([
                    'application_id' => $application->id,
                    'student_name' => $application->full_name,
                    'non_null_fields' => $nonNullFields,
                    'non_null_count' => count($nonNullFields),
                    'application_values' => $appValues,
                    'app_values_count' => $appValues->count()
                ]);
            })->name('test-data');

            Route::get('/subsubcriteria-weights', function() {
                $subSubCriterias = \App\Models\SubSubCriteria::with(['subCriteria'])
                    ->where('is_active', true)
                    ->get()
                    ->map(function($ssc) {
                        return [
                            'id' => $ssc->id,
                            'name' => $ssc->name,
                            'code' => $ssc->code,
                            'weight' => $ssc->weight,
                            'parent_subcriteria' => $ssc->subCriteria->name,
                            'parent_subcriteria_id' => $ssc->sub_criteria_id
                        ];
                    });

                return response()->json([
                    'subsubcriteria_count' => $subSubCriterias->count(),
                    'subsubcriteria_data' => $subSubCriterias
                ]);
            })->name('subsubcriteria-weights');

            Route::get('/weight-comparison/{application}', function(\App\Models\Application $application) {
                $applicationValues = \App\Models\ApplicationValue::where('application_id', $application->id)
                    ->where('criteria_type', 'subsubcriteria')
                    ->get();

                $comparison = [];
                
                foreach ($applicationValues as $appValue) {
                    $subSubCriteria = \App\Models\SubSubCriteria::with('subCriteria')->find($appValue->criteria_id);
                    
                    if ($subSubCriteria) {
                        $comparison[] = [
                            'application_value' => $appValue->value,
                            'subsubcriteria_name' => $subSubCriteria->name,
                            'subsubcriteria_weight' => $subSubCriteria->weight,
                            'subcriteria_name' => $subSubCriteria->subCriteria->name,
                            'subcriteria_weight' => $subSubCriteria->subCriteria->weight,
                            'weight_difference' => $subSubCriteria->weight - $subSubCriteria->subCriteria->weight
                        ];
                    }
                }

                return response()->json([
                    'application_id' => $application->id,
                    'application_name' => $application->full_name,
                    'weight_comparisons' => $comparison
                ]);
            })->name('weight-comparison');
        });
    }
    
    // TAMBAHKAN KE routes/web.php - Di dalam Route::middleware(['auth', 'admin'])

Route::prefix('debug')->name('debug.')->group(function () {
    
    /**
     * DEBUG 1: Bandingkan Data Excel vs Database
     */
    Route::get('/compare-holisiah', function() {
        $holisiah = \App\Models\Application::where('full_name', 'HOLISIAH')->first();
        
        if (!$holisiah) {
            return response()->json(['error' => 'HOLISIAH tidak ditemukan']);
        }
        
        // Data dari Excel (row A2)
        $excelData = [
            'nama' => 'HOLISIAH',
            'father_job' => 'Petani',
            'father_income' => 'Kurang dari Rp. 500,000',
            'mother_job' => 'Petani',
            'mother_income' => 'Kurang dari Rp. 500,000',
            'family_dependents' => '3–4 orang',
            'has_debt' => 'Tidak Punya Hutang',
            'parent_education' => 'SD',
            'wall_type' => 'Tembok belum diplester',
            'floor_type' => 'Semen',
            'roof_type' => 'Seng/Asbes',
            'house_status' => 'Kontrak/Sewa',
            'house_area' => '< 30 m²',
            'bedroom_count' => '2 kamar tidur',
            'people_per_bedroom' => '3–4 orang per kamar',
            'motorcycle' => 'Tidak punya',
            'car' => 'Tidak punya',
            'land' => 'Tidak punya',
            'electronics' => 'Punya 1–2 jenis',
            'electricity' => 'Listrik sendiri (PLN)',
            'water' => 'Sumur Bor/Pompa',
            'cooking_fuel' => 'LPG 3 kg',
            'social_aid' => 'Menerima Bantuan',
            
            // Skor dari Excel
            'excel_c1' => 0.149528054,
            'excel_c2' => 0.083435564,
            'excel_c3' => 0.078506215,
            'excel_c4' => 0.017290915,
            'excel_c5' => 0.030771362,
            'excel_total' => 0.359532111,
            'excel_rank' => 1
        ];
        
        // Data dari Database
        $dbData = [
            'nama' => $holisiah->full_name,
            'data_lengkap' => $holisiah->toArray(),
            'application_values_count' => \App\Models\ApplicationValue::where('application_id', $holisiah->id)->count(),
        ];
        
        // Ambil skor dari database
        $ranking = \App\Models\Ranking::where('application_id', $holisiah->id)->first();
        if ($ranking) {
            $criteriaScores = is_string($ranking->criteria_scores) 
                ? json_decode($ranking->criteria_scores, true) 
                : $ranking->criteria_scores;
            
            $dbData['db_c1'] = $criteriaScores['C1'] ?? 0;
            $dbData['db_c2'] = $criteriaScores['C2'] ?? 0;
            $dbData['db_c3'] = $criteriaScores['C3'] ?? 0;
            $dbData['db_c4'] = $criteriaScores['C4'] ?? 0;
            $dbData['db_c5'] = $criteriaScores['C5'] ?? 0;
            $dbData['db_total'] = $ranking->total_score;
            $dbData['db_rank'] = $ranking->rank;
        }
        
        // Bandingkan
        $comparison = [
            'C1' => ['excel' => $excelData['excel_c1'], 'db' => $dbData['db_c1'] ?? 0, 'diff' => abs($excelData['excel_c1'] - ($dbData['db_c1'] ?? 0))],
            'C2' => ['excel' => $excelData['excel_c2'], 'db' => $dbData['db_c2'] ?? 0, 'diff' => abs($excelData['excel_c2'] - ($dbData['db_c2'] ?? 0))],
            'C3' => ['excel' => $excelData['excel_c3'], 'db' => $dbData['db_c3'] ?? 0, 'diff' => abs($excelData['excel_c3'] - ($dbData['db_c3'] ?? 0))],
            'C4' => ['excel' => $excelData['excel_c4'], 'db' => $dbData['db_c4'] ?? 0, 'diff' => abs($excelData['excel_c4'] - ($dbData['db_c4'] ?? 0))],
            'C5' => ['excel' => $excelData['excel_c5'], 'db' => $dbData['db_c5'] ?? 0, 'diff' => abs($excelData['excel_c5'] - ($dbData['db_c5'] ?? 0))],
            'TOTAL' => ['excel' => $excelData['excel_total'], 'db' => $dbData['db_total'] ?? 0, 'diff' => abs($excelData['excel_total'] - ($dbData['db_total'] ?? 0))],
        ];
        
        return response()->json([
            'excel_data' => $excelData,
            'database_data' => $dbData,
            'comparison' => $comparison,
            'largest_difference' => collect($comparison)->sortByDesc('diff')->first()
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
    
    /**
     * DEBUG 2: Lihat Detail Perhitungan Criteria
     */
    Route::get('/criteria-breakdown/{application}', function(\App\Models\Application $application) {
        $result = [
            'student' => $application->full_name,
            'nisn' => $application->nisn,
            'criteria_breakdown' => []
        ];
        
        $criterias = \App\Models\Criteria::where('is_active', true)
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();
        
        foreach ($criterias as $criteria) {
            $breakdown = [
                'code' => $criteria->code,
                'name' => $criteria->name,
                'weight' => $criteria->weight,
                'subcriteria' => []
            ];
            
            foreach ($criteria->subCriterias as $sub) {
                $subData = [
                    'code' => $sub->code,
                    'name' => $sub->name,
                    'weight' => $sub->weight,
                    'has_subsubcriteria' => $sub->subSubCriterias->count() > 0,
                    'selected_value' => null,
                    'selected_weight' => 0
                ];
                
                if ($sub->subSubCriterias->count() > 0) {
                    // Ada SubSubCriteria
                    foreach ($sub->subSubCriterias as $subsub) {
                        $appVal = \App\Models\ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', 'subsubcriteria')
                            ->where('criteria_id', $subsub->id)
                            ->first();
                        
                        if ($appVal) {
                            $subData['selected_value'] = $subsub->name;
                            $subData['selected_weight'] = $subsub->weight;
                            break;
                        }
                    }
                } else {
                    // Direct SubCriteria
                    $appVal = \App\Models\ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->where('criteria_id', $sub->id)
                        ->first();
                    
                    if ($appVal) {
                        $subData['selected_value'] = $appVal->value;
                        $subData['selected_weight'] = $sub->weight;
                    }
                }
                
                $breakdown['subcriteria'][] = $subData;
            }
            
            $result['criteria_breakdown'][] = $breakdown;
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
    
    /**
     * DEBUG 3: Bandingkan SEMUA Bobot SubSubCriteria
     */
    Route::get('/all-weights', function() {
        $result = [
            'criteria_weights' => [],
            'subcriteria_weights' => [],
            'subsubcriteria_weights' => []
        ];
        
        // Criteria
        $criterias = \App\Models\Criteria::where('is_active', true)->orderBy('order')->get();
        foreach ($criterias as $c) {
            $result['criteria_weights'][] = [
                'code' => $c->code,
                'name' => $c->name,
                'weight' => $c->weight
            ];
        }
        
        // SubCriteria
        $subCriterias = \App\Models\SubCriteria::where('is_active', true)
            ->with('criteria')
            ->orderBy('criteria_id')
            ->orderBy('order')
            ->get();
        
        foreach ($subCriterias as $sc) {
            $result['subcriteria_weights'][] = [
                'parent' => $sc->criteria->code ?? 'N/A',
                'code' => $sc->code,
                'name' => $sc->name,
                'weight' => $sc->weight,
                'has_subsubcriteria' => $sc->subSubCriterias()->count() > 0
            ];
        }
        
        // SubSubCriteria
        $subSubCriterias = \App\Models\SubSubCriteria::where('is_active', true)
            ->with('subCriteria.criteria')
            ->get();
        
        foreach ($subSubCriterias as $ssc) {
            $result['subsubcriteria_weights'][] = [
                'grandparent' => $ssc->subCriteria->criteria->code ?? 'N/A',
                'parent' => $ssc->subCriteria->code ?? 'N/A',
                'code' => $ssc->code,
                'name' => $ssc->name,
                'weight' => $ssc->weight
            ];
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
    
    /**
     * DEBUG 4: Trace Manual Calculation
     */
    Route::get('/manual-calc/{application}', function(\App\Models\Application $application) {
        $result = [
            'student' => $application->full_name,
            'manual_calculation' => [],
            'total' => 0
        ];
        
        // Manual calculation step by step
        $criterias = \App\Models\Criteria::where('is_active', true)
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();
        
        foreach ($criterias as $criteria) {
            $criteriaScore = 0;
            $details = [];
            
            foreach ($criteria->subCriterias as $sub) {
                $subScore = 0;
                
                if ($sub->subSubCriterias->count() > 0) {
                    // Cari yang dipilih
                    foreach ($sub->subSubCriterias as $subsub) {
                        $appVal = \App\Models\ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', 'subsubcriteria')
                            ->where('criteria_id', $subsub->id)
                            ->first();
                        
                        if ($appVal) {
                            $subScore = $subsub->weight;
                            $details[] = "{$sub->name} = {$subsub->name} (weight: {$subsub->weight})";
                            break;
                        }
                    }
                } else {
                    $appVal = \App\Models\ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->where('criteria_id', $sub->id)
                        ->first();
                    
                    if ($appVal) {
                        $subScore = $sub->weight;
                        $details[] = "{$sub->name} = {$appVal->value} (weight: {$sub->weight})";
                    }
                }
                
                $criteriaScore += $subScore * $sub->weight;
            }
            
            $contribution = $criteriaScore * $criteria->weight;
            $result['total'] += $contribution;
            
            $result['manual_calculation'][] = [
                'criteria' => $criteria->code . ' - ' . $criteria->name,
                'criteria_weight' => $criteria->weight,
                'subcriteria_scores' => $details,
                'criteria_score' => $criteriaScore,
                'contribution' => $contribution,
                'formula' => "{$criteriaScore} × {$criteria->weight} = {$contribution}"
            ];
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
    
    /**
     * DEBUG 5: Export ke format Excel-like
     */
    Route::get('/export-comparison', function() {
        $applications = \App\Models\Application::where('period_id', 4)
            ->where('status', 'validated')
            ->orderBy('full_name')
            ->get();
        
        $data = [];
        
        foreach ($applications as $app) {
            $ranking = \App\Models\Ranking::where('application_id', $app->id)->first();
            
            if ($ranking) {
                $scores = is_string($ranking->criteria_scores) 
                    ? json_decode($ranking->criteria_scores, true) 
                    : $ranking->criteria_scores;
                
                $data[] = [
                    'nama' => $app->full_name,
                    'nisn' => $app->nisn,
                    'skor_c1' => $scores['C1'] ?? 0,
                    'skor_c2' => $scores['C2'] ?? 0,
                    'skor_c3' => $scores['C3'] ?? 0,
                    'skor_c4' => $scores['C4'] ?? 0,
                    'skor_c5' => $scores['C5'] ?? 0,
                    'total' => $ranking->total_score,
                    'rank' => $ranking->rank
                ];
            }
        }
        
        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
});

    Route::get('scoring/debug-mapping/{application}', [ApplicationScoringController::class, 'debugApplicationMapping'])
        ->name('admin.debug.application-mapping');

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
    
    // Reports - FIXED tanpa Excel
    Route::resource('report', ReportController::class)->only(['index', 'show']);
    Route::post('/report/{period}/calculate', [ReportController::class, 'calculate'])->name('report.calculate');
    Route::get('/report/{period}/export-pdf', [ReportController::class, 'exportPdf'])->name('report.export.pdf');
    // Route Excel dihapus
});

// Student routes
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    
    // Application management routes
    Route::get('/application/create', [ApplicationController::class, 'create'])->name('application.create');
    Route::post('/application', [ApplicationController::class, 'store'])->name('application.store');
    Route::get('/application/{application}/edit', [ApplicationController::class, 'edit'])->name('application.edit');
    
    Route::put('/application/{application}', [ApplicationController::class, 'update'])
        ->name('application.update')
        ->middleware('throttle:20,1');
    
    Route::post('/application/{application}/save-criteria', [ApplicationController::class, 'saveCriteria'])
        ->name('application.save-criteria')
        ->middleware('throttle:60,1');
    
    Route::post('/application/{application}/submit', [ApplicationController::class, 'submit'])
        ->name('application.submit')
        ->middleware('throttle:3,1');
    
    Route::post('/application/{application}/upload', [ApplicationController::class, 'uploadDocument'])
        ->name('application.upload')
        ->middleware('throttle:15,1');
        
    Route::delete('/application/{application}/document/{document}', [ApplicationController::class, 'deleteDocument'])
        ->name('application.document.delete')
        ->middleware('throttle:10,1');
});

// Validator routes
Route::middleware(['auth', 'validator'])->prefix('validator')->name('validator.')->group(function () {
    Route::get('/dashboard', [ValidatorController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/validation', [ValidationController::class, 'index'])->name('validation.index');
    Route::get('/validation/{application}', [ValidationController::class, 'show'])->name('validation.show');
    Route::post('/validation/{application}', [ValidationController::class, 'store'])->name('validation.store');
    
    Route::get('/document/{document}/show', [ValidationController::class, 'showDocument'])->name('document.show');
    Route::get('/document/{document}/download', [ValidationController::class, 'downloadDocument'])->name('document.download');
});

// API Routes
Route::prefix('api')->name('api.')->group(function () {
    
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        
        Route::get('/weights/summary', function() {
            try {
                $criteriaWeight = CriteriaWeight::where('level', 'criteria')
                    ->whereNull('parent_id')
                    ->first();
                
                $subcriteriaWeights = CriteriaWeight::where('level', 'subcriteria')
                    ->whereNotNull('parent_id')
                    ->get();
                
                $subsubcriteriaWeights = CriteriaWeight::where('level', 'subsubcriteria')
                    ->whereNotNull('parent_id')
                    ->get();
                
                return response()->json([
                    'criteria' => [
                        'consistent' => $criteriaWeight ? $criteriaWeight->is_consistent : false,
                        'cr' => $criteriaWeight ? $criteriaWeight->cr : null,
                    ],
                    'subcriteria' => [
                        'total_count' => $subcriteriaWeights->count(),
                        'consistent_count' => $subcriteriaWeights->where('is_consistent', true)->count(),
                        'all_consistent' => $subcriteriaWeights->count() > 0 ? $subcriteriaWeights->every('is_consistent') : false,
                    ],
                    'subsubcriteria' => [
                        'total_count' => $subsubcriteriaWeights->count(),
                        'consistent_count' => $subsubcriteriaWeights->where('is_consistent', true)->count(),
                        'all_consistent' => $subsubcriteriaWeights->count() > 0 ? $subsubcriteriaWeights->every('is_consistent') : false,
                    ]
                ]);
                
            } catch (\Exception $e) {
                \Log::error('API weights summary error: ' . $e->getMessage());
                
                return response()->json([
                    'error' => 'Failed to fetch weights summary'
                ], 500);
            }
        })->name('weights.summary');
        
        Route::get('/consistency-status/{level}/{parentId?}', function($level, $parentId = null) {
            try {
                $weight = CriteriaWeight::where('level', $level)
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
    
    Route::prefix('student')->name('student.')->middleware(['auth', 'student'])->group(function () {
        
        Route::get('/application/{application}/status', function($applicationId) {
            try {
                $application = \App\Models\Application::findOrFail($applicationId);
                
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