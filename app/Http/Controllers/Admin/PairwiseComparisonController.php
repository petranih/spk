<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\PairwiseComparison;
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
        $this->ahpController->calculateCriteriaWeights();

        return redirect()->route('admin.pairwise.criteria')
            ->with('success', 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung');
    }

    public function subCriteria(Criteria $criteria)
    {
        $subCriterias = $criteria->subCriterias()->active()->get();
        $comparisons = $this->getExistingComparisons('subcriteria', $criteria->id);
        
        return view('admin.pairwise.subcriteria', compact('criteria', 'subCriterias', 'comparisons'));
    }

    public function storeSubCriteria(Request $request, Criteria $criteria)
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
                    'parent_id' => $criteria->id,
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Store reciprocal B vs A
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subcriteria',
                    'parent_id' => $criteria->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => 1 / $comparison['value']
                ]);
            }
        }

        // Calculate weights
        $this->ahpController->calculateSubCriteriaWeights($criteria->id);

        return redirect()->route('admin.pairwise.subcriteria', $criteria->id)
            ->with('success', 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung');
    }

    public function subSubCriteria(SubCriteria $subCriteria)
    {
        $subSubCriterias = $subCriteria->subSubCriterias()->active()->get();
        $comparisons = $this->getExistingComparisons('subsubcriteria', $subCriteria->id);
        
        return view('admin.pairwise.subsubcriteria', compact('subCriteria', 'subSubCriterias', 'comparisons'));
    }

    public function storeSubSubCriteria(Request $request, SubCriteria $subCriteria)
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
                    'parent_id' => $subCriteria->id,
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Store reciprocal B vs A
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subsubcriteria',
                    'parent_id' => $subCriteria->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => 1 / $comparison['value']
                ]);
            }
        }

        // Calculate weights
        $this->ahpController->calculateSubSubCriteriaWeights($subCriteria->id);

        return redirect()->route('admin.pairwise.subsubcriteria', $subCriteria->id)
            ->with('success', 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung');
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
}