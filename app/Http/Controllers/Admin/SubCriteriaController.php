<?php
// app/Http/Controllers/Admin/SubCriteriaController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\SubCriteria;

/**
 * Controller untuk mengelola Sub Kriteria (Level 2 dari hierarki kriteria)
 * 
 * Hierarki Kriteria:
 * Level 1: Criteria (C1, C2, C3, C4, C5)
 * Level 2: SubCriteria (C1_1, C1_2, C1_3, dst) ← Controller ini
 * Level 3: SubSubCriteria (pilihan jawaban)
 * 
 * Fungsi Utama:
 * - CRUD SubCriteria dalam scope satu Criteria
 * - Mengelola urutan dan status aktif SubCriteria
 * - Bobot (weight) diinisialisasi 0, akan dihitung via pairwise comparison
 * 
 * Konsep:
 * - SubCriteria adalah anak dari Criteria
 * - Setiap Criteria dapat memiliki banyak SubCriteria
 * - Code format: {parent_code}_{sequence} (contoh: C1_1, C1_2, C1_3)
 * - Weight tidak di-input manual, dihitung otomatis via AHP
 * 
 * Contoh SubCriteria:
 * C1 (Kondisi Ekonomi):
 *   - C1_1: Pekerjaan Ayah
 *   - C1_2: Pekerjaan Ibu
 *   - C1_3: Pendapatan Ayah
 *   - dst...
 */
class SubCriteriaController extends Controller
{
    /**
     * Menampilkan daftar SubCriteria untuk Criteria tertentu
     * 
     * Fungsi: 
     * - Jika $criterion provided: tampilkan SubCriteria untuk criterion tersebut
     * - Jika $criterion null: tampilkan halaman pilih criterion dulu
     * Route: GET /admin/subcriteria (tanpa parameter)
     *        GET /admin/subcriteria/{criterion} (dengan parameter)
     * 
     * Fitur:
     * - List semua Criteria untuk navigasi/dropdown
     * - List SubCriteria terurut berdasarkan order
     * - Conditional display berdasarkan apakah criterion dipilih
     * 
     * @param Criteria|null $criterion Model Criteria yang SubCriteria-nya akan ditampilkan (opsional)
     * @return \Illuminate\View\View Halaman daftar subcriteria
     */
    public function index(Criteria $criterion = null)
    {
        // Ambil semua criteria untuk dropdown/navigasi
        // Urutkan berdasarkan order (C1, C2, C3, dst)
        $criterias = Criteria::orderBy('order')->get();
        
        // Inisialisasi collection kosong untuk subCriterias
        $subCriterias = collect();
        
        // Jika criterion dipilih, ambil subcriteria-nya
        if ($criterion) {
            // Ambil subcriteria yang terkait dengan criterion ini
            // Urutkan berdasarkan order (C1_1, C1_2, C1_3, dst)
            $subCriterias = $criterion->subCriterias()->orderBy('order')->get();
        }
        
        return view('admin.subcriteria.index', compact('criterion', 'subCriterias', 'criterias'));
    }

    /**
     * Menampilkan form untuk membuat SubCriteria baru
     * 
     * Fungsi:
     * - Jika $criterion provided: tampilkan form create untuk criterion tersebut
     * - Jika $criterion null: tampilkan halaman pilih criterion dulu
     * Route: GET /admin/subcriteria/create (tanpa parameter)
     *        GET /admin/subcriteria/{criterion}/create (dengan parameter)
     * 
     * Use Case:
     * - Admin menambah subcriteria baru ke dalam satu criteria
     * - Contoh: Menambah C1_8 (subcriteria ke-8) ke dalam C1
     * 
     * @param Criteria|null $criterion Model Criteria parent (opsional)
     * @return \Illuminate\View\View Halaman form create
     */
    public function create(Criteria $criterion = null)
    {
        if (!$criterion) {
            // Jika tidak ada criterion, tampilkan halaman pilih criterion dulu
            // User harus memilih C1/C2/C3/dst sebelum bisa create subcriteria
            return view('admin.subcriteria.create');
        }
        
        // Jika criterion sudah dipilih, tampilkan form create
        return view('admin.subcriteria.create', compact('criterion'));
    }

    /**
     * Menyimpan SubCriteria baru ke database
     * 
     * Fungsi: Memproses data dari form create dan simpan ke tabel sub_criterias
     * Route: POST /admin/subcriteria/{criterion}
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter (contoh: "Pekerjaan Ayah")
     * - code: wajib, string, maksimal 50 karakter, harus unique (contoh: "C1_1")
     * - description: opsional, string (deskripsi detail subcriteria)
     * - order: wajib, integer (urutan tampilan)
     * 
     * Catatan Penting:
     * - Weight diset 0 secara default
     * - Weight akan dihitung otomatis via pairwise comparison (AHP)
     * - Tidak ada input manual untuk weight
     * 
     * @param Request $request Data dari form input
     * @param Criteria $criterion Model Criteria parent (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(Request $request, Criteria $criterion)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',              // Nama subcriteria (wajib)
            'code' => 'required|string|max:50|unique:sub_criterias', // Kode unique (wajib)
            'description' => 'nullable|string',                // Deskripsi (opsional)
            'order' => 'required|integer',                     // Urutan tampilan (wajib)
        ]);

        // Buat subcriteria baru sebagai child dari criterion
        // Create without weight - will be calculated later via pairwise comparison
        $criterion->subCriterias()->create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'weight' => 0,          // Default weight = 0, akan dihitung via AHP
            'is_active' => true,    // Default aktif saat dibuat
        ]);
        
        // Redirect ke halaman index subcriteria untuk criterion ini
        return redirect()->route('admin.subcriteria.index', $criterion->id)
            ->with('success', 'Sub kriteria berhasil ditambahkan');
    }

    /**
     * Menampilkan detail satu SubCriteria beserta SubSubCriteria-nya
     * 
     * Fungsi: Menampilkan halaman detail yang berisi:
     *         - Informasi SubCriteria (nama, kode, bobot, deskripsi)
     *         - Daftar SubSubCriteria (pilihan jawaban) jika ada
     *         - Breadcrumb: Criteria > SubCriteria
     * Route: GET /admin/subcriteria/{criteria}/{subCriteria}
     * 
     * @param Criteria $criteria Model Criteria parent (route model binding)
     * @param SubCriteria $subCriteria Model SubCriteria yang akan ditampilkan (route model binding)
     * @return \Illuminate\View\View Halaman detail subcriteria
     */
    public function show(Criteria $criteria, SubCriteria $subCriteria)
    {
        // Eager load subSubCriterias untuk menampilkan pilihan jawaban
        $subCriteria->load('subSubCriterias');
        
        return view('admin.subcriteria.show', compact('criteria', 'subCriteria'));
    }

    /**
     * Menampilkan form untuk mengedit SubCriteria
     * 
     * Fungsi: Menampilkan halaman form edit dengan data subcriteria yang akan diedit
     * Route: GET /admin/subcriteria/{criteria}/{subCriteria}/edit
     * 
     * @param Criteria $criteria Model Criteria parent (route model binding)
     * @param SubCriteria $subCriteria Model SubCriteria yang akan diedit (route model binding)
     * @return \Illuminate\View\View Halaman form edit
     */
    public function edit(Criteria $criteria, SubCriteria $subCriteria)
    {
        return view('admin.subcriteria.edit', compact('criteria', 'subCriteria'));
    }

    /**
     * Memperbarui data SubCriteria di database
     * 
     * Fungsi: Memproses data dari form edit dan update ke database
     * Route: PUT/PATCH /admin/subcriteria/{criteria}/{subCriteria}
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter
     * - code: wajib, string, maksimal 50 karakter, unique kecuali untuk subcriteria ini
     * - description: opsional, string
     * - order: wajib, integer (untuk mengatur ulang urutan)
     * - is_active: boolean (status aktif/nonaktif)
     * 
     * Catatan:
     * - Weight tidak bisa diupdate manual, hanya via pairwise comparison
     * - is_active menggunakan $request->has() untuk handle checkbox
     * 
     * @param Request $request Data dari form input
     * @param Criteria $criteria Model Criteria parent (route model binding)
     * @param SubCriteria $subCriteria Model SubCriteria yang akan diupdate (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(Request $request, Criteria $criteria, SubCriteria $subCriteria)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            // Code unique dengan pengecualian untuk subcriteria ini sendiri
            'code' => 'required|string|max:50|unique:sub_criterias,code,' . $subCriteria->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        // Update data subcriteria
        $subCriteria->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            // is_active: true jika checkbox dicentang, false jika tidak
            'is_active' => $request->has('is_active'),
        ]);
        
        // Redirect ke halaman index subcriteria untuk criterion parent
        return redirect()->route('admin.subcriteria.index', $criteria->id)
            ->with('success', 'Sub kriteria berhasil diperbarui');
    }

    /**
     * Menghapus SubCriteria dari database
     * 
     * Fungsi: Menghapus record subcriteria
     * Route: DELETE /admin/subcriteria/{criteria}/{subCriteria}
     * 
     * Catatan:
     * - Menggunakan try-catch untuk handle error foreign key constraint
     * - SubCriteria tidak bisa dihapus jika:
     *   1. Masih memiliki SubSubCriteria
     *   2. Masih digunakan di ApplicationValue
     *   3. Masih ada PairwiseComparison terkait
     *   4. Masih ada CriteriaWeight terkait
     * 
     * Alternatif:
     * - Gunakan soft delete untuk data history
     * - Atau set is_active = false untuk menonaktifkan
     * 
     * @param Criteria $criteria Model Criteria parent (route model binding)
     * @param SubCriteria $subCriteria Model SubCriteria yang akan dihapus (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan
     */
    public function destroy(Criteria $criteria, SubCriteria $subCriteria)
    {
        try {
            // Coba hapus subcriteria dari database
            $subCriteria->delete();
            
            // Jika berhasil, redirect dengan pesan sukses
            return redirect()->route('admin.subcriteria.index', $criteria->id)
                ->with('success', 'Sub kriteria berhasil dihapus');
                
        } catch (\Exception $e) {
            // Jika gagal (biasanya karena foreign key constraint)
            // SubCriteria tidak bisa dihapus jika masih digunakan di:
            // - sub_sub_criterias (masih punya pilihan jawaban)
            // - application_values (masih ada jawaban siswa)
            // - pairwise_comparisons (masih ada perbandingan berpasangan)
            // - criteria_weights (masih ada perhitungan bobot)
            
            return redirect()->route('admin.subcriteria.index', $criteria->id)
                ->with('error', 'Sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}