<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait BuildsOperationalQueries
{
    protected function activeRole(Request $request, User $user): string
    {
        $role = strtolower((string) $request->query('role', $user->dashboard_role));

        if (! in_array($role, ['atl', 'soh'], true)) {
            $role = $user->dashboard_role;
        }

        return $user->dashboard_role === 'atl' ? 'atl' : $role;
    }

    protected function sohNumber(User $user): ?int
    {
        return $user->no_soh ? (int) $user->no_soh : null;
    }

    protected function atlNumber(User $user): ?int
    {
        if ($user->no_atl) {
            return (int) $user->no_atl;
        }

        $atl = DB::table('wilayah_atl')
            ->where('nip_atl', $user->nip)
            ->first();

        return $atl ? (int) $atl->urutan : null;
    }

    protected function visibleDealerQuery(User $user): Builder
    {
        $query = DB::table('dealercabang')
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')
                    ->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            });

        if ($user->dashboard_role === 'soh') {
            $sohNumber = $this->sohNumber($user);

            return $query->where(function (Builder $query) use ($user, $sohNumber): void {
                if ($sohNumber) {
                    $query->orWhere('no_soh', $sohNumber);
                }

                if (filled($user->nip)) {
                    $query->orWhere('soh', $user->nip);
                }

                if (filled($user->nama)) {
                    $query->orWhere('soh', $user->nama);
                }

                if (filled($user->username)) {
                    $query->orWhere('soh', $user->username);
                }
            });
        }

        $atlNumber = $this->atlNumber($user);

        return $query->where(function (Builder $query) use ($user, $atlNumber): void {
            if ($atlNumber) {
                $query->orWhere('no_atl', $atlNumber);
            }

            if (filled($user->nip)) {
                $query->orWhere('atl', $user->nip);
            }

            if (filled($user->nama)) {
                $query->orWhere('atl', $user->nama);
            }

            if (filled($user->username)) {
                $query->orWhere('atl', $user->username);
            }
        });
    }

    protected function dealerDropdownQuery(User $user): Builder
    {
        $query = $this->visibleDealerQuery($user);

        if ((clone $query)->count() > 0) {
            return $query;
        }

        return DB::table('dealercabang')
            ->where(function (Builder $query): void {
                $query->whereNull('status_kontrak')
                    ->orWhere('status_kontrak', '!=', 'Tidak Aktif');
            });
    }

    protected function atlDropdownQuery(User $user): Builder
    {
        $query = $this->visibleAtlQuery($user);

        if ((clone $query)->count() > 0) {
            return $query;
        }

        return DB::table('wilayah_atl')
            ->leftJoin('users', 'users.nip', '=', 'wilayah_atl.nip_atl')
            ->select([
                'wilayah_atl.id',
                'wilayah_atl.urutan',
                'wilayah_atl.nip_atl',
                'wilayah_atl.nama_wilayah',
                'users.nama',
                'users.username',
            ]);
    }

    protected function visibleMechanicQuery(User $user): Builder
    {
        $dealers = $this->visibleDealerQuery($user)
            ->select('dealer', 'cabang')
            ->get();

        return $this->mechanicQueryForDealerRows($dealers);
    }

    protected function mechanicQueryForDealerRows($dealers): Builder
    {
        return DB::table('users')
            ->where(function (Builder $query) use ($dealers): void {
                foreach ($dealers as $dealer) {
                    $query->orWhere(function (Builder $query) use ($dealer): void {
                        $query->where('dealer', $dealer->dealer)
                            ->where('cabang', $dealer->cabang);
                    });
                }
            })
            ->whereNotNull('nip')
            ->where(function (Builder $query): void {
                $query->whereNull('delete_at')
                    ->orWhere('delete_at', '>', now('Asia/Jakarta'));
            })
            ->where(function (Builder $query): void {
                $query->whereNull('resign_date')
                    ->orWhere('resign_date', '>', now('Asia/Jakarta')->toDateString());
            });
    }

    protected function visibleAtlQuery(User $user): Builder
    {
        $query = DB::table('wilayah_atl')
            ->leftJoin('users', 'users.nip', '=', 'wilayah_atl.nip_atl')
            ->select([
                'wilayah_atl.id',
                'wilayah_atl.urutan',
                'wilayah_atl.nip_atl',
                'wilayah_atl.nama_wilayah',
                'users.nama',
                'users.username',
            ]);

        if ($user->dashboard_role === 'soh') {
            $sohNumber = $this->sohNumber($user);

            return $sohNumber
                ? $query->whereExists(function (Builder $subquery) use ($sohNumber): void {
                    $subquery->selectRaw('1')
                        ->from('dealercabang')
                        ->whereColumn('dealercabang.no_atl', 'wilayah_atl.urutan')
                        ->where('dealercabang.no_soh', $sohNumber);
                })
                : $query->whereRaw('1 = 0');
        }

        $atlNumber = $this->atlNumber($user);

        return $atlNumber
            ? $query->where('wilayah_atl.urutan', $atlNumber)
            : $query->whereRaw('1 = 0');
    }

    protected function applyCommonFilters(Builder $query, Request $request, User $user, string $dealerColumn = 'dealercabang_id'): Builder
    {
        $dealerIds = $this->visibleDealerQuery($user)
            ->when($request->filled('atl_id') && $user->dashboard_role === 'soh', function (Builder $query) use ($request): void {
                $query->where('no_atl', $request->integer('atl_id'));
            })
            ->when($request->filled('dealer_id'), function (Builder $query) use ($request): void {
                $query->where('id', $request->integer('dealer_id'));
            })
            ->pluck('id');

        return $query->whereIn($dealerColumn, $dealerIds);
    }
}
