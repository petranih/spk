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

class ApplicationScoringController extends Controller
{
    /**
     * Tampilkan halaman perhitungan scoring
     */
    public function index()
    {
        $periods = Period::orderBy('created_at', 'desc')->get();
        $activePeriod = Period::where('is_active', true)->first();
        
        return view('admin.scoring.index', compact('periods', 'activePeriod'));
    }

    /**
     * Tampilkan aplikasi untuk periode tertentu
     */
    public function showApplications(Period $period)
    {
        $applications = Application::where('period_id', $period->id)
            ->where('status', 'validated')
            ->with(['student', 'applicationValues'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.scoring.applications', compact('period', 'applications'));
    }

    /**
     * Hitung score untuk satu aplikasi
     */
    public function calculateSingleScore(Application $application)
    {
        try {
            $totalScore = $this->calculateApplicationScore($application);
            
            return redirect()->back()->with('success', 
                "Perhitungan berhasil untuk {$application->student->name}. Skor total: " . number_format($totalScore, 6)
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 
                'Error dalam perhitungan: ' . $e->getMessage()
            );
        }
    }

    /**
     * Hitung score untuk semua aplikasi dalam periode
     */
    public function calculateAllScores(Period $period)
    {
        try {
            $applications = Application::where('period_id', $period->id)
                ->where('status', 'validated')
                ->get();

            $results = [];
            foreach ($applications as $application) {
                $score = $this->calculateApplicationScore($application);
                $results[] = [
                    'student_name' => $application->student->name,
                    'score' => $score
                ];
            }

            // Update ranking
            $this->updateRanking($period->id);

            return redirect()->back()->with('success', 
                'Perhitungan berhasil untuk ' . count($applications) . ' aplikasi'
            );

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 
                'Error dalam perhitungan: ' . $e->getMessage()
            );
        }
    }

    /**
     * Hitung score aplikasi berdasarkan metode AHP
     */
    private function calculateApplicationScore(Application $application)
    {
        $totalScore = 0;
        $criteriaScores = [];

        // Ambil semua kriteria dengan relasi
        $criterias = Criteria::active()
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        foreach ($criterias as $criteria) {
            $criteriaScore = 0;

            if ($criteria->subCriterias->count() > 0) {
                // Jika ada sub kriteria
                foreach ($criteria->subCriterias as $subCriteria) {
                    $subCriteriaScore = 0;

                    if ($subCriteria->subSubCriterias->count() > 0) {
                        // Jika ada sub sub kriteria, cari nilai dari aplikasi
                        $appValue = ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', 'subcriteria')
                            ->where('criteria_id', $subCriteria->id)
                            ->first();

                        if ($appValue && $appValue->value) {
                            // Cari sub sub kriteria yang cocok dengan nilai aplikasi
                            $matchingSubSubCriteria = $subCriteria->subSubCriterias()
                                ->where('code', $appValue->value)
                                ->first();

                            if ($matchingSubSubCriteria) {
                                $subCriteriaScore = $matchingSubSubCriteria->weight;
                            }
                        }
                    } else {
                        // Jika tidak ada sub sub kriteria
                        $appValue = ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', 'subcriteria')
                            ->where('criteria_id', $subCriteria->id)
                            ->first();

                        if ($appValue) {
                            $subCriteriaScore = $this->getSubCriteriaScore($subCriteria, $appValue->value);
                        }
                    }

                    $criteriaScore += $subCriteriaScore * $subCriteria->weight;
                }
            } else {
                // Jika tidak ada sub kriteria
                $appValue = ApplicationValue::where('application_id', $application->id)
                    ->where('criteria_type', 'criteria')
                    ->where('criteria_id', $criteria->id)
                    ->first();

                if ($appValue) {
                    $criteriaScore = $this->getCriteriaScore($criteria, $appValue->value);
                }
            }

            $criteriaScores[$criteria->code] = $criteriaScore;
            $totalScore += $criteriaScore * $criteria->weight;
        }

        // Update score pada aplikasi
        $application->update(['final_score' => $totalScore]);

        // Simpan detail scoring
        $this->saveApplicationScoring($application, $totalScore, $criteriaScores);

        return $totalScore;
    }

    /**
     * Get score untuk sub kriteria berdasarkan nilai
     */
    private function getSubCriteriaScore($subCriteria, $value)
    {
        // Implementasi berdasarkan jenis sub kriteria
        // Ini akan disesuaikan dengan struktur data Anda
        
        // Contoh untuk kriteria yang memiliki pilihan diskrit
        switch ($subCriteria->code) {
            case 'pekerjaan_ayah':
            case 'pekerjaan_ibu':
                return $this->getJobScore($value);
            
            case 'penghasilan_ayah':
            case 'penghasilan_ibu':
                return $this->getIncomeScore($value);
                
            case 'jumlah_tanggungan':
                return $this->getDependentsScore($value);
                
            case 'pendidikan_ortu':
                return $this->getEducationScore($value);
                
            default:
                return 0;
        }
    }

    /**
     * Scoring untuk pekerjaan
     */
    private function getJobScore($jobType)
    {
        $jobScores = [
            'petani' => 0.406477,
            'buruh' => 0.25624,
            'pns' => 0.158276,
            'wirausaha' => 0.09695,
            'tidak_bekerja' => 0.082057
        ];

        return $jobScores[strtolower($jobType)] ?? 0;
    }

    /**
     * Scoring untuk penghasilan
     */
    private function getIncomeScore($income)
    {
        $incomeScores = [
            'kurang_500k' => 0.513786,
            '500k_1jt' => 0.297663,
            '1jt_2jt' => 0.118691,
            'lebih_2jt' => 0.06986
        ];

        return $incomeScores[$income] ?? 0;
    }

    /**
     * Scoring untuk jumlah tanggungan
     */
    private function getDependentsScore($dependents)
    {
        $dependentsScores = [
            '1_2_orang' => 0.607962,
            '3_4_orang' => 0.272099,
            '5_lebih' => 0.119939
        ];

        return $dependentsScores[$dependents] ?? 0;
    }

    /**
     * Scoring untuk pendidikan orang tua
     */
    private function getEducationScore($education)
    {
        // Semakin rendah pendidikan, semakin tinggi score (untuk beasiswa)
        $educationScores = [
            'tidak_sekolah' => 0.406477,
            'sd' => 0.25624,
            'smp' => 0.158276,
            'sma' => 0.09695,
            'perguruan_tinggi' => 0.082057
        ];

        return $educationScores[strtolower($education)] ?? 0;
    }

    /**
     * Get score untuk kriteria utama
     */
    private function getCriteriaScore($criteria, $value)
    {
        // Implementasi untuk kriteria yang tidak memiliki sub kriteria
        return 0;
    }

    /**
     * Simpan detail scoring aplikasi
     */
    private function saveApplicationScoring($application, $totalScore, $criteriaScores)
    {
        Ranking::updateOrCreate([
            'period_id' => $application->period_id,
            'application_id' => $application->id,
        ], [
            'total_score' => $totalScore,
            'criteria_scores' => json_encode($criteriaScores),
            'calculated_at' => now(),
        ]);
    }

    /**
     * Update ranking berdasarkan score
     */
    private function updateRanking($periodId)
    {
        $rankings = Ranking::where('period_id', $periodId)
            ->orderBy('total_score', 'desc')
            ->get();

        foreach ($rankings as $index => $ranking) {
            $ranking->update(['rank' => $index + 1]);
            
            // Update rank di aplikasi juga
            $ranking->application->update(['rank' => $index + 1]);
        }
    }

    /**
     * Export hasil perhitungan
     */
    public function export(Period $period, $format = 'excel')
    {
        $rankings = Ranking::where('period_id', $period->id)
            ->with(['application.student'])
            ->orderBy('rank', 'asc')
            ->get();

        if ($format === 'pdf') {
            return $this->exportToPdf($period, $rankings);
        }

        return $this->exportToExcel($period, $rankings);
    }

    /**
     * Tampilkan detail perhitungan
     */
    public function showCalculationDetail(Application $application)
    {
        $criterias = Criteria::active()
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        $applicationValues = ApplicationValue::where('application_id', $application->id)
            ->get()
            ->keyBy(function ($item) {
                return $item->criteria_type . '_' . $item->criteria_id;
            });

        $ranking = Ranking::where('application_id', $application->id)->first();

        return view('admin.scoring.detail', compact(
            'application', 
            'criterias', 
            'applicationValues', 
            'ranking'
        ));
    }
}