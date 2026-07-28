<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AtlController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealerController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PotentialMonitoringController;
use App\Http\Controllers\VehicleCheckController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/dealers/options', [DashboardController::class, 'dealerOptions'])->name('dashboard.dealers.options');

    Route::get('/atl-regions', [AtlController::class, 'index'])->name('atl-regions.index');
    Route::get('/atl-regions/{atl}', [AtlController::class, 'show'])->whereNumber('atl')->name('atl-regions.show');
    Route::get('/atl-comparisons', [AtlController::class, 'comparison'])->name('atl-comparisons.index');

    Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
    Route::get('/dealers/performance', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Performa Dealer', 'Grafik target vs realisasi, tren performa, ranking dealer, dan perubahan performa dealer.'))->name('dealers.performance');
    Route::get('/dealers/{dealer}', [DealerController::class, 'show'])->whereNumber('dealer')->name('dealers.show');

    Route::get('/mechanics', [MechanicController::class, 'index'])->name('mechanics.index');
    Route::get('/mechanics/attendances/daily', [AttendanceController::class, 'index'])->name('mechanics.attendances.daily');
    Route::get('/mechanics/attendances/recap', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Rekap Presensi', 'Rekap mingguan, bulanan, kalender presensi, persentase kehadiran, dan keterlambatan.'))->name('mechanics.attendances.recap');
    Route::get('/mechanics/{mechanic}', [MechanicController::class, 'show'])->whereNumber('mechanic')->name('mechanics.show');

    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/inspections', [VehicleCheckController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/pending-postcheck', [VehicleCheckController::class, 'pendingPostcheck'])->name('inspections.pending-postcheck');
    Route::get('/inspections/verification', [VehicleCheckController::class, 'verification'])->name('inspections.verification');

    Route::get('/potentials', [PotentialMonitoringController::class, 'index'])->name('potentials.index');
    Route::get('/potentials/ranking', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Ranking Dealer', 'Ranking dealer berdasarkan skor potensi, performa saat ini, gap, dan status tindak lanjut.'))->name('potentials.ranking');
    Route::get('/potentials/analysis', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Analisis Potensi', 'Analisis kapasitas, utilisasi mekanik, produktivitas, target, dan potensi wilayah.'))->name('potentials.analysis');
    Route::get('/potentials/follow-ups', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Tindak Lanjut Potensi', 'Daftar temuan, rekomendasi, PIC, target, status, dan bukti tindak lanjut.'))->name('potentials.follow-ups');

    Route::get('/reports', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Laporan', 'Preview dan export laporan dealer, mekanik, presensi, precheck/postcheck, dan potensi.'))->name('reports.index');
    Route::get('/notifications', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Notifikasi', 'Peringatan penting terkait dealer, presensi, postcheck, verifikasi, dan tindak lanjut.'))->name('notifications.index');
    Route::get('/profile', fn (PageController $page, Illuminate\Http\Request $request) => $page($request, 'Profil', 'Informasi pengguna, role, area/wilayah, kontak, dan riwayat login.'))->name('profile.index');
});
