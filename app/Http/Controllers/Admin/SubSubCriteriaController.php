<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;

/**
 * Controller untuk mengelola Sub Sub Kriteria (Level 3 - Level paling detail)
 * 
 * Hierarki Kriteria:
 * Level 1: Criteria (C1, C2, C3, C4, C5)
 * Level 2: SubCriteria (C1_1, C1_2, C1_3, dst)
 * Level 3: SubSubCriteria (pilihan jawaban spesifik) ← Controller ini
 * 
 * Fungsi Utama:
 * - CRUD SubSubCriteria dalam scope satu SubCriteria
 * - Mengelola pilihan jawaban untuk setiap pertanyaan (SubCriteria)
 * - Score dan weight diinisialisasi 0, akan dihitung via pairwise comparison
 * 
 * Konsep SubSubCriteria:
 * - SubSubCriteria adalah pilihan jawaban konkret dari SubCriteria
 * - Ini adalah level paling detail dalam hierarki
 * - Setiap SubCriteria dapat memiliki banyak SubSubCriteria (pilihan jawaban)
 * - Siswa memilih salah satu SubSubCriteria saat mengisi aplikasi
 * - Score dan weight dihitung otomatis via AHP, tidak input manual
 * 
 * Contoh SubSubCriteria:
 * C1_1 (Pekerjaan Ayah):
 *   - "Tidak Bekerja" (score tertinggi - kondisi terburuk)
 *   - "Buruh Harian" (score tinggi)
 *   - "Pedagang Kecil" (score sedang)
 *   - "Karyawan Swasta" (score rendah)
 *   - "PNS/TNI/Polri" (score terendah - kondisi terbaik)
 * 
 * C2_1 (Jenis Dinding):
 *   - "Anyaman Bambu" (score tertinggi)
 *   - "Kayu" (score sedang)
 *   - "Tembok Tidak Diplester" (score rendah)
 *   - "Tembok Diplester" (score terendah)
 */
class SubSubCriteriaController extends Controller
{
    /**
     * Menampilkan daftar SubSubCriteria untuk SubCriteria tertentu
     * 
     * Fungsi:
     * - Jika $subcriterion provided: tampilkan SubSubCriteria untuk subcriterion tersebut
     * - Jika $subcriterion null: tampilkan halaman pilih subcriterion dulu
     * Route: GET /admin/subsubcriteria (tanpa parameter)
     *        GET /admin/subsubcriteria/{subcriterion} (dengan parameter)
     * 
     * Fitur:
     * - List semua SubCriteria (dengan eager load Criteria parent) untuk dropdown
     * - List SubSubCriteria terurut berdasarkan order
     * - Conditional display berdasarkan apakah subcriterion dipilih
     * 
     * @param SubCriteria|null $subcriterion Model SubCriteria parent (opsional)
     * @return \Illuminate\View\View Halaman daftar subsubcriteria
     */
    public function index(SubCriteria $subcriterion = null)
    {
        // Ambil semua subcriteria dengan eager load criteria parent untuk dropdown
        // Eager load criteria untuk menampilkan breadcrumb: C1 > C1_1 > Pilihan
        $subCriterias = SubCriteria::with('criteria')->orderBy('order')->get();
        
        // Inisialisasi collection kosong untuk subSubCriterias
        $subSubCriterias = collect();
        
        // Jika subcriterion dipilih, ambil subsubcriteria-nya (pilihan jawaban)
        if ($subcriterion) {
            // Ambil semua pilihan jawaban untuk subcriterion ini
            // Urutkan berdasarkan order (biasanya dari kondisi terburuk ke terbaik)
            $subSubCriterias = $subcriterion->subSubCriterias()->orderBy('order')->get();
        }
        
        return view('admin.subsubcriteria.index', compact('subcriterion', 'subSubCriterias', 'subCriterias'));
    }

    /**
     * Menampilkan form untuk membuat SubSubCriteria baru
     * 
     * Fungsi: Menampilkan halaman form create untuk menambah pilihan jawaban baru
     * Route: GET /admin/subsubcriteria/{subcriterion}/create
     * 
     * Use Case:
     * - Admin menambah pilihan jawaban baru untuk satu subcriteria
     * - Contoh: Menambah opsi "Freelance" ke pilihan "Pekerjaan Ayah"
     * - Menambah kategori baru yang belum ada
     * 
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @return \Illuminate\View\View Halaman form create
     */
    public function create(SubCriteria $subcriterion)
    {
        return view('admin.subsubcriteria.create', compact('subcriterion'));
    }

    /**
     * Menyimpan SubSubCriteria baru ke database
     * 
     * Fungsi: Memproses data dari form create dan simpan ke tabel sub_sub_criterias
     * Route: POST /admin/subsubcriteria/{subcriterion}
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter (contoh: "Tidak Bekerja")
     * - code: wajib, string, maksimal 50 karakter, harus unique
     * - description: opsional, string (deskripsi detail pilihan)
     * - order: wajib, integer (urutan tampilan, biasanya terburuk dulu)
     * 
     * Catatan Penting:
     * - Score diset 0 secara default (akan diupdate via pairwise comparison)
     * - Weight diset 0 secara default (akan dihitung via AHP)
     * - Tidak ada input manual untuk score dan weight
     * - Score dan weight akan dihitung otomatis berdasarkan perbandingan berpasangan
     * 
     * Logika Score:
     * - Semakin buruk kondisi = semakin tinggi score (prioritas lebih tinggi dapat bantuan)
     * - Contoh: "Tidak Bekerja" score > "PNS" score
     * 
     * @param Request $request Data dari form input
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(Request $request, SubCriteria $subcriterion)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',              // Nama pilihan jawaban (wajib)
            'code' => 'required|string|max:50|unique:sub_sub_criterias', // Kode unique (wajib)
            'description' => 'nullable|string',                // Deskripsi (opsional)
            'order' => 'required|integer',                     // Urutan tampilan (wajib)
        ]);

        // Buat subsubcriteria baru sebagai child dari subcriterion
        // Create without score - will be calculated later via pairwise comparison
        $subcriterion->subSubCriterias()->create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            'score' => 0,        // Default score = 0, akan diupdate setelah pairwise comparison
            'weight' => 0,       // Default weight = 0, akan dihitung via AHP
            'is_active' => true, // Default aktif saat dibuat
        ]);

        // Redirect ke halaman index subsubcriteria untuk subcriterion ini
        return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
            ->with('success', 'Sub sub kriteria berhasil ditambahkan');
    }

    /**
     * Menampilkan detail satu SubSubCriteria
     * 
     * Fungsi: Menampilkan halaman detail yang berisi:
     *         - Informasi SubSubCriteria (nama, kode, score, weight, deskripsi)
     *         - Breadcrumb: Criteria > SubCriteria > SubSubCriteria
     *         - Informasi parent hierarchy
     * Route: GET /admin/subsubcriteria/{subcriterion}/{subsubcriterion}
     * 
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @param SubSubCriteria $subsubcriterion Model SubSubCriteria yang akan ditampilkan (route model binding)
     * @return \Illuminate\View\View Halaman detail subsubcriteria
     */
    public function show(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        return view('admin.subsubcriteria.show', compact('subcriterion', 'subsubcriterion'));
    }

    /**
     * Menampilkan form untuk mengedit SubSubCriteria
     * 
     * Fungsi: Menampilkan halaman form edit dengan data subsubcriteria yang akan diedit
     * Route: GET /admin/subsubcriteria/{subcriterion}/{subsubcriterion}/edit
     * 
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @param SubSubCriteria $subsubcriterion Model SubSubCriteria yang akan diedit (route model binding)
     * @return \Illuminate\View\View Halaman form edit
     */
    public function edit(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        return view('admin.subsubcriteria.edit', compact('subcriterion', 'subsubcriterion'));
    }

    /**
     * Memperbarui data SubSubCriteria di database
     * 
     * Fungsi: Memproses data dari form edit dan update ke database
     * Route: PUT/PATCH /admin/subsubcriteria/{subcriterion}/{subsubcriterion}
     * 
     * Validasi:
     * - name: wajib, string, maksimal 255 karakter
     * - code: wajib, string, maksimal 50 karakter, unique kecuali untuk subsubcriteria ini
     * - description: opsional, string
     * - order: wajib, integer (untuk mengatur ulang urutan)
     * - is_active: boolean (status aktif/nonaktif)
     * 
     * Catatan:
     * - Score dan weight tidak bisa diupdate manual, hanya via pairwise comparison
     * - is_active menggunakan $request->has() untuk handle checkbox
     * - Perubahan order bisa mempengaruhi urutan tampilan di form aplikasi
     * 
     * @param Request $request Data dari form input
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @param SubSubCriteria $subsubcriterion Model SubSubCriteria yang akan diupdate (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(Request $request, SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        // Validasi input dari form
        $request->validate([
            'name' => 'required|string|max:255',
            // Code unique dengan pengecualian untuk subsubcriteria ini sendiri
            'code' => 'required|string|max:50|unique:sub_sub_criterias,code,' . $subsubcriterion->id,
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        // Update data subsubcriteria
        $subsubcriterion->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'order' => $request->order,
            // is_active: true jika checkbox dicentang, false jika tidak
            'is_active' => $request->has('is_active'),
        ]);

        // Redirect ke halaman index subsubcriteria untuk subcriterion parent
        return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
            ->with('success', 'Sub sub kriteria berhasil diperbarui');
    }

    /**
     * Menghapus SubSubCriteria dari database
     * 
     * Fungsi: Menghapus record subsubcriteria (pilihan jawaban)
     * Route: DELETE /admin/subsubcriteria/{subcriterion}/{subsubcriterion}
     * 
     * Catatan:
     * - Menggunakan try-catch untuk handle error foreign key constraint
     * - SubSubCriteria tidak bisa dihapus jika:
     *   1. Masih digunakan di ApplicationValue (masih ada siswa yang pilih opsi ini)
     *   2. Masih ada PairwiseComparison terkait
     *   3. Masih ada CriteriaWeight terkait
     * 
     * Impact Penghapusan:
     * - Jika dihapus, siswa tidak bisa memilih opsi ini lagi
     * - Data historical aplikasi yang sudah pakai opsi ini akan terpengaruh
     * - Lebih baik set is_active = false daripada delete untuk data integrity
     * 
     * Alternatif:
     * - Gunakan soft delete untuk data history
     * - Set is_active = false untuk menonaktifkan tanpa menghapus
     * - Archive data ke tabel terpisah sebelum delete
     * 
     * @param SubCriteria $subcriterion Model SubCriteria parent (route model binding)
     * @param SubSubCriteria $subsubcriterion Model SubSubCriteria yang akan dihapus (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan
     */
    public function destroy(SubCriteria $subcriterion, SubSubCriteria $subsubcriterion)
    {
        try {
            // Coba hapus subsubcriteria dari database
            $subsubcriterion->delete();
            
            // Jika berhasil, redirect dengan pesan sukses
            return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
                ->with('success', 'Sub sub kriteria berhasil dihapus');
                
        } catch (\Exception $e) {
            // Jika gagal (biasanya karena foreign key constraint)
            // SubSubCriteria tidak bisa dihapus jika masih digunakan di:
            // - application_values (masih ada siswa yang memilih opsi ini)
            // - pairwise_comparisons (masih ada perbandingan berpasangan)
            // - criteria_weights (masih ada perhitungan bobot)
            
            // Pesan error untuk user
            return redirect()->route('admin.subsubcriteria.index', $subcriterion->id)
                ->with('error', 'Sub sub kriteria tidak dapat dihapus karena masih digunakan');
        }
    }
}