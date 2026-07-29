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
        $query = DB::table('users')
            ->join('dealercabang', function ($join): void {
                $join->on('users.dealer', '=', 'dealercabang.dealer')->on('users.cabang', '=', 'dealercabang.cabang');
            })
            ->where('users.role', 0)
            ->whereNotNull('users.nip')
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('dealercabang.id', $request->integer('dealer_id'));
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

        $today = now('Asia/Jakarta')->toDateString();
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $visibleDealerIds = $this->dealerDropdownQuery($user)->pluck('id');

        return view('mechanics.index', [
            'role' => $this->activeRole($request, $user),
            'mechanics' => (clone $query)->orderBy('users.nama')->paginate(12)->withQueryString(),
            'dealers' => $this->dealerDropdownQuery($user)->orderBy('dealer')->limit(300)->get(),
            'attendanceToday' => $latestAttendanceDate ? DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->where('date', $latestAttendanceDate)->pluck('category', 'nip') : collect(),
            'jobCounts' => collect(),
            'kpis' => [
                'total' => DB::table('users')->where('role', 0)->whereNotNull('nip')->count(),
                'present_today' => $latestAttendanceDate ? DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->where('date', $latestAttendanceDate)->count() : 0,
                'late_today' => $latestAttendanceDate ? DB::table('presensi')->whereIn('dealercabang_id', $visibleDealerIds)->where('date', $latestAttendanceDate)->where('is_late', 1)->count() : 0,
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
