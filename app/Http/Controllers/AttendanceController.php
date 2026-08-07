<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $latestAttendanceDate = Cache::remember('attendance:latest-date', now()->addMinutes(10), fn () => DB::table('presensi')->max('date'));
        $period = (string) $request->query('period', $latestAttendanceDate ? substr($latestAttendanceDate, 0, 7) : now('Asia/Jakarta')->format('Y-m'));
        $date = (string) $request->query('date', $latestAttendanceDate ?: now('Asia/Jakarta')->toDateString());
        $isDailyRoute = $request->routeIs('mechanics.attendances.daily');
        $dealerId = $request->integer('dealer_id') ?: null;

        $query = DB::table('presensi')
            ->when($dealerId, function (Builder $query) use ($dealerId): void {
                $query->where('dealercabang_id', $dealerId);
            })
            ->when($isDailyRoute, function ($query) use ($date): void {
                $query->where('date', $date);
            }, function ($query) use ($period): void {
                $start = $period.'-01';
                $end = now('Asia/Jakarta')->createFromFormat('Y-m-d', $start)->addMonth()->toDateString();
                $query->where('date', '>=', $start)->where('date', '<', $end);
            });

        $categoryCounts = Cache::remember('attendance:category-counts:'.md5($request->fullUrl()), now()->addMinutes(5), fn () => (clone $query)
            ->selectRaw('COALESCE(category, "Belum Ada") as category_name, COUNT(*) as total')
            ->groupBy('category_name')
            ->pluck('total', 'category_name'));
        $lateCount = Cache::remember('attendance:late-count:'.md5($request->fullUrl()), now()->addMinutes(5), fn () => (clone $query)->where('is_late', 1)->count());

        $recaps = collect();

        if (! $isDailyRoute) {
            $recaps = (clone $query)
                ->selectRaw('nip, COALESCE(name, nip) as name, dealer, cabang')
                ->selectRaw('COUNT(*) as total_presensi')
                ->selectRaw('SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as total_terlambat')
                ->selectRaw('SUM(CASE WHEN category IN ("Reguler", "Regular") THEN 1 ELSE 0 END) as total_reguler')
                ->selectRaw('SUM(CASE WHEN category LIKE "%Backup%" THEN 1 ELSE 0 END) as total_backup')
                ->selectRaw('SUM(CASE WHEN category = "Piket" THEN 1 ELSE 0 END) as total_piket')
                ->selectRaw('SUM(CASE WHEN category = "Standby" THEN 1 ELSE 0 END) as total_standby')
                ->groupBy('nip', 'name', 'dealer', 'cabang')
                ->orderByDesc('total_presensi')
                ->paginate(12)
                ->withQueryString();
        }

        return view('attendances.index', [
            'role' => $this->activeRole($request, $user),
            'attendances' => (clone $query)
                ->latest('date')
                ->latest('time')
                ->paginate(12)
                ->withQueryString(),
            'recaps' => $recaps,
            'dealers' => Cache::remember('attendance:dealers:'.$user->id, now()->addMinutes(10), fn () => $this->dealerDropdownQuery($user)->orderBy('dealer')->limit(300)->get()),
            'kpis' => [
                'total' => Cache::remember('attendance:total:'.md5($request->fullUrl()), now()->addMinutes(5), fn () => (clone $query)->count()),
                'regular' => ($categoryCounts['Reguler'] ?? 0) + ($categoryCounts['Regular'] ?? 0),
                'late' => $lateCount,
                'backup' => ($categoryCounts['Backup'] ?? 0) + ($categoryCounts['Piket Backup'] ?? 0),
                'standby' => $categoryCounts['Standby'] ?? 0,
                'piket' => $categoryCounts['Piket'] ?? 0,
            ],
            'period' => $period,
            'date' => $date,
            'isDailyRoute' => $isDailyRoute,
            'isRecapRoute' => $request->routeIs('mechanics.attendances.recap'),
        ]);
    }
}
