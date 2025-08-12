<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\PairwiseComparison;
use App\Models\CriteriaWeight;

class CriteriaController extends Controller
{
    public function index()
    {
        $criterias = Criteria::orderBy('order')->get();
        return view('admin.criteria.index', compact('criterias'));
    }

    public function create()
    {
        return view('admin.criteria.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:criterias',
            'description' => 'nullable|string',
            'order' => 'required|integer',
        ]);

        Criteria::create($request->all());

        return redirect()->route('admin.criteria.index')
            ->with('success', 'Kriteria berhasil ditambahkan');
    }

    public function show(Criteria $criteria)
    {
        $criteria->load('subCriterias');
        return view('admin.criteria.show', compact('criteria'));
    }

    public function edit(Criteria $criteria)
    {
        return view('admin.criteria.edit', compact('criteria'));
    }

    public function update(Request $request, Criteria $criteria)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:criterias,code,' . $criteria->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $criteria->update($request->all());

        return redirect()->route('admin.criteria.index')
            ->with('success', 'Kriteria berhasil diperbarui');
    }

    public function destroy(Criteria $criteria)
    {
        try {
            $criteria->delete();
            return redirect()->route('admin.criteria.index')
                ->with('success', 'Kriteria berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.criteria.index')
                ->with('error', 'Kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}