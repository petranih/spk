<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Period;

/**
 * Controller untuk mengelola Periode Penerimaan Bantuan
 * 
 * Fungsi Utama:
 * - Mengelola periode/tahun ajaran untuk penerimaan bantuan siswa
 * - Mengaktifkan/menonaktifkan periode (hanya 1 periode aktif dalam 1 waktu)
 * - CRUD (Create, Read, Update, Delete) data periode
 * 
 * Konsep Periode:
 * - Setiap periode memiliki tanggal mulai dan selesai
 * - Hanya 1 periode yang bisa aktif di satu waktu
 * - Aplikasi bantuan siswa terkait dengan periode tertentu
 * - Digunakan untuk mengelompokkan aplikasi per tahun ajaran/semester
 * 
 * Contoh Periode:
 * - "Semester Ganjil 2024/2025"
 * - "Tahun Ajaran 2024/2025"
 * - "Periode Januari - Juni 2025"
 */
class PeriodController extends Controller
{
    /**
     * Menampilkan daftar semua periode
     * 
     * Fungsi: Menampilkan halaman index dengan list periode terurut dari yang terbaru
     * Route: GET /admin/period
     * 
     * @return \Illuminate\View\View Halaman daftar periode
     */
    public function index()
    {
        // Ambil semua periode, urutkan dari yang terbaru (latest = orderBy created_at DESC)
        $periods = Period::latest()->get();
        
        return view('admin.period.index', compact('periods'));
    }

    /**
     * Menampilkan form untuk membuat periode baru
     * 
     * Fungsi: Menampilkan halaman form create
     * Route: GET /admin/period/create
     * 
     * @return \Illuminate\View\View Halaman form create
     */
    public function create()
    {
        return view('admin.period.create');
    }

    /**
     * Menyimpan periode baru ke database
     * 
     * Fungsi: Memproses data dari form create dan simpan ke tabel periods
     * Route: POST /admin/period
     * 
     * Validasi:
     * - name: wajib diisi, string, maksimal 255 karakter (contoh: "Semester Ganjil 2024/2025")
     * - description: opsional, string (deskripsi tambahan periode)
     * - start_date: wajib diisi, harus berupa tanggal valid
     * - end_date: wajib diisi, harus tanggal valid, dan harus setelah start_date
     * 
     * @param Request $request Data dari form input
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',              // Nama periode (wajib)
            'description' => 'nullable|string',                // Deskripsi (opsional)
            'start_date' => 'required|date',                   // Tanggal mulai (wajib, format date)
            'end_date' => 'required|date|after:start_date',    // Tanggal selesai (wajib, harus setelah start_date)
        ]);

        // Simpan data periode baru ke database
        // $request->all() akan mengambil semua input yang divalidasi
        Period::create($request->all());

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil ditambahkan');
    }

    /**
     * Menampilkan form untuk mengedit periode
     * 
     * Fungsi: Menampilkan halaman form edit dengan data periode yang akan diedit
     * Route: GET /admin/period/{period}/edit
     * 
     * @param Period $period Model Period yang akan diedit (route model binding)
     * @return \Illuminate\View\View Halaman form edit
     */
    public function edit(Period $period)
    {
        return view('admin.period.edit', compact('period'));
    }

    /**
     * Memperbarui data periode di database
     * 
     * Fungsi: Memproses data dari form edit dan update ke database
     * Route: PUT/PATCH /admin/period/{period}
     * 
     * Validasi:
     * - name: wajib diisi, string, maksimal 255 karakter
     * - description: opsional, string
     * - start_date: wajib diisi, harus berupa tanggal valid
     * - end_date: wajib diisi, harus tanggal valid dan setelah start_date
     * - is_active: boolean (status aktif/nonaktif periode)
     * 
     * @param Request $request Data dari form input
     * @param Period $period Model Period yang akan diupdate (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(Request $request, Period $period)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',  // end_date harus setelah start_date
            'is_active' => 'boolean',                         // Status aktif/nonaktif
        ]);

        // Update data periode di database
        // $request->all() akan mengambil semua input yang divalidasi
        $period->update($request->all());

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil diperbarui');
    }

    /**
     * Menghapus periode dari database
     * 
     * Fungsi: Menghapus record periode
     * Route: DELETE /admin/period/{period}
     * 
     * Catatan:
     * - Menggunakan try-catch untuk handle error jika periode masih digunakan
     * - Jika periode memiliki aplikasi terkait (foreign key constraint), delete akan gagal
     * - Periode yang masih memiliki data aplikasi tidak bisa dihapus untuk integritas data
     * 
     * @param Period $period Model Period yang akan dihapus (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan
     */
    public function destroy(Period $period)
    {
        try {
            // Coba hapus periode dari database
            $period->delete();
            
            // Jika berhasil, redirect dengan pesan sukses
            return redirect()->route('admin.period.index')
                ->with('success', 'Periode berhasil dihapus');
                
        } catch (\Exception $e) {
            // Jika gagal (biasanya karena foreign key constraint)
            // Periode tidak bisa dihapus jika masih digunakan di:
            // - Applications (masih ada siswa yang mendaftar di periode ini)
            // - Rankings (masih ada data ranking di periode ini)
            return redirect()->route('admin.period.index')
                ->with('error', 'Periode tidak dapat dihapus karena masih digunakan');
        }
    }

    /**
     * Mengaktifkan satu periode dan menonaktifkan periode lainnya
     * 
     * Fungsi: Mengatur periode aktif (hanya 1 periode yang bisa aktif)
     * Route: POST /admin/period/{period}/activate
     * 
     * Konsep Single Active Period:
     * - Hanya 1 periode yang boleh aktif dalam 1 waktu
     * - Periode aktif adalah periode yang saat ini menerima aplikasi bantuan
     * - Ketika periode baru diaktifkan, semua periode lain otomatis dinonaktifkan
     * - Form aplikasi siswa akan menggunakan periode yang aktif
     * 
     * Flow:
     * 1. Nonaktifkan semua periode (set is_active = false untuk semua)
     * 2. Aktifkan periode yang dipilih (set is_active = true)
     * 
     * @param Period $period Model Period yang akan diaktifkan (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function activate(Period $period)
    {
        // Step 1: Nonaktifkan semua periode terlebih dahulu
        // Pastikan tidak ada lebih dari 1 periode aktif
        Period::where('is_active', true)->update(['is_active' => false]);
        
        // Step 2: Aktifkan periode yang dipilih
        $period->update(['is_active' => true]);

        // Redirect dengan pesan sukses
        return redirect()->route('admin.period.index')
            ->with('success', 'Periode berhasil diaktifkan');
    }
}