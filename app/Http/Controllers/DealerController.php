<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DealerController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $baseQuery = $this->visibleDealerQuery($user);
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

        $allFilteredDealers = (clone $filteredQuery)->select('id', 'dealer', 'cabang')->get();
        $mechanicCounts = DB::table('users')
            ->where(function (Builder $query) use ($allFilteredDealers): void {
                foreach ($allFilteredDealers as $dealer) {
                    $query->orWhere(function (Builder $query) use ($dealer): void {
                        $query->where('dealer', $dealer->dealer)->where('cabang', $dealer->cabang);
                    });
                }
            })
            ->selectRaw('dealer, cabang, COUNT(*) as total')
            ->groupBy('dealer', 'cabang')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->dealer.'|'.$row->cabang) => $row->total]);

        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $dealerIds = $allFilteredDealers->pluck('id');
        $serviceCounts = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', substr($period, 5, 2))
            ->whereYear('created_at', substr($period, 0, 4))
            ->selectRaw('dealercabang_id, COUNT(*) as total')
            ->groupBy('dealercabang_id')
            ->pluck('total', 'dealercabang_id');

        return view('dealers.index', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'mechanicCounts' => $mechanicCounts,
            'serviceCounts' => $serviceCounts,
            'atls' => $this->visibleAtlQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get(),
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

    public function show(Request $request, int $dealer): View
    {
        $dealerData = $this->visibleDealerQuery($request->user())->where('id', $dealer)->first();

        abort_unless($dealerData, 403);

        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));

        return view('dealers.show', [
            'role' => $this->activeRole($request, $request->user()),
            'dealer' => $dealerData,
            'mechanics' => DB::table('users')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->whereNotNull('nip')->orderBy('nama')->get(),
            'prechecks' => DB::table('precheck')->where('dealercabang_id', $dealerData->id)->latest('created_at')->limit(8)->get(),
            'postchecks' => DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->latest('created_at')->limit(8)->get(),
            'kpis' => [
                'mechanics' => DB::table('users')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->whereNotNull('nip')->count(),
                'presences' => DB::table('presensi')->where('dealercabang_id', $dealerData->id)->whereMonth('date', substr($period, 5, 2))->whereYear('date', substr($period, 0, 4))->count(),
                'prechecks' => DB::table('precheck')->where('dealercabang_id', $dealerData->id)->whereMonth('created_at', substr($period, 5, 2))->whereYear('created_at', substr($period, 0, 4))->count(),
                'postchecks' => DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->whereMonth('created_at', substr($period, 5, 2))->whereYear('created_at', substr($period, 0, 4))->count(),
            ],
        ]);
    }
}
