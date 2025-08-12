<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Period;

class PeriodController extends Controller
{
    public function index()
    {
        $periods = Period::latest()->get();
        return view('admin.period.index', compact('periods'));
    }

    public function create()
    {
        return view('admin.period.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        Period::create($request->all());

        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil ditambahkan');
    }

    public function edit(Period $period)
    {
        return view('admin.period.edit', compact('period'));
    }

    public function update(Request $request, Period $period)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $period->update($request->all());

        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil diperbarui');
    }

    public function destroy(Period $period)
    {
        try {
            $period->delete();
            return redirect()->route('admin.period.index')
                ->with('success', 'Periode berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.period.index')
                ->with('error', 'Periode tidak dapat dihapus karena masih digunakan');
        }
    }

    public function activate(Period $period)
    {
        // Deactivate all periods first
        Period::where('is_active', true)->update(['is_active' => false]);
        
        // Activate the selected period
        $period->update(['is_active' => true]);

        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil diaktifkan');
    }
}