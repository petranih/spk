<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Period;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\ApplicationValue;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller untuk Siswa mengelola Aplikasi Bantuan
 * 
 * Fungsi Utama:
 * - Siswa membuat aplikasi bantuan baru
 * - Mengisi data pribadi dan kriteria penilaian
 * - Upload dokumen persyaratan
 * - Submit aplikasi untuk divalidasi
 * 
 * Flow Aplikasi:
 * 1. CREATE: Siswa pilih periode dan buat aplikasi (status: draft)
 * 2. EDIT: Siswa isi data pribadi, kriteria, dan upload dokumen
 * 3. SUBMIT: Siswa submit aplikasi (status: submitted)
 * 4. VALIDASI: Admin/validator review aplikasi
 * 5. SCORING: Sistem hitung skor menggunakan AHP
 * 
 * Status Aplikasi:
 * - draft: Sedang diisi, belum submit
 * - submitted: Sudah submit, menunggu validasi
 * - validated: Sudah divalidasi, masuk perhitungan
 * - rejected: Ditolak oleh validator
 * 
 * Fitur Penting:
 * - AJAX auto-save untuk setiap input kriteria
 * - Radio group logic untuk pilihan eksklusif
 * - Document upload dengan validasi
 * - Real-time progress tracking
 */
class ApplicationController extends Controller
{
    /**
     * Menampilkan form untuk membuat aplikasi baru
     * 
     * Fungsi: Menampilkan form create aplikasi untuk periode tertentu
     * Route: GET /student/application/create?period={id}
     * 
     * Flow Logic:
     * 1. Cek apakah ada parameter period di URL
     * 2. Jika ada, validasi periode tersebut (aktif, ongoing, bisa terima aplikasi)
     * 3. Jika tidak ada, ambil periode aktif default
     * 4. Cek apakah siswa sudah punya aplikasi di periode ini
     * 5. Jika sudah ada, redirect ke edit (tidak bisa duplikat)
     * 6. Jika belum, tampilkan form create
     * 
     * Validasi Periode:
     * - is_active: Periode harus aktif di sistem
     * - is_ongoing: Periode masih berjalan (belum expired)
     * - canAcceptApplications(): Method custom untuk cek apakah masih terima aplikasi
     * 
     * @param Request $request HTTP request dengan optional parameter period
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        // Ambil parameter period dari URL query string
        $requestedPeriodId = $request->get('period');
        
        if ($requestedPeriodId) {
            // Jika ada parameter period, cari periode tersebut
            $selectedPeriod = Period::where('id', $requestedPeriodId)
                ->where('is_active', true)
                ->first();
                
            // Validasi: periode harus exist dan aktif
            if (!$selectedPeriod) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Periode yang diminta tidak tersedia atau sudah berakhir');
            }
            
            // Validasi: periode harus ongoing dan masih terima aplikasi
            if (!$selectedPeriod->is_ongoing || !$selectedPeriod->canAcceptApplications()) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Periode "' . $selectedPeriod->name . '" tidak sedang menerima pendaftaran');
            }
            
            $targetPeriod = $selectedPeriod;
        } else {
            // Jika tidak ada parameter, ambil periode aktif default
            $targetPeriod = Period::active()->first();
            
            // Validasi: harus ada periode aktif
            if (!$targetPeriod) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Tidak ada periode pendaftaran yang aktif');
            }
        }

        // Cek apakah siswa sudah punya aplikasi di periode ini
        $existingApplication = Application::where('user_id', Auth::id())
            ->where('period_id', $targetPeriod->id)
            ->first();

        // Jika sudah ada aplikasi, redirect ke edit (tidak boleh duplikat)
        if ($existingApplication) {
            return redirect()->route('student.application.edit', $existingApplication->id)
                ->with('info', 'Anda sudah memiliki aplikasi untuk periode "' . $targetPeriod->name . '". Silakan lengkapi data aplikasi Anda.');
        }

        // Tampilkan form create dengan data periode
        return view('student.application.create', compact('targetPeriod'));
    }

    /**
     * Menyimpan aplikasi baru (data pribadi awal)
     * 
     * Fungsi: Simpan data pribadi siswa dan buat record aplikasi baru (status: draft)
     * Route: POST /student/application
     * 
     * Flow:
     * 1. Validasi periode (sama seperti create)
     * 2. Cek duplikasi aplikasi
     * 3. Validasi input data pribadi
     * 4. Simpan ke database dengan status 'draft'
     * 5. Redirect ke halaman edit untuk lanjut isi kriteria
     * 
     * Data Pribadi yang Disimpan:
     * - full_name, nisn, school, class
     * - birth_date, birth_place, gender
     * - address, phone
     * 
     * Status: draft (belum submit, masih bisa diedit)
     * 
     * @param Request $request Data dari form
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Ambil period_id dari form hidden input
        $periodId = $request->input('period_id');
        
        if ($periodId) {
            // Cari periode berdasarkan ID
            $targetPeriod = Period::where('id', $periodId)
                ->where('is_active', true)
                ->first();
        } else {
            // Fallback ke periode aktif
            $targetPeriod = Period::active()->first();
        }
        
        // Validasi periode tersedia
        if (!$targetPeriod) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode pendaftaran tidak tersedia atau sudah berakhir');
        }

        // Validasi periode masih terima aplikasi
        if (!$targetPeriod->is_ongoing || !$targetPeriod->canAcceptApplications()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Periode "' . $targetPeriod->name . '" tidak sedang menerima pendaftaran');
        }

        // Cek duplikasi: siswa tidak boleh punya 2 aplikasi di periode yang sama
        $existingApplication = Application::where('user_id', Auth::id())
            ->where('period_id', $targetPeriod->id)
            ->first();
            
        if ($existingApplication) {
            return redirect()->route('student.application.edit', $existingApplication->id)
                ->with('info', 'Anda sudah memiliki aplikasi untuk periode ini. Silakan lengkapi data aplikasi Anda.');
        }

        // Validasi input data pribadi
        $request->validate([
            'full_name' => 'required|string|max:255',    // Nama lengkap
            'nisn' => 'required|string|max:20',          // NISN siswa
            'school' => 'required|string|max:255',       // Nama sekolah
            'class' => 'required|string|max:50',         // Kelas (contoh: "XII IPA 1")
            'birth_date' => 'required|date',             // Tanggal lahir
            'birth_place' => 'required|string|max:255',  // Tempat lahir
            'gender' => 'required|in:L,P',               // L = Laki-laki, P = Perempuan
            'address' => 'required|string',              // Alamat lengkap
            'phone' => 'required|string|max:20',         // Nomor telepon
        ]);

        // Buat record aplikasi baru dengan status 'draft'
        $application = Application::create([
            'user_id' => Auth::id(),                    // ID siswa yang login
            'period_id' => $targetPeriod->id,           // ID periode
            'full_name' => $request->full_name,
            'nisn' => $request->nisn,
            'school' => $request->school,
            'class' => $request->class,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'gender' => $request->gender,
            'address' => $request->address,
            'phone' => $request->phone,
            'status' => 'draft',                        // Status awal: draft (belum submit)
        ]);

        // Log untuk tracking
        Log::info('New application created', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'period_id' => $targetPeriod->id,
            'period_name' => $targetPeriod->name
        ]);

        // Redirect ke halaman edit untuk lanjut isi kriteria dan dokumen
        return redirect()->route('student.application.edit', $application->id)
            ->with('success', 'Data aplikasi untuk periode "' . $targetPeriod->name . '" berhasil disimpan. Silakan lengkapi data kriteria.');
    }

    /**
     * Menampilkan form edit aplikasi (form utama lengkap)
     * 
     * Fungsi: Menampilkan form lengkap untuk edit data pribadi, isi kriteria, dan upload dokumen
     * Route: GET /student/application/{application}/edit
     * 
     * Data yang Ditampilkan:
     * 1. Data pribadi siswa (nama, nisn, dll)
     * 2. Hierarki kriteria lengkap (Criteria > SubCriteria > SubSubCriteria)
     * 3. Nilai kriteria yang sudah disimpan (existing values)
     * 4. Dokumen yang sudah diupload
     * 
     * Authorization: Hanya siswa pemilik aplikasi yang bisa akses
     * 
     * @param Application $application Model aplikasi (route model binding)
     * @return \Illuminate\View\View
     */
    public function edit(Application $application)
    {
        // Authorization: cek apakah user yang login adalah pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Load semua kriteria aktif dengan hierarki lengkap
        // Eager loading untuk performa (menghindari N+1 query)
        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active()->with(['subSubCriterias' => function($subQuery) {
                    $subQuery->active()->orderBy('order');
                }])->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Ambil nilai-nilai kriteria yang sudah disimpan sebelumnya
        $existingValues = collect();
        $rawValues = ApplicationValue::where('application_id', $application->id)->get();
        
        Log::info('Loading application values for edit', [
            'application_id' => $application->id,
            'raw_values_count' => $rawValues->count()
        ]);
        
        // Konversi ke format yang mudah diakses di view
        // Format key: 'subsubcriteria_{id}' atau 'subcriteria_{id}'
        foreach ($rawValues as $value) {
            if ($value->criteria_type === 'subsubcriteria') {
                $key = 'subsubcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            } elseif ($value->criteria_type === 'subcriteria') {
                $key = 'subcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            }
        }

        // Ambil dokumen yang sudah diupload
        $documents = ApplicationDocument::where('application_id', $application->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Tampilkan view edit dengan semua data
        return view('student.application.edit', compact('application', 'criterias', 'existingValues', 'documents'));
    }

    /**
     * AJAX: Menyimpan satu kriteria (auto-save)
     * 
     * FUNGSI SAVE CRITERIA - FIXED UNTUK RADIO GROUP
     * 
     * Fungsi: 
     * - Menyimpan pilihan kriteria siswa secara real-time (AJAX)
     * - Handle radio button logic (hanya 1 pilihan per group)
     * - Menghapus pilihan lama dalam group yang sama sebelum simpan yang baru
     * 
     * Route: POST /student/application/{application}/save-criteria (AJAX)
     * 
     * Radio Group Logic:
     * - Untuk SubSubCriteria: 1 radio group = 1 SubCriteria parent
     *   Contoh: Untuk C1_1 (Pekerjaan Ayah), siswa hanya bisa pilih 1 dari:
     *   "Tidak Bekerja", "Buruh", "Pedagang", dll
     * 
     * - Untuk SubCriteria: 1 radio group = 1 Criteria parent
     *   Contoh: Untuk C1, siswa hanya bisa pilih 1 SubCriteria
     * 
     * Flow:
     * 1. Validasi authorization dan status draft
     * 2. Validasi input (criteria_type, criteria_id, value)
     * 3. Cari parent ID (untuk tentukan radio group)
     * 4. Hapus pilihan lama dalam radio group yang sama
     * 5. Simpan pilihan baru dengan score
     * 6. Return jumlah MAIN CRITERIA yang sudah terisi
     * 
     * @param Request $request Data AJAX (criteria_type, criteria_id, value)
     * @param Application $application Model aplikasi
     * @return \Illuminate\Http\JsonResponse Response JSON
     */
    public function saveCriteria(Request $request, Application $application)
    {
        Log::info('=== SAVE CRITERIA REQUEST ===', [
            'application_id' => $application->id,
            'request_data' => $request->all()
        ]);

        // Authorization: cek pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan'], 403);
        }

        // Validasi status: hanya aplikasi draft yang bisa diedit
        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
        }

        // Validasi input AJAX
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'criteria_type' => 'required|string|in:subcriteria,subsubcriteria', // Tipe kriteria
            'criteria_id' => 'required|integer|min:1',                           // ID kriteria
            'value' => 'required|string'                                         // Nilai/label pilihan
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Data tidak valid'
            ], 422);
        }

        try {
            // Ambil data dari request
            $criteriaType = $request->input('criteria_type');
            $criteriaId = (int) $request->input('criteria_id');
            $value = $request->input('value');

            // Gunakan transaction untuk data consistency
            DB::beginTransaction();

            try {
                // === LOGIC UNTUK SUBSUBCRITERIA ===
                if ($criteriaType === 'subsubcriteria') {
                    // Cari SubSubCriteria yang dipilih
                    $subSubCriteria = SubSubCriteria::find($criteriaId);
                    if (!$subSubCriteria) {
                        throw new \Exception('SubSubCriteria tidak ditemukan');
                    }
                    
                    // Cari parent SubCriteria untuk tentukan radio group
                    $parentSubCriteriaId = $subSubCriteria->sub_criteria_id;
                    $subCriteria = SubCriteria::find($parentSubCriteriaId);
                    $mainCriteriaId = $subCriteria->criteria_id;
                    
                    Log::info('SubSubCriteria selected', [
                        'subsubcriteria_id' => $criteriaId,
                        'parent_subcriteria_id' => $parentSubCriteriaId,
                        'main_criteria_id' => $mainCriteriaId
                    ]);
                    
                    // CRITICAL: Hapus hanya SubSubCriteria dalam SATU SubCriteria yang sama
                    // Ini memastikan radio group logic: hanya 1 pilihan per SubCriteria
                    $deletedSubSub = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subsubcriteria')
                        ->whereIn('criteria_id', function($query) use ($parentSubCriteriaId) {
                            // Subquery: ambil semua SubSubCriteria dalam parent yang sama
                            $query->select('id')
                                  ->from('sub_sub_criterias')
                                  ->where('sub_criteria_id', $parentSubCriteriaId);
                        })
                        ->delete();
                    
                    Log::info('Deleted old SubSubCriteria in same radio group', [
                        'parent_subcriteria' => $parentSubCriteriaId,
                        'count' => $deletedSubSub
                    ]);
                    
                // === LOGIC UNTUK SUBCRITERIA ===
                } elseif ($criteriaType === 'subcriteria') {
                    // Cari SubCriteria yang dipilih
                    $subCriteria = SubCriteria::find($criteriaId);
                    if (!$subCriteria) {
                        throw new \Exception('SubCriteria tidak ditemukan');
                    }
                    
                    // Cari parent Criteria untuk tentukan radio group
                    $mainCriteriaId = $subCriteria->criteria_id;
                    
                    Log::info('SubCriteria selected', [
                        'subcriteria_id' => $criteriaId,
                        'main_criteria_id' => $mainCriteriaId
                    ]);
                    
                    // CRITICAL: Hapus hanya SubCriteria dalam SATU Criteria yang sama
                    // Ambil semua SubCriteria dalam Criteria yang sama
                    $allSubCriteriaInSameMainCriteria = SubCriteria::where('criteria_id', $mainCriteriaId)
                        ->pluck('id')
                        ->toArray();
                    
                    $deletedSub = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->whereIn('criteria_id', $allSubCriteriaInSameMainCriteria)
                        ->delete();
                    
                    Log::info('Deleted old SubCriteria in same main criteria', [
                        'main_criteria_id' => $mainCriteriaId,
                        'subcriteria_ids_in_group' => $allSubCriteriaInSameMainCriteria,
                        'count' => $deletedSub
                    ]);
                }

                // Hitung score berdasarkan weight dari kriteria yang dipilih
                $score = $this->calculateScore($criteriaType, $criteriaId);

                // Simpan nilai BARU (hanya 1 karena sudah dihapus yang lama)
                $newValue = ApplicationValue::create([
                    'application_id' => $application->id,
                    'criteria_type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'value' => $value,
                    'score' => $score,
                ]);

                Log::info('New value saved', [
                    'id' => $newValue->id,
                    'type' => $criteriaType,
                    'criteria_id' => $criteriaId,
                    'score' => $score
                ]);

                DB::commit();

                // CRITICAL FIX: Hitung berapa CRITERIA UTAMA (C1, C2, C3...) yang sudah terisi
                // BUKAN jumlah SubCriteria!
                $totalSaved = $this->getCorrectSavedCount($application->id);

                Log::info('=== SAVE SUCCESS ===', ['total_saved' => $totalSaved]);

                return response()->json([
                    'success' => true,
                    'message' => 'Kriteria berhasil disimpan',
                    'total_criteria_saved' => $totalSaved
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('=== SAVE FAILED ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Menghitung jumlah MAIN CRITERIA yang sudah terisi
     * 
     * CRITICAL FIX: Hitung berapa MAIN CRITERIA (C1, C2, C3...) yang sudah terisi
     * BUKAN SubCriteria!
     * 
     * Logika:
     * 1. Ambil semua SubCriteria yang sudah dipilih (dari application_values)
     * 2. Ambil parent SubCriteria dari SubSubCriteria yang dipilih
     * 3. Gabungkan semua SubCriteria yang terisi
     * 4. Ambil MAIN CRITERIA ID (C1, C2, C3...) dari SubCriteria tersebut
     * 5. Hitung jumlah unique MAIN CRITERIA
     * 
     * Contoh:
     * - Jika siswa sudah isi C1_1 dan C1_2 = 1 MAIN CRITERIA (C1)
     * - Jika siswa sudah isi C1_1, C1_2, C2_1 = 2 MAIN CRITERIA (C1 dan C2)
     * 
     * @param int $applicationId ID aplikasi
     * @return int Jumlah MAIN CRITERIA yang sudah terisi
     */
    private function getCorrectSavedCount($applicationId)
    {
        // Ambil semua SubCriteria yang sudah dipilih langsung
        $savedSubCriteriaIds = ApplicationValue::where('application_id', $applicationId)
            ->where('criteria_type', 'subcriteria')
            ->pluck('criteria_id')
            ->toArray();
        
        // Ambil semua SubSubCriteria yang sudah dipilih
        $savedSubSubCriteriaIds = ApplicationValue::where('application_id', $applicationId)
            ->where('criteria_type', 'subsubcriteria')
            ->pluck('criteria_id')
            ->toArray();
        
        // Ambil parent SubCriteria ID dari SubSubCriteria yang dipilih
        $parentIdsFromSubSub = [];
        if (!empty($savedSubSubCriteriaIds)) {
            $parentIdsFromSubSub = SubSubCriteria::whereIn('id', $savedSubSubCriteriaIds)
                ->pluck('sub_criteria_id')
                ->toArray();
        }
        
        // Gabungkan semua SubCriteria yang terisi (dari pilihan langsung + dari subsubcriteria)
        $allFilledSubCriteriaIds = array_unique(array_merge($savedSubCriteriaIds, $parentIdsFromSubSub));
        
        // CRITICAL FIX: Ambil MAIN CRITERIA ID (C1, C2, C3...) dari SubCriteria
        $mainCriteriaIds = SubCriteria::whereIn('id', $allFilledSubCriteriaIds)
            ->pluck('criteria_id')
            ->unique()  // Hanya hitung unique criteria
            ->toArray();
        
        // HITUNG BERAPA MAIN CRITERIA YANG SUDAH TERISI
        $totalCount = count($mainCriteriaIds);
        
        Log::info('Counting saved MAIN CRITERIA', [
            'application_id' => $applicationId,
            'saved_subcriteria' => $savedSubCriteriaIds,
            'saved_subsubcriteria' => $savedSubSubCriteriaIds,
            'parents_from_subsub' => $parentIdsFromSubSub,
            'filled_subcriteria_ids' => $allFilledSubCriteriaIds,
            'main_criteria_ids' => $mainCriteriaIds,
            'total_MAIN_CRITERIA_filled' => $totalCount
        ]);
        
        return $totalCount;
    }

    /**
     * Helper: Menghitung score dari kriteria yang dipilih
     * 
     * Fungsi: Ambil weight dari SubCriteria atau SubSubCriteria yang dipilih
     * 
     * Logika Score:
     * - Untuk SubCriteria: 
     *   1. Jika punya weight sendiri, gunakan weight tersebut
     *   2. Jika tidak, ambil max weight dari SubSubCriteria-nya
     * 
     * - Untuk SubSubCriteria:
     *   Ambil weight langsung dari SubSubCriteria
     * 
     * Score ini akan digunakan dalam perhitungan AHP nantinya
     * 
     * @param string $criteriaType Tipe kriteria (subcriteria/subsubcriteria)
     * @param int $criteriaId ID kriteria
     * @return float Score/weight
     */
    private function calculateScore($criteriaType, $criteriaId)
    {
        try {
            $score = 0.0;

            if ($criteriaType === 'subcriteria') {
                $subCriteria = SubCriteria::find($criteriaId);
                
                if ($subCriteria) {
                    // Jika SubCriteria punya weight sendiri, gunakan itu
                    if ($subCriteria->weight !== null && $subCriteria->weight > 0) {
                        $score = (float) $subCriteria->weight;
                    } else {
                        // Jika tidak, ambil max weight dari SubSubCriteria-nya
                        if ($subCriteria->subSubCriterias && $subCriteria->subSubCriterias->count() > 0) {
                            $maxWeight = $subCriteria->subSubCriterias->max('weight');
                            $score = $maxWeight ? (float) $maxWeight : 0.0;
                        }
                    }
                }
                
            } elseif ($criteriaType === 'subsubcriteria') {
                $subSubCriteria = SubSubCriteria::find($criteriaId);
                
                // Untuk SubSubCriteria, ambil weight langsung
                if ($subSubCriteria && $subSubCriteria->weight !== null) {
                    $score = (float) $subSubCriteria->weight;
                }
            }
            
            return $score;
            
        } catch (\Exception $e) {
            Log::error('Error calculating score', [
                'type' => $criteriaType,
                'id' => $criteriaId,
                'error' => $e->getMessage()
            ]);
            
            return 0.0;
        }
    }

    /**
     * Update data pribadi aplikasi
     * 
     * Fungsi: Update data pribadi siswa (nama, nisn, alamat, dll)
     * Route: PUT/PATCH /student/application/{application}
     * 
     * Bisa dipanggil via:
     * - Form submit biasa
     * - AJAX request
     * 
     * Validasi:
     * - Hanya bisa update jika status masih 'draft'
     * - Semua field data pribadi wajib diisi
     * 
     * @param Request $request Data dari form
     * @param Application $application Model aplikasi
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Application $application)
    {
        // Authorization: cek pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Validasi status: hanya draft yang bisa diupdate
        if ($application->status !== 'draft') {
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi yang sudah disubmit tidak dapat diubah.');
        }

        // Validasi input data pribadi
        $request->validate([
            'full_name' => 'required|string|max:255',
            'nisn' => 'required|string|max:20',
            'school' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
        ]);

        try {
            // Gunakan transaction untuk data consistency
            DB::transaction(function () use ($request, $application) {
                // Update semua field data pribadi
                $application->update([
                    'full_name' => $request->full_name,
                    'nisn' => $request->nisn,
                    'school' => $request->school,
                    'class' => $request->class,
                    'birth_date' => $request->birth_date,
                    'birth_place' => $request->birth_place,
                    'gender' => $request->gender,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'updated_at' => now(),
                ]);

                Log::info('Personal data updated', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id()
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Update failed', ['error' => $e->getMessage()]);
            
            // Handle AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            // Handle form submit
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Terjadi kesalahan saat menyimpan data');
        }

        // Hitung jumlah criteria yang sudah terisi untuk progress bar
        $savedCount = $this->getCorrectSavedCount($application->id);
        
        // Return JSON untuk AJAX request
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pribadi berhasil diperbarui',
                'saved_criteria_count' => $savedCount
            ]);
        }

        // Redirect untuk form submit
        return redirect()->route('student.application.edit', $application->id)
            ->with('success', "Data pribadi berhasil diperbarui");
    }

    /**
     * Submit aplikasi untuk divalidasi
     * 
     * Fungsi: Submit aplikasi dari status 'draft' ke 'submitted'
     * Route: POST /student/application/{application}/submit
     * 
     * Validasi Sebelum Submit:
     * 1. Data pribadi harus lengkap (9 field)
     * 2. Semua MAIN CRITERIA harus terisi (C1, C2, C3, C4, C5)
     * 3. Semua dokumen wajib harus diupload (KTP, KK, Slip Gaji, Surat Keterangan)
     * 
     * Jika lolos validasi:
     * - Status berubah: draft → submitted
     * - submitted_at di-set ke waktu sekarang
     * - Aplikasi masuk antrian untuk divalidasi admin
     * 
     * Setelah submit:
     * - Aplikasi tidak bisa diedit lagi
     * - Menunggu validasi dari admin/validator
     * 
     * @param Request $request HTTP request (bisa AJAX atau form)
     * @param Application $application Model aplikasi
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request, Application $application)
    {
        Log::info('=== SUBMIT APPLICATION ===', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'status' => $application->status
        ]);

        // Authorization: cek pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Validasi status: hanya draft yang bisa disubmit
        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi sudah disubmit');
        }

        try {
            $validationErrors = [];

            // === VALIDASI 1: CEK DATA PRIBADI LENGKAP ===
            $personalFields = ['full_name', 'nisn', 'school', 'class', 'birth_date', 'birth_place', 'gender', 'address', 'phone'];
            foreach ($personalFields as $field) {
                if (!$application->$field || trim($application->$field) === '') {
                    $validationErrors[] = "Data pribadi belum lengkap: $field";
                }
            }

            // === VALIDASI 2: CEK SEMUA MAIN CRITERIA TERISI ===
            // CRITICAL FIX: Check MAIN CRITERIA count (5: C1-C5), bukan SubCriteria (22)
            $expectedCriteriaCount = Criteria::where('is_active', true)->count();
            $savedCriteriaCount = $this->getCorrectSavedCount($application->id);

            Log::info('Criteria validation', [
                'expected_MAIN_CRITERIA' => $expectedCriteriaCount,
                'saved_MAIN_CRITERIA' => $savedCriteriaCount
            ]);

            // Jika ada criteria yang belum diisi
            if ($savedCriteriaCount < $expectedCriteriaCount) {
                $validationErrors[] = "Kriteria belum lengkap ($savedCriteriaCount dari $expectedCriteriaCount)";
            }

            // === VALIDASI 3: CEK SEMUA DOKUMEN WAJIB DIUPLOAD ===
            $requiredDocs = ['ktp', 'kk', 'slip_gaji', 'surat_keterangan'];
            $existingDocs = ApplicationDocument::where('application_id', $application->id)
                ->pluck('document_type')
                ->toArray();

            // Cari dokumen yang belum diupload
            $missingDocs = array_diff($requiredDocs, $existingDocs);
            if (!empty($missingDocs)) {
                $validationErrors[] = 'Dokumen belum lengkap: ' . implode(', ', $missingDocs);
            }

            // Jika ada error validasi, return dengan pesan error
            if (!empty($validationErrors)) {
                Log::warning('Submit validation failed', ['errors' => $validationErrors]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validationErrors,
                        'detailed_message' => implode('; ', $validationErrors)
                    ], 422);
                }
                return redirect()->route('student.application.edit', $application->id)
                    ->with('error', implode('; ', $validationErrors));
            }

            // === SUBMIT APLIKASI ===
            DB::transaction(function() use ($application) {
                $application->update([
                    'status' => 'submitted',      // Ubah status menjadi submitted
                    'submitted_at' => now(),      // Catat waktu submit
                ]);
            });

            Log::info('APPLICATION SUBMITTED', [
                'application_id' => $application->id,
                'user_id' => $application->user_id
            ]);

            // Return response sukses
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aplikasi berhasil disubmit',
                    'redirect_url' => route('student.dashboard')
                ]);
            }

            return redirect()->route('student.dashboard')
                ->with('success', 'Aplikasi berhasil disubmit');
                
        } catch (\Exception $e) {
            Log::error('Submit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal submit: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal submit aplikasi');
        }
    }

    /**
     * AJAX: Upload dokumen persyaratan
     * 
     * Fungsi: Upload dokumen wajib (KTP, KK, Slip Gaji, Surat Keterangan)
     * Route: POST /student/application/{application}/upload-document (AJAX)
     * 
     * Dokumen Wajib:
     * - ktp: KTP Orang Tua
     * - kk: Kartu Keluarga
     * - slip_gaji: Slip Gaji Orang Tua
     * - surat_keterangan: Surat Keterangan Tidak Mampu
     * 
     * Validasi Upload:
     * - File type: PDF, JPG, JPEG, PNG
     * - Max size: 2MB per file
     * - 1 dokumen per type (jika upload ulang, yang lama dihapus)
     * 
     * Storage:
     * - Lokasi: storage/app/public/documents/{application_id}/
     * - Naming: {document_type}_{timestamp}_{uniqid}.{ext}
     * 
     * @param Request $request Data upload (file, document_type, document_name)
     * @param Application $application Model aplikasi
     * @return \Illuminate\Http\JsonResponse Response JSON dengan info file
     */
    public function uploadDocument(Request $request, Application $application)
    {
        // Authorization: cek pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Validasi status: hanya draft yang bisa upload
        if ($application->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
        }

        // Validasi input upload
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'document_type' => 'required|string|in:ktp,kk,slip_gaji,surat_keterangan', // Jenis dokumen
            'document_name' => 'required|string|max:255',                               // Nama dokumen
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',                 // File (max 2MB)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => implode(', ', $validator->errors()->all())
            ], 422);
        }

        try {
            // Cek apakah sudah ada dokumen dengan type yang sama
            $existingDoc = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type', $request->document_type)
                ->first();

            // Jika ada, hapus file lama dan record-nya
            if ($existingDoc) {
                if (Storage::disk('public')->exists($existingDoc->file_path)) {
                    Storage::disk('public')->delete($existingDoc->file_path);
                }
                $existingDoc->delete();
            }

            // Upload file baru
            $file = $request->file('file');
            $directory = 'documents/' . $application->id;
            Storage::disk('public')->makeDirectory($directory);
            
            // Generate filename unik
            $extension = $file->getClientOriginalExtension();
            $filename = $request->document_type . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Simpan file ke storage
            $path = $file->storeAs($directory, $filename, 'public');

            // Validasi file tersimpan
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('File gagal disimpan');
            }

            // Simpan record ke database
            $document = ApplicationDocument::create([
                'application_id' => $application->id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
            ]);

            // Return info dokumen untuk update UI
            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'document' => $document,
                'document_type_display' => $this->getDocumentTypeDisplay($document->document_type),
                'file_size_display' => number_format($document->file_size / 1024, 1) . ' KB',
                'created_at_display' => $document->created_at->format('d/m/Y H:i'),
                'view_url' => Storage::url($document->file_path),
                'delete_url' => route('student.application.document.delete', [$application->id, $document->id])
            ]);
                
        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Hapus dokumen
     * 
     * Fungsi: Menghapus dokumen yang sudah diupload
     * Route: DELETE /student/application/{application}/document/{document} (AJAX)
     * 
     * Proses:
     * 1. Hapus file fisik dari storage
     * 2. Hapus record dari database
     * 
     * Validasi:
     * - Hanya pemilik aplikasi yang bisa hapus
     * - Hanya aplikasi draft yang bisa diedit
     * 
     * @param Request $request HTTP request
     * @param Application $application Model aplikasi
     * @param ApplicationDocument $document Model dokumen yang akan dihapus
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteDocument(Request $request, Application $application, ApplicationDocument $document)
    {
        // Authorization: cek pemilik aplikasi dan dokumen
        if ($application->user_id !== Auth::id() || $document->application_id !== $application->id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Validasi status: hanya draft yang bisa diedit
        if ($application->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aplikasi sudah disubmit'], 422);
            }
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Aplikasi sudah disubmit');
        }

        try {
            // Hapus file fisik dari storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            
            // Hapus record dari database
            $document->delete();

            // Return response sukses
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
            }

            return redirect()->route('student.application.edit', $application->id)
                ->with('success', 'Dokumen berhasil dihapus');
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus'], 500);
            }
            
            return redirect()->route('student.application.edit', $application->id)
                ->with('error', 'Gagal menghapus dokumen');
        }
    }

    /**
     * Helper: Konversi document type ke display name
     * 
     * @param string $type Document type code
     * @return string Display name
     */
    private function getDocumentTypeDisplay($type)
    {
        $types = [
            'ktp' => 'KTP',
            'kk' => 'KK',
            'slip_gaji' => 'Slip Gaji',
            'surat_keterangan' => 'Surat Keterangan'
        ];
        
        return $types[$type] ?? $type;
    }

    /**
     * Menampilkan detail aplikasi (read-only)
     * 
     * Fungsi: Menampilkan halaman view aplikasi yang sudah disubmit (tidak bisa edit)
     * Route: GET /student/application/{application}
     * 
     * Perbedaan dengan edit():
     * - show(): Read-only, untuk aplikasi yang sudah submitted/validated
     * - edit(): Editable, untuk aplikasi yang masih draft
     * 
     * Ditampilkan:
     * - Data pribadi siswa
     * - Semua kriteria yang dipilih
     * - Dokumen yang diupload
     * - Status validasi (jika sudah divalidasi)
     * 
     * @param Application $application Model aplikasi
     * @return \Illuminate\View\View
     */
    public function show(Application $application)
    {
        // Authorization: cek pemilik aplikasi
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to application.');
        }

        // Load relasi yang diperlukan
        $application->load(['period', 'validation']);

        // Ambil semua criteria dengan strukturnya (sama seperti edit)
        $criterias = Criteria::active()
            ->with(['subCriterias' => function($query) {
                $query->active()->with(['subSubCriterias' => function($subQuery) {
                    $subQuery->active()->orderBy('order');
                }])->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Ambil nilai-nilai yang sudah disimpan
        $existingValues = collect();
        $rawValues = ApplicationValue::where('application_id', $application->id)->get();
        
        foreach ($rawValues as $value) {
            if ($value->criteria_type === 'subsubcriteria') {
                $key = 'subsubcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            } elseif ($value->criteria_type === 'subcriteria') {
                $key = 'subcriteria_' . $value->criteria_id;
                $existingValues[$key] = $value;
            }
        }

        // Ambil dokumen
        $documents = ApplicationDocument::where('application_id', $application->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Return view khusus untuk show (read-only)
        return view('student.application.show', compact(
            'application', 
            'criterias', 
            'existingValues', 
            'documents'
        ));
    }
}