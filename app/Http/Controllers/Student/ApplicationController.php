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
                // Update basic information
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

                // ENHANCED: Process criteria values with comprehensive debugging
                Log::info('DETAILED: Starting criteria processing', [
                    'application_id' => $application->id,
                    'full_request' => $request->all(),
                    'has_criteria_values' => $request->has('criteria_values'),
                    'criteria_values_content' => $request->input('criteria_values')
                ]);

                // Clear existing values first
                ApplicationValue::where('application_id', $application->id)->delete();
                Log::info('Cleared existing application values for application ' . $application->id);

                $createdCount = 0;
                $processedEntries = [];
                
                // IMPROVED: Multiple processing strategies
                
                // Strategy 1: Process nested criteria_values structure
                if ($request->has('criteria_values')) {
                    $criteriaValues = $request->input('criteria_values');
                    Log::info('Processing nested criteria_values structure', ['data' => $criteriaValues]);
                    
                    if (is_array($criteriaValues)) {
                        foreach ($criteriaValues as $criteriaType => $typeValues) {
                            if (is_array($typeValues)) {
                                foreach ($typeValues as $criteriaId => $selectedValue) {
                                    if (!empty($selectedValue)) {
                                        $result = $this->createApplicationValue($application->id, $criteriaType, $criteriaId, $selectedValue);
                                        if ($result['success']) {
                                            $createdCount++;
                                            $processedEntries[] = [
                                                'type' => $criteriaType,
                                                'id' => $criteriaId,
                                                'value' => $selectedValue,
                                                'source' => 'nested_structure'
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Strategy 2: Process individual form inputs (radio buttons)
                foreach ($request->all() as $inputName => $inputValue) {
                    if (empty($inputValue)) continue;

                    // Match patterns: criteria_values[type][id]
                    if (preg_match('/criteria_values\[(\w+)\]\[(\d+)\]/', $inputName, $matches)) {
                        $criteriaType = $matches[1];
                        $criteriaId = (int)$matches[2];
                        
                        // Avoid duplicates from strategy 1
                        $duplicate = false;
                        foreach ($processedEntries as $entry) {
                            if ($entry['type'] === $criteriaType && $entry['id'] === $criteriaId) {
                                $duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$duplicate) {
                            $result = $this->createApplicationValue($application->id, $criteriaType, $criteriaId, $inputValue);
                            if ($result['success']) {
                                $createdCount++;
                                $processedEntries[] = [
                                    'type' => $criteriaType,
                                    'id' => $criteriaId,
                                    'value' => $inputValue,
                                    'source' => 'individual_input'
                                ];
                            }
                        }
                    }
                }

                // Strategy 3: Process direct radio button names (fallback)
                foreach ($request->all() as $inputName => $inputValue) {
                    if (empty($inputValue) || strpos($inputName, '_token') !== false) continue;
                    
                    // Match patterns like: subcriteria_5, subsubcriteria_10
                    if (preg_match('/^(subcriteria|subsubcriteria)_(\d+)$/', $inputName, $matches)) {
                        $criteriaType = $matches[1];
                        $criteriaId = (int)$matches[2];
                        
                        // Avoid duplicates
                        $duplicate = false;
                        foreach ($processedEntries as $entry) {
                            if ($entry['type'] === $criteriaType && $entry['id'] === $criteriaId) {
                                $duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$duplicate) {
                            $result = $this->createApplicationValue($application->id, $criteriaType, $criteriaId, $inputValue);
                            if ($result['success']) {
                                $createdCount++;
                                $processedEntries[] = [
                                    'type' => $criteriaType,
                                    'id' => $criteriaId,
                                    'value' => $inputValue,
                                    'source' => 'direct_radio'
                                ];
                            }
                        }
                    }
                }

                // Final verification
                $finalDbCount = ApplicationValue::where('application_id', $application->id)->count();
                
                Log::info('CRITERIA PROCESSING COMPLETE', [
                    'application_id' => $application->id,
                    'created_count' => $createdCount,
                    'final_db_count' => $finalDbCount,
                    'processed_entries' => $processedEntries,
                    'success' => $finalDbCount > 0
                ]);

                // Additional debugging for zero results
                if ($finalDbCount === 0) {
                    Log::warning('NO CRITERIA VALUES SAVED - DEBUGGING', [
                        'application_id' => $application->id,
                        'request_method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'all_input_keys' => array_keys($request->all()),
                        'request_size' => strlen(serialize($request->all())),
                        'criteria_related_inputs' => array_filter($request->all(), function($key) {
                            return strpos($key, 'criteria') !== false || strpos($key, 'subcriteria') !== false;
                        }, ARRAY_FILTER_USE_KEY)
                    ]);
                }
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

        // Success response with verification
        $savedValuesCount = ApplicationValue::where('application_id', $application->id)->count();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data aplikasi berhasil diperbarui',
                'saved_criteria_count' => $savedValuesCount
            ]);
        }

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', "Data aplikasi berhasil diperbarui. Kriteria tersimpan: {$savedValuesCount}");
    }

    private function createApplicationValue($applicationId, $criteriaType, $criteriaId, $value)
    {
        try {
            $score = $this->calculateScore($criteriaType, $criteriaId, $value);
            
            $applicationValue = ApplicationValue::create([
                'application_id' => $applicationId,
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value,
                'score' => $score,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('CREATED application value', [
                'id' => $applicationValue->id,
                'application_id' => $applicationId,
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value,
                'score' => $score
            ]);

            return ['success' => true, 'value' => $applicationValue];
            
        } catch (\Exception $e) {
            Log::error('FAILED to create application value', [
                'application_id' => $applicationId,
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
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

    public function submit(Request $request, Application $application)
    {
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

            // Check personal data
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

            $missingPersonalData = [];
            foreach ($personalDataFields as $field => $label) {
                if (!$application->$field || trim($application->$field) === '') {
                    $missingPersonalData[] = $label;
                }
            }

            if (!empty($missingPersonalData)) {
                $validationErrors[] = 'Data pribadi belum lengkap: ' . implode(', ', $missingPersonalData);
            }

            // IMPROVED: Enhanced criteria validation
            $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
            $validValues = $applicationValues->filter(function($value) {
                return !empty($value->value) && $value->value !== '' && $value->value !== null;
            });
            
            Log::info('SUBMIT VALIDATION - Criteria check', [
                'application_id' => $application->id,
                'total_values' => $applicationValues->count(),
                'valid_values' => $validValues->count(),
                'values_detail' => $applicationValues->map(function($v) {
                    return [
                        'id' => $v->id,
                        'type' => $v->criteria_type,
                        'criteria_id' => $v->criteria_id,
                        'value' => $v->value,
                        'score' => $v->score,
                        'is_valid' => !empty($v->value) && $v->value !== ''
                    ];
                })->toArray()
            ]);

            if ($validValues->count() === 0) {
                $validationErrors[] = 'Data kriteria AHP belum diisi. Silakan pilih kriteria terlebih dahulu dan klik "Simpan Perubahan"';
            }

            // Check documents
            $requiredDocuments = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
            $existingDocs = ApplicationDocument::where('application_id', $application->id)
                ->pluck('document_type')
                ->toArray();

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
                
                $validationErrors[] = 'Dokumen yang belum diupload: ' . implode(', ', $missingDocNames);
            }

            if (!empty($validationErrors)) {
                Log::warning('Application submission failed validation', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id(),
                    'errors' => $validationErrors,
                    'current_criteria_count' => $validValues->count()
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

            // Submit application
            DB::transaction(function() use ($application, $validValues) {
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('APPLICATION SUBMITTED SUCCESSFULLY', [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'submitted_at' => $application->submitted_at,
                    'criteria_values_count' => $validValues->count(),
                    'total_score' => $validValues->sum('score')
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

    private function calculateScore($criteriaType, $criteriaId, $value)
    {
        try {
            Log::info('Calculating score', [
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value
            ]);

            if ($criteriaType === 'criteria') {
                $criteria = Criteria::find($criteriaId);
                return $criteria?->score ?? 1;
                
            } elseif ($criteriaType === 'subcriteria') {
                if (is_numeric($value)) {
                    $subCriteria = SubCriteria::find($value);
                    return $subCriteria?->score ?? 1;
                }
                return 1;
                
            } elseif ($criteriaType === 'subsubcriteria') {
                if (is_numeric($value)) {
                    $subSubCriteria = SubSubCriteria::find($value);
                    return $subSubCriteria?->score ?? 1;
                }
                return 1;
            }
            
        } catch (\Exception $e) {
            Log::error('Error calculating score: ' . $e->getMessage(), [
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value
            ]);
        }

        return 1;
    }

    private function getDocumentTypeDisplay($type)
    {
        $types = [
            'ktp' => 'KTP Orang Tua',
            'kk' => 'Kartu Keluarga',
            'slip_gaji' => 'Slip Gaji',
            'surat_keterangan' => 'Surat Keterangan'
        ];

        return $types[$type] ?? $type;
    }
    public function saveCriteria(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan'], 403);
        }

        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi yang sudah disubmit tidak dapat diubah'], 422);
        }

        $request->validate([
            'criteria_type' => 'required|string|in:subcriteria,subsubcriteria',
            'criteria_id' => 'required|integer',
            'value' => 'required'
        ]);

        try {
            DB::transaction(function () use ($request, $application) {
                $criteriaType = $request->input('criteria_type');
                $criteriaId = $request->input('criteria_id');
                $value = $request->input('value');

                // Hapus nilai kriteria yang lama untuk criteria_id yang sama
                ApplicationValue::where('application_id', $application->id)
                    ->where('criteria_type', $criteriaType)
                    ->where('criteria_id', $criteriaId)
                    ->delete();

                // Simpan nilai baru
                $score = $this->calculateScore($criteriaType, $criteriaId, $value);
                
                ApplicationValue::create([
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => $score,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Auto-save criteria berhasil', [
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => $score
                ]);
            });

            // Hitung total kriteria yang sudah disimpan
            $totalSaved = ApplicationValue::where('application_id', $application->id)->count();

            return response()->json([
                'success' => true,
                'message' => 'Kriteria berhasil disimpan otomatis',
                'total_criteria_saved' => $totalSaved
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-save criteria gagal', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kriteria: ' . $e->getMessage()
            ], 500);
        }
    }
}