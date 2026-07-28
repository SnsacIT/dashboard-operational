<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PotentialMonitoringController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request, OfficeOperationalData $officeData): View
    {
        $user = $request->user();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $dealerQuery = $this->visibleDealerQuery($user)
            ->when($request->filled('dealer_id'), function ($query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            });
        $visibleDealers = (clone $dealerQuery)->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab')->get();
        $dealerIds = $visibleDealers->pluck('id');

        $potentials = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', substr($period, 5, 2))
            ->whereYear('created_at', substr($period, 0, 4))
            ->selectRaw('dealercabang_id, dealer, cabang, COUNT(*) as service_count, SUM(COALESCE(unit, 1)) as unit_count')
            ->groupBy('dealercabang_id', 'dealer', 'cabang')
            ->orderByDesc('service_count')
            ->paginate(12)
            ->withQueryString();

        return view('potential-monitoring.index', [
            'role' => $this->activeRole($request, $user),
            'potentials' => $potentials,
            'dealers' => $this->dealerDropdownQuery($user)->orderBy('dealer')->get(),
            'period' => $period,
            'officePerformance' => $officeData->monthlyPerformance($visibleDealers, $period),
            'productivity' => $officeData->productivity($visibleDealers, $period),
            'postcheckRatio' => $officeData->postcheckRatio($visibleDealers, $period),
        ]);
    }
}
