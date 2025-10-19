<?php

namespace App\Http\Controllers;

use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\PairwiseComparison;
use App\Models\CriteriaWeight;
use App\Models\Application;
use App\Models\ApplicationValue;
use App\Models\Ranking;

class AHPController extends Controller
{
    private $ri = [
        1 => 0,
        2 => 0,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

    public function calculateCriteriaWeights()
    {
        $criterias = Criteria::active()->orderBy('order')->get();
        $n = $criterias->count();

        if ($n < 2) return;

        $matrix = $this->buildMatrix($criterias, 'criteria');
        $result = $this->calculateWeights($matrix);
        
        foreach ($criterias as $index => $criteria) {
            CriteriaWeight::updateOrCreate([
                'level' => 'criteria',
                'item_id' => $criteria->id,
                'parent_id' => null,
            ], [
                'weight' => $result['weights'][$index],
                'lambda_max' => $result['lambda_max'],
                'ci' => $result['ci'],
                'cr' => $result['cr'],
                'is_consistent' => $result['cr'] <= 0.1,
            ]);

            $criteria->update(['weight' => $result['weights'][$index]]);
        }

        return $result;
    }

    public function calculateSubCriteriaWeights($criteriaId)
    {
        $subCriterias = SubCriteria::where('criteria_id', $criteriaId)
            ->active()
            ->orderBy('order')
            ->get();
        $n = $subCriterias->count();

        if ($n < 2) return;

        $matrix = $this->buildMatrix($subCriterias, 'subcriteria', $criteriaId);
        $result = $this->calculateWeights($matrix);
        
        foreach ($subCriterias as $index => $subCriteria) {
            CriteriaWeight::updateOrCreate([
                'level' => 'subcriteria',
                'item_id' => $subCriteria->id,
                'parent_id' => $criteriaId,
            ], [
                'weight' => $result['weights'][$index],
                'lambda_max' => $result['lambda_max'],
                'ci' => $result['ci'],
                'cr' => $result['cr'],
                'is_consistent' => $result['cr'] <= 0.1,
            ]);

            $subCriteria->update(['weight' => $result['weights'][$index]]);
        }

        return $result;
    }

    public function calculateSubSubCriteriaWeights($subCriteriaId)
    {
        $subSubCriterias = SubSubCriteria::where('sub_criteria_id', $subCriteriaId)
            ->active()
            ->orderBy('order')
            ->get();
        $n = $subSubCriterias->count();

        if ($n < 2) return;

        $matrix = $this->buildMatrix($subSubCriterias, 'subsubcriteria', $subCriteriaId);
        $result = $this->calculateWeights($matrix);
        
        foreach ($subSubCriterias as $index => $subSubCriteria) {
            CriteriaWeight::updateOrCreate([
                'level' => 'subsubcriteria',
                'item_id' => $subSubCriteria->id,
                'parent_id' => $subCriteriaId,
            ], [
                'weight' => $result['weights'][$index],
                'lambda_max' => $result['lambda_max'],
                'ci' => $result['ci'],
                'cr' => $result['cr'],
                'is_consistent' => $result['cr'] <= 0.1,
            ]);

            $subSubCriteria->update(['weight' => $result['weights'][$index]]);
        }

        return $result;
    }

    private function buildMatrix($items, $type, $parentId = null)
    {
        $n = $items->count();
        $matrix = array_fill(0, $n, array_fill(0, $n, 1));

        $comparisons = PairwiseComparison::where('comparison_type', $type)
            ->where('parent_id', $parentId)
            ->get()
            ->keyBy(function ($item) {
                return $item->item_a_id . '_' . $item->item_b_id;
            });

        foreach ($items as $i => $itemA) {
            foreach ($items as $j => $itemB) {
                if ($i != $j) {
                    $key = $itemA->id . '_' . $itemB->id;
                    if (isset($comparisons[$key])) {
                        $matrix[$i][$j] = $comparisons[$key]->value;
                    }
                }
            }
        }

        return $matrix;
    }

    /**
     * FIXED: Perhitungan bobot yang benar sesuai metode AHP
     */
    private function calculateWeights($matrix)
    {
        $n = count($matrix);
        
        // Step 1: Calculate column sums
        $columnSums = array_fill(0, $n, 0);
        for ($j = 0; $j < $n; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        // Step 2: Normalize matrix
        $normalizedMatrix = array_fill(0, $n, array_fill(0, $n, 0));
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalizedMatrix[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
            }
        }

        // Step 3: Calculate weights (average of each row)
        $weights = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            $rowSum = 0;
            for ($j = 0; $j < $n; $j++) {
                $rowSum += $normalizedMatrix[$i][$j];
            }
            $weights[$i] = $rowSum / $n;
        }

        // Step 4: Calculate λmax (eigenvalue)
        $weightedSum = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $weightedSum[$i] += $matrix[$i][$j] * $weights[$j];
            }
        }

        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($weights[$i] != 0) {
                $lambdaMax += $weightedSum[$i] / $weights[$i];
            }
        }
        $lambdaMax /= $n;

        // Step 5: Calculate CI and CR
        $ci = ($lambdaMax - $n) / ($n - 1);
        $cr = $n > 2 ? $ci / $this->ri[$n] : 0;

        \Log::info("AHP Calculation:", [
            'n' => $n,
            'weights' => $weights,
            'lambda_max' => $lambdaMax,
            'ci' => $ci,
            'cr' => $cr,
            'is_consistent' => $cr <= 0.1
        ]);

        return [
            'weights' => $weights,
            'lambda_max' => $lambdaMax,
            'ci' => $ci,
            'cr' => $cr,
            'normalized_matrix' => $normalizedMatrix,
        ];
    }

    public function calculateApplicationScore(Application $application)
    {
        $totalScore = 0;
        $criteriaScores = [];

        $criterias = Criteria::active()->with(['subCriterias.subSubCriterias'])->get();

        foreach ($criterias as $criteria) {
            $criteriaScore = 0;
            $criteriaWeight = $criteria->weight;

            if ($criteria->subCriterias->count() > 0) {
                foreach ($criteria->subCriterias as $subCriteria) {
                    $subCriteriaScore = 0;
                    $subCriteriaWeight = $subCriteria->weight;

                    if ($subCriteria->subSubCriterias->count() > 0) {
                        foreach ($subCriteria->subSubCriterias as $subSubCriteria) {
                            $appValue = ApplicationValue::where('application_id', $application->id)
                                ->where('criteria_type', 'subsubcriteria')
                                ->where('criteria_id', $subSubCriteria->id)
                                ->first();

                            $score = $appValue ? $appValue->score : 0;
                            $subCriteriaScore += $score * $subSubCriteria->weight;
                        }
                    } else {
                        $appValue = ApplicationValue::where('application_id', $application->id)
                            ->where('criteria_type', 'subcriteria')
                            ->where('criteria_id', $subCriteria->id)
                            ->first();

                        $subCriteriaScore = $appValue ? $appValue->score : 0;
                    }

                    $criteriaScore += $subCriteriaScore * $subCriteriaWeight;
                }
            } else {
                $appValue = ApplicationValue::where('application_id', $application->id)
                    ->where('criteria_type', 'criteria')
                    ->where('criteria_id', $criteria->id)
                    ->first();

                $criteriaScore = $appValue ? $appValue->score : 0;
            }

            $criteriaScores[$criteria->code] = $criteriaScore;
            $totalScore += $criteriaScore * $criteriaWeight;
        }

        $application->update(['final_score' => $totalScore]);

        Ranking::updateOrCreate([
            'period_id' => $application->period_id,
            'application_id' => $application->id,
        ], [
            'total_score' => $totalScore,
            'criteria_scores' => $criteriaScores,
        ]);

        return $totalScore;
    }

    public function calculateAllApplicationsScores($periodId)
    {
        $applications = Application::where('period_id', $periodId)
            ->where('status', 'validated')
            ->get();

        foreach ($applications as $application) {
            $this->calculateApplicationScore($application);
        }

        $rankings = Ranking::where('period_id', $periodId)
            ->orderBy('total_score', 'desc')
            ->get();

        foreach ($rankings as $index => $ranking) {
            $ranking->update(['rank' => $index + 1]);
            $ranking->application->update(['rank' => $index + 1]);
        }

        return $rankings;
    }
}