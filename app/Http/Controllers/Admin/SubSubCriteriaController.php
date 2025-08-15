<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;

class SubSubCriteriaController extends Controller
{
    public function index(SubCriteria $subcriterion = null)
    {
        // Get all subcriteria for selection
        $subCriterias = SubCriteria::with('criteria')->orderBy('order')->get();
        
        $subSubCriterias = collect();
        
        if ($subcriterion) {
            $subSubCriterias = $subcriterion->subSubCriterias()->orderBy('order')->get();
        }
        
        return view('admin.subsubcriteria.index', compact('subcriterion', 'subSubCriterias', 'subCriterias'));
    }

    public function create(SubCriteria $subcriterion)
    {
        return view('admin.subsubcriteria.create', compact('subcriterion'));
    }

    public function store(Request $request, SubCriteria $subcriterion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_sub_criterias',
            'description' => 'nullable|string',
            'order' => 'required|integer',
        ]);

        // Create without score - will be calculated later via pairwise comparison
        $subcriterion->subSubCriterias()->create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'score' => 0, // Default score, will be updated later
            'weight' => 0, // Default weight, will be calculated later
            'is_active' => true,
        ]);

        return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
            ->with('success', 'Sub sub kriteria berhasil ditambahkan');
    }

    public function show(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        return view('admin.subsubcriteria.show', compact('subcriterion', 'subsubcriterion'));
    }

    public function edit(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        return view('admin.subsubcriteria.edit', compact('subcriterion', 'subsubcriterion'));
    }

    public function update(Request $request, SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_sub_criterias,code,' . $subsubcriterion->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $subsubcriterion->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
            ->with('success', 'Sub sub kriteria berhasil diperbarui');
    }

    public function destroy(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        try {
            $subsubcriterion->delete();
            return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
                ->with('success', 'Sub sub kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
                ->with('error', 'Sub sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}