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
            'raw_values_count' => $rawValues->count()
        ]);
        
        foreach ($rawValues as $value) {
            if ($value->criteria_type === 'subsubcriteria') {
                $key = 'subsubcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            } elseif ($value->criteria_type === 'subcriteria') {
                $key = 'subcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            }
        }

        $documents = ApplicationDocument::where('application_id', $application->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.application.edit', compact('application', 'criterias', 'existingValues', 'documents'));
    }

    /**
     * FUNGSI SAVE CRITERIA - FIXED UNTUK RADIO GROUP
     */
    public function saveCriteria(Request $request, Application $application)
    {
        Log::info('=== SAVE CRITERIA REQUEST ===', [
            'application_id' => $application->id,
            'request_data' => $request->all()
        ]);

        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan'], 403);
        }

        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'criteria_type' => 'required|string|in:subcriteria,subsubcriteria',
            'criteria_id' => 'required|integer|min:1',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Data tidak valid'
            ], 422);
        }

        try {
            $criteriaType = $request->input('criteria_type');
            $criteriaId = (int) $request->input('criteria_id');
            $value = $request->input('value');

            DB::beginTransaction();

            try {
                if ($criteriaType === 'subsubcriteria') {
                    $subSubCriteria = SubSubCriteria::find($criteriaId);
                    if (!$subSubCriteria) {
                        throw new \Exception('SubSubCriteria tidak ditemukan');
                    }
                    
                    $parentSubCriteriaId = $subSubCriteria->sub_criteria_id;
                    $subCriteria = SubCriteria::find($parentSubCriteriaId);
                    $mainCriteriaId = $subCriteria->criteria_id;
                    
                    Log::info('SubSubCriteria selected', [
                        'subsubcriteria_id' => $criteriaId,
                        'parent_subcriteria_id' => $parentSubCriteriaId,
                        'main_criteria_id' => $mainCriteriaId
                    ]);
                    
                    // Hapus hanya SubSubCriteria dalam SATU SubCriteria yang sama
                    $deletedSubSub = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subsubcriteria')
                        ->whereIn('criteria_id', function($query) use ($parentSubCriteriaId) {
                            $query->select('id')
                                  ->from('sub_sub_criterias')
                                  ->where('sub_criteria_id', $parentSubCriteriaId);
                        })
                        ->delete();
                    
                    Log::info('Deleted old SubSubCriteria in same radio group', [
                        'parent_subcriteria' => $parentSubCriteriaId,
                        'count' => $deletedSubSub
                    ]);
                    
                } elseif ($criteriaType === 'subcriteria') {
                    $subCriteria = SubCriteria::find($criteriaId);
                    if (!$subCriteria) {
                        throw new \Exception('SubCriteria tidak ditemukan');
                    }
                    
                    $mainCriteriaId = $subCriteria->criteria_id;
                    
                    Log::info('SubCriteria selected', [
                        'subcriteria_id' => $criteriaId,
                        'main_criteria_id' => $mainCriteriaId
                    ]);
                    
                    // Hapus hanya SubCriteria dalam SATU Criteria yang sama
                    $allSubCriteriaInSameMainCriteria = SubCriteria::where('criteria_id', $mainCriteriaId)
                        ->pluck('id')
                        ->toArray();
                    
                    $deletedSub = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->whereIn('criteria_id', $allSubCriteriaInSameMainCriteria)
                        ->delete();
                    
                    Log::info('Deleted old SubCriteria in same main criteria', [
                        'main_criteria_id' => $mainCriteriaId,
                        'subcriteria_ids_in_group' => $allSubCriteriaInSameMainCriteria,
                        'count' => $deletedSub
                    ]);
                }

                // Hitung score
                $score = $this->calculateScore($criteriaType, $criteriaId);

                // Simpan nilai BARU (hanya 1)
                $newValue = ApplicationValue::create([
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => $score,
                ]);

                Log::info('New value saved', [
                    'id' => $newValue->id,
                    'type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'score' => $score
                ]);

                DB::commit();

                // CRITICAL FIX: Hitung berapa CRITERIA UTAMA yang sudah terisi
                $totalSaved = $this->getCorrectSavedCount($application->id);

                Log::info('=== SAVE SUCCESS ===', ['total_saved' => $totalSaved]);

                return response()->json([
                    'success' => true,
                    'message' => 'Kriteria berhasil disimpan',
                    'total_criteria_saved' => $totalSaved
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('=== SAVE FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * CRITICAL FIX: Hitung berapa MAIN CRITERIA (C1, C2, C3...) yang sudah terisi
     * BUKAN SubCriteria!
     */
    private function getCorrectSavedCount($applicationId)
    {
        // Ambil semua nilai yang tersimpan
        $savedSubCriteriaIds = ApplicationValue::where('application_id', $applicationId)
            ->where('criteria_type', 'subcriteria')
            ->pluck('criteria_id')
            ->toArray();
        
        $savedSubSubCriteriaIds = ApplicationValue::where('application_id', $applicationId)
            ->where('criteria_type', 'subsubcriteria')
            ->pluck('criteria_id')
            ->toArray();
        
        // Ambil parent SubCriteria dari SubSubCriteria
        $parentIdsFromSubSub = [];
        if (!empty($savedSubSubCriteriaIds)) {
            $parentIdsFromSubSub = SubSubCriteria::whereIn('id', $savedSubSubCriteriaIds)
                ->pluck('sub_criteria_id')
                ->toArray();
        }
        
        // Gabungkan semua SubCriteria yang terisi
        $allFilledSubCriteriaIds = array_unique(array_merge($savedSubCriteriaIds, $parentIdsFromSubSub));
        
        // CRITICAL FIX: Ambil MAIN CRITERIA ID (C1, C2, C3...)
        $mainCriteriaIds = SubCriteria::whereIn('id', $allFilledSubCriteriaIds)
            ->pluck('criteria_id')
            ->unique()
            ->toArray();
        
        // HITUNG BERAPA MAIN CRITERIA YANG SUDAH TERISI
        $totalCount = count($mainCriteriaIds);
        
        Log::info('Counting saved MAIN CRITERIA', [
            'application_id' => $applicationId,
            'saved_subcriteria' => $savedSubCriteriaIds,
            'saved_subsubcriteria' => $savedSubSubCriteriaIds,
            'parents_from_subsub' => $parentIdsFromSubSub,
            'filled_subcriteria_ids' => $allFilledSubCriteriaIds,
            'main_criteria_ids' => $mainCriteriaIds,
            'total_MAIN_CRITERIA_filled' => $totalCount
        ]);
        
        return $totalCount;
    }

    private function calculateScore($criteriaType, $criteriaId)
    {
        try {
            $score = 0.0;

            if ($criteriaType === 'subcriteria') {
                $subCriteria = SubCriteria::find($criteriaId);
                
                if ($subCriteria) {
                    if ($subCriteria->weight !== null && $subCriteria->weight > 0) {
                        $score = (float) $subCriteria->weight;
                    } else {
                        if ($subCriteria->subSubCriterias && $subCriteria->subSubCriterias->count() > 0) {
                            $maxWeight = $subCriteria->subSubCriterias->max('weight');
                            $score = $maxWeight ? (float) $maxWeight : 0.0;
                        }
                    }
                }
                
            } elseif ($criteriaType === 'subsubcriteria') {
                $subSubCriteria = SubSubCriteria::find($criteriaId);
                
                if ($subSubCriteria && $subSubCriteria->weight !== null) {
                    $score = (float) $subSubCriteria->weight;
                }
            }
            
            return $score;
            
        } catch (\Exception $e) {
            Log::error('Error calculating score', [
                'type' => $criteriaType,
                'id' => $criteriaId,
                'error' => $e->getMessage()
            ]);
            
            return 0.0;
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

                Log::info('Personal data updated', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id()
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Update failed', ['error' => $e->getMessage()]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Terjadi kesalahan saat menyimpan data');
        }

        $savedCount = $this->getCorrectSavedCount($application->id);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pribadi berhasil diperbarui',
                'saved_criteria_count' => $savedCount
            ]);
        }

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', "Data pribadi berhasil diperbarui");
    }

    public function submit(Request $request, Application $application)
    {
        Log::info('=== SUBMIT APPLICATION ===', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'status' => $application->status
        ]);

        if ($application->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi sudah disubmit');
        }

        try {
            $validationErrors = [];

            // Check personal data
            $personalFields = ['full_name', 'nisn', 'school', 'class', 'birth_date', 'birth_place', 'gender', 'address', 'phone'];
            foreach ($personalFields as $field) {
                if (!$application->$field || trim($application->$field) === '') {
                    $validationErrors[] = "Data pribadi belum lengkap: $field";
                }
            }

            // CRITICAL FIX: Check MAIN CRITERIA count (23), bukan SubCriteria (22)
            $expectedCriteriaCount = Criteria::where('is_active', true)->count();
            $savedCriteriaCount = $this->getCorrectSavedCount($application->id);

            Log::info('Criteria validation', [
                'expected_MAIN_CRITERIA' => $expectedCriteriaCount,
                'saved_MAIN_CRITERIA' => $savedCriteriaCount
            ]);

            if ($savedCriteriaCount < $expectedCriteriaCount) {
                $validationErrors[] = "Kriteria belum lengkap ($savedCriteriaCount dari $expectedCriteriaCount)";
            }

            // Check documents
            $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
            $existingDocs = ApplicationDocument::where('application_id', $application->id)
                ->pluck('document_type')
                ->toArray();

            $missingDocs = array_diff($requiredDocs, $existingDocs);
            if (!empty($missingDocs)) {
                $validationErrors[] = 'Dokumen belum lengkap: ' . implode(', ', $missingDocs);
            }

            if (!empty($validationErrors)) {
                Log::warning('Submit validation failed', ['errors' => $validationErrors]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validationErrors,
                        'detailed_message' => implode('; ', $validationErrors)
                    ], 422);
                }
                return redirect()->route('student.application.edit', $application->id)
                    ->with('error', implode('; ', $validationErrors));
            }

            DB::transaction(function() use ($application) {
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            });

            Log::info('APPLICATION SUBMITTED', [
                'application_id' => $application->id,
                'user_id' => $application->user_id
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aplikasi berhasil disubmit',
                    'redirect_url' => route('student.dashboard')
                ]);
            }

            return redirect()->route('student.dashboard')
                ->with('success', 'Aplikasi berhasil disubmit');
                
        } catch (\Exception $e) {
            Log::error('Submit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal submit aplikasi');
        }
    }

    public function uploadDocument(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'document_type' => 'required|string|in:ktp,kk,slip_gaji,surat_keterangan',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => implode(', ', $validator->errors()->all())
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
                throw new \Exception('File gagal disimpan');
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
            Log::error('Upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteDocument(Request $request, Application $application, ApplicationDocument $document)
    {
        if ($application->user_id !== Auth::id() || $document->application_id !== $application->id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi sudah disubmit');
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
            Log::error('Delete failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus'], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal menghapus dokumen');
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