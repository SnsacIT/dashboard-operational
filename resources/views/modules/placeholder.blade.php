@php
    $userRole = auth()->user()->dashboard_role ?? 'atl';
    $activeRole = strtolower((string) request()->query('role', $userRole));

    if (! in_array($activeRole, ['atl', 'soh'], true)) {
        $activeRole = $userRole;
    }

    if ($userRole === 'atl') {
        $activeRole = 'atl';
    }
@endphp

@extends('layouts.dashboard')

@section('title', $title)

@section('page-title', $title)

@section('page-description', $description)

@section('content')
    <div class="card">
        <div class="card-header">
            <div
                class="d-flex flex-column flex-md-row
                       justify-content-between
                       align-items-md-center gap-3"
            >
                <div>
                    <h4 class="mb-1">
                        {{ $title }}
                    </h4>

                    <p class="text-muted mb-0">
                        Mode pengguna:
                        <strong>
                            {{ strtoupper($activeRole) }}
                        </strong>
                    </p>
                </div>

                <span class="badge bg-light-primary text-primary">
                    Dalam Pengembangan
                </span>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-light-primary mb-0">
                <i class="bi bi-info-circle-fill me-2"></i>

                Halaman ini sudah terhubung dengan template Mazer.
                Data dan fungsi akan dibuat pada tahap berikutnya.
            </div>
        </div>
    </div>
@endsection
