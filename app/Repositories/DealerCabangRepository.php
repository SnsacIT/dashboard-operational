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
}
