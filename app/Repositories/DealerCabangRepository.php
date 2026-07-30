<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DealerCabangRepository
{
    /**
     * Get list of dealercabang.
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public function getDealerCabang()
    {
        $query = DB::table('dealercabang')
            ->select('id', 'nama_dealer')
            ->where('nama_dealer', '!=', '')
            ->where(function ($q) {
                $q->whereNull('status_kontrak')
                  ->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            });

        // if (auth()->check()) {
        //     if (auth()->user()->dashboard_role == 'atl') {
        //         $query->where('atl', auth()->user()->nip);
        //     } elseif (auth()->user()->dashboard_role == 'soh') {
        //         $query->where('soh', auth()->user()->nip);
        //     } else {
        //         $query->where('soh', '1708010004');
        //     }
        // }

        return $query->orderBy('nama_dealer');
    }

    /**
     * Get potentials data aggregated by cabang.
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array $selectedDealerIds
     * @return array
     */
    public function getPotentialsByCabang($startDate, $endDate, $selectedDealerIds = [])
    {
        $bindings = [$startDate, $endDate];
        
        $whereClause = "WHERE (d.status_kontrak IS NULL OR d.status_kontrak != 'Tidak Aktif')";
        
        $selectedDealerIds = array_filter($selectedDealerIds);
        if (!empty($selectedDealerIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedDealerIds), '?'));
            $whereClause .= " AND d.id IN ($placeholders)";
            $bindings = array_merge($bindings, $selectedDealerIds);
        }

        $sql = "SELECT
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                d.cabang,
                COALESCE(SUM(a.unit_entry), 0) AS unit_entry,
                COALESCE(SUM(a.unit_ac), 0) AS unit_ac,
                COALESCE(SUM(a.omset_jasa), 0) AS omset_jasa,
                ROUND((COALESCE(SUM(a.unit_ac), 0) / NULLIF(SUM(a.unit_entry), 0)) * 100, 2) AS cr_percent,
                (SUM(a.omset_jasa) / NULLIF(SUM(a.unit_ac), 0)) AS rp_uac
            FROM dealercabang d
            INNER JOIN (
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
            $whereClause
            -- AND d.via LIKE '%SNS'
            -- AND d.soh = '1708010004'
            -- AND d.atl = '2211490373'
            GROUP BY
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                d.cabang
            ORDER BY
                d.dealer, d.nama_dealer, d.cabang
        ";

        return DB::select($sql, $bindings);
    }
}
