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
        $criterias = Criteria::orderBy('order')->get();
        
        if (!$criterion && $criterias->isNotEmpty()) {
            $criterion = $criterias->first();
        }
        
        if ($criterion) {
            $subCriterias = $criterion->subCriterias()->orderBy('order')->get();
        } else {
            $subCriterias = collect();
        }
        
        return view('admin.subcriteria.index', compact('criterion', 'subCriterias', 'criterias'));
    }

    public function create(Criteria $criterion)
    {
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

        $criterion->subCriterias()->create($request->all());
        
        return redirect()->route('admin.criteria.subcriteria.index', $criterion->id)
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

        $subCriteria->update($request->all());
        
        return redirect()->route('admin.criteria.subcriteria.index', $criteria->id)
            ->with('success', 'Sub kriteria berhasil diperbarui');
    }

    public function destroy(Criteria $criteria, SubCriteria $subCriteria)
    {
        try {
            $subCriteria->delete();
            return redirect()->route('admin.criteria.subcriteria.index', $criteria->id)
                ->with('success', 'Sub kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.criteria.subcriteria.index', $criteria->id)
                ->with('error', 'Sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}