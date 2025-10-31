<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Period;
use App\Models\Application;
use App\Models\Ranking;
use App\Http\Controllers\AHPController;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk mengelola Laporan dan Hasil Penilaian
 * 
 * Fungsi Utama:
 * - Menampilkan laporan hasil penilaian per periode
 * - Menghitung skor dan ranking aplikasi menggunakan AHP
 * - Export laporan ke PDF dan Excel
 * - Menampilkan statistik aplikasi per periode
 * 
 * Alur Penggunaan:
 * 1. Admin memilih periode yang akan dilaporkan
 * 2. Sistem menampilkan statistik dan ranking
 * 3. Admin dapat menjalankan ulang perhitungan AHP
 * 4. Admin dapat export hasil ke PDF/Excel untuk dokumentasi
 * 
 * Tipe Laporan:
 * - Ranking siswa berdasarkan skor AHP
 * - Statistik status aplikasi (validated, rejected, pending)
 * - Detail skor per kriteria
 */
class ReportController extends Controller
{
    /**
     * Menampilkan halaman utama laporan dengan daftar periode
     * 
     * Fungsi: Menampilkan halaman index yang berisi daftar semua periode
     *         beserta jumlah aplikasi dan ranking untuk setiap periode
     * Route: GET /admin/report
     * 
     * Data yang ditampilkan:
     * - Daftar periode (terbaru di atas)
     * - Jumlah aplikasi per periode
     * - Jumlah ranking yang sudah dihitung
     * 
     * @return \Illuminate\View\View Halaman index laporan
     */
    public function index()
    {
        // Ambil semua periode dengan eager loading relasi applications dan rankings
        // Eager loading untuk menghindari N+1 query problem
        // latest() = urutkan dari yang terbaru berdasarkan created_at
        $periods = Period::with(['applications', 'rankings'])
            ->latest()
            ->get();

        return view('admin.report.index', compact('periods'));
    }

    /**
     * Menampilkan detail laporan untuk satu periode tertentu
     * 
     * Fungsi: Menampilkan halaman detail yang berisi:
     *         - Ranking siswa yang sudah dihitung
     *         - Statistik aplikasi (total, validated, rejected, pending)
     *         - Pagination untuk ranking (20 per halaman)
     * Route: GET /admin/report/{period}
     * 
     * Statistik yang ditampilkan:
     * - Total aplikasi: Semua aplikasi yang masuk
     * - Validated: Aplikasi yang sudah divalidasi dan masuk perhitungan
     * - Rejected: Aplikasi yang ditolak
     * - Pending: Aplikasi yang masih menunggu validasi
     * 
     * @param Period $period Model Period yang akan ditampilkan (route model binding)
     * @return \Illuminate\View\View Halaman detail laporan
     */
    public function show(Period $period)
    {
        // Ambil ranking untuk periode ini, urutkan berdasarkan rank (terbaik di atas)
        // Dengan eager loading user untuk menampilkan data siswa
        // Pagination 20 data per halaman untuk performa
        $rankings = Ranking::where('period_id', $period->id)
            ->with(['application.user'])
            ->orderBy('rank')
            ->paginate(20);

        // Ambil semua aplikasi di periode ini untuk hitung statistik
        // Eager load user dan validation untuk detail data
        $applications = Application::where('period_id', $period->id)
            ->with(['user', 'validation'])
            ->get();

        // Hitung statistik aplikasi berdasarkan status
        $stats = [
            // Total semua aplikasi yang masuk
            'total_applications' => $applications->count(),
            
            // Aplikasi yang sudah divalidasi (masuk ke perhitungan AHP)
            'validated_applications' => $applications->where('status', 'validated')->count(),
            
            // Aplikasi yang ditolak oleh validator
            'rejected_applications' => $applications->where('status', 'rejected')->count(),
            
            // Aplikasi yang masih menunggu validasi
            'pending_applications' => $applications->where('status', 'submitted')->count(),
        ];

        return view('admin.report.show', compact('period', 'rankings', 'stats'));
    }

    /**
     * Menjalankan perhitungan AHP untuk semua aplikasi di periode tertentu
     * 
     * Fungsi: Trigger perhitungan ulang skor dan ranking untuk semua aplikasi
     *         yang sudah divalidasi dalam periode ini
     * Route: POST /admin/report/{period}/calculate
     * 
     * Kapan digunakan:
     * - Setelah ada perubahan bobot kriteria/subkriteria
     * - Setelah ada aplikasi baru yang divalidasi
     * - Untuk refresh ranking setelah update data
     * 
     * Proses yang dilakukan:
     * 1. Panggil AHPController untuk hitung skor semua aplikasi
     * 2. Hitung weighted score berdasarkan bobot AHP
     * 3. Urutkan dan assign ranking
     * 4. Simpan hasil ke tabel rankings
     * 
     * @param Period $period Model Period yang aplikasinya akan dihitung (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman detail dengan pesan sukses
     */
    public function calculate(Period $period)
    {
        // Inisialisasi AHP Controller untuk perhitungan
        $ahpController = new AHPController();
        
        // Jalankan perhitungan untuk semua aplikasi di periode ini
        // Return: Collection of Ranking yang sudah dihitung
        $rankings = $ahpController->calculateAllApplicationsScores($period->id);

        // Redirect kembali ke halaman detail dengan info jumlah yang berhasil dihitung
        return redirect()->route('admin.report.show', $period->id)
            ->with('success', 'Perhitungan AHP berhasil dijalankan. Total: ' . $rankings->count() . ' aplikasi dihitung.');
    }

    /**
     * Export laporan ranking ke format PDF
     * 
     * Fungsi: Generate dan download laporan ranking dalam format PDF
     * Route: GET /admin/report/{period}/export-pdf
     * 
     * Format PDF:
     * - Berisi tabel ranking lengkap
     * - Data siswa (NISN, nama, kelas)
     * - Skor per kriteria (C1, C2, C3, C4, C5)
     * - Total skor dan ranking akhir
     * - Header dengan info periode
     * 
     * Kegunaan:
     * - Dokumentasi resmi hasil penilaian
     * - Laporan untuk pimpinan sekolah
     * - Arsip periode penerimaan bantuan
     * - Pengumuman hasil seleksi
     * 
     * @param Period $period Model Period yang akan di-export (route model binding)
     * @return \Illuminate\Http\Response Download file PDF
     */
    public function exportPdf(Period $period)
    {
        // Ambil data ranking untuk periode ini, urutkan berdasarkan rank
        // Eager load application dan user untuk data lengkap
        $rankings = Ranking::where('period_id', $period->id)
            ->with(['application.user'])
            ->orderBy('rank')
            ->get();

        // Load view PDF dengan data ranking
        // View: admin.report.pdf (template blade khusus untuk PDF)
        $pdf = Pdf::loadView('admin.report.pdf', compact('period', 'rankings'));
        
        // Generate dan download PDF dengan nama file dinamis
        // Format nama: ranking-beasiswa-[nama-periode].pdf
        // Contoh: ranking-beasiswa-Semester-Ganjil-2024-2025.pdf
        return $pdf->download('ranking-beasiswa-' . $period->name . '.pdf');
    }

    /**
     * Export laporan ranking ke format Excel
     * 
     * Fungsi: Generate dan download laporan ranking dalam format Excel (.xlsx)
     * Route: GET /admin/report/{period}/export-excel
     * 
     * Status: Placeholder - Fitur belum diimplementasi
     * 
     * Rencana Implementasi:
     * - Gunakan package Laravel Excel (maatwebsite/excel)
     * - Export dengan multiple sheets:
     *   Sheet 1: Ranking lengkap
     *   Sheet 2: Statistik periode
     *   Sheet 3: Detail skor per kriteria
     * - Format dengan styling (header, border, number format)
     * - Formula Excel untuk validasi data
     * 
     * Kegunaan:
     * - Analisis data lebih lanjut dengan Excel
     * - Import ke sistem lain
     * - Manipulasi data oleh admin
     * - Backup data dalam format spreadsheet
     * 
     * TODO: Implementasi export Excel menggunakan Laravel Excel
     * 
     * @param Period $period Model Period yang akan di-export (route model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect dengan pesan info
     */
    public function exportExcel(Period $period)
    {
        // Implementation for Excel export
        // You can use Laravel Excel package for this
        
        // TODO: Install package: composer require maatwebsite/excel
        // TODO: Create export class: php artisan make:export RankingExport
        // TODO: Implement export logic:
        //       return Excel::download(new RankingExport($period), 'ranking.xlsx');
        
        // Sementara redirect dengan pesan bahwa fitur belum tersedia
        return redirect()->route('admin.report.show', $period->id)
            ->with('info', 'Fitur export Excel belum tersedia');
    }
}