<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AtlController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        abort_unless($request->user()->dashboard_role === 'soh', 403);

        $atls = $this->visibleAtlQuery($request->user())
            ->orderBy('wilayah_atl.nama_wilayah')
            ->paginate(12);

        $dealerCounts = DB::table('dealercabang')
            ->where('no_soh', $this->sohNumber($request->user()))
            ->selectRaw('no_atl, COUNT(*) as total')
            ->groupBy('no_atl')
            ->pluck('total', 'no_atl');

        return view('atls.index', [
            'role' => 'soh',
            'atls' => $atls,
            'dealerCounts' => $dealerCounts,
        ]);
    }

    public function show(Request $request, int $atl): View
    {
        abort_unless($request->user()->dashboard_role === 'soh', 403);

        $atlData = $this->visibleAtlQuery($request->user())
            ->where('wilayah_atl.urutan', $atl)
            ->first();

        abort_unless($atlData, 403);

        return view('atls.show', [
            'role' => 'soh',
            'atl' => $atlData,
            'dealers' => $this->visibleDealerQuery($request->user())->where('no_atl', $atl)->orderBy('dealer')->get(),
        ]);
    }
}
