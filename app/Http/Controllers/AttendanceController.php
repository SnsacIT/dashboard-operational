<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $date = (string) $request->query('date', now('Asia/Jakarta')->toDateString());
        $isDailyRoute = $request->routeIs('mechanics.attendances.daily');
        $dealerIds = $this->visibleDealerQuery($user)
            ->when($request->filled('dealer_id'), function ($query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            })
            ->pluck('id');

        $query = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->when($isDailyRoute, function ($query) use ($date): void {
                $query->whereDate('date', $date);
            }, function ($query) use ($period): void {
                $query->whereMonth('date', substr($period, 5, 2))
                    ->whereYear('date', substr($period, 0, 4));
            });

        $categoryCounts = (clone $query)
            ->selectRaw('COALESCE(category, "Belum Ada") as category_name, COUNT(*) as total')
            ->groupBy('category_name')
            ->pluck('total', 'category_name');

        return view('attendances.index', [
            'role' => $this->activeRole($request, $user),
            'attendances' => (clone $query)
                ->latest('date')
                ->latest('time')
                ->paginate(12)
                ->withQueryString(),
            'dealers' => $this->visibleDealerQuery($user)->orderBy('dealer')->get(),
            'kpis' => [
                'total' => (clone $query)->count(),
                'regular' => ($categoryCounts['Reguler'] ?? 0) + ($categoryCounts['Regular'] ?? 0),
                'late' => (clone $query)->where('is_late', 1)->count(),
                'backup' => ($categoryCounts['Backup'] ?? 0) + ($categoryCounts['Piket Backup'] ?? 0),
                'standby' => $categoryCounts['Standby'] ?? 0,
                'piket' => $categoryCounts['Piket'] ?? 0,
            ],
            'period' => $period,
            'date' => $date,
            'isDailyRoute' => $isDailyRoute,
        ]);
    }
}
