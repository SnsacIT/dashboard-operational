<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(Request $request, string $title, string $description): View
    {
        return view('modules.placeholder', [
            'role' => $this->activeRole($request),
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function activeRole(Request $request): string
    {
        $userRole = $request->user()->dashboard_role ?? 'atl';
        $role = strtolower((string) $request->query('role', $userRole));

        if (! in_array($role, ['atl', 'soh'], true)) {
            return $userRole;
        }

        return $userRole === 'atl' ? 'atl' : $role;
    }
}
