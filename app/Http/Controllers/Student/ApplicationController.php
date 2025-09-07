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
    // Support pemilihan periode spesifik dari parameter URL
    $requestedPeriodId = $request->get('period');
    
    if ($requestedPeriodId) {
        // Cari periode yang diminta
        $selectedPeriod = Period::where('id', $requestedPeriodId)
            ->where('is_active', true)
            ->first();
            
        if (!$selectedPeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode yang diminta tidak tersedia atau sudah berakhir');
        }
        
        // Cek apakah periode sedang berlangsung dan bisa menerima aplikasi
        if (!$selectedPeriod->is_ongoing || !$selectedPeriod->canAcceptApplications()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode "' . $selectedPeriod->name . '" tidak sedang menerima pendaftaran');
        }
        
        $targetPeriod = $selectedPeriod;
    } else {
        // Fallback ke periode aktif
        $targetPeriod = Period::active()->first();
        
        if (!$targetPeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Tidak ada periode pendaftaran yang aktif');
        }
    }

    // Cek apakah user sudah punya aplikasi untuk periode ini
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
    // PERBAIKAN: Support pemilihan periode dari form atau default ke periode aktif
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

    // Validasi periode masih bisa menerima aplikasi
    if (!$targetPeriod->is_ongoing || !$targetPeriod->canAcceptApplications()) {
        return redirect()->route('student.dashboard')
            ->with('error', 'Periode "' . $targetPeriod->name . '" tidak sedang menerima pendaftaran');
    }

    // Cek duplikasi aplikasi untuk periode ini
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
        // Pastikan aplikasi milik user yang sedang login
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Load relasi yang diperlukan
        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active()->with(['subSubCriterias' => function($subQuery) {
                    $subQuery->active()->orderBy('order');
                }])->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // PERBAIKAN: Get existing values dengan query yang lebih robust
        $existingValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

        Log::info('Loading existing values for application ' . $application->id, [
            'count' => $existingValues->count(),
            'keys' => $existingValues->keys()->toArray()
        ]);

        // Load documents
        $documents = ApplicationDocument::where('application_id', $application->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.application.edit', compact('application', 'criterias', 'existingValues', 'documents'));
    }

    public function update(Request $request, Application $application)
    {
        // Security check
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Cek apakah aplikasi masih bisa diedit
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

                // PERBAIKAN UTAMA: Proses kriteria dengan lebih hati-hati
                Log::info('Processing criteria values for update', [
                    'application_id' => $application->id,
                    'has_criteria_values' => $request->has('criteria_values'),
                    'raw_criteria_values' => $request->input('criteria_values', []),
                    'all_input_keys' => array_keys($request->all())
                ]);

                if ($request->has('criteria_values') && is_array($request->criteria_values)) {
                    // Hapus nilai kriteria lama
                    ApplicationValue::where('application_id', $application->id)->delete();

                    $createdCount = 0;
                    
                    foreach ($request->criteria_values as $criteriaType => $values) {
                        if (!is_array($values)) {
                            Log::warning('Invalid criteria values format', [
                                'criteria_type' => $criteriaType,
                                'values' => $values
                            ]);
                            continue;
                        }

                        foreach ($values as $criteriaId => $value) {
                            if (empty($value) || $value === '' || $value === null) {
                                Log::info('Skipping empty value', [
                                    'criteria_type' => $criteriaType,
                                    'criteria_id' => $criteriaId,
                                    'value' => $value
                                ]);
                                continue;
                            }

                            try {
                                $score = $this->calculateScore($criteriaType, $criteriaId, $value);
                                
                                // Pastikan score valid
                                if ($score === null || $score < 0) {
                                    $score = 1; // Default score
                                }
                                
                                $applicationValue = ApplicationValue::create([
                                    'application_id' => $application->id,
                                    'criteria_type' => $criteriaType,
                                    'criteria_id' => $criteriaId,
                                    'value' => $value,
                                    'score' => $score,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                $createdCount++;

                                Log::info('Created application value', [
                                    'id' => $applicationValue->id,
                                    'criteria_type' => $criteriaType,
                                    'criteria_id' => $criteriaId,
                                    'value' => $value,
                                    'score' => $score
                                ]);

                            } catch (\Exception $e) {
                                Log::error('Error creating application value', [
                                    'criteria_type' => $criteriaType,
                                    'criteria_id' => $criteriaId,
                                    'value' => $value,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }

                    Log::info('Criteria values update completed', [
                        'application_id' => $application->id,
                        'created_count' => $createdCount
                    ]);
                } else {
                    Log::warning('No criteria values in request', [
                        'has_criteria_values' => $request->has('criteria_values'),
                        'criteria_values_type' => gettype($request->input('criteria_values'))
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('Error updating application: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi berhasil diperbarui');
    }

    public function uploadDocument(Request $request, Application $application)
    {
        // Security checks
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
            // Cek dokumen existing
            $existingDoc = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type', $request->document_type)
                ->first();

            if ($existingDoc) {
                // Delete old file
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
        // Security check
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
            if (!$application->full_name || !$application->nisn || !$application->school || 
                !$application->class || !$application->birth_date || !$application->birth_place ||
                !$application->gender || !$application->address || !$application->phone) {
                $validationErrors[] = 'Data pribadi belum lengkap';
            }

            // PERBAIKAN: Check criteria values dengan query fresh
            $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
            
            Log::info('Submit validation - criteria check', [
                'application_id' => $application->id,
                'values_count' => $applicationValues->count(),
                'values_detail' => $applicationValues->map(function($val) {
                    return [
                        'criteria_type' => $val->criteria_type,
                        'criteria_id' => $val->criteria_id,
                        'value' => $val->value,
                        'score' => $val->score
                    ];
                })->toArray()
            ]);

            if ($applicationValues->count() == 0) {
                $validationErrors[] = 'Data kriteria AHP belum diisi';
            } else {
                $validValues = $applicationValues->filter(function($val) {
                    return $val->score !== null && $val->score >= 0;
                });
                
                if ($validValues->count() == 0) {
                    $validationErrors[] = 'Semua kriteria memiliki nilai tidak valid';
                }
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

            // If validation fails
            if (!empty($validationErrors)) {
                Log::warning('Application submission failed validation', [
                    'application_id' => $application->id,
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

            // Submit aplikasi
            DB::transaction(function() use ($application) {
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Application submitted successfully', [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'submitted_at' => $application->submitted_at
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
                if ($criteria && isset($criteria->score)) {
                    return $criteria->score;
                }
                return 1;
                
            } elseif ($criteriaType === 'subcriteria') {
                if (is_numeric($value)) {
                    // Value is SubSubCriteria ID
                    $subSubCriteria = SubSubCriteria::find($value);
                    if ($subSubCriteria) {
                        return $subSubCriteria->score ?? 1;
                    }
                    
                    // Or SubCriteria itself
                    $subCriteria = SubCriteria::find($criteriaId);
                    if ($subCriteria) {
                        return $subCriteria->score ?? 1;
                    }
                }
                return 1;
                
            } elseif ($criteriaType === 'subsubcriteria') {
                if (is_numeric($value)) {
                    $subSubCriteria = SubSubCriteria::find($value);
                    if ($subSubCriteria) {
                        return $subSubCriteria->score ?? 1;
                    }
                }
                
                $subSubCriteria = SubSubCriteria::find($criteriaId);
                if ($subSubCriteria) {
                    return $subSubCriteria->score ?? 1;
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

        return 1; // Default score
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
}