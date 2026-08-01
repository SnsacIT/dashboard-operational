<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Http\Requests\UnitEntryStoreRequest;
use App\Services\PotentialService;
use App\Repositories\DealercabangRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        
        $startDate = (string) $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = (string) $request->query('end_date', now('Asia/Jakarta')->format('Y-m-d'));

        $dealers = $this->dealerCabangRepository->getDealerCabang($startDate, $endDate)->get();
        $selectedDealerIds = $request->query('dealer', []);
        if (!is_array($selectedDealerIds)) {
            $selectedDealerIds = $selectedDealerIds ? [$selectedDealerIds] : [];
        }
        
        // Memanggil service dengan parameter yang sudah disesuaikan
        $data = $this->potentialService->getPotentialMonitoringData($startDate, $endDate, $selectedDealerIds);

        return view('potential-monitoring.index', [
            'role' => $this->activeRole($request, $user),
            'potentials' => $data['potentials'],
            'listUe' => $data['list_ue'],
            'totalUe' => $data['total_ue'],
            'pareto80Ue' => $data['pareto80_ue'],
            'listUac' => $data['list_uac'],
            'totalUac' => $data['total_uac'],
            'pareto80Uac' => $data['pareto80_uac'],
            'listRpuac' => $data['list_rpuac'],
            'totalRpuac' => $data['total_rpuac'],
            'pareto80Rpuac' => $data['pareto80_rpuac'],
            'dealers' => $dealers,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function inputUnitEntry(Request $request): View
    {
        $user = $request->user();
        
        // Target input month and year, defaults to current month
        $month = $request->query('month', now('Asia/Jakarta')->format('m'));
        $year = $request->query('year', now('Asia/Jakarta')->format('Y'));

        // For input form, we don't necessarily filter by transaction existence (unlike the main table).
        // We just want to list all active branches that belong to the user.
        // So we pass null for dates to getDealerCabang to skip the `whereExists` data_pekerjaan filter.
        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();

        return view('potential-monitoring.input_unit_entry', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function storeUnitEntry(UnitEntryStoreRequest $request)
    {
        // dd($request->validated());
        
        try {
            $this->potentialService->storeUnitEntry($request->validated());
            return redirect()->route('potentials.index')->with('success', 'Data berhasil disimpan');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return redirect()->route('potentials.input-unit-entry')->with('error', 'Gagal menyimpan data');
        }
    }
}
