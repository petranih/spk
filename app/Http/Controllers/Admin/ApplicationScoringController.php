<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationValue;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use App\Models\Ranking;
use App\Models\Period;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk mengelola perhitungan skor aplikasi bantuan siswa
 * Menggunakan sistem kriteria bertingkat (Criteria -> SubCriteria -> SubSubCriteria)
 */
class ApplicationScoringController extends Controller
{
    /**
     * Mapping field database aplikasi ke kode kriteria
     * 
     * FIXED: Field mapping untuk C5 dengan 2 subkriteria
     * 
     * Struktur:
     * - C1: Kondisi Ekonomi (7 subkriteria)
     * - C2: Kondisi Rumah (7 subkriteria)
     * - C3: Kepemilikan Aset (4 subkriteria)
     * - C4: Fasilitas Rumah (3 subkriteria)
     * - C5: Status Penerimaan Bantuan (2 subkriteria)
     * 
     * @return array Mapping kode kriteria ke nama field di tabel applications
     */
    private function getFieldMapping()
    {
        return [
            // Kondisi Ekonomi (C1)
            'C1_1' => 'father_job',           // Pekerjaan ayah
            'C1_2' => 'mother_job',           // Pekerjaan ibu
            'C1_3' => 'father_income',        // Pendapatan ayah
            'C1_4' => 'mother_income',        // Pendapatan ibu
            'C1_5' => 'family_dependents',    // Jumlah tanggungan keluarga
            'C1_6' => 'has_debt',             // Status memiliki hutang
            'C1_7' => 'parent_last_education', // Pendidikan terakhir orang tua
            
            // Kondisi Rumah (C2)
            'C2_1' => 'wall_type',            // Jenis dinding rumah
            'C2_2' => 'floor_type',           // Jenis lantai rumah
            'C2_3' => 'roof_type',            // Jenis atap rumah
            'C2_4' => 'house_status',         // Status kepemilikan rumah
            'C2_5' => 'house_area',           // Luas rumah
            'C2_6' => 'bedroom_count',        // Jumlah kamar tidur
            'C2_7' => 'people_per_bedroom',   // Jumlah orang per kamar
            
            // Kepemilikan Aset (C3)
            'C3_1' => 'motorcycle',           // Kepemilikan sepeda motor
            'C3_2' => 'car',                  // Kepemilikan mobil
            'C3_3' => 'land',                 // Kepemilikan tanah
            'C3_4' => 'electronics',          // Kepemilikan elektronik
            
            // Fasilitas Rumah (C4)
            'C4_1' => 'electricity',          // Fasilitas listrik
            'C4_2' => 'water',                // Sumber air
            'C4_3' => 'cooking_fuel',         // Bahan bakar memasak
            
            // Status Penerimaan Bantuan (C5) - FIXED: 2 fields
            'C5_1' => 'has_received_aid',     // Pernah menerima bantuan
            'C5_2' => 'is_receiving_aid',     // Sedang menerima bantuan
        ];
    }

    /**
     * Menghitung skor total aplikasi berdasarkan kriteria bertingkat
     * 
     * FIXED: Perhitungan skor sesuai Excel
     * Formula: Σ(SubSubCriteria_Weight × SubCriteria_Weight) × Criteria_Weight
     * 
     * Alur perhitungan:
     * 1. Loop setiap Criteria (C1, C2, C3, C4, C5)
     * 2. Loop setiap SubCriteria dalam Criteria tersebut
     * 3. Cari SubSubCriteria yang dipilih user (atau gunakan score dari ApplicationValue untuk SubCriteria langsung)
     * 4. Hitung: SubSubCriteria_Weight × SubCriteria_Weight = Contribution
     * 5. Total per Criteria = Σ Contribution
     * 6. Total Final = Σ(Criteria_Score × Criteria_Weight)
     * 
     * @param Application $application Aplikasi yang akan dihitung skornya
     * @return float Total skor akhir
     */
    private function calculateApplicationScore(Application $application)
    {
        // Inisialisasi variabel untuk menyimpan skor
        $totalScore = 0;          // Total skor akhir
        $criteriaScores = [];     // Skor per kriteria (C1, C2, dst)

        // Ambil semua kriteria yang aktif beserta relasi subkriteria dan subsubkriteria
        $criterias = Criteria::where('is_active', true)
            ->with(['subCriterias' => function($q) {
                $q->where('is_active', true)->orderBy('order');
            }, 'subCriterias.subSubCriterias' => function($q) {
                $q->where('is_active', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Log untuk tracking perhitungan
        \Log::info("=== SCORING START: {$application->full_name} (ID: {$application->id}) ===");

        // Loop setiap kriteria utama (C1, C2, C3, C4, C5)
        foreach ($criterias as $criteria) {
            $criteriaScore = 0; // Reset skor untuk kriteria ini
            \Log::info("CRITERIA: {$criteria->code} - {$criteria->name} (Weight: {$criteria->weight})");

            // Loop setiap subkriteria dalam kriteria ini
            foreach ($criteria->subCriterias as $subCriteria) {
                $subCriteriaScore = 0; // Skor yang akan digunakan untuk subkriteria ini
                $contribution = 0; // Kontribusi subkriteria ke total kriteria
                
                // Cek apakah subkriteria ini memiliki subsubkriteria (pilihan jawaban)
                if ($subCriteria->subSubCriterias->count() > 0) {
                    // ===== CASE 1: Ada SubSubCriteria =====
                    // Cari yang dipilih user
                    $selectedSubSub = $this->findSelectedSubSubCriteria($application, $subCriteria);
                    
                    if ($selectedSubSub) {
                        // Jika ditemukan pilihan user, gunakan weight dari SubSubCriteria tersebut
                        $subCriteriaScore = $selectedSubSub->weight;
                        \Log::info("  ✓ {$subCriteria->code}: {$selectedSubSub->name} = {$selectedSubSub->weight}");
                    } else {
                        // Tidak ada pilihan, gunakan weight terendah (asumsi kondisi terburuk)
                        $subCriteriaScore = $subCriteria->subSubCriterias->min('weight') ?: 0;
                        \Log::warning("  ⚠ {$subCriteria->code}: NO SELECTION, using min = {$subCriteriaScore}");
                    }
                    
                    // Formula untuk SubSubCriteria: SubSubWeight × SubWeight
                    $contribution = $subCriteriaScore * $subCriteria->weight;
                    \Log::info("    → {$subCriteriaScore} × {$subCriteria->weight} = {$contribution}");
                    
                } else {
                    // ===== CASE 2: Direct SubCriteria (tanpa SubSubCriteria) =====
                    // Contoh: C5_1 (Menerima Bantuan), C5_2 (Tidak)
                    // Cek apakah ada nilai yang tersimpan di application_values
                    $appValue = ApplicationValue::where('application_id', $application->id)
                        ->where('criteria_type', 'subcriteria')
                        ->where('criteria_id', $subCriteria->id)
                        ->first();
                    
                    if ($appValue) {
                        // ✅ PENTING: Untuk Direct SubCriteria, JANGAN kalikan dengan weight SubCriteria lagi!
                        // Score dari ApplicationValue SUDAH FINAL (misal: 0.75 untuk "Menerima Bantuan")
                        // Langsung gunakan sebagai kontribusi
                        $contribution = $appValue->score;
                        \Log::info("  ✓ {$subCriteria->code}: {$appValue->value} = {$contribution} (direct)");
                    } else {
                        // Tidak ada data, kontribusi = 0
                        $contribution = 0;
                        \Log::warning("  ✗ {$subCriteria->code}: NO DATA");
                    }
                }
                
                // Akumulasi kontribusi ke skor kriteria
                $criteriaScore += $contribution;
            }

            // Simpan skor untuk kriteria ini (sebelum dikalikan dengan criteria weight)
            $criteriaScores[$criteria->code] = $criteriaScore;
            
            // CRITICAL: Kalikan dengan Criteria weight untuk mendapat kontribusi final
            // Formula: Criteria_Score × Criteria_Weight
            $finalContribution = $criteriaScore * $criteria->weight;
            $totalScore += $finalContribution; // Akumulasi ke skor total akhir
            
            \Log::info("  SUBTOTAL {$criteria->code}: {$criteriaScore} × {$criteria->weight} = {$finalContribution}\n");
        }

        // Log skor akhir
        \Log::info("=== FINAL SCORE: {$totalScore} ===\n");

        // Update skor di tabel applications
        $application->update(['final_score' => $totalScore]);
        
        // Simpan detail scoring ke tabel rankings
        $this->saveApplicationScoring($application, $totalScore, $criteriaScores);

        return $totalScore;
    }

    /**
     * Mencari SubSubCriteria yang dipilih oleh user untuk SubCriteria tertentu
     * 
     * FIXED: Cari SubSubCriteria yang dipilih user
     * 
     * Prioritas pencarian:
     * 1. Cek dari tabel application_values (data yang tersimpan saat submit form)
     * 2. Fallback: Cek dari field di tabel applications (data langsung)
     * 
     * @param Application $application Aplikasi yang sedang diproses
     * @param SubCriteria $subCriteria SubCriteria yang akan dicari pilihannya
     * @return SubSubCriteria|null SubSubCriteria yang dipilih, atau null jika tidak ditemukan
     */
    private function findSelectedSubSubCriteria(Application $application, SubCriteria $subCriteria)
    {
        // PRIORITY 1: Cek dari application_values (lebih akurat)
        foreach ($subCriteria->subSubCriterias as $subSub) {
            $appValue = ApplicationValue::where('application_id', $application->id)
                ->where('criteria_type', 'subsubcriteria')
                ->where('criteria_id', $subSub->id)
                ->first();
            
            // Jika ditemukan record di application_values, return SubSubCriteria ini
            if ($appValue) {
                return $subSub;
            }
        }
        
        // PRIORITY 2: Fallback - Cari dari tabel applications berdasarkan field mapping
        $fieldMapping = $this->getFieldMapping();
        
        // Cek apakah subkriteria ini memiliki mapping field
        if ($subCriteria->code && isset($fieldMapping[$subCriteria->code])) {
            $field = $fieldMapping[$subCriteria->code]; // Nama field di tabel applications
            
            // Cek apakah field tersebut ada dan memiliki nilai
            if (property_exists($application, $field) && $application->$field !== null && $application->$field !== '') {
                // Normalisasi nilai user (convert Ya/Tidak, dll)
                $userValue = $this->normalizeValue($application->$field);
                
                // Cari SubSubCriteria yang match dengan nilai user
                foreach ($subCriteria->subSubCriterias as $subSub) {
                    if ($this->isMatch($userValue, $subSub->name)) {
                        return $subSub;
                    }
                }
            }
        }
        
        // Tidak ditemukan match
        return null;
    }

    /**
     * Logika matching antara nilai user dengan nama SubSubCriteria
     * 
     * Metode matching:
     * 1. Exact match (case-insensitive)
     * 2. Partial match (minimal 5 karakter)
     * 3. Number match (untuk range angka seperti "< 30 m²")
     * 
     * @param string $userValue Nilai dari user (sudah dinormalisasi)
     * @param string $subSubName Nama SubSubCriteria yang akan dicocokkan
     * @return bool True jika match, False jika tidak
     */
    private function isMatch($userValue, $subSubName)
    {
        // Bersihkan dan lowercase untuk perbandingan
        $userClean = strtolower(trim($userValue));
        $subSubClean = strtolower(trim($subSubName));
        
        // 1. Exact match - perbandingan langsung
        if ($userClean === $subSubClean) {
            return true;
        }
        
        // 2. Partial match - cek apakah salah satu string mengandung yang lain
        // Minimal 5 karakter untuk menghindari false positive
        if (strlen($subSubClean) >= 5 && str_contains($userClean, $subSubClean)) {
            return true;
        }
        
        if (strlen($userClean) >= 5 && str_contains($subSubClean, $userClean)) {
            return true;
        }
        
        // 3. Number match - cocokkan angka dalam string (untuk range)
        // Contoh: "< 30 m²" vs "kurang dari 30"
        if (preg_match('/\d+/', $userClean, $userMatches) && preg_match('/\d+/', $subSubClean, $subMatches)) {
            if ($userMatches[0] === $subMatches[0]) {
                return true;
            }
        }
        
        // Tidak ada yang match
        return false;
    }

    /**
     * Normalisasi nilai user ke format standar
     * 
     * Konversi berbagai variasi input menjadi format standar:
     * - Boolean true -> "Ya"
     * - Boolean false -> "Tidak"
     * - Variasi string "ya" (yes, 1, true, pernah, dll) -> "Ya"
     * - Variasi string "tidak" (no, 0, false, belum, dll) -> "Tidak"
     * 
     * @param mixed $value Nilai yang akan dinormalisasi
     * @return mixed Nilai yang sudah dinormalisasi
     */
    private function normalizeValue($value)
    {
        // Handle boolean value
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        // Handle string value
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            
            // Daftar variasi "Ya"
            if (in_array($lower, ['ya', 'yes', '1', 'true', 'pernah', 'sudah', 'iya', 'menerima', 'menerima bantuan'])) {
                return 'Ya';
            }
            
            // Daftar variasi "Tidak"
            if (in_array($lower, ['tidak', 'no', '0', 'false', 'belum', 'tidak ada', 'none', 'tidak menerima'])) {
                return 'Tidak';
            }
        }
        
        // Return nilai asli jika tidak perlu dinormalisasi
        return $value;
    }

    // ============ PUBLIC ROUTES ============
    
    /**
     * Menampilkan halaman utama scoring dengan daftar periode
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil semua periode, urutkan dari yang terbaru
        $periods = Period::orderBy('created_at', 'desc')->get();
        
        // Ambil periode yang sedang aktif
        $activePeriod = Period::where('is_active', true)->first();
        
        return view('admin.scoring.index', compact('periods', 'activePeriod'));
    }

    /**
     * Menampilkan daftar aplikasi yang sudah divalidasi dalam periode tertentu
     * 
     * @param Period $period Periode yang dipilih
     * @return \Illuminate\View\View
     */
    public function showApplications(Period $period)
    {
        // Ambil aplikasi dengan status 'validated' saja
        $applications = Application::where('period_id', $period->id)
            ->where('status', 'validated')
            ->with(['student', 'applicationValues']) // Eager load relasi
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.scoring.applications', compact('period', 'applications'));
    }

    /**
     * Menampilkan detail perhitungan skor untuk satu aplikasi
     * 
     * @param Application $application Aplikasi yang akan ditampilkan detailnya
     * @return \Illuminate\View\View
     */
    public function showCalculationDetail(Application $application)
    {
        // Ambil semua kriteria beserta relasi lengkap
        $criterias = Criteria::where('is_active', true)
            ->with(['subCriterias.subSubCriterias'])
            ->orderBy('order')
            ->get();

        // Ambil nilai-nilai yang tersimpan untuk aplikasi ini
        $applicationValues = ApplicationValue::where('application_id', $application->id)->get();
        
        // Ambil data ranking aplikasi ini
        $ranking = Ranking::where('application_id', $application->id)->first();

        return view('admin.scoring.detail', compact(
            'application', 
            'criterias', 
            'applicationValues', 
            'ranking'
        ));
    }

    /**
     * Menghitung skor untuk satu aplikasi saja
     * 
     * @param Application $application Aplikasi yang akan dihitung
     * @return \Illuminate\Http\RedirectResponse
     */
    public function calculateSingleScore(Application $application)
    {
        try {
            // Hitung skor aplikasi
            $totalScore = $this->calculateApplicationScore($application);
            
            // Update ranking dalam periode ini
            $this->updateSingleRanking($application->period_id, $application->id);
            
            return redirect()->back()->with('success', 
                "Perhitungan berhasil untuk {$application->full_name}. Skor: " . number_format($totalScore, 6)
            );
        } catch (\Exception $e) {
            \Log::error('Calculate single score error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Menghitung skor untuk semua aplikasi dalam periode tertentu
     * 
     * @param Period $period Periode yang aplikasinya akan dihitung
     * @return \Illuminate\Http\RedirectResponse
     */
    public function calculateAllScores(Period $period)
    {
        try {
            // Ambil semua aplikasi yang sudah divalidasi
            $applications = Application::where('period_id', $period->id)
                ->where('status', 'validated')
                ->get();

            // Loop dan hitung skor setiap aplikasi
            foreach ($applications as $application) {
                $this->calculateApplicationScore($application);
            }

            // Update ranking untuk semua aplikasi dalam periode ini
            $this->updateRanking($period->id);
            
            // Hitung jumlah ranking yang terbuat
            $count = Ranking::where('period_id', $period->id)->count();

            return redirect()->back()->with('success', 
                "Berhasil menghitung {$applications->count()} aplikasi. {$count} ranking dibuat."
            );
        } catch (\Exception $e) {
            \Log::error('Calculate all scores error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Export hasil ranking ke PDF format landscape
     * 
     * @param Period $period Periode yang akan di-export
     * @return \Illuminate\Http\Response PDF download
     */
    public function exportPdf(Period $period)
    {
        try {
            // Ambil data ranking yang sudah ada rank-nya, urutkan berdasarkan rank
            $rankings = Ranking::where('period_id', $period->id)
                ->with('application.student')
                ->whereNotNull('rank')
                ->orderBy('rank', 'asc')
                ->get();

            // Validasi: cek apakah ada data
            if ($rankings->isEmpty()) {
                return redirect()->back()->with('error', 'Belum ada data ranking.');
            }

            // Transform data untuk PDF
            $dataSiswa = $rankings->map(function($ranking) {
                // Parse JSON criteria_scores jika masih string
                $criteriaScores = is_string($ranking->criteria_scores) 
                    ? json_decode($ranking->criteria_scores, true) 
                    : ($ranking->criteria_scores ?? []);

                return (object)[
                    'rank' => $ranking->rank ?? 0,
                    'nisn' => $ranking->application->nisn ?? '-',
                    'nama' => $ranking->application->full_name ?? '-',
                    'c1_score' => $criteriaScores['C1'] ?? 0,  // Skor Kondisi Ekonomi
                    'c2_score' => $criteriaScores['C2'] ?? 0,  // Skor Kondisi Rumah
                    'c3_score' => $criteriaScores['C3'] ?? 0,  // Skor Kepemilikan Aset
                    'c4_score' => $criteriaScores['C4'] ?? 0,  // Skor Fasilitas Rumah
                    'c5_score' => $criteriaScores['C5'] ?? 0,  // Skor Status Bantuan
                    'total_score' => $ranking->total_score ?? 0, // Total skor akhir
                ];
            });

            // Siapkan data untuk view PDF
            $data = [
                'kelasName' => $period->name,
                'startDate' => $period->start_date->format('d F Y'),
                'endDate' => $period->end_date->format('d F Y'),
                'dataSiswa' => $dataSiswa
            ];

            // Generate PDF
            $pdf = Pdf::loadView('admin.scoring.pdf', $data);
            $pdf->setPaper('A4', 'landscape'); // Kertas A4 landscape
            
            // Generate filename dengan format: Ranking_NamaPeriode_Tanggal.pdf
            $filename = 'Ranking_' . str_replace(' ', '_', $period->name) . '_' . date('Ymd') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Export PDF error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan hasil perhitungan skor ke tabel rankings
     * 
     * @param Application $application Aplikasi yang skornya disimpan
     * @param float $totalScore Total skor akhir
     * @param array $criteriaScores Array skor per kriteria
     * @return void
     */
    private function saveApplicationScoring($application, $totalScore, $criteriaScores)
    {
        // Update or create record di tabel rankings
        Ranking::updateOrCreate([
            'period_id' => $application->period_id,
            'application_id' => $application->id,
        ], [
            'total_score' => $totalScore,
            'criteria_scores' => json_encode($criteriaScores, JSON_UNESCAPED_UNICODE), // Simpan detail skor per kriteria
            'calculated_at' => now(), // Timestamp perhitungan
        ]);
    }

    /**
     * Update ranking untuk satu aplikasi
     * Sebenarnya memanggil updateRanking untuk seluruh periode
     * 
     * @param int $periodId ID periode
     * @param int $applicationId ID aplikasi
     * @return void
     */
    private function updateSingleRanking($periodId, $applicationId)
    {
        // Call updateRanking untuk recalculate semua ranking dalam periode
        $this->updateRanking($periodId);
    }

    /**
     * Update ranking untuk semua aplikasi dalam periode tertentu
     * Mengurutkan berdasarkan total_score descending
     * 
     * @param int $periodId ID periode
     * @return void
     */
    private function updateRanking($periodId)
    {
        // Ambil semua ranking dalam periode, urutkan dari skor tertinggi
        $rankings = Ranking::where('period_id', $periodId)
            ->orderBy('total_score', 'desc')
            ->get();

        // Assign rank berdasarkan urutan (index + 1)
        foreach ($rankings as $index => $ranking) {
            // Update rank di tabel rankings
            $ranking->update(['rank' => $index + 1]);
            
            // Update rank di tabel applications juga
            if ($ranking->application) {
                $ranking->application->update(['rank' => $index + 1]);
            }
        }
    }
}