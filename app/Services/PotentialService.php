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
        
        // Agregasi untuk tabel utama
        $potentials = $collection->groupBy('dealer')->map(function ($items) {
            $first = $items->first();
            $unitEntry = $items->sum('unit_entry');
            $unitAc = $items->sum('unit_ac');
            $omsetJasa = $items->sum('omset_jasa');
            
            $crPercent = $unitEntry > 0 ? round(($unitAc / $unitEntry) * 100, 2) : 0;
            $rpUac = $unitAc > 0 ? ($omsetJasa / $unitAc) : 0;
            
            return (object) [
                'soh' => $first->soh,
                'atl' => $first->atl,
                'dealer' => $first->dealer,
                'nama_dealer' => $first->nama_dealer,
                'unit_entry' => $unitEntry,
                'unit_ac' => $unitAc,
                'cr_percent' => $crPercent,
                'omset_jasa' => $omsetJasa,
                'rp_uac' => $rpUac,
            ];
        })->values()->toArray();

        // Data untuk Modal Pareto UE: Sort ASC by unit_entry
        $paretoUe = $collection->sortByDesc('unit_entry')->values()->toArray();
        
        $totalUe = $collection->sum('unit_entry');
        $pareto80Ue = round($totalUe * 0.8);

        return [
            'potentials' => $potentials,
            'pareto_ue' => $paretoUe,
            'total_ue' => $totalUe,
            'pareto80_ue' => $pareto80Ue,
        ];
    }
}
