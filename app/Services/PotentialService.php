<?php

namespace App\Services;

use App\Repositories\DealerCabangRepository;

class PotentialService
{
    protected $dealerCabangRepository;

    public function __construct(DealerCabangRepository $dealerCabangRepository)
    {
        $this->dealerCabangRepository = $dealerCabangRepository;
    }

    public function getPotentialMonitoringData(string $startDate, string $endDate, array $selectedDealerIds = [])
    {
        $cabangData = $this->dealerCabangRepository->getPotentialsByCabang($startDate, $endDate, $selectedDealerIds);
        
        $collection = collect($cabangData);
        
        // Format data untuk tabel utama (semua cabang tampil, tidak di group by dealer)
        $potentials = $collection->map(function ($item) {
            $unitEntry = $item->unit_entry ?? 0;
            $unitAc = $item->unit_ac ?? 0;
            $omsetJasa = $item->omset_jasa ?? 0;
            
            $crPercent = $unitEntry > 0 ? round(($unitAc / $unitEntry) * 100, 2) : 0;
            $rpUac = $unitAc > 0 ? ($omsetJasa / $unitAc) : 0;
            
            return (object) [
                'soh' => $item->soh,
                'atl' => $item->atl,
                'dealer' => $item->dealer,
                'cabang' => $item->cabang,
                'nama_dealer' => $item->nama_dealer,
                'unit_entry' => $unitEntry,
                'unit_ac' => $unitAc,
                'cr_percent' => $crPercent,
                'omset_jasa' => $omsetJasa,
                'rp_uac' => $rpUac,
            ];
        })->values()->toArray();

        // Data untuk List UE
        $listUe = $collection->sortByDesc('unit_entry')->values()->toArray();
        $totalUe = $collection->sum('unit_entry');
        $pareto80Ue = round($totalUe * 0.8);

        // Data untuk List UAC
        $listUac = $collection->sortByDesc('unit_ac')->values()->toArray();
        $totalUac = $collection->sum('unit_ac');
        $pareto80Uac = round($totalUac * 0.8);
        
        // Data untuk List RP/UE (blm ada)
        // $listRpue = $collection->sortByDesc('rp_ue')->values()->toArray();
        // $totalRpue = $collection->sum('rp_ue');
        // $pareto80Rpue = round($totalRpue * 0.8);

        // Data untuk List RP/UAC
        $listRpuac = $collection->sortByDesc('rp_uac')->values()->toArray();
        $totalRpuac = $collection->sum('rp_uac');
        $pareto80Rpuac = round($totalRpuac * 0.8);


        return [
            'potentials' => $potentials,
            // unit entry
            'list_ue' => $listUe,
            'total_ue' => $totalUe,
            'pareto80_ue' => $pareto80Ue,
            // unit ac
            'list_uac' => $listUac,
            'total_uac' => $totalUac,
            'pareto80_uac' => $pareto80Uac,

            // rp / unit ue
            // 'list_rpue' => $listRpue,
            // 'total_rpue' => $totalRpue,
            // 'pareto80_rpue' => $pareto80Rpue,

            // rp / unit ac
            'list_rpuac' => $listRpuac,
            'total_rpuac' => $totalRpuac,
            'pareto80_rpuac' => $pareto80Rpuac,
        ];
    }
}
