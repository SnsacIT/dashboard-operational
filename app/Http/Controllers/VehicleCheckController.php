<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VehicleCheckController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        return $this->renderChecks($request, 'Monitoring Pemeriksaan', 'Seluruh aktivitas precheck dan postcheck pada periode terpilih.');
    }

    public function pendingPostcheck(Request $request): View
    {
        return $this->renderChecks($request, 'Menunggu Postcheck', 'Pemeriksaan yang terindikasi belum menyelesaikan postcheck.', true);
    }

    public function verification(Request $request): View
    {
        return $this->renderChecks($request, 'Perlu Verifikasi', 'Pemeriksaan dengan catatan/hasil kosong yang perlu diperiksa kembali.', false, true);
    }

    private function renderChecks(Request $request, string $title, string $description, bool $pendingOnly = false, bool $verificationOnly = false): View
    {
        $user = $request->user();
        $period = (string) $request->query('period', now('Asia/Jakarta')->format('Y-m'));
        $dealerIds = $this->visibleDealerQuery($user)
            ->when($request->filled('dealer_id'), function ($query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            })
            ->pluck('id');

        $prechecks = DB::table('precheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', substr($period, 5, 2))
            ->whereYear('created_at', substr($period, 0, 4));

        $postchecks = DB::table('postcheck')
            ->whereIn('dealercabang_id', $dealerIds)
            ->whereMonth('created_at', substr($period, 5, 2))
            ->whereYear('created_at', substr($period, 0, 4))
            ->when($pendingOnly, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('precheck')->orWhere('precheck', '!=', 'true');
                });
            })
            ->when($verificationOnly, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('hasil')->orWhere('hasil', '')->orWhereNull('catatan')->orWhere('catatan', '!=', '-');
                });
            });

        return view('vehicle-checks.index', [
            'role' => $this->activeRole($request, $user),
            'title' => $title,
            'description' => $description,
            'checks' => (clone $postchecks)->latest('created_at')->paginate(12)->withQueryString(),
            'dealers' => $this->dealerDropdownQuery($user)->orderBy('dealer')->get(),
            'period' => $period,
            'kpis' => [
                'work_orders' => (clone $postchecks)->count(),
                'prechecks' => (clone $prechecks)->count(),
                'pending' => DB::table('postcheck')->whereIn('dealercabang_id', $dealerIds)->where(function ($query): void {
                    $query->whereNull('precheck')->orWhere('precheck', '!=', 'true');
                })->count(),
                'completed' => (clone $postchecks)->whereNotNull('hasil')->count(),
                'verification' => DB::table('postcheck')->whereIn('dealercabang_id', $dealerIds)->where(function ($query): void {
                    $query->whereNull('hasil')->orWhere('hasil', '')->orWhereNull('catatan')->orWhere('catatan', '!=', '-');
                })->count(),
            ],
        ]);
    }
}
