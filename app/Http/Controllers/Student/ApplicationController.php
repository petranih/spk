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
    public function create()
    {
        $activePeriod = Period::active()->first();
        
        if (!$activePeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Tidak ada periode pendaftaran yang aktif');
        }

        $existingApplication = Application::where('user_id', Auth::id())
            ->where('period_id', $activePeriod->id)
            ->first();

        if ($existingApplication) {
            return redirect()->route('student.application.edit', $existingApplication->id);
        }

        return view('student.application.create', compact('activePeriod'));
    }

    public function store(Request $request)
    {
        $activePeriod = Period::active()->first();
        
        if (!$activePeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Tidak ada periode pendaftaran yang aktif');
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
            'period_id' => $activePeriod->id,
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

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi berhasil disimpan. Silakan lengkapi data kriteria.');
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

        // PERBAIKAN: Get existing values dengan logging untuk debug
        $existingValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

        Log::info('Existing values for application ' . $application->id, [
            'count' => $existingValues->count(),
            'keys' => $existingValues->keys()->toArray(),
            'values' => $existingValues->pluck('value', 'criteria_type')->toArray()
        ]);

        // Load documents dengan proper relation
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

            // PERBAIKAN: Update criteria values dengan logging dan validasi yang lebih baik
            Log::info('Processing criteria values', [
                'application_id' => $application->id,
                'has_criteria_values' => $request->has('criteria_values'),
                'criteria_values' => $request->input('criteria_values', [])
            ]);

            if ($request->has('criteria_values') && is_array($request->criteria_values)) {
                foreach ($request->criteria_values as $criteriaType => $values) {
                    if (!is_array($values)) {
                        Log::warning('Invalid criteria values format', [
                            'criteria_type' => $criteriaType,
                            'values' => $values
                        ]);
                        continue;
                    }

                    foreach ($values as $criteriaId => $value) {
                        Log::info('Processing individual criteria value', [
                            'criteria_type' => $criteriaType,
                            'criteria_id' => $criteriaId,
                            'value' => $value
                        ]);

                        // Hapus existing value untuk criteria ini
                        $deletedCount = ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', $criteriaType)
                            ->where('criteria_id', $criteriaId)
                            ->delete();

                        Log::info('Deleted existing values', ['count' => $deletedCount]);

                        // Insert new value jika ada dan valid
                        if (!empty($value) && $value !== '') {
                            $score = $this->calculateScore($criteriaType, $criteriaId, $value);
                            
                            Log::info('Calculated score', [
                                'criteria_type' => $criteriaType,
                                'criteria_id' => $criteriaId,
                                'value' => $value,
                                'score' => $score
                            ]);
                            
                            // PERBAIKAN: Pastikan score tidak null dan validasi value
                            if ($score === null || $score < 0) {
                                $score = 0;
                                Log::warning('Invalid score calculated, setting to 0', [
                                    'criteria_type' => $criteriaType,
                                    'criteria_id' => $criteriaId,
                                    'original_score' => $score
                                ]);
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

                            Log::info('Created application value', [
                                'id' => $applicationValue->id,
                                'application_id' => $application->id,
                                'criteria_type' => $criteriaType,
                                'criteria_id' => $criteriaId,
                                'value' => $value,
                                'score' => $score
                            ]);
                        }
                    }
                }
            } else {
                Log::warning('No criteria values found in request', [
                    'application_id' => $application->id,
                    'request_data' => $request->all()
                ]);
            }
        });

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

        // PERBAIKAN: Validasi yang lebih ketat
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
            // Cek apakah sudah ada dokumen dengan tipe yang sama
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
            
            // PERBAIKAN: Pastikan directory exists
            $directory = 'documents/' . $application->id;
            Storage::disk('public')->makeDirectory($directory);
            
            // Generate unique filename dengan timestamp
            $extension = $file->getClientOriginalExtension();
            $filename = $request->document_type . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            // PERBAIKAN: Validasi apakah file benar-benar tersimpan
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

            // Return response untuk AJAX
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
        // Security checks
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
            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            // Delete record from database
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

        // Comprehensive validation before submit
        $validationErrors = [];

        // Check personal data completeness
        if (!$application->full_name || !$application->nisn || !$application->school || 
            !$application->class || !$application->birth_date || !$application->birth_place ||
            !$application->gender || !$application->address || !$application->phone) {
            $validationErrors[] = 'Data pribadi belum lengkap';
        }

        // PERBAIKAN: Check criteria values dengan logging
        $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
        Log::info('Validation check for application values', [
            'application_id' => $application->id,
            'values_count' => $applicationValues->count(),
            'values' => $applicationValues->pluck('score', 'criteria_type')->toArray()
        ]);

        if ($applicationValues->count() == 0) {
            $validationErrors[] = 'Data kriteria AHP belum diisi';
        } else {
            // Cek apakah semua nilai memiliki score > 0
            $validValues = $applicationValues->where('score', '>', 0)->count();
            $totalValues = $applicationValues->count();
            
            Log::info('Criteria values validation', [
                'valid_values' => $validValues,
                'total_values' => $totalValues
            ]);
            
            if ($validValues == 0) {
                $validationErrors[] = 'Semua nilai kriteria masih 0, silakan periksa kembali pilihan Anda';
            }
        }

        // Validate required documents
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

        // If there are validation errors, return with errors
        if (!empty($validationErrors)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aplikasi belum dapat disubmit: ' . implode('; ', $validationErrors)
                ], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi belum dapat disubmit: ' . implode('; ', $validationErrors));
        }

        try {
            DB::transaction(function() use ($application) {
                $application->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aplikasi berhasil disubmit untuk validasi'
                ]);
            }

            return redirect()->route('student.dashboard')
                ->with('success', 'Aplikasi berhasil disubmit untuk validasi. Silakan tunggu proses validasi dari administrator.');
                
        } catch (\Exception $e) {
            Log::error('Application submission failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit aplikasi. Silakan coba lagi.'
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal submit aplikasi. Silakan coba lagi.');
        }
    }

    private function calculateScore($criteriaType, $criteriaId, $value)
    {
        // PERBAIKAN: Logic perhitungan score yang lebih akurat dengan fallback
        try {
            Log::info('Calculating score', [
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value
            ]);

            if ($criteriaType === 'criteria') {
                // Untuk criteria langsung, value mungkin adalah ID dari subcriteria atau nilai langsung
                if (is_numeric($value)) {
                    $criteria = Criteria::find($criteriaId);
                    if ($criteria && isset($criteria->score)) {
                        return $criteria->score;
                    }
                }
                return 1; // Default score untuk criteria
                
            } elseif ($criteriaType === 'subcriteria') {
                // Value adalah ID dari SubSubCriteria atau nilai langsung
                if (is_numeric($value)) {
                    // Coba cari sebagai SubSubCriteria ID first
                    $subSubCriteria = SubSubCriteria::find($value);
                    if ($subSubCriteria) {
                        Log::info('Found SubSubCriteria', [
                            'id' => $subSubCriteria->id,
                            'score' => $subSubCriteria->score
                        ]);
                        return $subSubCriteria->score ?? 1;
                    }
                    
                    // Jika tidak, ambil score dari SubCriteria
                    $subCriteria = SubCriteria::find($criteriaId);
                    if ($subCriteria) {
                        Log::info('Found SubCriteria', [
                            'id' => $subCriteria->id,
                            'score' => $subCriteria->score
                        ]);
                        return $subCriteria->score ?? 1;
                    }
                }
                return 1; // Default score
                
            } elseif ($criteriaType === 'subsubcriteria') {
                // Untuk subsubcriteria, value adalah pilihan atau ID
                if (is_numeric($value)) {
                    $subSubCriteria = SubSubCriteria::find($value);
                    if ($subSubCriteria) {
                        Log::info('Found SubSubCriteria for direct lookup', [
                            'id' => $subSubCriteria->id,
                            'score' => $subSubCriteria->score
                        ]);
                        return $subSubCriteria->score ?? 1;
                    }
                }
                
                // Jika value bukan ID, coba cari berdasarkan criteria_id
                $subSubCriteria = SubSubCriteria::find($criteriaId);
                if ($subSubCriteria) {
                    Log::info('Found SubSubCriteria by criteria_id', [
                        'id' => $subSubCriteria->id,
                        'score' => $subSubCriteria->score
                    ]);
                    return $subSubCriteria->score ?? 1;
                }
                
                return 1; // Default score
            }
        } catch (\Exception $e) {
            Log::error('Error calculating score: ' . $e->getMessage(), [
                'criteria_type' => $criteriaType,
                'criteria_id' => $criteriaId,
                'value' => $value
            ]);
        }

        return 1; // Default fallback score
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