<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MechanicController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->visibleMechanicQuery($user)
            ->join('dealercabang', function ($join): void {
                $join->on('users.dealer', '=', 'dealercabang.dealer')->on('users.cabang', '=', 'dealercabang.cabang');
            })
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('dealercabang.id', $request->integer('dealer_id'));
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                if ($request->query('status') === 'active') {
                    $query->where(function (Builder $query): void {
                        $query->whereNull('users.resign_date')->orWhere('users.resign_date', '>', now('Asia/Jakarta')->toDateString());
                    });
                }

                if ($request->query('status') === 'resigned') {
                    $query->whereNotNull('users.resign_date')->where('users.resign_date', '<=', now('Asia/Jakarta')->toDateString());
                }
            })
            ->when($request->filled('company'), function (Builder $query) use ($request): void {
                $query->where('users.company', $request->query('company'));
            })
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->query('search').'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('users.nip', 'like', $search)
                        ->orWhere('users.nama', 'like', $search)
                        ->orWhere('users.username', 'like', $search)
                        ->orWhere('users.dealer', 'like', $search)
                        ->orWhere('users.cabang', 'like', $search);
                });
            })
            ->select('users.*', 'dealercabang.no_atl');

        $perPage = min(100, max(10, (int) $request->query('per_page', 10)));
        $latestAttendanceDate = Cache::remember('mechanics:latest-attendance-date', now()->addMinutes(10), fn () => DB::table('presensi')->max('date'));
        $dealerDropdown = Cache::remember('mechanics:dealer-dropdown:'.$user->id, now()->addMinutes(10), fn () => $this->dealerDropdownQuery($user)->orderBy('dealer')->limit(300)->get());
        $visibleDealerIds = $dealerDropdown->pluck('id');
        $mechanicKpis = Cache::remember('mechanics:kpis:'.$user->id.':'.$latestAttendanceDate, now()->addMinutes(10), fn () => [
            'total' => DB::table('users')->where('role', 0)->whereNotNull('nip')->count(),
            'present_today' => $latestAttendanceDate ? DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->where('date', $latestAttendanceDate)->count() : 0,
            'late_today' => $latestAttendanceDate ? DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->where('date', $latestAttendanceDate)->where('is_late', 1)->count() : 0,
            'latest_date' => $latestAttendanceDate,
        ]);

        return view('mechanics.index', [
            'role' => $this->activeRole($request, $user),
            'mechanics' => (clone $query)->orderBy('users.nama')->paginate($perPage)->withQueryString(),
            'dealers' => $dealerDropdown,
            'companies' => Cache::remember('mechanics:companies', now()->addMinutes(30), fn () => DB::table('users')->where('role', 0)->whereNotNull('company')->where('company', '!=', '')->distinct()->orderBy('company')->pluck('company')),
            'perPage' => $perPage,
            'attendanceToday' => collect(),
            'jobCounts' => collect(),
            'kpis' => $mechanicKpis,
        ]);
    }

    public function show(Request $request, int $mechanic): View
    {
        $user = $request->user();
        $mechanicData = $this->visibleMechanicQuery($user)
            ->where('id', $mechanic)
            ->first();

        if (! $mechanicData && $user->dashboard_role === 'soh') {
            $mechanicData = DB::table('users')
                ->where('id', $mechanic)
                ->where('role', 0)
                ->whereNotNull('nip')
                ->first();
        }

        abort_unless($mechanicData, 403);

        $dealer = DB::table('dealercabang')
            ->where('dealer', $mechanicData->dealer)
            ->where('cabang', $mechanicData->cabang)
            ->first();
        $attendances = DB::table('presensi')
            ->where('nip', $mechanicData->nip)
            ->latest('date')
            ->latest('time')
            ->paginate(10, ['*'], 'attendance_page')
            ->withQueryString();
        $activityRows = $this->mechanicCheckRows($mechanicData);
        $jobPage = max(1, (int) $request->query('job_page', 1));
        $checks = new LengthAwarePaginator(
            $activityRows->forPage($jobPage, 10)->values(),
            $activityRows->count(),
            10,
            $jobPage,
            ['path' => $request->url(), 'pageName' => 'job_page']
        );
        $checks->appends($request->query());
        $jobStats = Cache::remember("mechanic:job-stats:{$mechanicData->nip}", now()->addMinutes(10), function () use ($mechanicData): array {
            $baseQuery = DB::table('postcheck')->where('nip', $mechanicData->nip);
            $total = (clone $baseQuery)->count();
            $acJobs = (clone $baseQuery)
                ->where(function (Builder $query): void {
                    foreach ($this->acJobKeywords() as $keyword) {
                        $query->orWhere('hasil', 'like', "%{$keyword}%")
                            ->orWhere('catatan', 'like', "%{$keyword}%");
                    }
                })
                ->count();

            return [
                'total' => $total,
                'ac' => $acJobs,
                'non_ac' => max(0, $total - $acJobs),
            ];
        });
        $attendanceTotal = Cache::remember("mechanic:attendance-total:{$mechanicData->nip}", now()->addMinutes(10), fn () => DB::table('presensi')->where('nip', $mechanicData->nip)->count());
        $lateTotal = Cache::remember("mechanic:late-total:{$mechanicData->nip}", now()->addMinutes(10), fn () => DB::table('presensi')->where('nip', $mechanicData->nip)->where('is_late', 1)->count());

        return view('mechanics.show', [
            'role' => $this->activeRole($request, $user),
            'mechanic' => $mechanicData,
            'attendances' => $attendances,
            'checks' => $checks,
            'dealer' => $dealer,
            'kpis' => [
                'attendance' => $attendanceTotal,
                'jobs' => $jobStats['total'],
                'ac_jobs' => $jobStats['ac'],
                'non_ac_jobs' => $jobStats['non_ac'],
                'late' => $lateTotal,
            ],
        ]);
    }

    private function isAcJob($check): bool
    {
        $text = strtolower((string) (($check->hasil ?? '').' '.($check->catatan ?? '')));

        foreach ($this->acJobKeywords() as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function acJobKeywords(): array
    {
        return ['ac', 'freon', 'evaporator', 'blower', 'kompresor', 'compressor', 'dryer', 'filter', 'suhu', 'dingin'];
    }

    private function mechanicCheckRows($mechanic)
    {
        $name = $mechanic->nama ?? $mechanic->username ?? '';

        return DB::table('precheck')
            ->where(function (Builder $query) use ($mechanic, $name): void {
                $query->where('nip', $mechanic->nip)
                    ->orWhere('teknisi', $mechanic->nip);

                if ($name !== '') {
                    $query->orWhere('teknisi', $name);
                }
            })
            ->select('created_at', 'dealer', 'cabang', 'noplat', 'teknisi', DB::raw("'Precheck' as type"), DB::raw("COALESCE(jenismobil, '-') as hasil"), DB::raw("NULL as catatan"))
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->merge(DB::table('postcheck')
                ->where(function (Builder $query) use ($mechanic, $name): void {
                    $query->where('nip', $mechanic->nip)
                        ->orWhere('teknisi', $mechanic->nip);

                    if ($name !== '') {
                        $query->orWhere('teknisi', $name);
                    }
                })
                ->select('created_at', 'dealer', 'cabang', 'noplat', 'teknisi', DB::raw("'Postcheck' as type"), DB::raw("COALESCE(hasil, '-') as hasil"), 'catatan')
                ->latest('created_at')
                ->limit(50)
                ->get())
            ->sortByDesc('created_at')
            ->values();
    }
}
