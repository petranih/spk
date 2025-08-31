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

        // Get existing values dengan proper key mapping
        $existingValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

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
                'updated_at' => now(), // Explicit update timestamp
            ]);

            // Update criteria values jika ada
            if ($request->has('criteria_values')) {
                foreach ($request->criteria_values as $criteriaType => $values) {
                    foreach ($values as $criteriaId => $value) {
                        // Validasi bahwa value yang dipilih valid
                        if ($criteriaType === 'subsubcriteria') {
                            $subSubCriteria = SubSubCriteria::find($value);
                            if (!$subSubCriteria) {
                                continue; // Skip invalid values
                            }
                            
                            ApplicationValue::updateOrCreate([
                                'application_id' => $application->id,
                                'criteria_type' => $criteriaType,
                                'criteria_id' => $criteriaId,
                            ], [
                                'value' => $value,
                                'score' => $subSubCriteria->score ?? 0,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi berhasil diperbarui');
    }

    public function uploadDocument(Request $request, Application $application)
    {
        // Security checks
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        if ($application->status !== 'draft') {
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi yang sudah disubmit tidak dapat diubah.');
        }

        $request->validate([
            'document_type' => 'required|string|in:ktp,kk,slip_gaji,surat_keterangan',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // max 2MB
        ]);

        try {
            // Cek apakah sudah ada dokumen dengan tipe yang sama
            $existingDoc = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type', $request->document_type)
                ->first();

            if ($existingDoc) {
                // Delete old file
                Storage::disk('public')->delete($existingDoc->file_path);
                $existingDoc->delete();
            }

            $file = $request->file('file');
            
            // Create directory if not exists
            $directory = 'documents/' . $application->id;
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Generate unique filename
            $filename = time() . '_' . $request->document_type . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, 'public');

            ApplicationDocument::create([
                'application_id' => $application->id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
            ]);

            return redirect()->route('student.application.edit', $application->id)
                ->with('success', 'Dokumen berhasil diupload');
                
        } catch (\Exception $e) {
            \Log::error('Document upload failed: ' . $e->getMessage());
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal mengupload dokumen. Silakan coba lagi.');
        }
    }

    public function deleteDocument(Application $application, ApplicationDocument $document)
    {
        // Security checks
        if ($application->user_id !== Auth::id() || $document->application_id !== $application->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($application->status !== 'draft') {
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

            return redirect()->route('student.application.edit', $application->id)
                ->with('success', 'Dokumen berhasil dihapus');
                
        } catch (\Exception $e) {
            \Log::error('Document deletion failed: ' . $e->getMessage());
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal menghapus dokumen. Silakan coba lagi.');
        }
    }

    public function submit(Application $application)
    {
        // Security check
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        if ($application->status !== 'draft') {
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

        // Check criteria values
        $applicationValues = ApplicationValue::where('application_id', $application->id)->count();
        if ($applicationValues == 0) {
            $validationErrors[] = 'Data kriteria AHP belum diisi';
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

            return redirect()->route('student.dashboard')
                ->with('success', 'Aplikasi berhasil disubmit untuk validasi. Silakan tunggu proses validasi dari administrator.');
                
        } catch (\Exception $e) {
            \Log::error('Application submission failed: ' . $e->getMessage());
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal submit aplikasi. Silakan coba lagi.');
        }
    }

    private function calculateScore($criteriaType, $criteriaId, $value)
    {
        // This method calculates score based on the selected sub-sub-criteria
        if ($criteriaType === 'subsubcriteria') {
            $subSubCriteria = SubSubCriteria::find($value);
            return $subSubCriteria ? $subSubCriteria->score : 0;
        }

        return 0;
    }
}