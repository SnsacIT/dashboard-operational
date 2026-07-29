<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DealerController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request, OfficeOperationalData $officeData): View
    {
        $user = $request->user();
        $baseQuery = $this->dealerDropdownQuery($user);
        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('atl_id') && $user->dashboard_role === 'soh', function (Builder $query) use ($request): void {
                $query->where('no_atl', $request->integer('atl_id'));
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status_kontrak', $request->query('status'));
            })
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->query('search').'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('dealer', 'like', $search)
                        ->orWhere('cabang', 'like', $search)
                        ->orWhere('kode', 'like', $search)
                        ->orWhere('kotakab', 'like', $search);
                });
            });

        $dealers = (clone $filteredQuery)
            ->select('dealercabang.*')
            ->orderBy('dealer')
            ->paginate(12)
            ->withQueryString();

        $allFilteredDealers = (clone $filteredQuery)->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab')->get();
        $dealerPairs = $allFilteredDealers->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $mechanicCounts = DB::table('users')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
            ->where('role', 0)
            ->whereNotNull('nip')
            ->selectRaw('dealer, cabang, COUNT(*) as total')
            ->groupBy('dealer', 'cabang')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->dealer.'|'.$row->cabang) => $row->total]);

        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $startDate = $period.'-01 00:00:00';
        $endDate = now('Asia/Jakarta')->createFromFormat('Y-m-d H:i:s', $startDate)->addMonth()->format('Y-m-d H:i:s');
        $dealerIds = $allFilteredDealers->pluck('id');
        $serviceCounts = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->selectRaw('dealercabang_id, COUNT(*) as total')
            ->groupBy('dealercabang_id')
            ->pluck('total', 'dealercabang_id');
        $officeDealerPerformance = collect();

        return view('dealers.index', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'mechanicCounts' => $mechanicCounts,
            'serviceCounts' => $serviceCounts,
            'officeDealerPerformance' => $officeDealerPerformance,
            'atls' => $this->atlDropdownQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get(),
            'kpis' => [
                'total' => $allFilteredDealers->count(),
                'active' => (clone $filteredQuery)->where('status_kontrak', 'Aktif')->count(),
                'attention' => (clone $filteredQuery)->where(function (Builder $query): void {
                    $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Aktif');
                })->count(),
                'with_service' => $serviceCounts->filter(fn ($total) => $total > 0)->count(),
            ],
            'period' => $period,
        ]);
    }

    public function show(Request $request, OfficeOperationalData $officeData, int $dealer): View
    {
        $dealerData = $this->dealerDropdownQuery($request->user())->where('id', $dealer)->first();

        abort_unless($dealerData, 403);

        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));

        return view('dealers.show', [
            'role' => $this->activeRole($request, $request->user()),
            'dealer' => $dealerData,
            'mechanics' => DB::table('users')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->where('role', 0)->whereNotNull('nip')->orderBy('nama')->limit(25)->get(),
            'prechecks' => DB::table('precheck')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->latest('created_at')->limit(8)->get(),
            'postchecks' => DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->latest('created_at')->limit(8)->get(),
            'officePerformance' => (object) ['unit_entry' => 0, 'omset_total' => 0],
            'productivity' => (object) ['unit_per_mechanic' => 0],
            'postcheckRatio' => (object) ['ratio' => 0],
            'kpis' => [
                'mechanics' => DB::table('users')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->where('role', 0)->whereNotNull('nip')->count(),
                'presences' => DB::table('presensi')->where('dealercabang_id', $dealerData->id)->where('date', '>=', substr($period, 0, 7).'-01')->where('date', '<', now('Asia/Jakarta')->createFromFormat('Y-m-d', substr($period, 0, 7).'-01')->addMonth()->toDateString())->count(),
                'prechecks' => DB::table('precheck')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->where('created_at', '>=', substr($period, 0, 7).'-01 00:00:00')->where('created_at', '<', now('Asia/Jakarta')->createFromFormat('Y-m-d H:i:s', substr($period, 0, 7).'-01 00:00:00')->addMonth()->format('Y-m-d H:i:s'))->count(),
                'postchecks' => DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->where('created_at', '>=', substr($period, 0, 7).'-01 00:00:00')->where('created_at', '<', now('Asia/Jakarta')->createFromFormat('Y-m-d H:i:s', substr($period, 0, 7).'-01 00:00:00')->addMonth()->format('Y-m-d H:i:s'))->count(),
            ],
        ]);
    }
}
