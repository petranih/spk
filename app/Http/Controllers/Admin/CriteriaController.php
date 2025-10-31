<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\PairwiseComparison;
use App\Models\CriteriaWeight;

/**
 * Controller untuk mengelola data Kriteria (C1, C2, C3, C4, C5)
 * 
 * Menangani operasi CRUD (Create, Read, Update, Delete) untuk kriteria utama
 * yang digunakan dalam sistem penilaian aplikasi bantuan siswa.
 * 
 * Contoh Kriteria:
 * - C1: Kondisi Ekonomi
 * - C2: Kondisi Rumah
 * - C3: Kepemilikan Aset
 * - C4: Fasilitas Rumah
 * - C5: Status Penerimaan Bantuan
 */
class CriteriaController extends Controller
{
    /**
     * Menampilkan daftar semua kriteria
     * 
     * Fungsi: Menampilkan halaman index dengan list kriteria terurut berdasarkan order
     * Route: GET /admin/criteria
     * 
     * @return \Illuminate\View\View Halaman daftar kriteria
     */
    public function index()
    {
        // Ambil semua kriteria, urutkan berdasarkan kolom 'order' (ascending)
        // Order digunakan untuk menentukan urutan tampilan (C1, C2, C3, dst)
        $criterias = Criteria::orderBy('order')->get();
        
        // Tampilkan view dengan data kriteria
        return view('admin.criteria.index', compact('criterias'));
    }

    /**
     * Menampilkan form untuk membuat kriteria baru
     * 
     * Fungsi: Menampilkan halaman form create
     * Route: GET /admin/criteria/create
     * 
     * @return \Illuminate\View\View Halaman form create
     */
    public function create()
    {
        return view('admin.criteria.create');
    }

    /**
     * Menyimpan kriteria baru ke database
     * 
     * Fungsi: Memproses data dari form create dan simpan ke tabel criterias
     * Route: POST /admin/criteria
     * 
     * Validasi:
     * - name: wajib diisi, string, maksimal 255 karakter
     * - code: wajib diisi, string, maksimal 50 karakter, harus unique (tidak boleh duplikat)
     * - description: opsional, string
     * - order: wajib diisi, integer (untuk menentukan urutan tampilan)
     * 
     * @param Request $request Data dari form input
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',              // Nama kriteria (contoh: "Kondisi Ekonomi")
            'code' => 'required|string|max:50|unique:criterias', // Kode kriteria (contoh: "C1"), harus unik
            'description' => 'nullable|string',                // Deskripsi kriteria (opsional)
            'order' => 'required|integer',                     // Urutan tampilan (1, 2, 3, dst)
        ]);

        // Simpan data kriteria baru ke database
        // $request->all() akan mengambil semua input yang divalidasi
        Criteria::create($request->all());

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.criteria.index')
            ->with('success', 'Kriteria berhasil ditambahkan');
    }

    /**
     * Menampilkan detail satu kriteria beserta sub-kriterianya
     * 
     * Fungsi: Menampilkan halaman detail kriteria dengan relasi subCriterias
     * Route: GET /admin/criteria/{criteria}
     * 
     * @param Criteria $criteria Model Criteria yang akan ditampilkan (route model binding)
     * @return \Illuminate\View\View Halaman detail kriteria
     */
    public function show(Criteria $criteria)
    {
        // Eager load relasi subCriterias untuk menampilkan sub-kriteria
        // Load() digunakan untuk menghindari N+1 query problem
        $criteria->load('subCriterias');
        
        // Tampilkan view detail dengan data kriteria dan sub-kriterianya
        return view('admin.criteria.show', compact('criteria'));
    }

    /**
     * Menampilkan form untuk mengedit kriteria
     * 
     * Fungsi: Menampilkan halaman form edit dengan data kriteria yang akan diedit
     * Route: GET /admin/criteria/{criteria}/edit
     * 
     * @param Criteria $criteria Model Criteria yang akan diedit (route model binding)
     * @return \Illuminate\View\View Halaman form edit
     */
    public function edit(Criteria $criteria)
    {
        return view('admin.criteria.edit', compact('criteria'));
    }

    /**
     * Memperbarui data kriteria di database
     * 
     * Fungsi: Memproses data dari form edit dan update ke database
     * Route: PUT/PATCH /admin/criteria/{criteria}
     * 
     * Validasi:
     * - name: wajib diisi, string, maksimal 255 karakter
     * - code: wajib diisi, string, maksimal 50 karakter, unique kecuali untuk record ini sendiri
     * - description: opsional, string
     * - order: wajib diisi, integer
     * - is_active: boolean (untuk enable/disable kriteria)
     * 
     * @param Request $request Data dari form input
     * @param Criteria $criteria Model Criteria yang akan diupdate (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(Request $request, Criteria $criteria)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            // Validasi unique dengan pengecualian untuk record ini sendiri
            // Format: unique:table,column,except_id
            'code' => 'required|string|max:50|unique:criterias,code,' . $criteria->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',  // Status aktif/nonaktif kriteria
        ]);

        // Update data kriteria di database
        // $request->all() akan mengambil semua input yang divalidasi
        $criteria->update($request->all());

        // Redirect ke halaman index dengan flash message sukses
        return redirect()->route('admin.criteria.index')
            ->with('success', 'Kriteria berhasil diperbarui');
    }

    /**
     * Menghapus kriteria dari database
     * 
     * Fungsi: Menghapus record kriteria
     * Route: DELETE /admin/criteria/{criteria}
     * 
     * Catatan:
     * - Menggunakan try-catch untuk handle error jika kriteria masih digunakan
     * - Jika kriteria memiliki relasi (foreign key constraint), delete akan gagal
     * 
     * @param Criteria $criteria Model Criteria yang akan dihapus (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan
     */
    public function destroy(Criteria $criteria)
    {
        try {
            // Coba hapus kriteria dari database
            $criteria->delete();
            
            // Jika berhasil, redirect dengan pesan sukses
            return redirect()->route('admin.criteria.index')
                ->with('success', 'Kriteria berhasil dihapus');
                
        } catch (\Exception $e) {
            // Jika gagal (biasanya karena foreign key constraint)
            // Kriteria tidak bisa dihapus jika masih digunakan di:
            // - SubCriteria
            // - ApplicationValue
            // - PairwiseComparison
            // - CriteriaWeight
            return redirect()->route('admin.criteria.index')
                ->with('error', 'Kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}