<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\OfficeOperationalData;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AtlController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request, OfficeOperationalData $officeData): View
    {
        abort_unless($request->user()->dashboard_role === 'soh', 403);

        $user = $request->user();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $atls = $this->atlDropdownQuery($user)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->query('search').'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('wilayah_atl.nama_wilayah', 'like', $search)
                        ->orWhere('users.nama', 'like', $search)
                        ->orWhere('users.username', 'like', $search)
                        ->orWhere('wilayah_atl.nip_atl', 'like', $search);
                });
            })
            ->orderBy('wilayah_atl.nama_wilayah')
            ->get();

        $visibleDealers = $this->dealerDropdownQuery($user)->select('id', 'dealer', 'cabang', 'no_atl')->get();
        $dealerIds = $visibleDealers->pluck('id');
        $attendanceDate = DB::table('presensi')->whereIn('dealercabang_id', $dealerIds)->max('date');

        $dealerCounts = $visibleDealers
            ->groupBy('no_atl')
            ->map->count();
        $dealerRowsByAtl = $visibleDealers->groupBy('no_atl');
        $mechanicCounts = DB::table('users')
            ->where(function (Builder $query) use ($visibleDealers): void {
                foreach ($visibleDealers as $dealer) {
                    $query->orWhere(function (Builder $query) use ($dealer): void {
                        $query->where('dealer', $dealer->dealer)->where('cabang', $dealer->cabang);
                    });
                }
            })
            ->whereNotNull('nip')
            ->where('role', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('delete_at')->orWhere('delete_at', '>', now('Asia/Jakarta'));
            })
            ->where(function (Builder $query): void {
                $query->whereNull('resign_date')->orWhere('resign_date', '>', now('Asia/Jakarta')->toDateString());
            })
            ->selectRaw('dealer, cabang, COUNT(*) as total')
            ->groupBy('dealer', 'cabang')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->dealer.'|'.$row->cabang => $row->total]);
        $attendanceCounts = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->when($attendanceDate, function (Builder $query) use ($attendanceDate): void {
                $query->whereDate('date', $attendanceDate);
            })
            ->join('dealercabang', 'dealercabang.id', '=', 'presensi.dealercabang_id')
            ->selectRaw('no_atl, COUNT(*) as total')
            ->groupBy('no_atl')
            ->pluck('total', 'no_atl');
        $postcheckCounts = collect();

        $summaries = $atls->map(function ($atl) use ($dealerRowsByAtl, $dealerCounts, $mechanicCounts, $attendanceCounts, $postcheckCounts) {
            $dealers = $dealerRowsByAtl[(string) $atl->urutan] ?? collect();
            $mechanics = $dealers->sum(fn ($dealer) => (int) ($mechanicCounts[$dealer->dealer.'|'.$dealer->cabang] ?? 0));
            $postchecks = (int) ($postcheckCounts[$atl->urutan] ?? 0);
            $dealerTotal = (int) ($dealerCounts[$atl->urutan] ?? 0);
            $score = min(100, round(($dealerTotal * 8) + ($mechanics * 2) + ($postchecks * 0.5)));

            return (object) [
                'urutan' => $atl->urutan,
                'nip_atl' => $atl->nip_atl,
                'name' => $atl->nama ?? $atl->username ?? $atl->nip_atl,
                'region' => $atl->nama_wilayah,
                'dealers' => $dealerTotal,
                'mechanics' => $mechanics,
                'present_today' => (int) ($attendanceCounts[$atl->urutan] ?? 0),
                'postchecks' => $postchecks,
                'unit_entry' => 0,
                'omset_total' => 0,
                'score' => $score,
            ];
        });

        return view('atls.index', [
            'role' => 'soh',
            'atls' => $summaries,
            'period' => $period,
            'kpis' => [
                'total' => max($summaries->count(), $visibleDealers->pluck('no_atl')->filter()->unique()->count()),
                'dealers' => $visibleDealers->count(),
                'mechanics' => $mechanicCounts->sum(),
                'present_today' => $attendanceCounts->sum(),
                'attendance_date' => $attendanceDate,
            ],
        ]);
    }

    public function show(Request $request, OfficeOperationalData $officeData, int $atl): View
    {
        abort_unless($request->user()->dashboard_role === 'soh', 403);

        $user = $request->user();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $atlData = $this->atlDropdownQuery($user)
            ->where('wilayah_atl.urutan', $atl)
            ->first();

        abort_unless($atlData, 403);

        $dealers = $this->dealerDropdownQuery($user)->where('no_atl', $atl)->orderBy('dealer')->get();
        $dealerIds = $dealers->pluck('id');
        $attendanceDate = DB::table('presensi')->whereIn('dealercabang_id', $dealerIds)->max('date');
        $performance = (object) ['unit_entry' => 0, 'omset_total' => 0];
        $postcheckRatio = (object) ['ratio' => 0];

        return view('atls.show', [
            'role' => 'soh',
            'atl' => $atlData,
            'dealers' => $dealers,
            'period' => $period,
            'officePerformance' => $performance,
            'postcheckRatio' => $postcheckRatio,
            'kpis' => [
                'dealers' => $dealers->count(),
                'mechanics' => $this->mechanicQueryForDealerRows($dealers)->count(),
                'present_today' => DB::table('presensi')->whereIn('dealercabang_id', $dealerIds)->when($attendanceDate, function (Builder $query) use ($attendanceDate): void {
                    $query->whereDate('date', $attendanceDate);
                })->count(),
                'postchecks' => 0,
                'attendance_date' => $attendanceDate,
            ],
        ]);
    }

    public function comparison(Request $request, OfficeOperationalData $officeData): View
    {
        abort_unless($request->user()->dashboard_role === 'soh', 403);

        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $summaries = $this->comparisonSummaries($request, $officeData, $period);

        return view('atls.comparison', [
            'role' => 'soh',
            'period' => $period,
            'summaries' => $summaries,
            'leader' => $summaries->sortByDesc('score')->first(),
            'highestOmset' => $summaries->sortByDesc('omset_total')->first(),
            'highestPresence' => $summaries->sortByDesc('present_today')->first(),
        ]);
    }

    private function comparisonSummaries(Request $request, OfficeOperationalData $officeData, string $period)
    {
        $user = $request->user();
        $atls = $this->atlDropdownQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get();
        $dealers = $this->dealerDropdownQuery($user)->select('id', 'dealer', 'cabang', 'no_atl')->get();
        $dealerIds = $dealers->pluck('id');
        $attendanceDate = DB::table('presensi')->whereIn('dealercabang_id', $dealerIds)->max('date');
        $dealersByAtl = $dealers->groupBy('no_atl');
        $attendanceCounts = DB::table('presensi')
            ->whereIn('dealercabang_id', $dealerIds)
            ->when($attendanceDate, function (Builder $query) use ($attendanceDate): void {
                $query->whereDate('date', $attendanceDate);
            })
            ->join('dealercabang', 'dealercabang.id', '=', 'presensi.dealercabang_id')
            ->selectRaw('no_atl, COUNT(*) as total')
            ->groupBy('no_atl')
            ->pluck('total', 'no_atl');
        $postcheckCounts = collect();

        return $atls->map(function ($atl) use ($dealersByAtl, $attendanceCounts, $postcheckCounts, $officeData, $period) {
            $dealerRows = $dealersByAtl[(string) $atl->urutan] ?? collect();
            $score = min(100, round(($dealerRows->count() * 8) + ((int) ($attendanceCounts[$atl->urutan] ?? 0) * 1.5) + ((int) ($postcheckCounts[$atl->urutan] ?? 0) * 0.5)));

            return (object) [
                'urutan' => $atl->urutan,
                'name' => $atl->nama ?? $atl->username ?? $atl->nip_atl,
                'region' => $atl->nama_wilayah,
                'dealers' => $dealerRows->count(),
                'present_today' => (int) ($attendanceCounts[$atl->urutan] ?? 0),
                'postchecks' => (int) ($postcheckCounts[$atl->urutan] ?? 0),
                'unit_entry' => 0,
                'omset_total' => 0,
                'unit_per_mechanic' => 0,
                'postcheck_ratio' => 0,
                'score' => $score,
            ];
        })->sortByDesc('score')->values();
    }
}
