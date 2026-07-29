<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use BuildsOperationalQueries;

    public function __invoke(Request $request, OfficeOperationalData $officeData): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));

        $dashboardData = Cache::remember("dashboard:summary:{$period}", now()->addMinutes(10), function (): array {
            return $this->buildDashboardSummary();
        });

        $dashboardData = $this->normalizeDashboardData($dashboardData);

        if ($role === 'soh') {
            $dashboardData = $this->ensureSohDashboardData($dashboardData, $user);
        }

        if ($role === 'atl') {
            $dashboardData = $this->buildAtlModeData($dashboardData, $request, $user);
        }

        if ($request->filled('atl_id') || $request->filled('dealer_id')) {
            $dashboardData = $this->applyDashboardFilters($dashboardData, $request);
        }

        return view('dashboard.index', array_merge($dashboardData, [
            'role' => $role,
            'period' => $period,
            'selectedDealer' => $request->filled('dealer_id') ? DB::table('dealercabang')->where('id', $request->integer('dealer_id'))->first() : null,
        ]));
    }

    private function ensureSohDashboardData(array $data, $user): array
    {
        if (collect($data['atls'] ?? [])->filter(fn ($row) => is_object($row) && isset($row->urutan))->isEmpty()) {
            $data['atls'] = $this->atlDropdownQuery($user)
                ->orderBy('wilayah_atl.nama_wilayah')
                ->get();
        }

        if (collect($data['atlSummaries'] ?? [])->filter(fn ($row) => is_object($row) && isset($row->name, $row->region))->isEmpty()) {
            $data['atlSummaries'] = DB::table('dealercabang')
                ->leftJoin('wilayah_atl', 'wilayah_atl.urutan', '=', 'dealercabang.no_atl')
                ->leftJoin('users as atl_users', 'atl_users.nip', '=', 'wilayah_atl.nip_atl')
                ->whereNotNull('dealercabang.no_atl')
                ->where('dealercabang.no_atl', '!=', 0)
                ->where(function (Builder $query): void {
                    $query->whereNull('dealercabang.status_kontrak')->orWhere('dealercabang.status_kontrak', '!=', 'Tidak Aktif');
                })
                ->selectRaw('dealercabang.no_atl as urutan')
                ->selectRaw('COALESCE(atl_users.nama, atl_users.username, wilayah_atl.nip_atl, CONCAT("ATL ", dealercabang.no_atl)) as name')
                ->selectRaw('COALESCE(wilayah_atl.nama_wilayah, CONCAT("Wilayah ", dealercabang.no_atl)) as region')
                ->selectRaw('COUNT(*) as dealers')
                ->groupBy('dealercabang.no_atl', 'atl_users.nama', 'atl_users.username', 'wilayah_atl.nip_atl', 'wilayah_atl.nama_wilayah')
                ->orderByDesc('dealers')
                ->limit(8)
                ->get();
        }

        if (
            collect($data['dealerSummaries'] ?? [])->filter(fn ($row) => is_object($row) && isset($row->dealer, $row->cabang))->isEmpty()
            || collect($data['mechanics'] ?? [])->filter(fn ($row) => is_object($row) && isset($row->id))->isEmpty()
            || collect($data['recentAttendances'] ?? [])->filter(fn ($row) => is_object($row) && isset($row->date))->isEmpty()
        ) {
            $dealerQuery = DB::table('dealercabang')
                ->where(function (Builder $query): void {
                    $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
                })
                ->whereNotNull('no_atl')
                ->where('no_atl', '!=', 0);

            $dealers = (clone $dealerQuery)
                ->select('id', 'dealer', 'cabang', 'nama_dealer', 'no_atl')
                ->orderBy('dealer')
                ->limit(8)
                ->get();
            $dealerPairs = $dealers->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
            $mechanicsByDealer = $dealerPairs->isEmpty()
                ? collect()
                : DB::table('users')
                    ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
                    ->where('role', 0)
                    ->whereNotNull('nip')
                    ->selectRaw("CONCAT(dealer, '|', cabang) as dealer_key, COUNT(*) as total")
                    ->groupBy('dealer_key')
                    ->pluck('total', 'dealer_key');

            $data['dealerSummaries'] = $dealers->map(function ($dealer) use ($mechanicsByDealer) {
                $dealer->mechanics = (int) ($mechanicsByDealer[$dealer->dealer.'|'.$dealer->cabang] ?? 0);

                return $dealer;
            });
            $data['mechanics'] = DB::table('users')
                ->where('role', 0)
                ->whereNotNull('nip')
                ->whereNotNull('dealer')
                ->whereNotNull('cabang')
                ->orderBy('nama')
                ->limit(8)
                ->get();
            $latestAttendanceDate = DB::table('presensi')->max('date');
            $data['recentAttendances'] = DB::table('presensi')
                ->when($latestAttendanceDate, function (Builder $query) use ($latestAttendanceDate): void {
                    $query->where('date', $latestAttendanceDate);
                })
                ->latest('date')
                ->latest('time')
                ->limit(8)
                ->get();
        }

        return $data;
    }

    private function buildAtlModeData(array $data, Request $request, $user): array
    {
        $atlId = $request->filled('atl_id') ? $request->integer('atl_id') : $this->atlNumber($user);

        if (! $atlId && $user->dashboard_role === 'soh') {
            $atlId = (int) $this->atlDropdownQuery($user)->orderBy('wilayah_atl.urutan')->value('wilayah_atl.urutan');
        }

        $dealerQuery = DB::table('dealercabang')
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            })
            ->when($atlId, fn (Builder $query) => $query->where('no_atl', $atlId))
            ->when($request->filled('dealer_id'), fn (Builder $query) => $query->where('id', $request->integer('dealer_id')));

        $dealers = $dealerQuery
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'no_atl', 'kotakab')
            ->orderBy('dealer')
            ->orderBy('cabang')
            ->limit(8)
            ->get();

        $dealerPairs = $dealers->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $mechanicsByDealer = $dealerPairs->isEmpty()
            ? collect()
            : DB::table('users')
                ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
                ->where('role', 0)
                ->whereNotNull('nip')
                ->selectRaw("CONCAT(dealer, '|', cabang) as dealer_key, COUNT(*) as total")
                ->groupBy('dealer_key')
                ->pluck('total', 'dealer_key');

        $data['dealerSummaries'] = $dealers->map(function ($dealer) use ($mechanicsByDealer) {
            $dealer->mechanics = (int) ($mechanicsByDealer[$dealer->dealer.'|'.$dealer->cabang] ?? 0);

            return $dealer;
        });

        $data['mechanics'] = $dealerPairs->isEmpty()
            ? collect()
            : DB::table('users')
                ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
                ->where('role', 0)
                ->whereNotNull('nip')
                ->orderBy('nama')
                ->limit(8)
                ->get();

        $data['recentAttendances'] = $dealerPairs->isEmpty()
            ? collect()
            : DB::table('presensi')
                ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
                ->latest('date')
                ->latest('time')
                ->limit(8)
                ->get();

        return $data;
    }

    private function applyDashboardFilters(array $data, Request $request): array
    {
        $atlId = $request->filled('atl_id') ? $request->integer('atl_id') : null;
        $dealerId = $request->filled('dealer_id') ? $request->integer('dealer_id') : null;

        if ($atlId) {
            $data['atlSummaries'] = collect($data['atlSummaries'])->where('urutan', $atlId)->values();
            $data['dealerSummaries'] = collect($data['dealerSummaries'])->where('no_atl', $atlId)->values();
        }

        if ($dealerId) {
            $dealer = DB::table('dealercabang')->where('id', $dealerId)->first();

            if ($dealer) {
                $data['dealerSummaries'] = collect([$dealer])->map(function ($row) {
                    $row->mechanics = DB::table('users')
                        ->where('role', 0)
                        ->where('dealer', $row->dealer)
                        ->where('cabang', $row->cabang)
                        ->whereNotNull('nip')
                        ->count();

                    return $row;
                });
                $data['mechanics'] = DB::table('users')
                    ->where('role', 0)
                    ->where('dealer', $dealer->dealer)
                    ->where('cabang', $dealer->cabang)
                    ->whereNotNull('nip')
                    ->orderBy('nama')
                    ->limit(8)
                    ->get();
                $data['recentAttendances'] = DB::table('presensi')
                    ->where('dealer', $dealer->dealer)
                    ->where('cabang', $dealer->cabang)
                    ->latest('date')
                    ->latest('time')
                    ->limit(8)
                    ->get();
            }
        }

        return $data;
    }

    private function normalizeDashboardData(array $data): array
    {
        foreach (['atls', 'dealers', 'mechanics', 'allDealers', 'atlSummaries', 'dealerSummaries', 'recentAttendances', 'recentChecks'] as $key) {
            $data[$key] = collect($data[$key] ?? [])->map(function ($row) {
                if (is_array($row)) {
                    return (object) $row;
                }

                if (is_object($row)) {
                    return $row;
                }

                return (object) [];
            });
        }

        foreach (['officePerformance', 'productivity', 'postcheckRatio'] as $key) {
            $value = $data[$key] ?? [];

            $data[$key] = is_array($value) || $value instanceof \__PHP_Incomplete_Class
                ? (object) $value
                : (is_object($value) ? $value : (object) []);
        }

        $data['atlChart'] = [
            'labels' => collect($data['atlChart']['labels'] ?? [])->flatten()->values(),
            'dealers' => collect($data['atlChart']['dealers'] ?? [])->flatten()->values(),
            'regions' => collect($data['atlChart']['regions'] ?? [])->flatten()->values(),
        ];

        return $data;
    }

    private function buildDashboardSummary(): array
    {
        $dealerQuery = DB::table('dealercabang')->where(function (Builder $query): void {
            $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
        });
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $atlChartRows = (clone $dealerQuery)
            ->leftJoin('wilayah_atl', 'wilayah_atl.urutan', '=', 'dealercabang.no_atl')
            ->leftJoin('users', 'users.nip', '=', 'wilayah_atl.nip_atl')
            ->whereNotNull('dealercabang.no_atl')
            ->where('dealercabang.no_atl', '!=', 0)
            ->selectRaw('dealercabang.no_atl, wilayah_atl.nama_wilayah, users.nama, users.username, COUNT(*) as total_dealer')
            ->groupBy('dealercabang.no_atl', 'wilayah_atl.nama_wilayah', 'users.nama', 'users.username')
            ->orderByDesc('total_dealer')
            ->limit(5)
            ->get();
        $latestPostcheckDate = $latestAttendanceDate ?: now('Asia/Jakarta')->toDateString();
        $workPerformance = DB::table('postcheck')
            ->where('created_at', '>=', $latestPostcheckDate.' 00:00:00')
            ->where('created_at', '<=', $latestPostcheckDate.' 23:59:59')
            ->selectRaw('COUNT(*) as unit_entry')
            ->selectRaw("COUNT(DISTINCT NULLIF(noplat, '')) as unit_ac")
            ->first();
        $potentialOpen = DB::table('postcheck')
            ->where('created_at', '>=', $latestPostcheckDate.' 00:00:00')
            ->where('created_at', '<=', $latestPostcheckDate.' 23:59:59')
            ->where(function (Builder $query): void {
                $query->whereNull('hasil')
                    ->orWhere('hasil', '')
                    ->orWhere('hasil', 'like', '%saran%')
                    ->orWhere('hasil', 'like', '%rekomendasi%')
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('catatan')->where('catatan', '!=', '-')->where('catatan', '!=', '');
                    });
            })
            ->limit(1000)
            ->count();

        $kpis = [
            'atls' => (clone $dealerQuery)->whereNotNull('no_atl')->distinct()->count('no_atl'),
            'dealers' => (clone $dealerQuery)->count(),
            'mechanics' => DB::table('users')->where('role', 0)->whereNotNull('dealer')->whereNotNull('cabang')->count(),
            'present_today' => 0,
            'present_latest' => $latestAttendanceDate ? DB::table('presensi')->where('date', $latestAttendanceDate)->count() : 0,
            'latest_attendance_date' => $latestAttendanceDate,
            'prechecks' => 0,
            'postchecks' => 0,
            'potential_open' => $potentialOpen,
            'late_attendances' => DB::table('presensi')
                ->when($latestAttendanceDate, function (Builder $query) use ($latestAttendanceDate): void {
                    $query->whereDate('date', $latestAttendanceDate);
                })
                ->where('is_late', 1)
                ->count(),
        ];

        $atlSummaries = DB::table('dealercabang')
            ->leftJoin('wilayah_atl', 'wilayah_atl.urutan', '=', 'dealercabang.no_atl')
            ->leftJoin('users as atl_users', 'atl_users.nip', '=', 'wilayah_atl.nip_atl')
            ->whereNotNull('dealercabang.no_atl')
            ->where('dealercabang.no_atl', '!=', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('dealercabang.status_kontrak')->orWhere('dealercabang.status_kontrak', '!=', 'Tidak Aktif');
            })
            ->selectRaw('dealercabang.no_atl as urutan')
            ->selectRaw('COALESCE(atl_users.nama, atl_users.username, wilayah_atl.nip_atl, CONCAT("ATL ", dealercabang.no_atl)) as name')
            ->selectRaw('COALESCE(wilayah_atl.nama_wilayah, CONCAT("Wilayah ", dealercabang.no_atl)) as region')
            ->selectRaw('COUNT(*) as dealers')
            ->groupBy('dealercabang.no_atl', 'atl_users.nama', 'atl_users.username', 'wilayah_atl.nip_atl', 'wilayah_atl.nama_wilayah')
            ->orderByDesc('dealers')
            ->limit(8)
            ->get();
        $mechanicsByAtl = DB::table('users')
            ->join('dealercabang', function ($join): void {
                $join->on('users.dealer', '=', 'dealercabang.dealer')->on('users.cabang', '=', 'dealercabang.cabang');
            })
            ->where('users.role', 0)
            ->whereNotNull('users.nip')
            ->whereNotNull('dealercabang.no_atl')
            ->where('dealercabang.no_atl', '!=', 0)
            ->selectRaw('dealercabang.no_atl, COUNT(DISTINCT users.nip) as total')
            ->groupBy('dealercabang.no_atl')
            ->pluck('total', 'no_atl');
        $atlSummaries = $atlSummaries->map(function ($atl) use ($mechanicsByAtl) {
                $atl->mechanics = (int) ($mechanicsByAtl[$atl->urutan] ?? 0);
                return $atl;
            });

        $dealerSummaries = (clone $dealerQuery)
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            })
            ->whereNotNull('no_atl')
            ->where('no_atl', '!=', 0)
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'no_atl')
            ->orderBy('dealer')
            ->limit(8)
            ->get();
        $dealerPairs = $dealerSummaries->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->values();
        $mechanicsByDealer = DB::table('users')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
            ->where('role', 0)
            ->whereNotNull('nip')
            ->selectRaw("CONCAT(dealer, '|', cabang) as dealer_key, COUNT(*) as total")
            ->groupBy('dealer_key')
            ->pluck('total', 'dealer_key');
        $dealerSummaries = $dealerSummaries->map(function ($dealer) use ($mechanicsByDealer) {
                $dealer->mechanics = (int) ($mechanicsByDealer[$dealer->dealer.'|'.$dealer->cabang] ?? 0);
                return $dealer;
            });
        $officePerformance = (object) [
            'unit_entry' => 0,
            'unit_ac' => 0,
            'pekerjaan_total' => (int) ($workPerformance->unit_entry ?? 0),
            'omset_total' => 0,
        ];
        $productivity = (object) [
            'unit_per_mechanic' => 0,
            'omset_per_dealer' => 0,
        ];
        $postcheckRatio = (object) ['ratio' => 0];

        $kpis['unit_entry'] = (int) ($workPerformance->unit_entry ?? 0);
        $kpis['unit_ac'] = (int) ($workPerformance->unit_ac ?? 0);
        $kpis['omset_total'] = (float) ($officePerformance->omset_total ?? 0);
        $kpis['postcheck_ratio'] = (float) ($postcheckRatio->ratio ?? 0);

        return [
            'kpis' => $kpis,
            'atls' => DB::table('wilayah_atl')
                ->leftJoin('users', 'users.nip', '=', 'wilayah_atl.nip_atl')
                ->select([
                    'wilayah_atl.urutan',
                    'wilayah_atl.nip_atl',
                    'wilayah_atl.nama_wilayah',
                    'users.nama',
                    'users.username',
                ])
                ->orderBy('wilayah_atl.nama_wilayah')
                ->get(),
            'dealers' => $dealerSummaries,
            'mechanics' => DB::table('users')
                ->where('role', 0)
                ->whereNotNull('nip')
                ->whereNotNull('dealer')
                ->whereNotNull('cabang')
                ->orderBy('nama')
                ->limit(8)
                ->get(),
            'allDealers' => collect(),
            'atlSummaries' => $atlSummaries,
            'dealerSummaries' => $dealerSummaries,
            'officePerformance' => $officePerformance,
            'productivity' => $productivity,
            'postcheckRatio' => $postcheckRatio,
            'recentAttendances' => DB::table('presensi')
                ->when($latestAttendanceDate, function (Builder $query) use ($latestAttendanceDate): void {
                    $query->where('date', $latestAttendanceDate);
                })
                ->latest('date')
                ->latest('time')
                ->limit(8)
                ->get(),
            'recentChecks' => collect(),
            'atlChart' => [
                'labels' => $atlChartRows->map(fn ($row) => (string) ($row->nama ?? $row->username ?? $row->nama_wilayah ?? 'ATL '.$row->no_atl))->values()->all(),
                'dealers' => $atlChartRows->pluck('total_dealer')->map(fn ($value) => (int) $value)->values()->all(),
                'regions' => $atlChartRows->map(fn ($row) => (string) ($row->nama_wilayah ?? ''))->values()->all(),
            ],
        ];
    }

    public function dealerOptions(Request $request)
    {
        $query = DB::table('dealercabang')
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            })
            ->when($request->filled('atl_id'), function (Builder $query) use ($request): void {
                $query->where('no_atl', $request->integer('atl_id'));
            })
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->query('search').'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nama_dealer', 'like', $search)
                        ->orWhere('dealer', 'like', $search)
                        ->orWhere('cabang', 'like', $search)
                        ->orWhere('kotakab', 'like', $search)
                        ->orWhere('area', 'like', $search)
                        ->orWhere('wilayah', 'like', $search)
                        ->orWhere('alamat', 'like', $search);
                });
            })
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab')
            ->orderBy('dealer')
            ->orderBy('cabang')
            ->limit(300)
            ->get()
            ->map(fn ($dealer) => [
                'id' => $dealer->id,
                'label' => ($dealer->nama_dealer ?: trim(($dealer->dealer ?? '').' '.($dealer->cabang ?? ''))).($dealer->kotakab ? ' - '.$dealer->kotakab : ''),
            ]);

        return response()->json($query);
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

    private function mechanicCountForDealers($dealers): int
    {
        if ($dealers->isEmpty()) {
            return 0;
        }

        return DB::table('users')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealers->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values())
            ->where('role', 0)
            ->whereNotNull('nip')
            ->where(function (Builder $query): void {
                $query->whereNull('delete_at')->orWhere('delete_at', '>', now('Asia/Jakarta'));
            })
            ->where(function (Builder $query): void {
                $query->whereNull('resign_date')->orWhere('resign_date', '>', now('Asia/Jakarta')->toDateString());
            })
            ->count();
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
                    ->where('dealer', $dealer->dealer)
                    ->where('cabang', $dealer->cabang)
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
