<?php

namespace App\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DealerCabangRepository
{
    /**
     * Get auth role filter data if applicable
     *
     * @return array|null ['column' => string, 'value' => mixed]
     */
    private function getAuthRoleFilter(): ?array
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->role == 2) {
                return ['column' => 'soh', 'value' => $user->nip];
            } elseif ($user->role == 1) {
                return ['column' => 'atl', 'value' => $user->nip];
            }
        }

        return null;
    }

    /**
     * Get list of dealercabang.
     *
     * @return Builder
     */
    public function getDealerCabang($startDate = null, $endDate = null)
    {
        $query = DB::table('dealercabang as d')
            ->select('d.id', 'd.dealer', 'd.nama_dealer')
            ->where('d.nama_dealer', '!=', '')
            ->where(function ($q) {
                $q->whereNull('d.status_kontrak')
                    ->orWhere('d.status_kontrak', '!=', 'Tidak Aktif');
            });

        if ($startDate && $endDate) {
            $query->whereExists(function ($q) use ($startDate, $endDate) {
                $q->select(DB::raw(1))
                    ->from('data_pekerjaan as dp')
                    ->whereColumn('dp.dealer', 'd.dealer')
                    ->whereColumn('dp.cabang', 'd.cabang')
                    ->whereBetween('dp.tanggal', [$startDate, $endDate]);
            });
        }

        if ($filter = $this->getAuthRoleFilter()) {
            $query->where("d.{$filter['column']}", $filter['value']);
        }

        return $query->orderBy('d.dealer')->orderBy('d.nama_dealer')->orderBy('d.cabang');
    }

    /**
     * Get potentials data aggregated by cabang.
     *
     * @param  string|null  $startDate
     * @param  string|null  $endDate
     * @param  array  $selectedDealerIds
     * @return array
     */
    public function getPotentialsByCabang($startDate, $endDate, $selectedDealerIds = [])
    {
        $bindings = [$startDate, $endDate, $startDate, $endDate];

        $whereClause = "WHERE (d.status_kontrak IS NULL OR d.status_kontrak != 'Tidak Aktif') AND d.nama_dealer != ''";

        $selectedDealerIds = array_filter($selectedDealerIds);
        if (! empty($selectedDealerIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedDealerIds), '?'));
            $whereClause .= " AND d.id IN ($placeholders)";
            $bindings = array_merge($bindings, $selectedDealerIds);
        }

        if ($filter = $this->getAuthRoleFilter()) {
            $whereClause .= " AND d.{$filter['column']} = ?";
            $bindings[] = $filter['value'];
        }

        $sql = "SELECT
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                d.cabang,
                
                COALESCE(b.rp_unit_entry, 0) AS rp_unit_entry,
                b.period,
                COALESCE(SUM(a.unit_entry), 0) AS unit_entry,
                COALESCE(SUM(a.unit_ac), 0) AS unit_ac,
                COALESCE(SUM(a.omset_jasa), 0) AS omset_jasa,
                ROUND((COALESCE(SUM(a.unit_ac), 0) / NULLIF(SUM(a.unit_entry), 0)) * 100, 2) AS cr_percent,
                (SUM(a.omset_jasa) / NULLIF(SUM(a.unit_ac), 0)) AS rp_uac
            FROM dealercabang d
            LEFT JOIN (
                -- hitung perhari dulu
                SELECT 
                    dp.dealer, 
                    dp.cabang, 
                    MAX(dp.unit_entry) AS unit_entry,
                    COUNT(CASE WHEN dp.nopol != '' AND (dp.pekerjaan_jasa != 0 OR dp.pekerjaan_part != 0) THEN 1 END) AS unit_ac,
                    SUM(dp.omset_jasa) AS omset_jasa
                FROM data_pekerjaan dp
                WHERE dp.tanggal BETWEEN ? AND ?
                GROUP BY 
                    dp.dealer, 
                    dp.cabang, 
                    dp.tanggal
            ) AS a ON d.dealer = a.dealer AND d.cabang = a.cabang
            LEFT JOIN (
                SELECT 
                    potential_unit_entry.rp_unit_entry,
                    potential_unit_entry.period,
                    potential_unit_entry.id_dealercabang
                FROM potential_unit_entry
                WHERE potential_unit_entry.period BETWEEN ? AND ?
            ) AS b ON d.id = b.id_dealercabang 
            $whereClause
            -- AND d.via LIKE '%SNS'
            -- AND d.soh = '1708010004'
            -- AND d.atl = '2211490373'
            GROUP BY
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                d.cabang,
                b.rp_unit_entry,
                b.period
            ORDER BY
                d.dealer, d.nama_dealer, d.cabang
        ";

        return DB::select($sql, $bindings);
    }

    public function getPotentialByUnitEntry($startDate, $endDate, $selectedDealerIds = [])
    {
        $bindings = [$startDate, $endDate];

        $whereClause = "WHERE (d.status_kontrak IS NULL OR d.status_kontrak != 'Tidak Aktif') AND d.nama_dealer != ''";

        $selectedDealerIds = array_filter($selectedDealerIds);
        if (! empty($selectedDealerIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedDealerIds), '?'));
            $whereClause .= " AND d.id IN ($placeholders)";
            $bindings = array_merge($bindings, $selectedDealerIds);
        }

        if ($filter = $this->getAuthRoleFilter()) {
            $whereClause .= " AND d.{$filter['column']} = ?";
            $bindings[] = $filter['value'];
        }

        $sql = "SELECT
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                d.cabang,
                COALESCE(p.unit_entry, 0) AS unit_entry,
                COALESCE(p.rp_unit_entry, 0) AS rp_unit_entry,
                /*COALESCE(p.unit_entry * p.rp_unit_entry, 0) AS total_potential,*/
                p.period
            FROM dealercabang d
            LEFT JOIN (
                SELECT 
                    id_dealercabang,
                    SUM(unit_entry) as unit_entry,
                    AVG(rp_unit_entry) as rp_unit_entry,
                    MIN(period) as period
                FROM potential_unit_entry
                WHERE period BETWEEN ? AND ?
                GROUP BY id_dealercabang
            ) AS p ON d.id = p.id_dealercabang
            $whereClause
            ORDER BY d.dealer, d.nama_dealer, d.cabang
        ";

        return DB::select($sql, $bindings);
    }
}
