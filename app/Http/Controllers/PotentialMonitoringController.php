<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use App\Http\Requests\UnitEntryStoreRequest;
use App\Repositories\DealercabangRepository;
use App\Services\PotentialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PotentialMonitoringController extends Controller
{
    protected $potentialService;

    protected $dealerCabangRepository;

    use BuildsOperationalQueries;

    public function __construct(PotentialService $potentialService, DealercabangRepository $dealerCabangRepository)
    {
        $this->potentialService = $potentialService;
        $this->dealerCabangRepository = $dealerCabangRepository;
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $periode = $request->query('periode', now('Asia/Jakarta')->format('Y-m'));
        $selectedDealers = $request->query('dealer', []);
        
        if (!is_array($selectedDealers)) {
            $selectedDealers = $selectedDealers ? [$selectedDealers] : [];
        }

        $startDate = Carbon::createFromFormat('Y-m', $periode)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->format('Y-m-d');

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();
        $allowedDealerIds = $dealers->pluck('id')->toArray();

        // 1. Data Potensi
        $potensiData = $this->potentialService->getPotentialMonitoringData($startDate, $endDate, $selectedDealers);

        // 2. Data Kompetisi
        $queryKompetisi = DB::table('kompetisi as k')
            ->leftJoin('dealercabang as d', 'k.id_dealercabang', '=', 'd.id')
            ->select('k.*', 'd.nama_dealer')
            ->whereIn('k.id_dealercabang', $allowedDealerIds)
            ->where('k.periode', 'like', $periode.'%');
            
        if (!empty($selectedDealers)) {
            $queryKompetisi->whereIn('k.id_dealercabang', $selectedDealers);
        }
        $kompetisi = $queryKompetisi->get();

        // 3. Data Relasi
        $queryRelasi = DB::table('relasi as r')
            ->leftJoin('dealercabang as d', 'r.id_dealercabang', '=', 'd.id')
            ->select('r.*', 'd.nama_dealer')
            ->whereIn('r.id_dealercabang', $allowedDealerIds)
            ->where('r.periode', 'like', $periode.'%');

        if (!empty($selectedDealers)) {
            $queryRelasi->whereIn('r.id_dealercabang', $selectedDealers);
        }
        $relasi = $queryRelasi->get();

        return view('potential-monitoring.dashboard', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'periode' => $periode,
            
            // Variabel Potensi
            'potentials' => $potensiData['potentials'],
            'listUe' => $potensiData['list_ue'],
            'totalUe' => $potensiData['total_ue'],
            'pareto80Ue' => $potensiData['pareto80_ue'],
            'listUac' => $potensiData['list_uac'],
            'totalUac' => $potensiData['total_uac'],
            'pareto80Uac' => $potensiData['pareto80_uac'],
            'listRpue' => $potensiData['list_rpue'],
            'totalRpue' => $potensiData['total_rpue'],
            'pareto80Rpue' => $potensiData['pareto80_rpue'],
            'listRpuac' => $potensiData['list_rpuac'],
            'totalRpuac' => $potensiData['total_rpuac'],
            'pareto80Rpuac' => $potensiData['pareto80_rpuac'],
            'potentialsUnitEntry' => $potensiData['potentials_unit_entry'],

            // Variabel Kompetisi & Relasi
            'kompetisi' => $kompetisi,
            'relasi' => $relasi,
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $startDate = (string) $request->query('start_date', now('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = (string) $request->query('end_date', now('Asia/Jakarta')->format('Y-m-d'));

        $dealers = $this->dealerCabangRepository->getDealerCabang($startDate, $endDate)->get();
        $selectedDealerIds = $request->query('dealer', []);
        if (! is_array($selectedDealerIds)) {
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
            'listRpue' => $data['list_rpue'],
            'totalRpue' => $data['total_rpue'],
            'pareto80Rpue' => $data['pareto80_rpue'],
            'listRpuac' => $data['list_rpuac'],
            'totalRpuac' => $data['total_rpuac'],
            'pareto80Rpuac' => $data['pareto80_rpuac'],
            'dealers' => $dealers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'potentialsUnitEntry' => $data['potentials_unit_entry'],
        ]);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $selectedDealerIds = $request->input('dealer', []);

        // Fetch full data using existing service logic
        $data = $this->potentialService->getPotentialMonitoringData($startDate, $endDate, $selectedDealerIds);

        $tab = $request->input('tab', 'data-potensi');
        $fileName = $tab === 'data-potensi' ? 'Data_Potensi.xls' : 'Data_Unit_Entry.xls';

        $html = view('potential-monitoring.export', [
            'potentials' => $data['potentials'],
            'potentialsUnitEntry' => $data['potentials_unit_entry'],
            'tab' => $tab,
            'title' => $tab === 'data-potensi' ? 'Data Potensi' : 'Data Unit Entry',
        ])->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
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

    public function kompetisi(Request $request)
    {
        $periode = $request->query('periode', now('Asia/Jakarta')->format('Y-m'));
        $selectedDealers = $request->query('dealer', []);

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();
        $allowedDealerIds = $dealers->pluck('id')->toArray();

        $query = DB::table('kompetisi as k')
            ->leftJoin('dealercabang as d', 'k.id_dealercabang', '=', 'd.id')
            ->select('k.*', 'd.nama_dealer')
            ->whereIn('k.id_dealercabang', $allowedDealerIds);

        if ($periode) {
            $query->where('k.periode', 'like', $periode.'%');
        }

        if (! empty($selectedDealers)) {
            $query->whereIn('k.id_dealercabang', $selectedDealers);
        }

        $kompetisi = $query->get();


        return view('potential-monitoring.kompetisi', [
            'role' => $this->activeRole($request, $request->user()),
            'kompetisi' => $kompetisi,
            'dealers' => $dealers,
        ]);
    }

    public function inputKompetisi(Request $request): View
    {
        $user = $request->user();

        $month = $request->query('month', now('Asia/Jakarta')->format('m'));
        $year = $request->query('year', now('Asia/Jakarta')->format('Y'));

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();

        return view('potential-monitoring.input_kompetisi', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function storeKompetisi(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric',
            'year' => 'required|numeric',
            'inputs' => 'required|array',
        ]);

        try {
            $month = $request->input('month');
            $year = $request->input('year');
            $periode = $year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-01';

            DB::beginTransaction();

            foreach ($request->input('inputs') as $idDealerCabang => $input) {
                // Hanya insert/update jika ada salah satu field yang diisi
                if (isset($input['kompetitor'])) {
                    DB::table('kompetisi')->updateOrInsert(
                        [
                            'id_dealercabang' => $idDealerCabang,
                            'periode' => $periode,
                        ],
                        [
                            'kompetitor' => $input['kompetitor'],
                            'insentif' => $input['insentif'] ?? null,
                            'harga' => $input['harga'] ?? null,
                            'grooming' => $input['grooming'] ?? null,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->route('potentials.kompetisi')->with('success', 'Data kompetisi berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());

            return redirect()->route('potentials.input-kompetisi')->with('error', 'Gagal menyimpan data kompetisi');
        }
    }

    public function updateKompetisi(Request $request, $id)
    {
        try {
            DB::table('kompetisi')
                ->where('id', $id)
                ->update([
                    'kompetitor' => $request->input('kompetitor'),
                    'insentif' => $request->input('insentif'),
                    'harga' => $request->input('harga'),
                    'grooming' => $request->input('grooming'),
                    'updated_at' => now(),
                ]);

            return redirect()->route('potentials.kompetisi')->with('success', 'Data kompetisi berhasil diupdate');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return redirect()->route('potentials.kompetisi')->with('error', 'Gagal mengupdate data kompetisi');
        }
    }

    public function exportKompetisi(Request $request)
    {
        $periode = $request->query('periode', now('Asia/Jakarta')->format('Y-m'));
        $selectedDealers = $request->query('dealer', []);

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();
        $allowedDealerIds = $dealers->pluck('id')->toArray();

        $query = DB::table('kompetisi as k')
            ->leftJoin('dealercabang as d', 'k.id_dealercabang', '=', 'd.id')
            ->select('k.*', 'd.nama_dealer', 'd.dealer')
            ->whereIn('k.id_dealercabang', $allowedDealerIds);

        if ($periode) {
            $query->where('k.periode', 'like', $periode.'%');
        }

        if (! empty($selectedDealers)) {
            $query->whereIn('k.id_dealercabang', $selectedDealers);
        }

        $kompetisi = $query->get();
        $fileName = 'Data_Kompetisi_'.$periode.'.xls';

        $html = view('potential-monitoring.export', [
            'data' => $kompetisi,
            'tab' => 'kompetisi',
            'title' => 'Data Kompetisi',
        ])->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    public function relasi(Request $request)
    {
        $periode = $request->query('periode', now('Asia/Jakarta')->format('Y-m'));
        $selectedDealers = $request->query('dealer', []);

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();
        $allowedDealerIds = $dealers->pluck('id')->toArray();

        $query = DB::table('relasi as r')
            ->leftJoin('dealercabang as d', 'r.id_dealercabang', '=', 'd.id')
            ->select('r.*', 'd.nama_dealer')
            ->whereIn('r.id_dealercabang', $allowedDealerIds);

        if ($periode) {
            $query->where('r.periode', 'like', $periode.'%');
        }

        if (! empty($selectedDealers)) {
            $query->whereIn('r.id_dealercabang', $selectedDealers);
        }

        $relasi = $query->get();

        return view('potential-monitoring.relasi', [
            'role' => $this->activeRole($request, $request->user()),
            'relasi' => $relasi,
            'dealers' => $dealers,
        ]);
    }

    public function exportRelasi(Request $request)
    {
        $periode = $request->query('periode', now('Asia/Jakarta')->format('Y-m'));
        $selectedDealers = $request->query('dealer', []);

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();
        $allowedDealerIds = $dealers->pluck('id')->toArray();

        $query = DB::table('relasi as r')
            ->leftJoin('dealercabang as d', 'r.id_dealercabang', '=', 'd.id')
            ->select('r.*', 'd.nama_dealer')
            ->whereIn('r.id_dealercabang', $allowedDealerIds);

        if ($periode) {
            $query->where('r.periode', 'like', $periode.'%');
        }

        if (! empty($selectedDealers)) {
            $query->whereIn('r.id_dealercabang', $selectedDealers);
        }

        $relasi = $query->get();
        $fileName = 'Data_Relasi_'.$periode.'.xls';

        $html = view('potential-monitoring.export', [
            'data' => $relasi,
            'tab' => 'relasi',
            'title' => 'Data Relasi',
        ])->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    public function inputRelasi(Request $request): View
    {
        $user = $request->user();
        $month = $request->query('month', now('Asia/Jakarta')->format('m'));
        $year = $request->query('year', now('Asia/Jakarta')->format('Y'));

        $dealers = $this->dealerCabangRepository->getDealerCabang(null, null)->get();

        return view('potential-monitoring.input_relasi', [
            'role' => $this->activeRole($request, $user),
            'dealers' => $dealers,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function storeRelasi(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric',
            'year' => 'required|numeric',
            'inputs' => 'required|array',
        ]);

        try {
            $month = $request->input('month');
            $year = $request->input('year');
            $periode = $year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-01';

            DB::beginTransaction();

            foreach ($request->input('inputs') as $idDealerCabang => $input) {
                // Hanya insert/update jika ada salah satu field yang diisi
                if (isset($input['sa']) || isset($input['sm'])) {
                    DB::table('relasi')->updateOrInsert(
                        [
                            'id_dealercabang' => $idDealerCabang,
                            'periode' => $periode,
                        ],
                        [
                            'sa' => $input['sa'] ?? null,
                            'concern_sa' => $input['concern_sa'] ?? null,
                            'sm' => $input['sm'] ?? null,
                            'concern_sm' => $input['concern_sm'] ?? null,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->route('potentials.relasi')->with('success', 'Data relasi berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());

            return redirect()->route('potentials.input-relasi')->with('error', 'Gagal menyimpan data relasi');
        }
    }

    public function updateRelasi(Request $request, $id)
    {
        try {
            DB::table('relasi')
                ->where('id', $id)
                ->update([
                    'sa' => $request->input('sa'),
                    'concern_sa' => $request->input('concern_sa'),
                    'sm' => $request->input('sm'),
                    'concern_sm' => $request->input('concern_sm'),
                    'updated_at' => now(),
                ]);

            return redirect()->route('potentials.relasi')->with('success', 'Data relasi berhasil diupdate');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return redirect()->route('potentials.relasi')->with('error', 'Gagal mengupdate data relasi');
        }
    }
}
