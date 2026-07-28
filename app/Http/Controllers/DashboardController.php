<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use BuildsOperationalQueries;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $isSoh = $role === 'soh';
        $today = now('Asia/Jakarta')->toDateString();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);

        $baseDealersQuery = $this->visibleDealerQuery($user);
        $filteredDealersQuery = (clone $baseDealersQuery)
            ->when($request->filled('atl_id') && $user->dashboard_role === 'soh', function (Builder $query) use ($request): void {
                $query->where('no_atl', $request->integer('atl_id'));
            })
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            });

        $filteredDealers = (clone $filteredDealersQuery)
            ->select('id', 'dealer', 'cabang', 'no_atl', 'no_soh', 'status_kontrak', 'kotakab')
            ->get();
        $dealerIds = $filteredDealers->pluck('id');

        $mechanicsQuery = $this->mechanicsForDealers($filteredDealers);

        $attendanceToday = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereDate('date', $today);

        $latestAttendanceDate = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->max('date');

        $attendanceLatest = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->when($latestAttendanceDate, function (Builder $query) use ($latestAttendanceDate): void {
                $query->whereDate('date', $latestAttendanceDate);
            });

        $precheckPeriod = DB::table('precheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $postcheckPeriod = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $kpis = [
            'atls' => $this->visibleAtlQuery($user)->count(),
            'dealers' => $filteredDealers->count(),
            'mechanics' => (clone $mechanicsQuery)->count(),
            'present_today' => (clone $attendanceToday)->count(),
            'present_latest' => $latestAttendanceDate ? (clone $attendanceLatest)->count() : 0,
            'latest_attendance_date' => $latestAttendanceDate,
            'prechecks' => (clone $precheckPeriod)->count(),
            'postchecks' => (clone $postcheckPeriod)->count(),
            'potential_open' => (clone $postcheckPeriod)
                ->where(function (Builder $query): void {
                    $query->whereNull('hasil')
                        ->orWhere('hasil', '')
                        ->orWhere('hasil', 'like', '%saran%')
                        ->orWhere('hasil', 'like', '%rekomendasi%');
                })
                ->count(),
            'late_attendances' => (clone $attendanceToday)->where('is_late', 1)->count(),
        ];

        $atlSummaries = $isSoh
            ? $this->atlSummaries($user, $dealerIds, $month, $year)
            : collect();

        $dealerSummaries = $this->dealerSummaries($filteredDealers, $month, $year);

        return view('dashboard.index', [
            'role' => $role,
            'kpis' => $kpis,
            'atls' => $this->visibleAtlQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get(),
            'dealers' => $filteredDealers->take(8),
            'mechanics' => (clone $mechanicsQuery)->orderBy('nama')->limit(8)->get(),
            'allDealers' => (clone $baseDealersQuery)->orderBy('dealer')->get(),
            'atlSummaries' => $atlSummaries,
            'dealerSummaries' => $dealerSummaries,
            'recentAttendances' => DB::table('presensi')
                ->whereIn('dealercabang_id', $dealerIds)
                ->latest('date')
                ->latest('time')
                ->limit(8)
                ->get(),
            'recentChecks' => DB::table('postcheck')
                ->whereIn('dealercabang_id', $dealerIds)
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'period' => $period,
        ]);
    }

    private function mechanicsForDealers($dealers): Builder
    {
        return DB::table('users')
            ->where(function (Builder $query) use ($dealers): void {
                foreach ($dealers as $dealer) {
                    $query->orWhere(function (Builder $query) use ($dealer): void {
                        $query->where('dealer', $dealer->dealer)
                            ->where('cabang', $dealer->cabang);
                    });
                }
            })
            ->whereNotNull('nip')
            ->where(function (Builder $query): void {
                $query->whereNull('delete_at')
                    ->orWhere('delete_at', '>', now('Asia/Jakarta'));
            })
            ->where(function (Builder $query): void {
                $query->whereNull('resign_date')
                    ->orWhere('resign_date', '>', now('Asia/Jakarta')->toDateString());
            });
    }

    private function atlSummaries($user, $dealerIds, string $month, string $year)
    {
        return $this->visibleAtlQuery($user)
            ->orderBy('wilayah_atl.nama_wilayah')
            ->get()
            ->map(function ($atl) use ($dealerIds, $month, $year) {
                $atlDealerIds = DB::table('dealercabang')
                    ->whereIn('id', $dealerIds)
                    ->where('no_atl', $atl->urutan)
                    ->pluck('id');

                return (object) [
                    'urutan' => $atl->urutan,
                    'name' => $atl->nama ?? $atl->username ?? $atl->nip_atl,
                    'region' => $atl->nama_wilayah,
                    'dealers' => $atlDealerIds->count(),
                    'mechanics' => DB::table('users')
                        ->whereIn('dealer', DB::table('dealercabang')->whereIn('id', $atlDealerIds)->pluck('dealer'))
                        ->whereIn('cabang', DB::table('dealercabang')->whereIn('id', $atlDealerIds)->pluck('cabang'))
                        ->whereNotNull('nip')
                        ->count(),
                    'postchecks' => DB::table('postcheck')
                        ->whereIn('dealercabang_id', $atlDealerIds)
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->count(),
                ];
            });
    }

    private function dealerSummaries($dealers, string $month, string $year)
    {
        return $dealers->take(8)->map(function ($dealer) use ($month, $year) {
            return (object) [
                'id' => $dealer->id,
                'dealer' => $dealer->dealer,
                'cabang' => $dealer->cabang,
                'no_atl' => $dealer->no_atl,
                'mechanics' => DB::table('users')
                    ->where('dealer', $dealer->dealer)
                    ->where('cabang', $dealer->cabang)
                    ->whereNotNull('nip')
                    ->count(),
                'prechecks' => DB::table('precheck')
                    ->where('dealercabang_id', $dealer->id)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->count(),
                'postchecks' => DB::table('postcheck')
                    ->where('dealercabang_id', $dealer->id)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->count(),
            ];
        });
    }
}
