<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\PairwiseComparison;
use App\Models\CriteriaWeight;
use App\Http\Controllers\AHPController;

/**
 * Controller untuk mengelola Perbandingan Berpasangan (Pairwise Comparison)
 * menggunakan metode AHP (Analytic Hierarchy Process)
 * 
 * Fungsi Utama:
 * - Mengelola perbandingan berpasangan di 3 level: Criteria, SubCriteria, SubSubCriteria
 * - Menghitung bobot (weight) dari setiap elemen menggunakan AHP
 * - Mengecek konsistensi matriks perbandingan (Consistency Ratio/CR)
 * - Menyimpan hasil perbandingan dan bobotnya
 * 
 * AHP Workflow:
 * 1. User membandingkan setiap pasang elemen (A vs B)
 * 2. Nilai perbandingan disimpan beserta reciprocal-nya (jika A vs B = 5, maka B vs A = 1/5)
 * 3. Sistem menghitung bobot menggunakan eigenvector
 * 4. Sistem menghitung Consistency Ratio (CR) untuk validasi konsistensi
 * 5. CR <= 0.1 dianggap konsisten, CR > 0.1 perlu diperbaiki
 */
class PairwiseComparisonController extends Controller
{
    /**
     * Instance AHPController untuk perhitungan bobot
     * @var AHPController
     */
    protected $ahpController;

    /**
     * Constructor - inisialisasi AHPController
     */
    public function __construct()
    {
        $this->ahpController = new AHPController();
    }

    /**
     * Helper function: Membersihkan nilai reciprocal dari floating point error
     * 
     * Fungsi: Mencegah nilai seperti 3.0000001 atau 0.33333334 yang disebabkan
     *         oleh keterbatasan presisi floating point di PHP
     * 
     * Problem yang diselesaikan:
     * - Input: 3 → Reciprocal: 1/3 = 0.333333... → Saat dibalik: 1/0.333333 = 3.0000001
     * - Sistem menampilkan 3.003 padahal seharusnya 3
     * 
     * Solusi:
     * 1. Hitung reciprocal dengan presisi tinggi
     * 2. Bandingkan dengan nilai standar AHP (1-9 dan reciprocal-nya)
     * 3. Jika mendekati nilai standar, gunakan nilai standar tersebut
     * 4. Ini memastikan konsistensi dengan perhitungan Excel
     * 
     * Contoh:
     * - cleanReciprocalValue(3) → 0.333333... (bukan 0.333333334)
     * - cleanReciprocalValue(0.333333) → 3 (bukan 3.0000001)
     * - cleanReciprocalValue(5) → 0.2 (bukan 0.200000001)
     * 
     * @param float $value Nilai perbandingan yang akan dihitung reciprocal-nya
     * @return float Nilai reciprocal yang sudah dibersihkan
     */
    private function cleanReciprocalValue($value)
    {
        // Hitung reciprocal (kebalikan)
        $reciprocal = 1 / $value;
        
        // Round ke 10 desimal untuk handle floating point precision error
        $reciprocal = round($reciprocal, 10);
        
        // Daftar nilai standar dalam skala AHP Saaty (1-9)
        // Termasuk nilai reciprocal dari skala tersebut
        $standardValues = [
            1/9, 1/8, 1/7, 1/6, 1/5, 1/4, 1/3, 1/2,  // Reciprocal dari 9-2
            1, 2, 3, 4, 5, 6, 7, 8, 9                 // Nilai standar
        ];
        
        // Cek apakah reciprocal mendekati salah satu nilai standar
        foreach ($standardValues as $standard) {
            // Jika selisihnya < 0.0001, anggap sama dengan nilai standar
            if (abs($reciprocal - $standard) < 0.0001) {
                return $standard;
            }
        }
        
        // Jika tidak mendekati nilai standar manapun, return reciprocal yang sudah di-round
        return $reciprocal;
    }

    /**
     * Menampilkan halaman perbandingan berpasangan untuk CRITERIA (Level 1)
     * 
     * Fungsi: Menampilkan form untuk membandingkan kriteria utama (C1 vs C2, C1 vs C3, dst)
     * Route: GET /admin/pairwise/criteria
     * 
     * @return \Illuminate\View\View Halaman perbandingan criteria
     */
    public function criteria()
    {
        // Load criteria dengan subcriteria untuk keperluan navigasi menu
        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active();
            }])
            ->orderBy('order')
            ->get();
        
        // Ambil perbandingan yang sudah ada (jika sudah pernah disimpan)
        $comparisons = $this->getExistingComparisons('criteria');
        
        return view('admin.pairwise.criteria', compact('criterias', 'comparisons'));
    }

    /**
     * Menyimpan perbandingan berpasangan CRITERIA dan menghitung bobotnya
     * 
     * Fungsi: 
     * - Menyimpan nilai perbandingan berpasangan antar criteria
     * - Menyimpan reciprocal (kebalikan) dari setiap perbandingan
     * - Memanggil AHP untuk menghitung bobot
     * - Mengecek consistency ratio (CR)
     * 
     * Route: POST /admin/pairwise/criteria
     * 
     * Flow:
     * 1. Validasi input
     * 2. Loop setiap perbandingan yang diinput
     * 3. Simpan A vs B dengan nilai yang diinput
     * 4. Simpan B vs A dengan nilai reciprocal yang sudah dibersihkan
     * 5. Hitung bobot dengan AHP
     * 6. Cek CR untuk validasi konsistensi
     * 
     * @param Request $request Data perbandingan dari form
     * @return \Illuminate\Http\RedirectResponse Redirect dengan pesan sukses/warning
     */
    public function storeCriteria(Request $request)
    {
        // Validasi input perbandingan
        $request->validate([
            'comparisons' => 'required|array',                          // Harus ada array perbandingan
            'comparisons.*.item_a_id' => 'required|exists:criterias,id', // ID criteria A harus valid
            'comparisons.*.item_b_id' => 'required|exists:criterias,id', // ID criteria B harus valid
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',   // Nilai perbandingan 0.1-9 (skala AHP)
        ]);

        // Loop setiap perbandingan yang diinput
        foreach ($request->comparisons as $comparison) {
            // Pastikan tidak membandingkan item dengan dirinya sendiri
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Simpan perbandingan A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'criteria',        // Tipe: criteria level
                    'parent_id' => null,                    // Tidak ada parent (level tertinggi)
                    'item_a_id' => $comparison['item_a_id'], // Criteria A
                    'item_b_id' => $comparison['item_b_id'], // Criteria B
                ], [
                    'value' => $comparison['value']         // Nilai perbandingan (1-9)
                ]);

                // Simpan reciprocal B vs A (kebalikan) dengan nilai yang sudah dibersihkan
                // Contoh: jika A vs B = 5, maka B vs A = 1/5 = 0.2 (bukan 0.200000001)
                // Menggunakan cleanReciprocalValue() untuk mencegah floating point error
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'criteria',
                    'parent_id' => null,
                    'item_a_id' => $comparison['item_b_id'], // Dibalik: B sebagai item A
                    'item_b_id' => $comparison['item_a_id'], // Dibalik: A sebagai item B
                ], [
                    'value' => $this->cleanReciprocalValue($comparison['value']) // Reciprocal yang dibersihkan
                ]);
            }
        }

        // Panggil AHP Controller untuk menghitung bobot berdasarkan perbandingan
        $result = $this->ahpController->calculateCriteriaWeights();

        // Siapkan pesan respons
        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        
        // Cek consistency ratio (CR)
        // CR > 0.1 berarti matriks tidak konsisten, user perlu memperbaiki perbandingan
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        // Redirect dengan pesan sukses (CR <= 0.1) atau warning (CR > 0.1)
        return redirect()->route('admin.pairwise.criteria')
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    /**
     * Menampilkan halaman perbandingan berpasangan untuk SUBCRITERIA (Level 2)
     * 
     * Fungsi: Menampilkan form untuk membandingkan subcriteria dalam satu criteria
     * Contoh: Untuk C1, bandingkan C1_1 vs C1_2, C1_1 vs C1_3, dst
     * Route: GET /admin/pairwise/subcriteria/{criterion}
     * 
     * @param Criteria $criterion Criteria parent yang subcriteria-nya akan dibandingkan
     * @return \Illuminate\View\View Halaman perbandingan subcriteria
     */
    public function subCriteria(Criteria $criterion)
    {
        // Load subcriteria dari criteria ini, beserta sub-sub-criteria untuk navigasi
        $subCriterias = $criterion->subCriterias()
            ->active()
            ->with(['subSubCriterias' => function($query) {
                $query->active();
            }])
            ->orderBy('order')
            ->get();
        
        // Ambil perbandingan yang sudah ada untuk criteria ini
        $comparisons = $this->getExistingComparisons('subcriteria', $criterion->id);
        
        return view('admin.pairwise.subcriteria', compact('criterion', 'subCriterias', 'comparisons'));
    }

    /**
     * Menyimpan perbandingan berpasangan SUBCRITERIA dan menghitung bobotnya
     * 
     * Route: POST /admin/pairwise/subcriteria/{criterion}
     * 
     * Flow sama seperti criteria, tapi dengan parent_id = criterion->id
     * 
     * @param Request $request Data perbandingan dari form
     * @param Criteria $criterion Criteria parent
     * @return \Illuminate\Http\RedirectResponse Redirect dengan pesan
     */
    public function storeSubCriteria(Request $request, Criteria $criterion)
    {
        // Validasi input perbandingan subcriteria
        $request->validate([
            'comparisons' => 'required|array',
            'comparisons.*.item_a_id' => 'required|exists:sub_criterias,id',
            'comparisons.*.item_b_id' => 'required|exists:sub_criterias,id',
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',
        ]);

        // Loop setiap perbandingan subcriteria
        foreach ($request->comparisons as $comparison) {
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Simpan perbandingan A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subcriteria',      // Tipe: subcriteria level
                    'parent_id' => $criterion->id,           // Parent = Criteria ID
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Simpan reciprocal B vs A dengan nilai yang sudah dibersihkan
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subcriteria',
                    'parent_id' => $criterion->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => $this->cleanReciprocalValue($comparison['value'])
                ]);
            }
        }

        // Hitung bobot subcriteria untuk criteria ini
        $result = $this->ahpController->calculateSubCriteriaWeights($criterion->id);

        // Pesan respons dengan pengecekan CR
        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        return redirect()->route('admin.pairwise.subcriteria', $criterion->id)
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    /**
     * Menampilkan halaman perbandingan berpasangan untuk SUBSUBCRITERIA (Level 3)
     * 
     * Fungsi: Menampilkan form untuk membandingkan subsubcriteria dalam satu subcriteria
     * Contoh: Untuk C1_1, bandingkan pilihan jawaban 1 vs 2, 1 vs 3, dst
     * Route: GET /admin/pairwise/subsubcriteria/{subcriterion}
     * 
     * @param SubCriteria $subcriterion SubCriteria parent yang subsubcriteria-nya akan dibandingkan
     * @return \Illuminate\View\View Halaman perbandingan subsubcriteria
     */
    public function subSubCriteria(SubCriteria $subcriterion)
    {
        // Load subsubcriteria yang aktif
        $subSubCriterias = $subcriterion->subSubCriterias()->active()->orderBy('order')->get();
        
        // Ambil perbandingan yang sudah ada
        $comparisons = $this->getExistingComparisons('subsubcriteria', $subcriterion->id);
        
        // Load relasi untuk breadcrumb dan tampilan hierarki
        $subcriterion->load(['criteria', 'subSubCriterias']);
        
        return view('admin.pairwise.subsubcriteria', compact('subcriterion', 'subSubCriterias', 'comparisons'));
    }

    /**
     * Menyimpan perbandingan berpasangan SUBSUBCRITERIA dan menghitung bobotnya
     * 
     * Route: POST /admin/pairwise/subsubcriteria/{subcriterion}
     * 
     * Flow sama seperti level sebelumnya, tapi untuk level paling detail
     * 
     * @param Request $request Data perbandingan dari form
     * @param SubCriteria $subcriterion SubCriteria parent
     * @return \Illuminate\Http\RedirectResponse Redirect dengan pesan
     */
    public function storeSubSubCriteria(Request $request, SubCriteria $subcriterion)
    {
        // Validasi input perbandingan subsubcriteria
        $request->validate([
            'comparisons' => 'required|array',
            'comparisons.*.item_a_id' => 'required|exists:sub_sub_criterias,id',
            'comparisons.*.item_b_id' => 'required|exists:sub_sub_criterias,id',
            'comparisons.*.value' => 'required|numeric|min:0.1|max:9',
        ]);

        // Loop setiap perbandingan subsubcriteria
        foreach ($request->comparisons as $comparison) {
            if ($comparison['item_a_id'] != $comparison['item_b_id']) {
                // Simpan perbandingan A vs B
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subsubcriteria',   // Tipe: subsubcriteria level
                    'parent_id' => $subcriterion->id,        // Parent = SubCriteria ID
                    'item_a_id' => $comparison['item_a_id'],
                    'item_b_id' => $comparison['item_b_id'],
                ], [
                    'value' => $comparison['value']
                ]);

                // Simpan reciprocal B vs A dengan nilai yang sudah dibersihkan
                PairwiseComparison::updateOrCreate([
                    'comparison_type' => 'subsubcriteria',
                    'parent_id' => $subcriterion->id,
                    'item_a_id' => $comparison['item_b_id'],
                    'item_b_id' => $comparison['item_a_id'],
                ], [
                    'value' => $this->cleanReciprocalValue($comparison['value'])
                ]);
            }
        }

        // Hitung bobot subsubcriteria untuk subcriteria ini
        $result = $this->ahpController->calculateSubSubCriteriaWeights($subcriterion->id);

        // Pesan respons dengan pengecekan CR
        $message = 'Perbandingan berpasangan berhasil disimpan dan bobot dihitung';
        if ($result && isset($result['cr']) && $result['cr'] > 0.1) {
            $message .= '. Perhatian: Matriks tidak konsisten (CR = ' . number_format($result['cr'], 4) . '). Sebaiknya periksa kembali nilai perbandingan Anda.';
        }

        return redirect()->route('admin.pairwise.subsubcriteria', $subcriterion->id)
            ->with($result && $result['cr'] <= 0.1 ? 'success' : 'warning', $message);
    }

    /**
     * Helper function: Ambil perbandingan yang sudah ada dari database
     * 
     * Fungsi: Untuk menampilkan nilai perbandingan yang sudah pernah disimpan
     * Return: Collection dengan key = "itemA_id_itemB_id"
     * 
     * @param string $type Tipe perbandingan (criteria/subcriteria/subsubcriteria)
     * @param int|null $parentId ID parent (null untuk criteria, ID untuk level lainnya)
     * @return \Illuminate\Support\Collection Collection perbandingan dengan key custom
     */
    private function getExistingComparisons($type, $parentId = null)
    {
        return PairwiseComparison::where('comparison_type', $type)
            ->where('parent_id', $parentId)
            ->get()
            ->keyBy(function ($item) {
                // Buat key unik: gabungan item_a_id dan item_b_id
                // Contoh: "1_2" untuk perbandingan item 1 vs item 2
                return $item->item_a_id . '_' . $item->item_b_id;
            });
    }

    /**
     * Menampilkan overview konsistensi untuk semua level perbandingan
     * 
     * Fungsi: Dashboard untuk melihat status konsistensi (CR) di semua level sekaligus
     * Route: GET /admin/pairwise/consistency-overview
     * 
     * Menampilkan:
     * - Konsistensi criteria
     * - Konsistensi subcriteria (per criteria)
     * - Konsistensi subsubcriteria (per subcriteria)
     * - Lambda max, CI, CR untuk setiap level
     * 
     * @return \Illuminate\View\View Halaman overview konsistensi
     */
    public function consistencyOverview()
    {
        // Inisialisasi array untuk menyimpan data konsistensi
        $data = [
            'criteria' => [],           // Konsistensi level criteria
            'subcriteria' => [],        // Konsistensi level subcriteria
            'subsubcriteria' => []      // Konsistensi level subsubcriteria
        ];

        // === AMBIL DATA KONSISTENSI CRITERIA ===
        $criteriaWeights = CriteriaWeight::where('level', 'criteria')
            ->where('parent_id', null)
            ->with(['criteria'])
            ->get();

        // Loop dan format data criteria
        foreach ($criteriaWeights as $weight) {
            if ($weight->criteria) {
                $data['criteria'][] = [
                    'name' => $weight->criteria->name,
                    'code' => $weight->criteria->code,
                    'cr' => $weight->cr,                    // Consistency Ratio
                    'is_consistent' => $weight->is_consistent, // True jika CR <= 0.1
                    'lambda_max' => $weight->lambda_max,    // Eigenvalue maksimum
                    'ci' => $weight->ci                     // Consistency Index
                ];
            }
        }

        // === AMBIL DATA KONSISTENSI SUBCRITERIA ===
        $subCriteriaWeights = CriteriaWeight::where('level', 'subcriteria')
            ->whereNotNull('parent_id')
            ->with(['subCriteria.criteria'])
            ->get();

        // Loop dan format data subcriteria
        foreach ($subCriteriaWeights as $weight) {
            if ($weight->subCriteria && $weight->subCriteria->criteria) {
                $data['subcriteria'][] = [
                    'parent' => $weight->subCriteria->criteria->code, // Criteria parent (C1, C2, dst)
                    'name' => $weight->subCriteria->name,
                    'code' => $weight->subCriteria->code,
                    'cr' => $weight->cr,
                    'is_consistent' => $weight->is_consistent,
                    'lambda_max' => $weight->lambda_max,
                    'ci' => $weight->ci
                ];
            }
        }

        // === AMBIL DATA KONSISTENSI SUBSUBCRITERIA ===
        $subSubCriteriaWeights = CriteriaWeight::where('level', 'subsubcriteria')
            ->whereNotNull('parent_id')
            ->with(['subSubCriteria.subCriteria.criteria'])
            ->get();

        // Loop dan format data subsubcriteria
        foreach ($subSubCriteriaWeights as $weight) {
            if ($weight->subSubCriteria && $weight->subSubCriteria->subCriteria) {
                $data['subsubcriteria'][] = [
                    'grandparent' => $weight->subSubCriteria->subCriteria->criteria->code, // Criteria (C1)
                    'parent' => $weight->subSubCriteria->subCriteria->code,               // SubCriteria (C1_1)
                    'name' => $weight->subSubCriteria->name,
                    'code' => $weight->subSubCriteria->code,
                    'cr' => $weight->cr,
                    'is_consistent' => $weight->is_consistent,
                    'lambda_max' => $weight->lambda_max,
                    'ci' => $weight->ci
                ];
            }
        }

        return view('admin.pairwise.consistency-overview', compact('data'));
    }

    /**
     * Export matriks perbandingan ke Excel/PDF
     * 
     * Fungsi: Generate laporan matriks perbandingan untuk dokumentasi
     * Route: GET /admin/pairwise/export/{type}/{parentId?}
     * 
     * Status: Placeholder - implementasi akan ditambahkan
     * 
     * @param string $type Tipe perbandingan
     * @param int|null $parentId ID parent
     * @return \Illuminate\Http\JsonResponse Response JSON
     */
    public function exportMatrix($type, $parentId = null)
    {
        // TODO: Implementasi export ke Excel atau PDF
        // Akan menampilkan matriks perbandingan lengkap beserta hasil perhitungan
        
        return response()->json([
            'message' => 'Export functionality will be implemented',
            'type' => $type,
            'parent_id' => $parentId
        ]);
    }

    /**
     * Reset semua perbandingan untuk level tertentu
     * 
     * Fungsi: Menghapus semua perbandingan dan bobot untuk mulai dari awal
     * Route: POST /admin/pairwise/reset
     * 
     * Yang direset:
     * 1. Data di tabel pairwise_comparisons
     * 2. Data di tabel criteria_weights
     * 3. Weight di tabel criteria/sub_criterias/sub_sub_criterias
     * 
     * @param Request $request Data reset (type dan parent_id)
     * @return \Illuminate\Http\RedirectResponse Redirect dengan pesan sukses
     */
    public function resetComparisons(Request $request)
    {
        // Validasi input reset
        $request->validate([
            'type' => 'required|in:criteria,subcriteria,subsubcriteria', // Hanya 3 tipe ini yang valid
            'parent_id' => 'nullable|integer'                            // ID parent (jika ada)
        ]);

        // Hapus semua perbandingan berpasangan untuk tipe dan parent ini
        PairwiseComparison::where('comparison_type', $request->type)
            ->where('parent_id', $request->parent_id)
            ->delete();

        // Hapus hasil perhitungan bobot yang sesuai
        CriteriaWeight::where('level', $request->type)
            ->where('parent_id', $request->parent_id)
            ->delete();

        // Reset weight di model terkait menjadi 0
        switch ($request->type) {
            case 'criteria':
                // Reset weight semua criteria
                Criteria::where('id', '>', 0)->update(['weight' => 0]);
                break;
            case 'subcriteria':
                // Reset weight subcriteria untuk criteria tertentu
                SubCriteria::where('criteria_id', $request->parent_id)->update(['weight' => 0]);
                break;
            case 'subsubcriteria':
                // Reset weight subsubcriteria untuk subcriteria tertentu
                SubSubCriteria::where('sub_criteria_id', $request->parent_id)->update(['weight' => 0]);
                break;
        }

        return redirect()->back()->with('success', 'Perbandingan berpasangan berhasil direset');
    }

    /**
     * Halaman utama navigasi perbandingan berpasangan
     * 
     * Fungsi: Dashboard/menu utama untuk navigasi ke berbagai level perbandingan
     * Route: GET /admin/pairwise
     * 
     * Menampilkan:
     * - Struktur hierarki lengkap (Criteria > SubCriteria > SubSubCriteria)
     * - Status konsistensi untuk setiap level
     * - Link navigasi ke halaman perbandingan masing-masing level
     * 
     * @return \Illuminate\View\View Halaman index navigasi
     */
    public function index()
    {
        // Load struktur hierarki lengkap dengan relasi nested
        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active()->with(['subSubCriterias' => function($subQuery) {
                    $subQuery->active();
                }]);
            }])
            ->orderBy('order')
            ->get();

        // Inisialisasi data summary konsistensi
        $consistencyData = [
            'criteria_consistent' => CriteriaWeight::where('level', 'criteria')
                ->where('parent_id', null)
                ->where('is_consistent', true)
                ->exists(),                         // Apakah criteria sudah konsisten?
            'subcriteria_total' => 0,               // Total subcriteria yang perlu dibandingkan
            'subcriteria_consistent' => 0,          // Jumlah subcriteria yang sudah konsisten
            'subsubcriteria_total' => 0,            // Total subsubcriteria yang perlu dibandingkan
            'subsubcriteria_consistent' => 0,       // Jumlah subsubcriteria yang sudah konsisten
        ];

        // Loop setiap criteria untuk hitung status konsistensi
        foreach ($criterias as $criteria) {
            // Cek subcriteria (hanya jika ada >= 2 item untuk dibandingkan)
            if ($criteria->subCriterias->count() >= 2) {
                $consistencyData['subcriteria_total']++;
                
                // Cek apakah sudah konsisten
                $weight = CriteriaWeight::where('level', 'subcriteria')
                    ->where('parent_id', $criteria->id)
                    ->where('is_consistent', true)
                    ->first();
                    
                if ($weight) {
                    $consistencyData['subcriteria_consistent']++;
                }
            }

            // Loop setiap subcriteria untuk cek subsubcriteria
            foreach ($criteria->subCriterias as $subCriteria) {
                // Cek subsubcriteria (hanya jika ada >= 2 item)
                if ($subCriteria->subSubCriterias->count() >= 2) {
                    $consistencyData['subsubcriteria_total']++;
                    
                    // Cek apakah sudah konsisten
                    $weight = CriteriaWeight::where('level', 'subsubcriteria')
                        ->where('parent_id', $subCriteria->id)
                        ->where('is_consistent', true)
                        ->first();
                        
                    if ($weight) {
                        $consistencyData['subsubcriteria_consistent']++;
                    }
                }
            }
        }

        return view('admin.pairwise.index', compact('criterias', 'consistencyData'));
    }
}