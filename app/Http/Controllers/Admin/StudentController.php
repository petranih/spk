<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Application;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        $students = User::where('role', 'student')
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('admin.student.index', compact('students'));
    }

    public function show(User $student)
    {
        if (!$student->isStudent()) {
            abort(404);
        }

        $applications = Application::where('user_id', $student->id)
            ->with(['period', 'validation'])
            ->latest()
            ->get();

        return view('admin.student.show', compact('student', 'applications'));
    }

    public function create()
    {
        return view('admin.student.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => 'student',
        ]);

        return redirect()->route('admin.student.index')
            ->with('success', 'Siswa berhasil ditambahkan');
    }

    public function edit(User $student)
    {
        if (!$student->isStudent()) {
            abort(404);
        }

        return view('admin.student.edit', compact('student'));
    }

    public function update(Request $request, User $student)
    {
        if (!$student->isStudent()) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $student->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $updateData = $request->except(['password']);
        
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $updateData['password'] = Hash::make($request->password);
        }

        $student->update($updateData);

        return redirect()->route('admin.student.index')
            ->with('success', 'Data siswa berhasil diperbarui');
    }

    public function destroy(User $student)
    {
        if (!$student->isStudent()) {
            abort(404);
        }

        try {
            $student->delete();
            return redirect()->route('admin.student.index')
                ->with('success', 'Siswa berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('admin.student.index')
                ->with('error', 'Siswa tidak dapat dihapus karena masih memiliki aplikasi');
        }
    }
}