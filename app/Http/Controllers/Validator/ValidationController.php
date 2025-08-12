<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Validation;
use App\Models\ApplicationValue;
use App\Models\Criteria;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AHPController;

class ValidationController extends Controller
{
    public function index()
    {
        $applications = Application::where('status', 'submitted')
            ->with(['user', 'period'])
            ->paginate(10);

        return view('validator.validation.index', compact('applications'));
    }

    public function show(Application $application)
    {
        if ($application->status !== 'submitted') {
            return redirect()->route('validator.validation.index')
                ->with('error', 'Aplikasi tidak dapat divalidasi');
        }

        $criterias = Criteria::active()
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        $applicationValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

        $documents = $application->documents;

        return view('validator.validation.show', compact('application', 'criterias', 'applicationValues', 'documents'));
    }

    // GANTI nama method dari validate() menjadi processValidation()
    public function processValidation(Request $request, Application $application)
    {
        if ($application->status !== 'submitted') {
            return redirect()->route('validator.validation.index')
                ->with('error', 'Aplikasi tidak dapat divalidasi');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
        ]);

        // Create validation record
        Validation::create([
            'application_id' => $application->id,
            'validator_id' => Auth::id(),
            'status' => $request->status,
            'notes' => $request->notes,
            'validated_at' => now(),
        ]);

        // Update application status
        $newStatus = $request->status === 'approved' ? 'validated' : 'rejected';
        $application->update([
            'status' => $newStatus,
            'notes' => $request->notes,
        ]);

        // If approved, calculate AHP score
        if ($request->status === 'approved') {
            $ahpController = new AHPController();
            $ahpController->calculateApplicationScore($application);
        }

        return redirect()->route('validator.validation.index')
            ->with('success', 'Validasi berhasil disimpan');
    }
}