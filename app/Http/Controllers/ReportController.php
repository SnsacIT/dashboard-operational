<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $period.'-01 00:00:00', 'Asia/Jakarta')->startOfMonth();
        $end = (clone $start)->addMonth();
        $dealerRows = $this->visibleDealerQuery($user)
            ->when($role === 'soh' && $request->filled('atl_id'), fn (Builder $query) => $query->where('no_atl', $request->integer('atl_id')))
            ->when($request->filled('dealer_id'), fn (Builder $query) => $query->where('id', $request->integer('dealer_id')))
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')
            ->get();

        if ($dealerRows->isEmpty()) {
            $dealerRows = DB::table('dealercabang')->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif')->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')->get();
        }

        $dealerIds = $dealerRows->pluck('id')->values();
        $dealerPairs = $dealerRows->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $prechecks = DB::table('precheck')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'))
            ->count();
        $postchecks = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'))
            ->count();

        $dealerPerformance = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'))
            ->selectRaw('dealer, cabang, dealercabang_id, COUNT(*) as postchecks, COUNT(DISTINCT NULLIF(noplat, "")) as unit_ac')
            ->groupBy('dealer', 'cabang', 'dealercabang_id')
            ->orderByDesc('postchecks')
            ->limit(10)
            ->get();

        $reports = collect([
            (object) ['name' => 'Laporan Dealer', 'description' => 'Ringkasan dealer, mekanik, unit AC, dan postcheck.', 'route' => route('dealers.index', ['role' => $role, 'period' => $period]), 'status' => 'Siap Preview'],
            (object) ['name' => 'Laporan Presensi', 'description' => 'Presensi harian dan rekap bulanan mekanik.', 'route' => route('mechanics.attendances.recap', ['role' => $role, 'period' => $period]), 'status' => 'Siap Preview'],
            (object) ['name' => 'Laporan Precheck/Postcheck', 'description' => 'Flow pemeriksaan, pending, dan verifikasi.', 'route' => route('inspections.index', ['role' => $role, 'period' => $period]), 'status' => 'Siap Preview'],
            (object) ['name' => 'Laporan Potensi', 'description' => 'Potensi open dan ranking tindak lanjut dealer.', 'route' => route('potentials.index', ['role' => $role, 'period' => $period]), 'status' => 'Siap Preview'],
        ]);

        return view('reports.index', [
            'role' => $role,
            'period' => $period,
            'dealers' => $this->dealerDropdownQuery($user)->orderBy('dealer')->get(),
            'atls' => $role === 'soh' ? $this->atlDropdownQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get() : collect(),
            'reports' => $reports,
            'dealerPerformance' => $dealerPerformance,
            'kpis' => [
                'dealers' => $dealerRows->count(),
                'mechanics' => DB::table('users')->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)->where('role', 0)->whereNotNull('nip')->count(),
                'attendance' => $latestAttendanceDate ? DB::table('presensi')->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)->where('date', $latestAttendanceDate)->count() : 0,
                'prechecks' => $prechecks,
                'postchecks' => $postchecks,
                'ratio' => $prechecks > 0 ? round(($postchecks / $prechecks) * 100, 1) : 0,
            ],
        ]);
    }
}
