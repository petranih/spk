<?php
// app/Http/Controllers/Admin/SubCriteriaController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\SubCriteria;

class SubCriteriaController extends Controller
{
    public function index(Criteria $criterion = null)
    {
        // Get all criterias for selection
        $criterias = Criteria::orderBy('order')->get();
        
        $subCriterias = collect();
        
        if ($criterion) {
            $subCriterias = $criterion->subCriterias()->orderBy('order')->get();
        }
        
        return view('admin.subcriteria.index', compact('criterion', 'subCriterias', 'criterias'));
    }

    public function create(Criteria $criterion = null)
    {
        if (!$criterion) {
            // If no criterion is provided, show selection page
            return view('admin.subcriteria.create');
        }
        
        return view('admin.subcriteria.create', compact('criterion'));
    }

    public function store(Request $request, Criteria $criterion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_criterias',
            'description' => 'nullable|string',
            'order' => 'required|integer',
        ]);

        // Create without weight - will be calculated later via pairwise comparison
        $criterion->subCriterias()->create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'weight' => 0, // Default weight, will be calculated later
            'is_active' => true,
        ]);
        
        return redirect()->route('admin.subcriteria.index', $criterion->id)
            ->with('success', 'Sub kriteria berhasil ditambahkan');
    }

    public function show(Criteria $criteria, SubCriteria $subCriteria)
    {
        $subCriteria->load('subSubCriterias');
        return view('admin.subcriteria.show', compact('criteria', 'subCriteria'));
    }

    public function edit(Criteria $criteria, SubCriteria $subCriteria)
    {
        return view('admin.subcriteria.edit', compact('criteria', 'subCriteria'));
    }

    public function update(Request $request, Criteria $criteria, SubCriteria $subCriteria)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_criterias,code,' . $subCriteria->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $subCriteria->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('admin.subcriteria.index', $criteria->id)
            ->with('success', 'Sub kriteria berhasil diperbarui');
    }

    public function destroy(Criteria $criteria, SubCriteria $subCriteria)
    {
        try {
            $subCriteria->delete();
            return redirect()->route('admin.subcriteria.index', $criteria->id)
                ->with('success', 'Sub kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.subcriteria.index', $criteria->id)
                ->with('error', 'Sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}