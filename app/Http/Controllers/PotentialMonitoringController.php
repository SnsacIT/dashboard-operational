<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Services\PotentialService;
use App\Repositories\DealercabangRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PotentialMonitoringController extends Controller
{
    protected $potentialService;
    protected $dealerCabangRepository;

    use BuildsOperationalQueries;

    public function __construct(PotentialService $potentialService, DealerCabangRepository $dealerCabangRepository)
    {
        $this->potentialService = $potentialService;
        $this->dealerCabangRepository = $dealerCabangRepository;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $dealers = $this->dealerCabangRepository->getDealerCabang()->get();
        
        $startDate = (string) $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = (string) $request->query('end_date', now('Asia/Jakarta')->format('Y-m-d'));
        $selectedDealerIds = $request->query('dealer', []);
        if (!is_array($selectedDealerIds)) {
            $selectedDealerIds = $selectedDealerIds ? [$selectedDealerIds] : [];
        }
        
        // Memanggil service dengan parameter yang sudah disesuaikan
        $data = $this->potentialService->getPotentialMonitoringData($startDate, $endDate, $selectedDealerIds);

        return view('potential-monitoring.index', [
            'role' => $this->activeRole($request, $user),
            'potentials' => $data['potentials'],
            'paretoUe' => $data['pareto_ue'],
            'totalUe' => $data['total_ue'],
            'pareto80Ue' => $data['pareto80_ue'],
            'dealers' => $dealers,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
