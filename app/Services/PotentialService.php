<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PotentialService
{
    public function getPotentialMonitoringData($dealerIds, string $startDate, string $endDate, ?string $selectedDealerId = null)
    {
        $bindings = [$startDate, $endDate];
        
        $whereClause = "WHERE (d.status_kontrak IS NULL OR d.status_kontrak != 'Tidak Aktif')";
        if (!empty($selectedDealerId)) {
            $whereClause .= " AND d.id = ?";
            $bindings[] = $selectedDealerId;
        }

        $sql = "SELECT
                d.soh,
                d.atl,
                d.dealer,
                d.nama_dealer,
                COALESCE(SUM(a.unit_entry), 0) AS unit_entry,
                COALESCE(SUM(a.unit_ac), 0) AS unit_ac,
                ROUND((COALESCE(SUM(a.unit_ac), 0) / NULLIF(SUM(a.unit_entry), 0)) * 100, 2) AS cr_percent,
                -- SUM(a.omset_jasa) AS o_jasa
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
                d.nama_dealer
            ORDER BY
                d.dealer, d.nama_dealer
        ";

        return DB::select($sql, $bindings);
    }
}
