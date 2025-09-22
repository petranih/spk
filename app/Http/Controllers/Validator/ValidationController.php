<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Validation;
use App\Models\ApplicationValue;
use App\Models\Criteria;
use App\Models\Document;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\AHPController;
use Illuminate\Support\Facades\DB;

class ValidationController extends Controller
{
    public function show(Application $application)
    {
        if ($application->status !== 'submitted') {
            return redirect()->route('validator.validation.index')
                ->with('error', 'Aplikasi tidak dapat divalidasi');
        }

        $rawData = ApplicationValue::where('application_id', $application->id)->get();
        
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
    
    // Create mapping of criteria names to find correct placement
    $criteriaMapping = [];
    foreach ($criterias as $criteria) {
        foreach ($criteria->subCriterias as $subCriteria) {
            $criteriaMapping[$subCriteria->id] = [
                'criteria' => $criteria,
                'subCriteria' => $subCriteria,
                'name_lower' => strtolower($subCriteria->name),
                'criteria_name_lower' => strtolower($criteria->name)
            ];
        }
    }
    
    // Smart data mapping based on content matching
    $mappedData = [];
    
    foreach ($rawData as $record) {
        $value = strtolower(trim($record->value));
        $bestMatch = null;
        $bestScore = 0;
        
        // Try to find the best matching criteria for this data
        foreach ($criteriaMapping as $subCriteriaId => $mapping) {
            $score = 0;
            
            // Direct content matching for bantuan (both yes and no answers)
            if (strpos($value, 'menerima bantuan') !== false || $value === 'menerima bantuan' ||
                strpos($value, 'tidak menerima bantuan') !== false || $value === 'tidak menerima bantuan' ||
                $value === 'tidak' || $value === 'ya') {
                if (strpos($mapping['name_lower'], 'bantuan') !== false || 
                    strpos($mapping['criteria_name_lower'], 'bantuan') !== false ||
                    strpos($mapping['name_lower'], 'penerimaan') !== false) {
                    $score = 100; // Highest priority for bantuan content
                }
            }
            elseif (strpos($value, 'tidak punya hutang') !== false || strpos($value, 'punya hutang') !== false) {
                if (strpos($mapping['name_lower'], 'hutang') !== false) {
                    $score = 100;
                }
            }
            elseif (is_numeric($value) && (strpos($value, 'orang') !== false || $record->criteria_id == $subCriteriaId)) {
                if (strpos($mapping['name_lower'], 'tanggungan') !== false || 
                    strpos($mapping['name_lower'], 'jumlah') !== false) {
                    $score = 90;
                }
            }
            elseif (strpos($value, 'rumah') !== false) {
                if (strpos($mapping['name_lower'], 'rumah') !== false) {
                    $score = 90;
                }
            }
            elseif (strpos($value, 'pendapatan') !== false || strpos($value, 'gaji') !== false) {
                if (strpos($mapping['name_lower'], 'pendapatan') !== false || 
                    strpos($mapping['criteria_name_lower'], 'ekonomi') !== false) {
                    $score = 90;
                }
            }
            
            // Fallback: if original criteria_id matches and no better match found
            if ($record->criteria_id == $subCriteriaId && $score == 0) {
                $score = 50;
            }
            
            // Update best match if this score is higher
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $subCriteriaId;
            }
        }
        
        // Use best match or fallback to original
        $targetId = $bestMatch ?: $record->criteria_id;
        $mappedData[$targetId] = $record;
    }
    
    // Process each criteria and subcriteria in the correct order
    foreach ($criterias as $criteria) {
        foreach ($criteria->subCriterias as $subCriteria) {
            $viewKey = "subcriteria_{$subCriteria->id}";
            
            // Look for mapped data for this subcriteria ID
            $subCriteriaData = $mappedData[$subCriteria->id] ?? null;
            
            if ($subCriteriaData) {
                // We have data for this subcriteria
                $applicationValues->put($viewKey, (object)[
                    'id' => $subCriteriaData->id,
                    'value' => $subCriteriaData->value,
                    'score' => (float) $subCriteriaData->score,
                    'criteria_type' => $subCriteriaData->criteria_type,
                    'criteria_id' => $subCriteriaData->criteria_id,
                    'sub_criteria' => $subCriteria,
                    'sub_sub_criteria' => null,
                    'created_at' => $subCriteriaData->created_at,
                    'is_empty' => false
                ]);
                
                // Process sub-sub criteria if they exist
                if ($subCriteria->subSubCriterias->count() > 0) {
                    foreach ($subCriteria->subSubCriterias as $subSubCriteria) {
                        $subSubKey = "subsubcriteria_{$subCriteria->id}_{$subSubCriteria->id}";
                        
                        // Check if this sub-sub criteria matches the subcriteria data
                        $isMatch = false;
                        $recordValue = strtolower(trim($subCriteriaData->value));
                        $subSubValue = strtolower(trim($subSubCriteria->name));
                        
                        // Enhanced matching logic for sub-sub criteria
                        if ($recordValue == $subSubValue ||
                            strpos($recordValue, $subSubValue) !== false || 
                            strpos($subSubValue, $recordValue) !== false ||
                            // Special cases for bantuan - handle both positive and negative responses
                            ($recordValue == 'menerima bantuan' && ($subSubValue == 'ya' || $subSubValue == 'menerima')) ||
                            ($recordValue == 'tidak menerima bantuan' && ($subSubValue == 'tidak' || $subSubValue == 'tidak menerima')) ||
                            ($recordValue == 'ya' && ($subSubValue == 'menerima bantuan' || $subSubValue == 'menerima')) ||
                            ($recordValue == 'tidak' && ($subSubValue == 'tidak menerima bantuan' || $subSubValue == 'tidak menerima' || $subSubValue == 'tidak')) ||
                            // Special cases for hutang
                            ($recordValue == 'tidak punya hutang' && ($subSubValue == 'tidak' || $subSubValue == 'tidak ada')) ||
                            ($recordValue == 'punya hutang' && ($subSubValue == 'ya' || $subSubValue == 'ada')) ||
                            // Additional matching for simple yes/no responses
                            (($recordValue == 'ya' || $recordValue == 'yes') && ($subSubValue == 'ya' || $subSubValue == 'menerima')) ||
                            (($recordValue == 'tidak' || $recordValue == 'no') && ($subSubValue == 'tidak' || $subSubValue == 'tidak menerima'))) {
                            $isMatch = true;
                        }
                        
                        $applicationValues->put($subSubKey, (object)[
                            'id' => $isMatch ? $subCriteriaData->id : null,
                            'value' => $isMatch ? $subCriteriaData->value : null,
                            'score' => $isMatch ? (float) $subCriteriaData->score : 0,
                            'criteria_type' => 'subsubcriteria',
                            'criteria_id' => $subSubCriteria->id,
                            'sub_criteria' => $subCriteria,
                            'sub_sub_criteria' => $subSubCriteria,
                            'created_at' => $isMatch ? $subCriteriaData->created_at : null,
                            'is_empty' => !$isMatch,
                            'is_selected' => $isMatch
                        ]);
                    }
                }
            } else {
                // No data for this subcriteria
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
                
                // Process empty sub-sub criteria
                if ($subCriteria->subSubCriterias->count() > 0) {
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
    }

    $filledValues = $applicationValues->filter(function($item) {
        return !$item->is_empty && $item->score > 0;
    });

    $emptyValues = $applicationValues->filter(function($item) {
        return $item->is_empty || $item->score <= 0;
    });

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
            $document = Document::findOrFail($id);
            
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
            $document = Document::findOrFail($id);
            
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