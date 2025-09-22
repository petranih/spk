<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Period;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\ApplicationValue;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    public function create(Request $request)
    {
        $requestedPeriodId = $request->get('period');
        
        if ($requestedPeriodId) {
            $selectedPeriod = Period::where('id', $requestedPeriodId)
                ->where('is_active', true)
                ->first();
                
            if (!$selectedPeriod) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Periode yang diminta tidak tersedia atau sudah berakhir');
            }
            
            if (!$selectedPeriod->is_ongoing || !$selectedPeriod->canAcceptApplications()) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Periode "' . $selectedPeriod->name . '" tidak sedang menerima pendaftaran');
            }
            
            $targetPeriod = $selectedPeriod;
        } else {
            $targetPeriod = Period::active()->first();
            
            if (!$targetPeriod) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Tidak ada periode pendaftaran yang aktif');
            }
        }

        $existingApplication = Application::where('user_id', Auth::id())
            ->where('period_id', $targetPeriod->id)
            ->first();

        if ($existingApplication) {
            return redirect()->route('student.application.edit', $existingApplication->id)
                ->with('info', 'Anda sudah memiliki aplikasi untuk periode "' . $targetPeriod->name . '". Silakan lengkapi data aplikasi Anda.');
        }

        return view('student.application.create', compact('targetPeriod'));
    }

    public function store(Request $request)
    {
        $periodId = $request->input('period_id');
        
        if ($periodId) {
            $targetPeriod = Period::where('id', $periodId)
                ->where('is_active', true)
                ->first();
        } else {
            $targetPeriod = Period::active()->first();
        }
        
        if (!$targetPeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode pendaftaran tidak tersedia atau sudah berakhir');
        }

        if (!$targetPeriod->is_ongoing || !$targetPeriod->canAcceptApplications()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode "' . $targetPeriod->name . '" tidak sedang menerima pendaftaran');
        }

        $existingApplication = Application::where('user_id', Auth::id())
            ->where('period_id', $targetPeriod->id)
            ->first();
            
        if ($existingApplication) {
            return redirect()->route('student.application.edit', $existingApplication->id)
                ->with('info', 'Anda sudah memiliki aplikasi untuk periode ini. Silakan lengkapi data aplikasi Anda.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'nisn' => 'required|string|max:20',
            'school' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        $application = Application::create([
            'user_id' => Auth::id(),
            'period_id' => $targetPeriod->id,
            'full_name' => $request->full_name,
            'nisn' => $request->nisn,
            'school' => $request->school,
            'class' => $request->class,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'gender' => $request->gender,
            'address' => $request->address,
            'phone' => $request->phone,
            'status' => 'draft',
        ]);

        Log::info('New application created', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'period_id' => $targetPeriod->id,
            'period_name' => $targetPeriod->name
        ]);

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi untuk periode "' . $targetPeriod->name . '" berhasil disimpan. Silakan lengkapi data kriteria.');
    }

    public function edit(Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active()->with(['subSubCriterias' => function($subQuery) {
                    $subQuery->active()->orderBy('order');
                }])->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        $existingValues = collect();
        $rawValues = ApplicationValue::where('application_id', $application->id)->get();
        
        Log::info('Loading application values for edit', [
            'application_id' => $application->id,
            'raw_values_count' => $rawValues->count(),
            'raw_values' => $rawValues->map(function($val) {
                return [
                    'id' => $val->id,
                    'criteria_type' => $val->criteria_type,
                    'criteria_id' => $val->criteria_id,
                    'value' => $val->value,
                    'score' => $val->score
                ];
            })->toArray()
        ]);
        
        foreach ($rawValues as $value) {
            $key = $value->criteria_type . '_' . $value->criteria_id;
            $existingValues[$key] = $value;
        }

        $documents = ApplicationDocument::where('application_id', $application->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.application.edit', compact('application', 'criterias', 'existingValues', 'documents'));
    }

    // FIXED: Save criteria dengan penanganan BigDecimal yang benar
    public function saveCriteria(Request $request, Application $application)
    {
        Log::info('SaveCriteria called', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan'], 403);
        }

        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi yang sudah disubmit tidak dapat diubah'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'criteria_type' => 'required|string|in:subcriteria,subsubcriteria',
            'criteria_id' => 'required|integer|min:1',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            Log::error('SaveCriteria validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Data tidak valid: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $application) {
                $criteriaType = $request->input('criteria_type');
                $criteriaId = (int) $request->input('criteria_id');
                $value = $request->input('value');

                Log::info('Processing criteria save with validated data', [
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'value_type' => gettype($value)
                ]);

                // Hapus nilai kriteria yang lama untuk criteria_id yang sama
                $deletedCount = ApplicationValue::where('application_id', $application->id)
                    ->where('criteria_type', $criteriaType)
                    ->where('criteria_id', $criteriaId)
                    ->delete();

                Log::info('Deleted existing values', ['count' => $deletedCount]);

                // FIXED: Calculate score dengan penanganan BigDecimal yang benar
                $score = $this->calculateScore($criteriaType, $criteriaId, $value);
                
                Log::info('Score calculation result', [
                    'raw_score' => $score,
                    'score_type' => gettype($score)
                ]);
                
                // FIXED: Gunakan query builder raw untuk menghindari masalah BigDecimal
                $newValueId = DB::table('application_values')->insertGetId([
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => (float) $score, // Cast ke float untuk menghindari BigDecimal
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Created new application value successfully', [
                    'id' => $newValueId,
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => (float) $score
                ]);
            });

            // Hitung total kriteria yang sudah disimpan
            $totalSaved = ApplicationValue::where('application_id', $application->id)->count();

            Log::info('Auto-save criteria berhasil', [
                'application_id' => $application->id,
                'total_saved' => $totalSaved
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kriteria berhasil disimpan otomatis',
                'total_criteria_saved' => $totalSaved
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-save criteria gagal', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kriteria: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        if ($application->status !== 'draft') {
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi yang sudah disubmit tidak dapat diubah.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'nisn' => 'required|string|max:20',
            'school' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        try {
            DB::transaction(function () use ($request, $application) {
                $application->update([
                    'full_name' => $request->full_name,
                    'nisn' => $request->nisn,
                    'school' => $request->school,
                    'class' => $request->class,
                    'birth_date' => $request->birth_date,
                    'birth_place' => $request->birth_place,
                    'gender' => $request->gender,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'updated_at' => now(),
                ]);

                Log::info('Personal data updated successfully', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id()
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error updating application: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }

        $savedValuesCount = ApplicationValue::where('application_id', $application->id)->count();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pribadi berhasil diperbarui',
                'saved_criteria_count' => $savedValuesCount
            ]);
        }

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', "Data pribadi berhasil diperbarui. Kriteria tersimpan: {$savedValuesCount}");
    }

    public function submit(Request $request, Application $application)
    {
        Log::info('Submit application called', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'application_status' => $application->status
        ]);

        if ($application->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
            }
            abort(403, 'Unauthorized access to application.');
        }

        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi tidak dapat disubmit'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi tidak dapat disubmit');
        }

        try {
            $validationErrors = [];

            // 1. Check personal data
            $personalDataFields = [
                'full_name' => 'Nama Lengkap',
                'nisn' => 'NISN', 
                'school' => 'Sekolah',
                'class' => 'Kelas',
                'birth_date' => 'Tanggal Lahir',
                'birth_place' => 'Tempat Lahir',
                'gender' => 'Jenis Kelamin',
                'address' => 'Alamat',
                'phone' => 'No. Telepon'
            ];

            foreach ($personalDataFields as $field => $label) {
                if (!$application->$field || trim($application->$field) === '') {
                    $validationErrors[] = "Data pribadi belum lengkap: $label";
                }
            }

            Log::info('Personal data validation', [
                'errors' => $validationErrors
            ]);

            // 2. FIXED: Check criteria values dengan penghitungan yang benar
            $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
            
            // Hitung berdasarkan kriteria utama yang memiliki subcriteria
            $mainCriteriaWithSubcriteria = Criteria::active()
                ->with(['subCriterias' => function($query) {
                    $query->active();
                }])
                ->get()
                ->filter(function($criteria) {
                    return $criteria->subCriterias->count() > 0;
                });
            
            $expectedCriteriaCount = $mainCriteriaWithSubcriteria->count();
            
            Log::info('Criteria validation', [
                'application_id' => $application->id,
                'total_values' => $applicationValues->count(),
                'expected_criteria' => $expectedCriteriaCount,
                'main_criteria_with_subcriteria' => $mainCriteriaWithSubcriteria->count(),
                'values_detail' => $applicationValues->map(function($v) {
                    return [
                        'id' => $v->id,
                        'type' => $v->criteria_type,
                        'criteria_id' => $v->criteria_id,
                        'value' => $v->value,
                        'score' => is_object($v->score) ? (string) $v->score : $v->score
                    ];
                })->toArray()
            ]);

            if ($applicationValues->count() === 0) {
                $validationErrors[] = 'Data kriteria AHP belum diisi sama sekali';
            } elseif ($applicationValues->count() < $expectedCriteriaCount) {
                $validationErrors[] = "Kriteria belum lengkap ({$applicationValues->count()} dari {$expectedCriteriaCount} kriteria terisi)";
            }

            // 3. Check documents
            $requiredDocuments = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
            $existingDocs = ApplicationDocument::where('application_id', $application->id)
                ->pluck('document_type')
                ->toArray();

            Log::info('Document validation', [
                'required_docs' => $requiredDocuments,
                'existing_docs' => $existingDocs
            ]);

            $missingDocs = array_diff($requiredDocuments, $existingDocs);
            if (!empty($missingDocs)) {
                $docNames = [
                    'ktp' => 'KTP Orang Tua',
                    'kk' => 'Kartu Keluarga', 
                    'slip_gaji' => 'Slip Gaji/Surat Keterangan Penghasilan',
                    'surat_keterangan' => 'Surat Keterangan Tidak Mampu'
                ];
                
                $missingDocNames = array_map(function($doc) use ($docNames) {
                    return $docNames[$doc];
                }, $missingDocs);
                
                $validationErrors[] = 'Dokumen belum lengkap: ' . implode(', ', $missingDocNames);
            }

            if (!empty($validationErrors)) {
                Log::warning('Application submission failed validation', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id(),
                    'errors' => $validationErrors
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aplikasi belum dapat disubmit',
                        'errors' => $validationErrors,
                        'detailed_message' => implode('; ', $validationErrors)
                    ], 422);
                }
                return redirect()->route('student.application.edit', $application->id)
                    ->with('error', 'Aplikasi belum dapat disubmit: ' . implode('; ', $validationErrors));
            }

            // All validation passed - submit application
            DB::transaction(function() use ($application, $applicationValues) {
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'updated_at' => now(),
                ]);

                // FIXED: Calculate total score dengan handling BigDecimal yang benar
                $totalScore = 0;
                foreach ($applicationValues as $value) {
                    $score = is_object($value->score) ? (float) $value->score : (float) $value->score;
                    $totalScore += $score;
                }

                Log::info('APPLICATION SUBMITTED SUCCESSFULLY', [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'submitted_at' => $application->submitted_at,
                    'criteria_values_count' => $applicationValues->count(),
                    'total_score' => $totalScore
                ]);
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aplikasi berhasil disubmit untuk validasi',
                    'redirect_url' => route('student.dashboard')
                ]);
            }

            return redirect()->route('student.dashboard')
                ->with('success', 'Aplikasi berhasil disubmit untuk validasi.');
                
        } catch (\Exception $e) {
            Log::error('Application submission failed', [
                'application_id' => $application->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit aplikasi: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal submit aplikasi: ' . $e->getMessage());
        }
    }

    // FIXED: Calculate score method dengan penanganan BigDecimal yang tepat
// FIXED: Calculate score method dengan logic yang benar
private function calculateScore($criteriaType, $criteriaId, $value)
{
    try {
        Log::info('Calculating score', [
            'criteria_type' => $criteriaType,
            'criteria_id' => $criteriaId,
            'value' => $value
        ]);

        $score = 0.0;

        // FIXED: Gunakan $criteriaId untuk mencari score, bukan $value
        if ($criteriaType === 'subcriteria') {
            $subCriteria = SubCriteria::find($criteriaId); // FIXED: Gunakan $criteriaId
            if ($subCriteria && $subCriteria->score !== null) {
                // Handle BigDecimal dengan konversi eksplisit ke float
                $rawScore = $subCriteria->score;
                if (is_object($rawScore) && method_exists($rawScore, 'toFloat')) {
                    $score = $rawScore->toFloat();
                } elseif (is_numeric($rawScore)) {
                    $score = (float) $rawScore;
                } else {
                    $score = 0.0;
                }
            }
            
            Log::info('SubCriteria score calculated', [
                'subcriteria_id' => $criteriaId, // FIXED: Log yang benar
                'subcriteria_found' => $subCriteria ? true : false,
                'raw_score_from_db' => $subCriteria ? $subCriteria->score : null,
                'calculated_score' => $score,
                'score_type' => gettype($score)
            ]);
            
        } elseif ($criteriaType === 'subsubcriteria') {
            $subSubCriteria = SubSubCriteria::find($criteriaId); // FIXED: Gunakan $criteriaId
            if ($subSubCriteria && $subSubCriteria->score !== null) {
                // Handle BigDecimal dengan konversi eksplisit ke float
                $rawScore = $subSubCriteria->score;
                if (is_object($rawScore) && method_exists($rawScore, 'toFloat')) {
                    $score = $rawScore->toFloat();
                } elseif (is_numeric($rawScore)) {
                    $score = (float) $rawScore;
                } else {
                    $score = 0.0;
                }
            }
            
            Log::info('SubSubCriteria score calculated', [
                'subsubcriteria_id' => $criteriaId, // FIXED: Log yang benar
                'subsubcriteria_found' => $subSubCriteria ? true : false,
                'raw_score_from_db' => $subSubCriteria ? $subSubCriteria->score : null,
                'calculated_score' => $score,
                'score_type' => gettype($score)
            ]);
        }
        
        // FIXED: Jika tidak ada score dari database criteria, gunakan value sebagai fallback
        if ($score <= 0 && is_numeric($value)) {
            $score = (float) $value;
            Log::info('Using numeric value as fallback score', [
                'value' => $value,
                'fallback_score' => $score
            ]);
        }
        
        // FIXED: Jika masih 0 dan ada value, berikan score minimal
        if ($score <= 0 && !empty($value)) {
            $score = 1.0; // Score minimal untuk jawaban yang diisi
            Log::info('Using minimal score for non-empty value', [
                'value' => $value,
                'minimal_score' => $score
            ]);
        }
        
        // Ensure score is a valid positive float
        $finalScore = max(0.0, (float) $score);
        
        Log::info('Final score calculation', [
            'criteria_type' => $criteriaType,
            'criteria_id' => $criteriaId,
            'value' => $value,
            'raw_score' => $score,
            'final_score' => $finalScore,
            'final_score_type' => gettype($finalScore)
        ]);
        
        return $finalScore;
        
    } catch (\Exception $e) {
        Log::error('Error calculating score: ' . $e->getMessage(), [
            'criteria_type' => $criteriaType,
            'criteria_id' => $criteriaId,
            'value' => $value,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return minimal score for any filled value even if calculation fails
        return !empty($value) ? 1.0 : 0.0;
    }
}

    public function uploadDocument(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi yang sudah disubmit tidak dapat diubah'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'document_type' => 'required|string|in:ktp,kk,slip_gaji,surat_keterangan',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Validasi gagal: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        try {
            $existingDoc = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type', $request->document_type)
                ->first();

            if ($existingDoc) {
                if (Storage::disk('public')->exists($existingDoc->file_path)) {
                    Storage::disk('public')->delete($existingDoc->file_path);
                }
                $existingDoc->delete();
            }

            $file = $request->file('file');
            $directory = 'documents/' . $application->id;
            Storage::disk('public')->makeDirectory($directory);
            
            $extension = $file->getClientOriginalExtension();
            $filename = $request->document_type . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            $path = $file->storeAs($directory, $filename, 'public');

            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('File gagal disimpan ke storage');
            }

            $document = ApplicationDocument::create([
                'application_id' => $application->id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'document' => $document,
                'document_type_display' => $this->getDocumentTypeDisplay($document->document_type),
                'file_size_display' => number_format($document->file_size / 1024, 1) . ' KB',
                'created_at_display' => $document->created_at->format('d/m/Y H:i'),
                'view_url' => Storage::url($document->file_path),
                'delete_url' => route('student.application.document.delete', [$application->id, $document->id])
            ]);
                
        } catch (\Exception $e) {
            Log::error('Document upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteDocument(Request $request, Application $application, ApplicationDocument $document)
    {
        if ($application->user_id !== Auth::id() || $document->application_id !== $application->id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi yang sudah disubmit tidak dapat diubah'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi yang sudah disubmit tidak dapat diubah.');
        }

        try {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            $document->delete();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
            }

            return redirect()->route('student.application.edit', $application->id)
                ->with('success', 'Dokumen berhasil dihapus');
                
        } catch (\Exception $e) {
            Log::error('Document deletion failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus dokumen'], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal menghapus dokumen. Silakan coba lagi.');
        }
    }

    private function getDocumentTypeDisplay($type)
    {
        $types = [
            'ktp' => 'KTP',
            'kk' => 'KK',
            'slip_gaji' => 'Slip Gaji',
            'surat_keterangan' => 'Surat Keterangan'
        ];
        
        return $types[$type] ?? $type;
    }
}