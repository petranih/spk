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
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationScoringController extends Controller
{
    /**
     * FIXED: Field mapping untuk C5 dengan 2 subkriteria
     */
    private function getFieldMapping()
    {
        return [
            // Kondisi Ekonomi (C1)
            'C1_1' => 'father_job',
            'C1_2' => 'mother_job', 
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
            'C2_5' => 'house_area',
            'C2_6' => 'bedroom_count',
            'C2_7' => 'people_per_bedroom',
            
            // Kepemilikan Aset (C3)
            'C3_1' => 'motorcycle',
            'C3_2' => 'car',
            'C3_3' => 'land',
            'C3_4' => 'electronics',
            
            // Fasilitas Rumah (C4)
            'C4_1' => 'electricity',
            'C4_2' => 'water',
            'C4_3' => 'cooking_fuel',
            
            // Status Penerimaan Bantuan (C5) - FIXED: 2 fields
            'C5_1' => 'has_received_aid',
            'C5_2' => 'is_receiving_aid',
        ];
    }

    /**
     * FIXED: Perhitungan skor sesuai Excel
     * Formula: Σ(SubSubCriteria_Weight × SubCriteria_Weight) × Criteria_Weight
     */
    private function calculateApplicationScore(Application $application)
    {
        $totalScore = 0;
        $criteriaScores = [];

        $criterias = Criteria::where('is_active', true)
            ->with(['subCriterias' => function($q) {
                $q->where('is_active', true)->orderBy('order');
            }, 'subCriterias.subSubCriterias' => function($q) {
                $q->where('is_active', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        \Log::info("=== SCORING START: {$application->full_name} (ID: {$application->id}) ===");

        foreach ($criterias as $criteria) {
            $criteriaScore = 0;
            \Log::info("CRITERIA: {$criteria->code} - {$criteria->name} (Weight: {$criteria->weight})");

            foreach ($criteria->subCriterias as $subCriteria) {
                $subCriteriaScore = 0;
                
                if ($subCriteria->subSubCriterias->count() > 0) {
                    // Ada SubSubCriteria - cari yang dipilih user
                    $selectedSubSub = $this->findSelectedSubSubCriteria($application, $subCriteria);
                    
                    if ($selectedSubSub) {
                        $subCriteriaScore = $selectedSubSub->weight;
                        \Log::info("  ✓ {$subCriteria->code}: {$selectedSubSub->name} = {$selectedSubSub->weight}");
                    } else {
                        // Tidak ada pilihan, gunakan weight terendah
                        $subCriteriaScore = $subCriteria->subSubCriterias->min('weight') ?: 0;
                        \Log::warning("  ⚠ {$subCriteria->code}: NO SELECTION, using min = {$subCriteriaScore}");
                    }
                } else {
                    // Direct SubCriteria (tanpa SubSubCriteria)
                    $appValue = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->where('criteria_id', $subCriteria->id)
                        ->first();
                    
                    if ($appValue) {
                        // GUNAKAN WEIGHT LANGSUNG dari SubCriteria yang dipilih
                        $subCriteriaScore = $subCriteria->weight;
                        \Log::info("  ✓ {$subCriteria->code}: {$appValue->value} = {$subCriteriaScore}");
                    } else {
                        $subCriteriaScore = 0;
                        \Log::warning("  ✗ {$subCriteria->code}: NO DATA");
                    }
                }
                
                // CRITICAL: Kalikan dengan SubCriteria weight (sesuai Excel)
                $contribution = $subCriteriaScore * $subCriteria->weight;
                $criteriaScore += $contribution;
                
                \Log::info("    → {$subCriteriaScore} × {$subCriteria->weight} = {$contribution}");
            }

            $criteriaScores[$criteria->code] = $criteriaScore;
            
            // CRITICAL: Kalikan dengan Criteria weight
            $finalContribution = $criteriaScore * $criteria->weight;
            $totalScore += $finalContribution;
            
            \Log::info("  SUBTOTAL {$criteria->code}: {$criteriaScore} × {$criteria->weight} = {$finalContribution}\n");
        }

        \Log::info("=== FINAL SCORE: {$totalScore} ===\n");

        $application->update(['final_score' => $totalScore]);
        $this->saveApplicationScoring($application, $totalScore, $criteriaScores);

        return $totalScore;
    }

    /**
     * FIXED: Cari SubSubCriteria yang dipilih user
     */
    private function findSelectedSubSubCriteria(Application $application, SubCriteria $subCriteria)
    {
        // Cek dari application_values (PRIORITY 1)
        foreach ($subCriteria->subSubCriterias as $subSub) {
            $appValue = ApplicationValue::where('application_id', $application->id)
                ->where('criteria_type', 'subsubcriteria')
                ->where('criteria_id', $subSub->id)
                ->first();
            
            if ($appValue) {
                return $subSub;
            }
        }
        
        // Fallback: Cari dari tabel applications
        $fieldMapping = $this->getFieldMapping();
        
        if ($subCriteria->code && isset($fieldMapping[$subCriteria->code])) {
            $field = $fieldMapping[$subCriteria->code];
            
            if (property_exists($application, $field) && $application->$field !== null && $application->$field !== '') {
                $userValue = $this->normalizeValue($application->$field);
                
                // Cari exact match
                foreach ($subCriteria->subSubCriterias as $subSub) {
                    if ($this->isMatch($userValue, $subSub->name)) {
                        return $subSub;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Matching logic untuk SubSubCriteria
     */
    private function isMatch($userValue, $subSubName)
    {
        $userClean = strtolower(trim($userValue));
        $subSubClean = strtolower(trim($subSubName));
        
        // Exact match
        if ($userClean === $subSubClean) {
            return true;
        }
        
        // Partial match (minimum 5 characters)
        if (strlen($subSubClean) >= 5 && str_contains($userClean, $subSubClean)) {
            return true;
        }
        
        if (strlen($userClean) >= 5 && str_contains($subSubClean, $userClean)) {
            return true;
        }
        
        // Number match (untuk range seperti "< 30 m²" vs "kurang dari 30")
        if (preg_match('/\d+/', $userClean, $userMatches) && preg_match('/\d+/', $subSubClean, $subMatches)) {
            if ($userMatches[0] === $subMatches[0]) {
                return true;
            }
        }
        
        return false;
    }

    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            
            if (in_array($lower, ['ya', 'yes', '1', 'true', 'pernah', 'sudah', 'iya', 'menerima', 'menerima bantuan'])) {
                return 'Ya';
            }
            
            if (in_array($lower, ['tidak', 'no', '0', 'false', 'belum', 'tidak ada', 'none', 'tidak menerima'])) {
                return 'Tidak';
            }
        }
        
        return $value;
    }

    // ============ PUBLIC ROUTES ============
    
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

        $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
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

    public function exportPdf(Period $period)
    {
        try {
            $rankings = Ranking::where('period_id', $period->id)
                ->with('application.student')
                ->whereNotNull('rank')
                ->orderBy('rank', 'asc')
                ->get();

            if ($rankings->isEmpty()) {
                return redirect()->back()->with('error', 'Belum ada data ranking.');
            }

            $dataSiswa = $rankings->map(function($ranking) {
                $criteriaScores = is_string($ranking->criteria_scores) 
                    ? json_decode($ranking->criteria_scores, true) 
                    : ($ranking->criteria_scores ?? []);

                return (object)[
                    'rank' => $ranking->rank ?? 0,
                    'nisn' => $ranking->application->nisn ?? '-',
                    'nama' => $ranking->application->full_name ?? '-',
                    'c1_score' => $criteriaScores['C1'] ?? 0,
                    'c2_score' => $criteriaScores['C2'] ?? 0,
                    'c3_score' => $criteriaScores['C3'] ?? 0,
                    'c4_score' => $criteriaScores['C4'] ?? 0,
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
            
            $filename = 'Ranking_' . str_replace(' ', '_', $period->name) . '_' . date('Ymd') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Export PDF error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
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