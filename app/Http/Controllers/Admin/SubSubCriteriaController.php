<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;

class SubSubCriteriaController extends Controller
{
    public function index(SubCriteria $subCriteria)
    {
        $subSubCriterias = $subCriteria->subSubCriterias()->orderBy('order')->get();
        return view('admin.subsubcriteria.index', compact('subCriteria', 'subSubCriterias'));
    }

    public function create(SubCriteria $subCriteria)
    {
        return view('admin.subsubcriteria.create', compact('subCriteria'));
    }

    public function store(Request $request, SubCriteria $subCriteria)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_sub_criterias',
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'score' => 'required|numeric|min:0|max:1',
        ]);

        $subCriteria->subSubCriterias()->create($request->all());

        return redirect()->route('admin.subsubcriteria.index', $subCriteria->id)
            ->with('success', 'Sub sub kriteria berhasil ditambahkan');
    }

    public function edit(SubCriteria $subCriteria, SubSubCriteria $subSubCriteria)
    {
        return view('admin.subsubcriteria.edit', compact('subCriteria', 'subSubCriteria'));
    }

    public function update(Request $request, SubCriteria $subCriteria, SubSubCriteria $subSubCriteria)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sub_sub_criterias,code,' . $subSubCriteria->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'score' => 'required|numeric|min:0|max:1',
            'is_active' => 'boolean',
        ]);

        $subSubCriteria->update($request->all());

        return redirect()->route('admin.subsubcriteria.index', $subCriteria->id)
            ->with('success', 'Sub sub kriteria berhasil diperbarui');
    }

    public function destroy(SubCriteria $subCriteria, SubSubCriteria $subSubCriteria)
    {
        try {
            $subSubCriteria->delete();
            return redirect()->route('admin.subsubcriteria.index', $subCriteria->id)
                ->with('success', 'Sub sub kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.subsubcriteria.index', $subCriteria->id)
                ->with('error', 'Sub sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}