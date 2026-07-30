<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $dealerRows = $this->visibleDealerQuery($user)->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')->get();

        if ($dealerRows->isEmpty()) {
            $dealerRows = DB::table('dealercabang')->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif')->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')->get();
        }

        $dealerIds = $dealerRows->pluck('id')->values();
        $dealerPairs = $dealerRows->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $latestAttendanceDate = DB::table('presensi')->max('date');
        $notifications = collect();

        DB::table('presensi')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
            ->when($latestAttendanceDate, fn (Builder $query) => $query->where('date', $latestAttendanceDate))
            ->where('is_late', 1)
            ->latest('date')
            ->latest('time')
            ->limit(8)
            ->get()
            ->each(fn ($row) => $notifications->push((object) [
                'type' => 'Presensi',
                'level' => 'warning',
                'title' => 'Mekanik terlambat presensi',
                'message' => ($row->name ?? '-').' terlambat di '.$row->dealer.' '.$row->cabang,
                'time' => trim(($row->date ?? '').' '.($row->time ?? '')),
                'route' => route('mechanics.attendances.daily', ['role' => $role]),
            ]));

        DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where(function (Builder $query): void {
                $query->whereNull('hasil')->orWhere('hasil', '')->orWhere(function (Builder $query): void {
                    $query->whereNotNull('catatan')->where('catatan', '!=', '')->where('catatan', '!=', '-');
                });
            })
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->each(fn ($row) => $notifications->push((object) [
                'type' => 'Verifikasi',
                'level' => 'danger',
                'title' => 'Postcheck perlu verifikasi',
                'message' => ($row->noplat ?? '-').' di '.$row->dealer.' '.$row->cabang,
                'time' => $row->created_at,
                'route' => route('inspections.verification', ['role' => $role]),
            ]));

        DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->where(function (Builder $query): void {
                $query->whereNull('precheck')->orWhere('precheck', '!=', 'true');
            })
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->each(fn ($row) => $notifications->push((object) [
                'type' => 'Postcheck',
                'level' => 'warning',
                'title' => 'Postcheck belum lengkap',
                'message' => ($row->noplat ?? '-').' belum terhubung precheck',
                'time' => $row->created_at,
                'route' => route('inspections.pending-postcheck', ['role' => $role]),
            ]));

        $notifications = $notifications->sortByDesc('time')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $paginatedNotifications = new LengthAwarePaginator(
            $notifications->forPage($page, $perPage)->values(),
            $notifications->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('notifications.index', [
            'role' => $role,
            'notifications' => $paginatedNotifications,
            'kpis' => [
                'total' => $notifications->count(),
                'danger' => $notifications->where('level', 'danger')->count(),
                'warning' => $notifications->where('level', 'warning')->count(),
            ],
        ]);
    }
}
