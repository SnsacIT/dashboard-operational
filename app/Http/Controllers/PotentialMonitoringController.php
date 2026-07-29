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
        $selectedDealerId = $request->query('dealer');
        
        // Memanggil service tanpa array dealer (karena tidak dipakai), tapi meneruskan selectedDealerId
        $potentials = $this->potentialService->getPotentialMonitoringData([], $startDate, $endDate, $selectedDealerId);

        return view('potential-monitoring.index', [
            'role' => $this->activeRole($request, $user),
            'potentials' => $potentials,
            'dealers' => $dealers,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
