<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationValue;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\Ranking;
use App\Models\Period;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationScoringController extends Controller
{
    /**
     * FIELD MAPPING - FIXED untuk C5
     */
    private function getFieldMapping()
    {
        return [
            // Kondisi Ekonomi (C1)
            'C1_3' => 'father_income',
            'C1_4' => 'mother_income',
            'C1_5' => 'family_dependents',
            'C1_6' => 'has_debt',
            'C1_7' => 'parent_last_education',
            
            // Kondisi Rumah (C2)
            'C2_1' => 'wall_type',
            'C2_2' => 'floor_type',
            'C2_3' => 'roof_type',
            'C2_4' => 'house_status',
            
            // Kepemilikan Aset (C3)
            'C3_1' => 'vehicle_ownership',
            'C3_2' => 'land_ownership',
            'C3_3' => 'land_area',
            'C3_4' => 'electronics_ownership',
            
            // Fasilitas Rumah (C4)
            'C4_1' => 'electricity_source',
            'C4_2' => 'water_source',
            'C4_3' => 'cooking_fuel',
            
            // Status Penerimaan Bantuan (C5) - FIXED: Single field only
            'C5_1' => 'has_received_aid',
            'C5_2' => 'is_receiving_aid',
        ];
    }

    /**
     * Ambil data lengkap aplikasi dengan mapping yang benar
     */
    private function getCompleteApplicationData(Application $application)
    {
        $result = collect();
        
        \Log::info("=== COMPLETE DATA MAPPING START for App {$application->id} ===");
        
        // 1. Ambil dari application_values (PRIORITY 1)
        $dbValues = ApplicationValue::where('application_id', $application->id)->get();
        \Log::info("Found {$dbValues->count()} values in application_values table");
        
        foreach ($dbValues as $value) {
            if ($value->criteria_type === 'subsubcriteria') {
                $subSubData = SubSubCriteria::with('subCriteria')->find($value->criteria_id);
                
                if ($subSubData) {
                    $parentKey = 'subcriteria_' . $subSubData->sub_criteria_id;
                    
                    $result->put($parentKey, (object) [
                        'id' => $value->id,
                        'application_id' => $value->application_id,
                        'criteria_type' => 'subcriteria',
                        'criteria_id' => $subSubData->sub_criteria_id,
                        'value' => $value->value,
                        'source' => 'from_subsubcriteria_db',
                        'confidence' => 100,
                        'subsubcriteria_id' => $value->criteria_id,
                        'subsubcriteria_name' => $subSubData->name,
                        'actual_weight' => $subSubData->weight,
                        'subsubcriteria_data' => $subSubData
                    ]);
                    
                    \Log::info("✓ DB: {$subSubData->name} -> {$subSubData->subCriteria->name} (weight: {$subSubData->weight})");
                }
            } else {
                $key = $value->criteria_type . '_' . $value->criteria_id;
                $result->put($key, (object) [
                    'id' => $value->id,
                    'application_id' => $value->application_id,
                    'criteria_type' => $value->criteria_type,
                    'criteria_id' => $value->criteria_id,
                    'value' => $value->value,
                    'source' => 'application_values_db',
                    'confidence' => 100
                ]);
            }
        }
        
        // 2. FALLBACK dari applications table
        $this->fillMissingDataFromApplicationTable($application, $result);
        
        \Log::info("=== MAPPING COMPLETE: {$result->count()} total items ===");
        return $result;
    }
    
    /**
     * FALLBACK: Isi data yang kosong dari tabel applications
     */
    private function fillMissingDataFromApplicationTable(Application $application, &$result)
    {
        $allSubCriterias = SubCriteria::whereHas('criteria', function($q) {
            $q->where('is_active', true);
        })->with('subSubCriterias')->get();
        
        $fieldMapping = $this->getFieldMapping();
        
        \Log::info("--- Starting FALLBACK for missing data ---");
        
        $c5Found = false;
        
        foreach ($allSubCriterias as $subCriteria) {
            $key = 'subcriteria_' . $subCriteria->id;
            
            if ($result->has($key)) {
                if (str_contains($subCriteria->code ?? '', 'C5')) {
                    $c5Found = true;
                }
                continue;
            }
            
            if (str_contains($subCriteria->code ?? '', 'C5') && $c5Found) {
                \Log::info("⊗ SKIP {$subCriteria->name}: C5 already filled");
                continue;
            }
            
            $value = $this->findValueInApplicationTable($application, $subCriteria, $fieldMapping);
            
            if ($value !== null && $value !== '' && $value !== '-') {
                if (str_contains($subCriteria->code ?? '', 'C5')) {
                    $c5Found = true;
                }
                
                if ($subCriteria->subSubCriterias->count() === 0) {
                    $result->put($key, (object) [
                        'application_id' => $application->id,
                        'criteria_type' => 'subcriteria',
                        'criteria_id' => $subCriteria->id,
                        'value' => $value,
                        'source' => 'application_table_direct',
                        'confidence' => 95,
                        'actual_weight' => $subCriteria->weight,
                        'is_direct_subcriteria' => true
                    ]);
                    \Log::info("✓ DIRECT SubCriteria: {$subCriteria->name} = {$value} (weight: {$subCriteria->weight})");
                } else {
                    $matchingSubSub = $this->findExactSubSubMatch($subCriteria, $value);
                    
                    if ($matchingSubSub) {
                        $result->put($key, (object) [
                            'application_id' => $application->id,
                            'criteria_type' => 'subcriteria',
                            'criteria_id' => $subCriteria->id,
                            'value' => $value,
                            'source' => 'application_table_matched',
                            'confidence' => 90,
                            'subsubcriteria_id' => $matchingSubSub->id,
                            'subsubcriteria_name' => $matchingSubSub->name,
                            'actual_weight' => $matchingSubSub->weight,
                            'subsubcriteria_data' => $matchingSubSub
                        ]);
                        \Log::info("✓ FALLBACK MATCHED: {$subCriteria->name} = {$value} -> {$matchingSubSub->name}");
                    } else {
                        $result->put($key, (object) [
                            'application_id' => $application->id,
                            'criteria_type' => 'subcriteria',
                            'criteria_id' => $subCriteria->id,
                            'value' => $value,
                            'source' => 'application_table_unmatched',
                            'confidence' => 70
                        ]);
                        \Log::info("⚠ FALLBACK NO MATCH: {$subCriteria->name} = {$value}");
                    }
                }
            } else {
                \Log::info("✗ NO DATA: {$subCriteria->name} (Code: {$subCriteria->code})");
            }
        }
    }
    
    private function findValueInApplicationTable(Application $application, SubCriteria $subCriteria, array $fieldMapping)
    {
        if ($subCriteria->code && isset($fieldMapping[$subCriteria->code])) {
            $field = $fieldMapping[$subCriteria->code];
            
            if (property_exists($application, $field) && $application->$field !== null && $application->$field !== '') {
                return $this->normalizeValue($application->$field);
            }
        }
        
        $normalizedName = strtolower(str_replace(' ', '_', trim($subCriteria->name)));
        if (isset($fieldMapping[$normalizedName])) {
            $field = $fieldMapping[$normalizedName];
            if (property_exists($application, $field) && $application->$field !== null) {
                return $this->normalizeValue($application->$field);
            }
        }
        
        if (property_exists($application, $normalizedName) && $application->$normalizedName !== null) {
            return $this->normalizeValue($application->$normalizedName);
        }
        
        $withoutUnderscore = str_replace('_', '', $normalizedName);
        if (property_exists($application, $withoutUnderscore) && $application->$withoutUnderscore !== null) {
            return $this->normalizeValue($application->$withoutUnderscore);
        }
        
        return null;
    }
    
    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            
            if (in_array($lower, ['ya', 'yes', '1', 'true', 'pernah', 'sudah', 'iya'])) {
                return 'Ya';
            }
            
            if (in_array($lower, ['tidak', 'no', '0', 'false', 'belum', 'tidak ada', 'none'])) {
                return 'Tidak';
            }
        }
        
        return $value;
    }

    private function calculateApplicationScore(Application $application)
    {
        $totalScore = 0;
        $criteriaScores = [];

        $applicationData = $this->getCompleteApplicationData($application);
        $criterias = Criteria::where('is_active', true)
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        \Log::info("=== SCORING START for {$application->full_name} (ID: {$application->id}) ===");

        foreach ($criterias as $criteria) {
            $criteriaScore = 0;
            \Log::info("Processing: {$criteria->name} (Weight: {$criteria->weight})");

            if ($criteria->subCriterias->count() > 0) {
                foreach ($criteria->subCriterias as $subCriteria) {
                    $subCriteriaScore = 0;
                    $dataKey = 'subcriteria_' . $subCriteria->id;

                    if ($applicationData->has($dataKey)) {
                        $appData = $applicationData->get($dataKey);
                        
                        if (isset($appData->is_direct_subcriteria) && $appData->is_direct_subcriteria === true) {
                            $valueLower = strtolower(trim($appData->value));
                            if (in_array($valueLower, ['ya', 'yes', '1', 'true', 'pernah', 'sudah'])) {
                                $subCriteriaScore = 1.0;
                            } else {
                                $subCriteriaScore = 0.0;
                            }
                            \Log::info("  ✓ DIRECT {$subCriteria->name}: {$appData->value} = {$subCriteriaScore}");
                        }
                        elseif (isset($appData->actual_weight)) {
                            $subCriteriaScore = $appData->actual_weight;
                            \Log::info("  ✓ {$subCriteria->name}: {$appData->value} = {$subCriteriaScore} (from {$appData->subsubcriteria_name})");
                        } 
                        elseif ($subCriteria->subSubCriterias->count() > 0) {
                            $matchingSubSub = $this->findExactSubSubMatch($subCriteria, $appData->value);
                            
                            if ($matchingSubSub) {
                                $subCriteriaScore = $matchingSubSub->weight;
                                \Log::info("  ✓ {$subCriteria->name}: matched to {$matchingSubSub->name} = {$subCriteriaScore}");
                            } else {
                                $subCriteriaScore = $subCriteria->subSubCriterias->min('weight') ?: 0;
                                \Log::warning("  ⚠ {$subCriteria->name}: no match, using min = {$subCriteriaScore}");
                            }
                        } 
                        else {
                            $subCriteriaScore = 1.0;
                        }
                    } else {
                        if ($subCriteria->subSubCriterias->count() > 0) {
                            $subCriteriaScore = $subCriteria->subSubCriterias->min('weight') ?: 0;
                        } else {
                            $subCriteriaScore = 0;
                        }
                        \Log::info("  ✗ {$subCriteria->name}: MISSING, using = {$subCriteriaScore}");
                    }

                    $contribution = $subCriteriaScore * $subCriteria->weight;
                    $criteriaScore += $contribution;
                }
            }

            $criteriaScores[$criteria->code] = $criteriaScore;
            $finalContribution = $criteriaScore * $criteria->weight;
            $totalScore += $finalContribution;
            
            \Log::info("CRITERIA '{$criteria->name}': score={$criteriaScore} × weight={$criteria->weight} = {$finalContribution}");
        }

        \Log::info("=== FINAL SCORE: {$totalScore} ===\n");

        $application->update(['final_score' => $totalScore]);
        $this->saveApplicationScoring($application, $totalScore, $criteriaScores);

        return $totalScore;
    }

    private function findExactSubSubMatch($subCriteria, $appValue)
    {
        if (!$appValue || $appValue === '-') {
            return null;
        }
        
        $appValueClean = strtolower(trim($appValue));
        
        foreach ($subCriteria->subSubCriterias as $subSub) {
            $subSubClean = strtolower(trim($subSub->name));
            
            if ($appValueClean === $subSubClean) {
                return $subSub;
            }
            
            if (strlen($subSubClean) > 3 && str_contains($appValueClean, $subSubClean)) {
                return $subSub;
            }
            
            if (strlen($appValueClean) > 3 && str_contains($subSubClean, $appValueClean)) {
                return $subSub;
            }
            
            if (preg_match('/\d+/', $appValueClean, $appMatches) && preg_match('/\d+/', $subSubClean, $subMatches)) {
                if ($appMatches[0] === $subMatches[0]) {
                    return $subSub;
                }
            }
        }
        
        return null;
    }

    public function index()
    {
        $periods = Period::orderBy('created_at', 'desc')->get();
        $activePeriod = Period::where('is_active', true)->first();
        
        return view('admin.scoring.index', compact('periods', 'activePeriod'));
    }

    public function showApplications(Period $period)
    {
        $applications = Application::where('period_id', $period->id)
            ->where('status', 'validated')
            ->with(['student', 'applicationValues'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.scoring.applications', compact('period', 'applications'));
    }

    public function showCalculationDetail(Application $application)
    {
        $criterias = Criteria::where('is_active', true)
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        $applicationValues = $this->getCompleteApplicationData($application);
        
        foreach ($applicationValues as $key => $data) {
            if (isset($data->subsubcriteria_data)) {
                $data->display_weight = $data->subsubcriteria_data->weight;
                $data->display_weight_formatted = number_format($data->subsubcriteria_data->weight, 6);
            }
        }
        
        $ranking = Ranking::where('application_id', $application->id)->first();

        return view('admin.scoring.detail', compact(
            'application', 
            'criterias', 
            'applicationValues', 
            'ranking'
        ));
    }

    public function calculateSingleScore(Application $application)
    {
        try {
            $totalScore = $this->calculateApplicationScore($application);
            $this->updateSingleRanking($application->period_id, $application->id);
            
            return redirect()->back()->with('success', 
                "Perhitungan berhasil untuk {$application->full_name}. Skor: " . number_format($totalScore, 6)
            );
        } catch (\Exception $e) {
            \Log::error('Calculate single score error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function calculateAllScores(Period $period)
    {
        try {
            $applications = Application::where('period_id', $period->id)
                ->where('status', 'validated')
                ->get();

            foreach ($applications as $application) {
                $this->calculateApplicationScore($application);
            }

            $this->updateRanking($period->id);
            $count = Ranking::where('period_id', $period->id)->count();

            return redirect()->back()->with('success', 
                "Berhasil menghitung {$applications->count()} aplikasi. {$count} ranking dibuat."
            );
        } catch (\Exception $e) {
            \Log::error('Calculate all scores error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * EXPORT PDF - FIXED with Rank
     */
    public function exportPdf(Period $period)
    {
        try {
            $rankings = Ranking::where('period_id', $period->id)
                ->with('application.student')
                ->whereNotNull('rank')
                ->orderBy('rank', 'asc')
                ->get();

            if ($rankings->isEmpty()) {
                return redirect()->back()->with('error', 'Belum ada data ranking. Silakan hitung skor terlebih dahulu.');
            }

            $dataSiswa = $rankings->map(function($ranking) {
                $criteriaScores = is_string($ranking->criteria_scores) 
                    ? json_decode($ranking->criteria_scores, true) 
                    : ($ranking->criteria_scores ?? []);

                return (object)[
                    'rank' => $ranking->rank ?? 0,
                    'nisn' => $ranking->application->nisn ?? '-',
                    'nama' => $ranking->application->full_name ?? '-',
                    'hadir_count' => $criteriaScores['C1'] ?? 0,
                    'izin_count' => $criteriaScores['C2'] ?? 0,
                    'sakit_count' => $criteriaScores['C3'] ?? 0,
                    'alpa_count' => $criteriaScores['C4'] ?? 0,
                    'c5_score' => $criteriaScores['C5'] ?? 0,
                    'total_score' => $ranking->total_score ?? 0,
                ];
            });

            $data = [
                'kelasName' => $period->name,
                'startDate' => $period->start_date->format('d F Y'),
                'endDate' => $period->end_date->format('d F Y'),
                'dataSiswa' => $dataSiswa
            ];

            $pdf = Pdf::loadView('admin.scoring.pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'Rekap_Ranking_' . str_replace(' ', '_', $period->name) . '_' . date('Ymd') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Export PDF error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Error saat export PDF: ' . $e->getMessage());
        }
    }

    public function debugApplicationMapping($id)
    {
        $application = Application::findOrFail($id);
        $applicationData = $this->getCompleteApplicationData($application);
        
        $result = [
            'application_id' => $application->id,
            'application_name' => $application->full_name,
            'total_mapped' => $applicationData->count(),
            'mapped_data' => [],
            'application_fields' => []
        ];
        
        foreach ($applicationData as $key => $data) {
            $result['mapped_data'][$key] = [
                'value' => $data->value,
                'source' => $data->source,
                'confidence' => $data->confidence ?? null,
                'actual_weight' => $data->actual_weight ?? null,
                'subsubcriteria_name' => $data->subsubcriteria_name ?? null
            ];
        }
        
        foreach ($application->toArray() as $field => $value) {
            if (!in_array($field, ['id', 'user_id', 'period_id', 'created_at', 'updated_at', 'deleted_at']) 
                && $value !== null && $value !== '') {
                $result['application_fields'][$field] = $value;
            }
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function saveApplicationScoring($application, $totalScore, $criteriaScores)
    {
        Ranking::updateOrCreate([
            'period_id' => $application->period_id,
            'application_id' => $application->id,
        ], [
            'total_score' => $totalScore,
            'criteria_scores' => json_encode($criteriaScores, JSON_UNESCAPED_UNICODE),
            'calculated_at' => now(),
        ]);
    }

    private function updateSingleRanking($periodId, $applicationId)
    {
        $this->updateRanking($periodId);
    }

    private function updateRanking($periodId)
    {
        $rankings = Ranking::where('period_id', $periodId)
            ->orderBy('total_score', 'desc')
            ->get();

        foreach ($rankings as $index => $ranking) {
            $ranking->update(['rank' => $index + 1]);
            
            if ($ranking->application) {
                $ranking->application->update(['rank' => $index + 1]);
            }
        }
    }
}