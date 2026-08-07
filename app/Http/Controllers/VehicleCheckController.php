<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VehicleCheckController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        return $this->renderChecks($request, 'Monitoring Pemeriksaan', 'Alur precheck, postcheck, pending, dan verifikasi kendaraan pada periode terpilih.');
    }

    public function pendingPostcheck(Request $request): View
    {
        return $this->renderChecks($request, 'Menunggu Postcheck', 'Precheck yang belum memiliki pasangan postcheck dan perlu ditindaklanjuti.', 'pending');
    }

    public function verification(Request $request): View
    {
        return $this->renderChecks($request, 'Perlu Verifikasi', 'Postcheck dengan hasil/catatan yang perlu divalidasi ulang.', 'verification');
    }

    private function renderChecks(Request $request, string $title, string $description, string $mode = 'monitoring'): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $period = (string) $request->query('period', $this->defaultInspectionPeriod());
        $dealerRows = $this->visibleDealerQuery($user)
            ->when($role === 'soh' && $request->filled('atl_id'), function (Builder $query) use ($request): void {
                $query->where('no_atl', $request->integer('atl_id'));
            })
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            })
            ->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')
            ->get();
        $dealerRows = $this->dealerRowsWithInspectionFallback($dealerRows, $request, $role);
        $dealerPairs = $dealerRows->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $dealerIds = $dealerRows->pluck('id')->values();
        $period = $this->periodWithInspectionFallback($period, $dealerPairs, $dealerIds);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $period.'-01 00:00:00', 'Asia/Jakarta')->startOfMonth();
        $end = (clone $start)->addMonth();

        $precheckBase = DB::table('precheck')
            ->when($dealerPairs->isNotEmpty(), function (Builder $query) use ($dealerRows): void {
                $query->where(function (Builder $query) use ($dealerRows): void {
                    foreach ($dealerRows as $dealer) {
                        $query->orWhere(function (Builder $query) use ($dealer): void {
                            $query->where('dealer', $dealer->dealer)->where('cabang', $dealer->cabang);
                        });
                    }
                });
            }, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'));

        $postcheckBase = DB::table('postcheck')
            ->when($dealerIds->isNotEmpty(), function (Builder $query) use ($dealerIds): void {
                $query->whereIn('dealercabang_id', $dealerIds);
            }, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'));

        $kpiCacheKey = 'inspections:kpis:'.md5($role.'|'.$period.'|'.$mode.'|'.$dealerIds->implode(',').'|'.$dealerPairs->implode(','));
        $inspectionKpis = Cache::remember($kpiCacheKey, now()->addMinutes(10), function () use ($precheckBase, $postcheckBase): array {
            $precheckCount = (clone $precheckBase)->count();
            $postcheckCount = (clone $postcheckBase)->count();
            $completedCount = (clone $postcheckBase)->whereNotNull('hasil')->where('hasil', '!=', '')->count();
            $verificationCount = (clone $postcheckBase)->where(function (Builder $query): void {
                $query->whereNull('hasil')
                    ->orWhere('hasil', '')
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('catatan')->where('catatan', '!=', '')->where('catatan', '!=', '-');
                    });
            })->count();

            return compact('precheckCount', 'postcheckCount', 'completedCount', 'verificationCount');
        });
        $precheckCount = $inspectionKpis['precheckCount'];
        $postcheckCount = $inspectionKpis['postcheckCount'];
        $completedCount = $inspectionKpis['completedCount'];
        $verificationCount = $inspectionKpis['verificationCount'];
        $pendingCount = max(0, $precheckCount - $postcheckCount);
        $ratio = $precheckCount > 0 ? round(($postcheckCount / $precheckCount) * 100, 1) : 0;

        $checks = match ($mode) {
            'pending' => $this->pendingRows($precheckBase, $postcheckBase),
            'verification' => (clone $postcheckBase)->where(function (Builder $query): void {
                $query->whereNull('hasil')
                    ->orWhere('hasil', '')
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('catatan')->where('catatan', '!=', '')->where('catatan', '!=', '-');
                    });
            })->selectRaw("'postcheck' as source, id, nowo, noplat, jenismobil, dealer, cabang, nip, teknisi, precheck_id, hasil, catatan, created_at"),
            default => (clone $postcheckBase)->selectRaw("'postcheck' as source, id, nowo, noplat, jenismobil, dealer, cabang, nip, teknisi, precheck_id, hasil, catatan, created_at"),
        };

        return view('vehicle-checks.index', [
            'role' => $role,
            'title' => $title,
            'description' => $description,
            'mode' => $mode,
            'checks' => $checks->latest('created_at')->paginate(12)->withQueryString(),
            'dealers' => Cache::remember('inspections:dealers:'.$user->id, now()->addMinutes(10), fn () => $this->dealerDropdownQuery($user)->orderBy('dealer')->get()),
            'atls' => $role === 'soh' ? Cache::remember('inspections:atls:'.$user->id, now()->addMinutes(10), fn () => $this->atlDropdownQuery($user)->orderBy('wilayah_atl.nama_wilayah')->get()) : collect(),
            'period' => $period,
            'kpis' => [
                'prechecks' => $precheckCount,
                'postchecks' => $postcheckCount,
                'pending' => $pendingCount,
                'completed' => $completedCount,
                'verification' => $verificationCount,
                'ratio' => $ratio,
            ],
        ]);
    }

    private function dealerRowsWithInspectionFallback($dealerRows, Request $request, string $role)
    {
        if ($dealerRows->isEmpty() || ! $this->hasInspectionData($dealerRows)) {
            return DB::table('dealercabang')
                ->where(function (Builder $query): void {
                    $query->whereNull('status_kontrak')->orWhere('status_kontrak', '!=', 'Tidak Aktif');
                })
                ->when($role === 'soh' && $request->filled('atl_id'), function (Builder $query) use ($request): void {
                    $query->where('no_atl', $request->integer('atl_id'));
                })
                ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                    $query->where('id', $request->integer('dealer_id'));
                })
                ->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl')
                ->get();
        }

        return $dealerRows;
    }

    private function hasInspectionData($dealerRows): bool
    {
        $dealerPairs = $dealerRows->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)->unique()->values();
        $dealerIds = $dealerRows->pluck('id')->values();

        return DB::table('postcheck')->whereIn('dealercabang_id', $dealerIds)->exists()
            || DB::table('precheck')->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)->exists();
    }

    private function defaultInspectionPeriod(): string
    {
        $latest = Cache::remember('inspections:default-period-source', now()->addMinutes(10), fn () => collect([
            DB::table('precheck')->max('created_at'),
            DB::table('postcheck')->max('created_at'),
        ])->filter()->max());

        return $latest ? substr((string) $latest, 0, 7) : now('Asia/Jakarta')->format('Y-m');
    }

    private function periodWithInspectionFallback(string $period, $dealerPairs, $dealerIds): string
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $period.'-01 00:00:00', 'Asia/Jakarta')->startOfMonth();
        $end = (clone $start)->addMonth();
        $hasData = DB::table('precheck')
            ->when($dealerPairs->isNotEmpty(), function (Builder $query) use ($dealerPairs): void {
                $query->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs);
            }, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $end->format('Y-m-d H:i:s'))
            ->exists()
            || DB::table('postcheck')
                ->when($dealerIds->isNotEmpty(), function (Builder $query) use ($dealerIds): void {
                    $query->whereIn('dealercabang_id', $dealerIds);
                }, fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
                ->where('created_at', '<', $end->format('Y-m-d H:i:s'))
                ->exists();

        if ($hasData) {
            return $period;
        }

        $latest = collect([
            DB::table('precheck')
                ->when($dealerPairs->isNotEmpty(), function (Builder $query) use ($dealerPairs): void {
                    $query->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs);
                }, fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->max('created_at'),
            DB::table('postcheck')
                ->when($dealerIds->isNotEmpty(), function (Builder $query) use ($dealerIds): void {
                    $query->whereIn('dealercabang_id', $dealerIds);
                }, fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->max('created_at'),
        ])->filter()->max();

        return $latest ? substr((string) $latest, 0, 7) : $period;
    }

    private function pendingRows(Builder $precheckBase, Builder $postcheckBase): Builder
    {
        $postcheckKeys = (clone $postcheckBase)
            ->whereNotNull('precheck_id')
            ->limit(5000)
            ->pluck('precheck_id')
            ->filter()
            ->values();

        return (clone $precheckBase)
            ->when($postcheckKeys->isNotEmpty(), function (Builder $query) use ($postcheckKeys): void {
                $query->whereNotIn('id', $postcheckKeys);
            })
            ->selectRaw("'precheck' as source, id, NULL as nowo, noplat, jenismobil, dealer, cabang, nip, teknisi, id as precheck_id, NULL as hasil, NULL as catatan, created_at");
    }
}
