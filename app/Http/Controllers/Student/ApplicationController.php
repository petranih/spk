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
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $criterias = Criteria::active()
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        // Get existing values
        $existingValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

        $documents = ApplicationDocument::where('application_id', $application->id)->get();

        return view('student.application.edit', compact('application', 'criterias', 'existingValues', 'documents'));
    }

    public function update(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403);
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
            ]);

            // Update criteria values
            if ($request->has('criteria_values')) {
                foreach ($request->criteria_values as $criteriaType => $values) {
                    foreach ($values as $criteriaId => $value) {
                        ApplicationValue::updateOrCreate([
                            'application_id' => $application->id,
                            'criteria_type' => $criteriaType,
                            'criteria_id' => $criteriaId,
                        ], [
                            'value' => $value,
                            'score' => $this->calculateScore($criteriaType, $criteriaId, $value),
                        ]);
                    }
                }
            }
        });

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi berhasil diperbarui');
    }

    public function uploadDocument(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'document_type' => 'required|string',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $application->id, 'public');

        ApplicationDocument::create([
            'application_id' => $application->id,
            'document_type' => $request->document_type,
            'document_name' => $request->document_name,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
        ]);

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Dokumen berhasil diupload');
    }

    public function deleteDocument(Application $application, ApplicationDocument $document)
    {
        if ($application->user_id !== Auth::id() || $document->application_id !== $application->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Dokumen berhasil dihapus');
    }

    public function submit(Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        if ($application->status !== 'draft') {
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi tidak dapat disubmit');
        }

        // Validate required fields
        $requiredDocuments = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
        $existingDocs = ApplicationDocument::where('application_id', $application->id)
            ->pluck('document_type')
            ->toArray();

        $missingDocs = array_diff($requiredDocuments, $existingDocs);
        if (!empty($missingDocs)) {
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Harap lengkapi semua dokumen yang diperlukan');
        }

        $application->update(['status' => 'submitted']);

        return redirect()->route('student.dashboard')
            ->with('success', 'Aplikasi berhasil disubmit untuk validasi');
    }

    private function calculateScore($criteriaType, $criteriaId, $value)
    {
        // This method should calculate score based on the selected sub-sub-criteria
        // For now, we'll return the weight of the selected option
        if ($criteriaType === 'subsubcriteria') {
            $subSubCriteria = SubSubCriteria::find($criteriaId);
            return $subSubCriteria ? $subSubCriteria->score : 0;
        }

        return 0;
    }
}