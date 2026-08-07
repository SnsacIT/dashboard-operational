<?php

namespace App\Services;

use App\Repositories\DealerCabangRepository;
use Illuminate\Support\Facades\DB;

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
                'rp_unit_entry' => $item->rp_unit_entry ?? 0,
                'period' => $item->period ?? null,
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
        
        // Data from potential_unit_entry for RP/UE
        $potentialsUnitEntry = $this->dealerCabangRepository->getPotentialByUnitEntry($startDate, $endDate, $selectedDealerIds);
        $collectionUnitEntry = collect($potentialsUnitEntry);

        // Data untuk List RP/UE
        $listRpue = $collectionUnitEntry->sortByDesc('rp_unit_entry')->values()->toArray();
        $totalRpue = $collectionUnitEntry->sum('rp_unit_entry');
        $pareto80Rpue = round($totalRpue * 0.8);

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
            'list_rpue' => $listRpue,
            'total_rpue' => $totalRpue,
            'pareto80_rpue' => $pareto80Rpue,

            // rp / unit ac
            'list_rpuac' => $listRpuac,
            'total_rpuac' => $totalRpuac,
            'pareto80_rpuac' => $pareto80Rpuac,

            // data from potential_unit_entry
            'potentials_unit_entry' => $potentialsUnitEntry,
        ];
    }

    public function storeUnitEntry(array $data)
    {
        DB::beginTransaction();
        try {
            $month = $data['month'];
            $year = $data['year'];
            $period = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
            
            $userId = auth()->user()->username ?? auth()->id() ?? 'system';
            $now = now();

            foreach ($data['inputs'] ?? [] as $idDealerCabang => $input) {
                // Proses hanya jika ada input (unit_entry atau rp_unit_entry tidak null)
                // Berdasarkan rule validasi, jika salah satu ada, maka yang lain juga harus ada
                if (isset($input['unit_entry']) && isset($input['rp_unit_entry'])) {
                    $exists = DB::table('potential_unit_entry')
                        ->where('id_dealercabang', $idDealerCabang)
                        ->where('period', $period)
                        ->first();

                    if ($exists) {
                        DB::table('potential_unit_entry')
                            ->where('id_dealercabang', $idDealerCabang)
                            ->where('period', $period)
                            ->update([
                                'unit_entry' => $input['unit_entry'],
                                'rp_unit_entry' => $input['rp_unit_entry'],
                                'updated_by' => $userId,
                                'updated_at' => $now,
                            ]);
                    } else {
                        DB::table('potential_unit_entry')->insert([
                            'id_dealercabang' => $idDealerCabang,
                            'unit_entry' => $input['unit_entry'],
                            'rp_unit_entry' => $input['rp_unit_entry'],
                            'period' => $period,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
