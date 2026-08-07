<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $allFilteredDealers = (clone $filteredQuery)->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'status_kontrak')->get();
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
        $postcheckAttentionDealerIds = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->where(function (Builder $query): void {
                $query->whereNull('hasil')
                    ->orWhere('hasil', '')
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('catatan')->where('catatan', '!=', '')->where('catatan', '!=', '-');
                    });
            })
            ->distinct()
            ->pluck('dealercabang_id');
        $attentionDealerIds = $postcheckAttentionDealerIds
            ->unique()
            ->values();
        $officeDealerPerformance = collect();

        return view('dealers.index', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'mechanicCounts' => $mechanicCounts,
            'serviceCounts' => $serviceCounts,
            'officeDealerPerformance' => $officeDealerPerformance,
            'atls' => Cache::remember('dealer:index:atls:'.$user->id, now()->addMinutes(10), fn () => $this->atlDropdownQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get()),
            'kpis' => [
                'total' => $allFilteredDealers->count(),
                'active' => $allFilteredDealers->filter(fn ($dealer) => ($dealer->status_kontrak ?? 'Aktif') === 'Aktif')->count(),
                'attention' => $attentionDealerIds->count(),
                'with_service' => $serviceCounts->filter(fn ($total) => $total > 0)->count(),
            ],
            'period' => $period,
        ]);
    }

    public function show(Request $request, OfficeOperationalData $officeData, int $dealer): View
    {
        $dealerData = $this->dealerDropdownQuery($request->user())->where('id', $dealer)->first();

        abort_unless($dealerData, 403);

        $latestActivity = Cache::remember("dealer:latest-activity:{$dealerData->id}", now()->addMinutes(10), fn () => collect([
            DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->max('created_at'),
            DB::table('precheck')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->max('created_at'),
            DB::table('presensi')->where('dealercabang_id', $dealerData->id)->max('date'),
        ])->filter()->max());
        $period = (string) $request->query('period', $latestActivity ? substr((string) $latestActivity, 0, 7) : now('Asia/Jakarta')->format('Y-m'));
        $startDate = substr($period, 0, 7).'-01';
        $endDate = now('Asia/Jakarta')->createFromFormat('Y-m-d', $startDate)->addMonth()->toDateString();
        $startDateTime = $startDate.' 00:00:00';
        $endDateTime = $endDate.' 00:00:00';
        $hasPeriodActivity = DB::table('postcheck')
            ->where('dealercabang_id', $dealerData->id)
            ->where('created_at', '>=', $startDateTime)
            ->where('created_at', '<', $endDateTime)
            ->exists()
            || DB::table('precheck')
                ->where('dealer', $dealerData->dealer)
                ->where('cabang', $dealerData->cabang)
                ->where('created_at', '>=', $startDateTime)
                ->where('created_at', '<', $endDateTime)
                ->exists()
            || DB::table('presensi')
                ->where('dealercabang_id', $dealerData->id)
                ->where('date', '>=', $startDate)
                ->where('date', '<', $endDate)
                ->exists();

        if (! $hasPeriodActivity && $latestActivity) {
            $period = substr((string) $latestActivity, 0, 7);
            $startDate = $period.'-01';
            $endDate = now('Asia/Jakarta')->createFromFormat('Y-m-d', $startDate)->addMonth()->toDateString();
            $startDateTime = $startDate.' 00:00:00';
            $endDateTime = $endDate.' 00:00:00';
        }
        $mechanicQuery = DB::table('users')
            ->where('dealer', $dealerData->dealer)
            ->where('cabang', $dealerData->cabang)
            ->where('role', 0)
            ->whereNotNull('nip');
        $mechanicNipsFromActivity = DB::table('postcheck')
            ->where('dealercabang_id', $dealerData->id)
            ->whereNotNull('nip')
            ->pluck('nip')
            ->merge(DB::table('presensi')->where('dealercabang_id', $dealerData->id)->whereNotNull('nip')->pluck('nip'))
            ->unique()
            ->values();
        $mechanicRowsQuery = (clone $mechanicQuery)->count() > 0
            ? clone $mechanicQuery
            : DB::table('users')->whereIn('nip', $mechanicNipsFromActivity)->where('role', 0)->whereNotNull('nip');
        $activityRows = DB::table('precheck')
            ->where('dealer', $dealerData->dealer)
            ->where('cabang', $dealerData->cabang)
            ->select('created_at', 'noplat', 'teknisi', DB::raw("'Precheck' as type"), DB::raw("COALESCE(jenismobil, '-') as result"))
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->merge(DB::table('postcheck')
                ->where('dealercabang_id', $dealerData->id)
                ->select('created_at', 'noplat', 'teknisi', DB::raw("'Postcheck' as type"), DB::raw("COALESCE(hasil, '-') as result"))
                ->latest('created_at')
                ->limit(50)
                ->get())
            ->sortByDesc('created_at')
            ->values();
        $activityPage = max(1, (int) $request->query('activity_page', 1));
        $activities = new LengthAwarePaginator(
            $activityRows->forPage($activityPage, 8)->values(),
            $activityRows->count(),
            8,
            $activityPage,
            ['path' => $request->url(), 'pageName' => 'activity_page']
        );
        $activities->appends($request->query());

        return view('dealers.show', [
            'role' => $this->activeRole($request, $request->user()),
            'dealer' => $dealerData,
            'period' => $period,
            'mechanics' => (clone $mechanicRowsQuery)->orderBy('nama')->paginate(8, ['*'], 'mechanic_page')->withQueryString(),
            'activities' => $activities,
            'kpis' => Cache::remember("dealer:kpis:{$dealerData->id}:{$period}", now()->addMinutes(10), fn () => [
                'mechanics' => (clone $mechanicRowsQuery)->count(),
                'presences' => DB::table('presensi')->where('dealercabang_id', $dealerData->id)->where('date', '>=', $startDate)->where('date', '<', $endDate)->count(),
                'prechecks' => DB::table('precheck')->where('dealer', $dealerData->dealer)->where('cabang', $dealerData->cabang)->where('created_at', '>=', $startDateTime)->where('created_at', '<', $endDateTime)->count(),
                'postchecks' => DB::table('postcheck')->where('dealercabang_id', $dealerData->id)->where('created_at', '>=', $startDateTime)->where('created_at', '<', $endDateTime)->count(),
            ]),
        ]);
    }
}
