<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\PairwiseComparison;
use App\Models\CriteriaWeight;
use App\Http\Controllers\AHPController;

class PairwiseComparisonController extends Controller
{
    protected $ahpController;

    public function __construct()
    {
        $this->ahpController = new AHPController();
    }

    public function criteria()
    {
        $criterias = Criteria::active()->orderBy('order')->get();
        $comparisons = $this->getExistingComparisons('criteria');
        
        return view('admin.pairwise.criteria', compact('criterias', 'comparisons'));
    }

    public function storeCriteria(Request $request)
    {
        $request->validate([
            'comparisons' => 'required|array',
            'comparisons.*.item_a_id' => 'required|exists:criterias,id',
            'comparisons.*.item_b_id' => 'required|exists:criterias,id',
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',
        ]);

        foreach ($request->comparisons as $comparison) {
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Store A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'criteria',
                    'parent_id' => null,
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Store reciprocal B vs A
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'criteria',
                    'parent_id' => null,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => 1 / $comparison['value']
                ]);
            }
        }

        // Calculate weights
        $result = $this->ahpController->calculateCriteriaWeights();

        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        return redirect()->route('admin.pairwise.criteria')
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    public function subCriteria(Criteria $criterion)
    {
        $subCriterias = $criterion->subCriterias()->active()->orderBy('order')->get();
        $comparisons = $this->getExistingComparisons('subcriteria', $criterion->id);
        
        return view('admin.pairwise.subcriteria', compact('criterion', 'subCriterias', 'comparisons'));
    }

    public function storeSubCriteria(Request $request, Criteria $criterion)
    {
        $request->validate([
            'comparisons' => 'required|array',
            'comparisons.*.item_a_id' => 'required|exists:sub_criterias,id',
            'comparisons.*.item_b_id' => 'required|exists:sub_criterias,id',
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',
        ]);

        foreach ($request->comparisons as $comparison) {
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Store A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subcriteria',
                    'parent_id' => $criterion->id,
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Store reciprocal B vs A
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subcriteria',
                    'parent_id' => $criterion->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => 1 / $comparison['value']
                ]);
            }
        }

        // Calculate weights
        $result = $this->ahpController->calculateSubCriteriaWeights($criterion->id);

        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        return redirect()->route('admin.pairwise.subcriteria', $criterion->id)
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    public function subSubCriteria(SubCriteria $subcriterion)
    {
        $subSubCriterias = $subcriterion->subSubCriterias()->active()->orderBy('order')->get();
        $comparisons = $this->getExistingComparisons('subsubcriteria', $subcriterion->id);
        
        // Load relationships for breadcrumb and hierarchy display
        $subcriterion->load('criteria');
        
        return view('admin.pairwise.subsubcriteria', compact('subcriterion', 'subSubCriterias', 'comparisons'));
    }

    public function storeSubSubCriteria(Request $request, SubCriteria $subcriterion)
    {
        $request->validate([
            'comparisons' => 'required|array',
            'comparisons.*.item_a_id' => 'required|exists:sub_sub_criterias,id',
            'comparisons.*.item_b_id' => 'required|exists:sub_sub_criterias,id',
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',
        ]);

        foreach ($request->comparisons as $comparison) {
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Store A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subsubcriteria',
                    'parent_id' => $subcriterion->id,
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Store reciprocal B vs A
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subsubcriteria',
                    'parent_id' => $subcriterion->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => 1 / $comparison['value']
                ]);
            }
        }

        // Calculate weights
        $result = $this->ahpController->calculateSubSubCriteriaWeights($subcriterion->id);

        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        return redirect()->route('admin.pairwise.subsubcriteria', $subcriterion->id)
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    private function getExistingComparisons($type, $parentId = null)
    {
        return PairwiseComparison::where('comparison_type', $type)
            ->where('parent_id', $parentId)
            ->get()
            ->keyBy(function ($item) {
                return $item->item_a_id . '_' . $item->item_b_id;
            });
    }

    /**
     * Get consistency overview for all levels
     */
    public function consistencyOverview()
    {
        $data = [
            'criteria' => [],
            'subcriteria' => [],
            'subsubcriteria' => []
        ];

        // Get criteria consistency
        $criteriaWeights = CriteriaWeight::where('level', 'criteria')
            ->where('parent_id', null)
            ->with(['criteria'])
            ->get();

        foreach ($criteriaWeights as $weight) {
            if ($weight->criteria) {
                $data['criteria'][] = [
                    'name' => $weight->criteria->name,
                    'code' => $weight->criteria->code,
                    'cr' => $weight->cr,
                    'is_consistent' => $weight->is_consistent,
                    'lambda_max' => $weight->lambda_max,
                    'ci' => $weight->ci
                ];
            }
        }

        // Get subcriteria consistency
        $subCriteriaWeights = CriteriaWeight::where('level', 'subcriteria')
            ->whereNotNull('parent_id')
            ->with(['subCriteria.criteria'])
            ->get();

        foreach ($subCriteriaWeights as $weight) {
            if ($weight->subCriteria && $weight->subCriteria->criteria) {
                $data['subcriteria'][] = [
                    'parent' => $weight->subCriteria->criteria->code,
                    'name' => $weight->subCriteria->name,
                    'code' => $weight->subCriteria->code,
                    'cr' => $weight->cr,
                    'is_consistent' => $weight->is_consistent,
                    'lambda_max' => $weight->lambda_max,
                    'ci' => $weight->ci
                ];
            }
        }

        // Get subsubcriteria consistency
        $subSubCriteriaWeights = CriteriaWeight::where('level', 'subsubcriteria')
            ->whereNotNull('parent_id')
            ->with(['subSubCriteria.subCriteria.criteria'])
            ->get();

        foreach ($subSubCriteriaWeights as $weight) {
            if ($weight->subSubCriteria && $weight->subSubCriteria->subCriteria) {
                $data['subsubcriteria'][] = [
                    'grandparent' => $weight->subSubCriteria->subCriteria->criteria->code,
                    'parent' => $weight->subSubCriteria->subCriteria->code,
                    'name' => $weight->subSubCriteria->name,
                    'code' => $weight->subSubCriteria->code,
                    'cr' => $weight->cr,
                    'is_consistent' => $weight->is_consistent,
                    'lambda_max' => $weight->lambda_max,
                    'ci' => $weight->ci
                ];
            }
        }

        return view('admin.pairwise.consistency-overview', compact('data'));
    }

    /**
     * Export pairwise comparison matrix to Excel/PDF
     */
    public function exportMatrix($type, $parentId = null)
    {
        // Implementation for exporting comparison matrices
        // This would generate Excel or PDF reports of the comparison matrices
        
        return response()->json([
            'message' => 'Export functionality will be implemented',
            'type' => $type,
            'parent_id' => $parentId
        ]);
    }

    /**
     * Reset all comparisons for a specific level
     */
    public function resetComparisons(Request $request)
    {
        $request->validate([
            'type' => 'required|in:criteria,subcriteria,subsubcriteria',
            'parent_id' => 'nullable|integer'
        ]);

        PairwiseComparison::where('comparison_type', $request->type)
            ->where('parent_id', $request->parent_id)
            ->delete();

        // Also reset the corresponding weights
        CriteriaWeight::where('level', $request->type)
            ->where('parent_id', $request->parent_id)
            ->delete();

        // Reset model weights based on type
        switch ($request->type) {
            case 'criteria':
                Criteria::where('id', '>', 0)->update(['weight' => 0]);
                break;
            case 'subcriteria':
                SubCriteria::where('criteria_id', $request->parent_id)->update(['weight' => 0]);
                break;
            case 'subsubcriteria':
                SubSubCriteria::where('sub_criteria_id', $request->parent_id)->update(['weight' => 0]);
                break;
        }

        return redirect()->back()->with('success', 'Perbandingan berpasangan berhasil direset');
    }
}