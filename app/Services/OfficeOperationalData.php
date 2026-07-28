<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficeOperationalData
{
    public function monthlyPerformance(Collection $dealers, string $period): object
    {
        $dealerPairs = $this->dealerPairs($dealers);

        if ($dealerPairs->isEmpty() || ! $this->tableExists('data_pekerjaan')) {
            return (object) [
                'unit_entry' => 0,
                'unit_ac' => 0,
                'unit_nonac' => 0,
                'pekerjaan_total' => 0,
                'omset_total' => 0,
            ];
        }

        return DB::table('data_pekerjaan as dp')
            ->join('dealercabang as dc', function ($join): void {
                $join->on('dp.dealer', '=', 'dc.dealer')->on('dp.cabang', '=', 'dc.cabang');
            })
            ->whereIn(DB::raw("CONCAT(dc.dealer, '|', dc.cabang)"), $dealerPairs)
            ->where('dp.tanggal', 'like', $period.'%')
            ->selectRaw('COALESCE(SUM(dp.unit_entry), 0) as unit_entry')
            ->selectRaw("COUNT(CASE WHEN dp.nopol IS NOT NULL AND dp.nopol != '' AND (COALESCE(dp.pekerjaan_jasa, 0) > 0 OR COALESCE(dp.pekerjaan_part, 0) > 0) THEN 1 END) as unit_ac")
            ->selectRaw("COUNT(CASE WHEN dp.nopol IS NOT NULL AND dp.nopol != '' AND COALESCE(dp.pekerjaan_nonac, 0) > 0 THEN 1 END) as unit_nonac")
            ->selectRaw('COALESCE(SUM(COALESCE(dp.pekerjaan_jasa, 0) + COALESCE(dp.pekerjaan_nonac, 0) + COALESCE(dp.pekerjaan_part, 0) + COALESCE(dp.pekerjaan_spooring, 0)), 0) as pekerjaan_total')
            ->selectRaw('COALESCE(SUM(COALESCE(dp.omset_jasa, 0) + COALESCE(dp.omset_nonac, 0) + COALESCE(dp.omset_part, 0) + COALESCE(dp.omset_spooring, 0)), 0) as omset_total')
            ->first();
    }

    public function dealerPerformance(Collection $dealers, string $period): Collection
    {
        $dealerPairs = $this->dealerPairs($dealers);

        if ($dealerPairs->isEmpty() || ! $this->tableExists('data_pekerjaan')) {
            return collect();
        }

        return DB::table('data_pekerjaan as dp')
            ->join('dealercabang as dc', function ($join): void {
                $join->on('dp.dealer', '=', 'dc.dealer')->on('dp.cabang', '=', 'dc.cabang');
            })
            ->whereIn(DB::raw("CONCAT(dc.dealer, '|', dc.cabang)"), $dealerPairs)
            ->where('dp.tanggal', 'like', $period.'%')
            ->selectRaw('dc.id as dealercabang_id, dc.dealer, dc.cabang')
            ->selectRaw("COUNT(DISTINCT CASE WHEN dp.nopol IS NOT NULL AND dp.nopol != '' THEN CONCAT(REPLACE(dp.nopol, ' ', ''), '|', DATE(dp.tanggal)) END) as unit_total")
            ->selectRaw('COALESCE(SUM(COALESCE(dp.pekerjaan_jasa, 0) + COALESCE(dp.pekerjaan_nonac, 0) + COALESCE(dp.pekerjaan_part, 0) + COALESCE(dp.pekerjaan_spooring, 0)), 0) as pekerjaan_total')
            ->selectRaw('COALESCE(SUM(COALESCE(dp.omset_jasa, 0) + COALESCE(dp.omset_nonac, 0) + COALESCE(dp.omset_part, 0) + COALESCE(dp.omset_spooring, 0)), 0) as omset_total')
            ->groupBy('dc.id', 'dc.dealer', 'dc.cabang')
            ->orderByDesc('omset_total')
            ->get()
            ->keyBy('dealercabang_id');
    }

    public function productivity(Collection $dealers, string $period): object
    {
        $performance = $this->monthlyPerformance($dealers, $period);
        $mechanics = $this->mechanicCount($dealers);
        $dealerCount = $dealers->count();
        $unit = (int) (($performance->unit_ac ?? 0) + ($performance->unit_nonac ?? 0));
        $omset = (float) ($performance->omset_total ?? 0);

        return (object) [
            'unit_per_mechanic' => $mechanics > 0 ? round($unit / $mechanics, 1) : 0,
            'omset_per_mechanic' => $mechanics > 0 ? round($omset / $mechanics, 0) : 0,
            'unit_per_dealer' => $dealerCount > 0 ? round($unit / $dealerCount, 1) : 0,
            'omset_per_dealer' => $dealerCount > 0 ? round($omset / $dealerCount, 0) : 0,
        ];
    }

    public function postcheckRatio(Collection $dealers, string $period): object
    {
        $dealerPairs = $this->dealerPairs($dealers);

        if ($dealerPairs->isEmpty() || ! $this->tableExists('data_pekerjaan') || ! $this->tableExists('data_pekerjaan_postcheck')) {
            return (object) ['unit_ac' => 0, 'postchecks' => 0, 'verified' => 0, 'ratio' => 0];
        }

        $unitAc = DB::table('data_pekerjaan as dp')
            ->join('dealercabang as dc', function ($join): void {
                $join->on('dp.dealer', '=', 'dc.dealer')->on('dp.cabang', '=', 'dc.cabang');
            })
            ->whereIn(DB::raw("CONCAT(dc.dealer, '|', dc.cabang)"), $dealerPairs)
            ->where('dp.tanggal', 'like', $period.'%')
            ->whereNotNull('dp.nopol')
            ->where('dp.nopol', '!=', '')
            ->where(function ($query): void {
                $query->where('dp.pekerjaan_jasa', '>', 0)->orWhere('dp.pekerjaan_part', '>', 0);
            })
            ->count(DB::raw("DISTINCT CONCAT(REPLACE(dp.nopol, ' ', ''), '|', DATE(dp.tanggal))"));

        $postchecks = DB::table('data_pekerjaan_postcheck as pc')
            ->join('dealercabang as dc', function ($join): void {
                $join->on('pc.dealer', '=', 'dc.dealer')->on('pc.cabang', '=', 'dc.cabang');
            })
            ->whereIn(DB::raw("CONCAT(dc.dealer, '|', dc.cabang)"), $dealerPairs)
            ->where('pc.tanggal', 'like', $period.'%')
            ->count(DB::raw("DISTINCT CONCAT(REPLACE(pc.nopol, ' ', ''), '|', DATE(pc.tanggal))"));

        $verified = DB::table('data_pekerjaan_postcheck as pc')
            ->join('data_pekerjaan as dp', function ($join): void {
                $join->on('pc.dealer', '=', 'dp.dealer')
                    ->on('pc.cabang', '=', 'dp.cabang')
                    ->whereRaw("REPLACE(dp.nopol, ' ', '') = REPLACE(COALESCE(pc.nopol_admin, pc.nopol), ' ', '')")
                    ->whereRaw('YEAR(dp.tanggal) = YEAR(pc.tanggal)')
                    ->whereRaw('MONTH(dp.tanggal) = MONTH(pc.tanggal)');
            })
            ->join('dealercabang as dc', function ($join): void {
                $join->on('pc.dealer', '=', 'dc.dealer')->on('pc.cabang', '=', 'dc.cabang');
            })
            ->whereIn(DB::raw("CONCAT(dc.dealer, '|', dc.cabang)"), $dealerPairs)
            ->where('pc.tanggal', 'like', $period.'%')
            ->count(DB::raw("DISTINCT CONCAT(REPLACE(pc.nopol, ' ', ''), '|', DATE(pc.tanggal))"));

        return (object) [
            'unit_ac' => $unitAc,
            'postchecks' => $postchecks,
            'verified' => $verified,
            'ratio' => $unitAc > 0 ? round(($postchecks / $unitAc) * 100, 1) : 0,
        ];
    }

    private function mechanicCount(Collection $dealers): int
    {
        $dealerPairs = $this->dealerPairs($dealers);

        if ($dealerPairs->isEmpty()) {
            return 0;
        }

        return DB::table('users')
            ->whereIn(DB::raw("CONCAT(dealer, '|', cabang)"), $dealerPairs)
            ->whereNotNull('nip')
            ->where(function ($query): void {
                $query->whereNull('delete_at')->orWhere('delete_at', '>', now('Asia/Jakarta'));
            })
            ->where(function ($query): void {
                $query->whereNull('resign_date')->orWhere('resign_date', '>', now('Asia/Jakarta')->toDateString());
            })
            ->count();
    }

    private function dealerPairs(Collection $dealers): Collection
    {
        return $dealers
            ->filter(fn ($dealer) => filled($dealer->dealer ?? null) && filled($dealer->cabang ?? null))
            ->map(fn ($dealer) => $dealer->dealer.'|'.$dealer->cabang)
            ->unique()
            ->values();
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
