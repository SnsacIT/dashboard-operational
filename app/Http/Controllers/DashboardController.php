<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
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
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);

        $dealerQuery = DB::table('dealercabang')->where(function (Builder $query): void {
            $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
        });
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $atlChartRows = DB::table('dealercabang')
            ->leftJoin('wilayah_atl', 'wilayah_atl.urutan', '=', 'dealercabang.no_atl')
            ->leftJoin('users', 'users.nip', '=', 'wilayah_atl.nip_atl')
            ->where(function (Builder $query): void {
                $query->whereNull('dealercabang.status_kontrak')->orWhere('dealercabang.status_kontrak', '!=', 'Tidak Aktif');
            })
            ->whereNotNull('dealercabang.no_atl')
            ->selectRaw('dealercabang.no_atl, wilayah_atl.nama_wilayah, users.nama, users.username, COUNT(*) as total_dealer')
            ->groupBy('dealercabang.no_atl', 'wilayah_atl.nama_wilayah', 'users.nama', 'users.username')
            ->orderByDesc('total_dealer')
            ->limit(5)
            ->get();
        $latestPostcheckDate = $latestAttendanceDate ?: now('Asia/Jakarta')->toDateString();
        $workPerformance = DB::table('postcheck')
            ->whereDate('created_at', $latestPostcheckDate)
            ->selectRaw('COUNT(*) as unit_entry')
            ->selectRaw("COUNT(DISTINCT NULLIF(noplat, '')) as unit_ac")
            ->first();
        $potentialOpen = DB::table('postcheck')
            ->whereDate('created_at', $latestPostcheckDate)
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
            'present_latest' => $latestAttendanceDate ? DB::table('presensi')->whereDate('date', $latestAttendanceDate)->count() : 0,
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
            ->get()
            ->map(function ($atl) {
                $atl->mechanics = DB::table('users')
                    ->join('dealercabang', function ($join): void {
                        $join->on('users.dealer', '=', 'dealercabang.dealer')->on('users.cabang', '=', 'dealercabang.cabang');
                    })
                    ->where('dealercabang.no_atl', $atl->urutan)
                    ->where('users.role', 0)
                    ->whereNotNull('users.nip')
                    ->count();

                return $atl;
            });

        $dealerSummaries = DB::table('dealercabang')
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            })
            ->whereNotNull('no_atl')
            ->where('no_atl', '!=', 0)
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'no_atl')
            ->orderBy('dealer')
            ->limit(8)
            ->get()
            ->map(function ($dealer) {
                $dealer->mechanics = DB::table('users')
                    ->where('dealer', $dealer->dealer)
                    ->where('cabang', $dealer->cabang)
                    ->where('role', 0)
                    ->whereNotNull('nip')
                    ->count();

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

        return view('dashboard.index', [
            'role' => $role,
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
            'allDealers' => DB::table('dealercabang')
                ->where(function (Builder $query): void {
                    $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
                })
                ->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab')
                ->orderBy('dealer')
                ->orderBy('cabang')
                ->get(),
            'atlSummaries' => $atlSummaries,
            'dealerSummaries' => $dealerSummaries,
            'officePerformance' => $officePerformance,
            'productivity' => $productivity,
            'postcheckRatio' => $postcheckRatio,
            'recentAttendances' => DB::table('presensi')
                ->when($latestAttendanceDate, function (Builder $query) use ($latestAttendanceDate): void {
                    $query->whereDate('date', $latestAttendanceDate);
                })
                ->latest('date')
                ->latest('time')
                ->limit(8)
                ->get(),
            'recentChecks' => collect(),
            'atlChart' => [
                'labels' => $atlChartRows->map(fn ($row) => $row->nama ?? $row->username ?? $row->nama_wilayah ?? 'ATL '.$row->no_atl)->values(),
                'dealers' => $atlChartRows->pluck('total_dealer')->map(fn ($value) => (int) $value)->values(),
                'regions' => $atlChartRows->pluck('nama_wilayah')->values(),
            ],
            'period' => $period,
        ]);
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
