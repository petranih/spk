<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Validation;
use App\Models\ApplicationValue;
use App\Models\Criteria;
use App\Models\ApplicationDocument;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\AHPController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidationController extends Controller
{
    public function show(Application $application)
    {
        if ($application->status !== 'submitted') {
            return redirect()->route('validator.validation.index')
                ->with('error', 'Aplikasi tidak dapat divalidasi');
        }

        // Load raw application values
        $rawData = ApplicationValue::where('application_id', $application->id)->get();
        
        Log::info('Loading validation data', [
            'application_id' => $application->id,
            'raw_data_count' => $rawData->count(),
            'raw_data' => $rawData->map(function($d) {
                return [
                    'id' => $d->id,
                    'type' => $d->criteria_type,
                    'criteria_id' => $d->criteria_id,
                    'value' => $d->value,
                    'score' => $d->score
                ];
            })->toArray()
        ]);
        
        $criterias = Criteria::with(['subCriterias.subSubCriterias'])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $processedData = $this->processData($rawData, $criterias);

        $documents = $application->documents->map(function($doc) {
            $doc->url = asset('storage/' . $doc->file_path);
            $doc->exists = Storage::disk('public')->exists($doc->file_path);
            return $doc;
        });

        return view('validator.validation.show', [
            'application' => $application,
            'criterias' => $criterias,
            'applicationValues' => $processedData['all'],
            'filledValues' => $processedData['filled'],
            'emptyValues' => $processedData['empty'],
            'documents' => $documents
        ]);
    }

    private function processData($rawData, $criterias)
    {
        $applicationValues = collect();
        
        // FIXED: Process data dengan mapping yang benar
        foreach ($criterias as $criteria) {
            foreach ($criteria->subCriterias as $subCriteria) {
                $viewKey = "subcriteria_{$subCriteria->id}";
                
                // FIXED: Cari data berdasarkan criteria_type dan criteria_id yang sesuai
                $savedData = $rawData->first(function($item) use ($subCriteria) {
                    // Data bisa tersimpan sebagai subcriteria dengan ID subcriteria
                    if ($item->criteria_type === 'subcriteria' && $item->criteria_id == $subCriteria->id) {
                        return true;
                    }
                    
                    // ATAU tersimpan sebagai subsubcriteria
                    // Cek apakah ada subsubcriteria yang cocok
                    if ($item->criteria_type === 'subsubcriteria') {
                        foreach ($subCriteria->subSubCriterias as $subSub) {
                            if ($item->criteria_id == $subSub->id) {
                                return true;
                            }
                        }
                    }
                    
                    return false;
                });
                
                Log::info('Processing subcriteria', [
                    'subcriteria_id' => $subCriteria->id,
                    'subcriteria_name' => $subCriteria->name,
                    'found_data' => $savedData ? [
                        'id' => $savedData->id,
                        'type' => $savedData->criteria_type,
                        'criteria_id' => $savedData->criteria_id,
                        'value' => $savedData->value,
                        'score' => $savedData->score
                    ] : null
                ]);
                
                if ($savedData) {
                    // Ada data tersimpan
                    $displayValue = $savedData->value;
                    $actualScore = is_object($savedData->score) ? (float)$savedData->score : (float)$savedData->score;
                    
                    // Jika data tersimpan sebagai subsubcriteria, cari nama subsubnya
                    if ($savedData->criteria_type === 'subsubcriteria') {
                        $foundSubSub = SubSubCriteria::find($savedData->criteria_id);
                        if ($foundSubSub) {
                            $displayValue = $foundSubSub->name;
                        }
                    }
                    
                    $applicationValues->put($viewKey, (object)[
                        'id' => $savedData->id,
                        'value' => $displayValue,
                        'score' => $actualScore,
                        'criteria_type' => 'subcriteria',
                        'criteria_id' => $subCriteria->id,
                        'sub_criteria' => $subCriteria,
                        'sub_sub_criteria' => null,
                        'created_at' => $savedData->created_at,
                        'is_empty' => false
                    ]);
                    
                    // Process detail untuk sub-sub-criteria
                    if ($subCriteria->subSubCriterias->count() > 0) {
                        foreach ($subCriteria->subSubCriterias as $subSubCriteria) {
                            $subSubKey = "subsubcriteria_{$subCriteria->id}_{$subSubCriteria->id}";
                            
                            // Check if this subsubcriteria is selected
                            $isSelected = false;
                            if ($savedData->criteria_type === 'subsubcriteria' && $savedData->criteria_id == $subSubCriteria->id) {
                                $isSelected = true;
                            } elseif ($savedData->criteria_type === 'subcriteria') {
                                // Check by name match
                                $valueClean = strtolower(trim($displayValue));
                                $subSubNameClean = strtolower(trim($subSubCriteria->name));
                                if ($valueClean === $subSubNameClean) {
                                    $isSelected = true;
                                }
                            }
                            
                            $applicationValues->put($subSubKey, (object)[
                                'id' => $isSelected ? $savedData->id : null,
                                'value' => $isSelected ? $subSubCriteria->name : null,
                                'score' => $isSelected ? $actualScore : 0,
                                'criteria_type' => 'subsubcriteria',
                                'criteria_id' => $subSubCriteria->id,
                                'sub_criteria' => $subCriteria,
                                'sub_sub_criteria' => $subSubCriteria,
                                'created_at' => $isSelected ? $savedData->created_at : null,
                                'is_empty' => !$isSelected,
                                'is_selected' => $isSelected
                            ]);
                        }
                    }
                } else {
                    // Tidak ada data
                    $applicationValues->put($viewKey, (object)[
                        'id' => null,
                        'value' => null,
                        'score' => 0,
                        'criteria_type' => 'subcriteria',
                        'criteria_id' => $subCriteria->id,
                        'sub_criteria' => $subCriteria,
                        'sub_sub_criteria' => null,
                        'created_at' => null,
                        'is_empty' => true
                    ]);
                    
                    // Mark all sub-sub as unselected
                    foreach ($subCriteria->subSubCriterias as $subSubCriteria) {
                        $subSubKey = "subsubcriteria_{$subCriteria->id}_{$subSubCriteria->id}";
                        
                        $applicationValues->put($subSubKey, (object)[
                            'id' => null,
                            'value' => null,
                            'score' => 0,
                            'criteria_type' => 'subsubcriteria',
                            'criteria_id' => $subSubCriteria->id,
                            'sub_criteria' => $subCriteria,
                            'sub_sub_criteria' => $subSubCriteria,
                            'created_at' => null,
                            'is_empty' => true,
                            'is_selected' => false
                        ]);
                    }
                }
            }
        }

        $filledValues = $applicationValues->filter(function($item) {
            return !$item->is_empty && isset($item->score) && $item->score > 0;
        });

        $emptyValues = $applicationValues->filter(function($item) {
            return $item->is_empty || !isset($item->score) || $item->score <= 0;
        });
        
        Log::info('Processed validation data', [
            'total_values' => $applicationValues->count(),
            'filled_count' => $filledValues->count(),
            'empty_count' => $emptyValues->count()
        ]);

        return [
            'all' => $applicationValues,
            'filled' => $filledValues,
            'empty' => $emptyValues
        ];
    }

    public function index()
    {
        $applications = Application::where('status', 'submitted')
            ->with(['user', 'period'])
            ->paginate(10);

        return view('validator.validation.index', compact('applications'));
    }

    public function store(Request $request, Application $application)
    {
        if ($application->status !== 'submitted') {
            return redirect()->route('validator.validation.index')
                ->with('error', 'Aplikasi tidak dapat divalidasi');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        Validation::create([
            'application_id' => $application->id,
            'validator_id' => Auth::id(),
            'status' => $request->status,
            'notes' => $request->notes,
            'validated_at' => now(),
        ]);

        $newStatus = $request->status === 'approved' ? 'validated' : 'rejected';
        $application->update([
            'status' => $newStatus,
            'notes' => $request->notes,
        ]);

        if ($request->status === 'approved') {
            try {
                $ahpController = new AHPController();
                $ahpController->calculateApplicationScore($application);
            } catch (\Exception $e) {
                \Log::error('AHP calculation failed: ' . $e->getMessage());
            }
        }

        $message = $request->status === 'approved' ? 
            'Aplikasi berhasil disetujui dan akan diproses lebih lanjut' : 
            'Aplikasi berhasil ditolak dengan catatan yang diberikan';

        return redirect()->route('validator.validation.index')
            ->with('success', $message);
    }

    public function showDocument($id)
    {
        try {
            $document = ApplicationDocument::findOrFail($id);
            
            if ($document->application->status !== 'submitted') {
                abort(403, 'Document not accessible');
            }

            $filePath = storage_path('app/public/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'File not found');
            }

            $mimeType = mime_content_type($filePath);
            
            return Response::file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing document: ' . $e->getMessage());
            abort(500, 'Error loading document');
        }
    }

    public function downloadDocument($id)
    {
        try {
            $document = ApplicationDocument::findOrFail($id);
            
            if ($document->application->status !== 'submitted') {
                abort(403, 'Document not accessible');
            }

            $filePath = storage_path('app/public/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'File not found');
            }

            return Response::download($filePath, $document->document_name);
        } catch (\Exception $e) {
            \Log::error('Error downloading document: ' . $e->getMessage());
            abort(500, 'Error downloading document');
        }
    }
}