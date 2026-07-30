<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsOperationalQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use BuildsOperationalQueries;

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->activeRole($request, $user);
        $dealers = $this->visibleDealerQuery($user)->select('id', 'dealer', 'cabang', 'nama_dealer', 'kotakab', 'no_atl', 'no_soh')->limit(8)->get();

        return view('profile.index', [
            'role' => $role,
            'user' => $user,
            'dealers' => $dealers,
            'kpis' => [
                'dealers' => $this->dealerDropdownQuery($user)->count(),
                'mechanics' => DB::table('users')->where('role', 0)->whereNotNull('nip')->whereNotNull('dealer')->whereNotNull('cabang')->count(),
                'last_login' => $user->last_login_at,
            ],
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $user = $request->user();
        $path = $request->file('photo')->store('profile-photos', 'public');

        if ($user->profile && Storage::disk('public')->exists($user->profile)) {
            Storage::disk('public')->delete($user->profile);
        }

        $user->forceFill(['profile' => $path])->save();

        return back()->with('profile_status', 'Foto profil berhasil diperbarui.');
    }
}
