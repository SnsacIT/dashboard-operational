<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MechanicController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $dealerRows = $this->visibleDealerQuery($user)
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            })
            ->select('dealer', 'cabang')
            ->get();

        $query = $this->mechanicQueryForDealerRows($dealerRows)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->query('search').'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nip', 'like', $search)
                        ->orWhere('nama', 'like', $search)
                        ->orWhere('username', 'like', $search)
                        ->orWhere('dealer', 'like', $search)
                        ->orWhere('cabang', 'like', $search);
                });
            });

        $today = now('Asia/Jakarta')->toDateString();
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $visibleDealerIds = $this->visibleDealerQuery($user)->pluck('id');

        return view('mechanics.index', [
            'role' => $this->activeRole($request, $user),
            'mechanics' => (clone $query)->orderBy('nama')->paginate(12)->withQueryString(),
            'dealers' => $this->visibleDealerQuery($user)->orderBy('dealer')->get(),
            'attendanceToday' => DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->whereDate('date', $today)->pluck('category', 'nip'),
            'jobCounts' => DB::table('postcheck')->whereIn('dealercabang_id', $visibleDealerIds)->selectRaw('nip, COUNT(*) as total')->groupBy('nip')->pluck('total', 'nip'),
            'kpis' => [
                'total' => (clone $query)->count(),
                'present_today' => DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->whereDate('date', $today)->count(),
                'late_today' => DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->whereDate('date', $today)->where('is_late', 1)->count(),
                'latest_date' => $latestAttendanceDate,
            ],
        ]);
    }

    public function show(Request $request, int $mechanic): View
    {
        $user = $request->user();
        $mechanicData = $this->visibleMechanicQuery($user)
            ->where('id', $mechanic)
            ->first();

        abort_unless($mechanicData, 403);

        $dealer = DB::table('dealercabang')
            ->where('dealer', $mechanicData->dealer)
            ->where('cabang', $mechanicData->cabang)
            ->first();

        return view('mechanics.show', [
            'role' => $this->activeRole($request, $user),
            'mechanic' => $mechanicData,
            'attendances' => DB::table('presensi')->where('nip', $mechanicData->nip)->latest('date')->limit(10)->get(),
            'checks' => DB::table('postcheck')->where('nip', $mechanicData->nip)->latest('created_at')->limit(10)->get(),
            'dealer' => $dealer,
            'kpis' => [
                'attendance' => DB::table('presensi')->where('nip', $mechanicData->nip)->count(),
                'jobs' => DB::table('postcheck')->where('nip', $mechanicData->nip)->count(),
                'late' => DB::table('presensi')->where('nip', $mechanicData->nip)->where('is_late', 1)->count(),
            ],
        ]);
    }
}
