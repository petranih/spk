<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Application;
use Illuminate\Support\Facades\Hash;

/**
 * Controller untuk mengelola Data Siswa (Student Management)
 * 
 * Fungsi Utama:
 * - CRUD (Create, Read, Update, Delete) data siswa
 * - Menampilkan riwayat aplikasi bantuan per siswa
 * - Mengelola akun siswa (password, status aktif)
 * - Validasi bahwa user yang dikelola adalah role 'student'
 * 
 * Konsep:
 * - Siswa adalah user dengan role 'student'
 * - Setiap siswa dapat memiliki multiple aplikasi (berbeda periode)
 * - Admin dapat membuat akun siswa langsung atau siswa register sendiri
 * - Siswa tidak bisa dihapus jika masih memiliki aplikasi (data integrity)
 * 
 * Security:
 * - Semua method memvalidasi bahwa user adalah siswa (isStudent())
 * - Password di-hash menggunakan bcrypt
 * - Email harus unique
 */
class StudentController extends Controller
{
    /**
     * Menampilkan daftar semua siswa
     * 
     * Fungsi: Menampilkan halaman index dengan list siswa dan jumlah aplikasi mereka
     * Route: GET /admin/student
     * 
     * Fitur:
     * - Pagination 10 siswa per halaman (untuk performa)
     * - Menampilkan jumlah aplikasi per siswa (withCount)
     * - Urutkan dari siswa terbaru (latest)
     * - Filter hanya user dengan role 'student'
     * 
     * @return \Illuminate\View\View Halaman daftar siswa
     */
    public function index()
    {
        // Ambil user dengan role student saja
        // withCount('applications'): tambahkan kolom applications_count untuk jumlah aplikasi
        // latest(): urutkan dari terbaru berdasarkan created_at
        // paginate(10): bagi menjadi halaman, 10 data per halaman
        $students = User::where('role', 'student')
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('admin.student.index', compact('students'));
    }

    /**
     * Menampilkan detail satu siswa beserta riwayat aplikasinya
     * 
     * Fungsi: Menampilkan halaman detail yang berisi:
     *         - Informasi siswa (nama, email, kontak, dll)
     *         - Riwayat semua aplikasi bantuan yang pernah diajukan
     *         - Status aplikasi dan hasil validasi
     * Route: GET /admin/student/{student}
     * 
     * Security: Abort 404 jika bukan siswa
     * 
     * @param User $student Model User siswa yang akan ditampilkan (route model binding)
     * @return \Illuminate\View\View Halaman detail siswa
     */
    public function show(User $student)
    {
        // Validasi: pastikan user yang diakses adalah siswa
        // Jika bukan siswa (admin/validator), return 404 Not Found
        if (!$student->isStudent()) {
            abort(404);
        }

        // Ambil semua aplikasi milik siswa ini
        // Eager load period dan validation untuk menampilkan info lengkap
        // latest(): urutkan dari aplikasi terbaru
        $applications = Application::where('user_id', $student->id)
            ->with(['period', 'validation'])
            ->latest()
            ->get();

        return view('admin.student.show', compact('student', 'applications'));
    }

    /**
     * Menampilkan form untuk menambah siswa baru
     * 
     * Fungsi: Menampilkan halaman form create untuk registrasi siswa manual oleh admin
     * Route: GET /admin/student/create
     * 
     * Use Case:
     * - Admin mendaftarkan siswa yang belum punya akun
     * - Registrasi siswa secara batch/massal
     * - Siswa yang tidak bisa register sendiri
     * 
     * @return \Illuminate\View\View Halaman form create
     */
    public function create()
    {
        return view('admin.student.create');
    }

    /**
     * Menyimpan data siswa baru ke database
     * 
     * Fungsi: Memproses data dari form create dan buat akun siswa baru
     * Route: POST /admin/student
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter
     * - email: wajib, format email valid, unique (tidak boleh duplikat)
     * - password: wajib, minimal 8 karakter, harus confirmed (input 2x)
     * - phone: opsional, string, maksimal 20 karakter
     * - address: opsional, string (untuk alamat lengkap)
     * 
     * Security:
     * - Password otomatis di-hash dengan bcrypt sebelum disimpan
     * - Role otomatis di-set sebagai 'student'
     * 
     * @param Request $request Data dari form input
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',                  // Nama lengkap siswa (wajib)
            'email' => 'required|string|email|max:255|unique:users', // Email unik untuk login (wajib)
            'password' => 'required|string|min:8|confirmed',      // Password min 8 karakter + konfirmasi (wajib)
            'phone' => 'nullable|string|max:20',                  // Nomor telepon (opsional)
            'address' => 'nullable|string',                       // Alamat lengkap (opsional)
        ]);

        // Buat user baru dengan role 'student'
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),  // Hash password dengan bcrypt
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => 'student',                           // Set role sebagai student
        ]);

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.student.index')
            ->with('success', 'Siswa berhasil ditambahkan');
    }

    /**
     * Menampilkan form untuk mengedit data siswa
     * 
     * Fungsi: Menampilkan halaman form edit dengan data siswa yang akan diedit
     * Route: GET /admin/student/{student}/edit
     * 
     * Security: Abort 404 jika bukan siswa
     * 
     * @param User $student Model User siswa yang akan diedit (route model binding)
     * @return \Illuminate\View\View Halaman form edit
     */
    public function edit(User $student)
    {
        // Validasi: pastikan yang diedit adalah siswa
        if (!$student->isStudent()) {
            abort(404);
        }

        return view('admin.student.edit', compact('student'));
    }

    /**
     * Memperbarui data siswa di database
     * 
     * Fungsi: Memproses data dari form edit dan update ke database
     * Route: PUT/PATCH /admin/student/{student}
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter
     * - email: wajib, format email, unique kecuali untuk siswa ini sendiri
     * - phone: opsional, string, maksimal 20 karakter
     * - address: opsional, string
     * - is_active: boolean (status aktif/nonaktif akun siswa)
     * 
     * Fitur Update Password:
     * - Password bersifat opsional saat update
     * - Jika field password diisi, maka password akan diupdate (min 8 karakter + confirmed)
     * - Jika field password kosong, password lama tetap digunakan
     * 
     * Security: Abort 404 jika bukan siswa
     * 
     * @param Request $request Data dari form input
     * @param User $student Model User siswa yang akan diupdate (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(Request $request, User $student)
    {
        // Validasi: pastikan yang diupdate adalah siswa
        if (!$student->isStudent()) {
            abort(404);
        }

        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            // Email unique dengan pengecualian untuk siswa ini sendiri
            'email' => 'required|string|email|max:255|unique:users,email,' . $student->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',  // Status aktif/nonaktif akun
        ]);

        // Ambil semua data request kecuali password
        // Password di-exclude karena akan dihandle secara terpisah
        $updateData = $request->except(['password']);
        
        // Cek apakah field password diisi (tidak kosong)
        if ($request->filled('password')) {
            // Jika diisi, validasi password (min 8 + confirmed)
            $request->validate(['password' => 'string|min:8|confirmed']);
            
            // Hash password baru dan tambahkan ke data update
            $updateData['password'] = Hash::make($request->password);
        }
        // Jika password tidak diisi, password lama tetap digunakan

        // Update data siswa
        $student->update($updateData);

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.student.index')
            ->with('success', 'Data siswa berhasil diperbarui');
    }

    /**
     * Menghapus siswa dari database
     * 
     * Fungsi: Menghapus record siswa (soft delete atau hard delete tergantung konfigurasi)
     * Route: DELETE /admin/student/{student}
     * 
     * Catatan:
     * - Menggunakan try-catch untuk handle error foreign key constraint
     * - Siswa tidak bisa dihapus jika masih memiliki aplikasi (data integrity)
     * - Ini mencegah orphaned data (aplikasi tanpa user)
     * 
     * Security: Abort 404 jika bukan siswa
     * 
     * Alternatif:
     * - Bisa gunakan soft delete (SoftDeletes trait) agar data tidak hilang permanen
     * - Atau set is_active = false untuk menonaktifkan tanpa menghapus
     * 
     * @param User $student Model User siswa yang akan dihapus (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan
     */
    public function destroy(User $student)
    {
        // Validasi: pastikan yang dihapus adalah siswa
        if (!$student->isStudent()) {
            abort(404);
        }

        try {
            // Coba hapus siswa dari database
            $student->delete();
            
            // Jika berhasil, redirect dengan pesan sukses
            return redirect()->route('admin.student.index')
                ->with('success', 'Siswa berhasil dihapus');
                
        } catch (\Exception $e) {
            // Jika gagal (biasanya karena foreign key constraint)
            // Siswa tidak bisa dihapus jika masih memiliki data di:
            // - applications (masih ada aplikasi yang diajukan)
            // - rankings (masih ada data ranking)
            // - application_validations (masih ada catatan validasi)
            
            return redirect()->route('admin.student.index')
                ->with('error', 'Siswa tidak dapat dihapus karena masih memiliki aplikasi');
        }
    }
}