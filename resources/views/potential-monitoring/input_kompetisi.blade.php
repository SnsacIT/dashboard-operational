@extends('layouts.dashboard')
@section('title', 'Input Kompetisi')
@section('page-title', 'Input Kompetisi')
@section('page-description', 'Input nilai insentif, harga, grooming, dan status unggul untuk tiap cabang.')
@section('styles')
<style>
    /* Sticky footer for save button */
    .sticky-action-bar {
        position: sticky;
        bottom: 0;
        background: white;
        z-index: 1020;
        padding: 15px 20px;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
        border-top: 1px solid #e9ecef;
        margin: 0 -20px -20px -20px;
        border-radius: 0 0 10px 10px;
    }
    
    /* Sticky header for the table so it scrolls nicely */
    .table-sticky-header th {
        position: sticky;
        top: 0;
        background-color: #f2f7ff;
        z-index: 10;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .table-container {
        max-height: 65vh;
        overflow-y: auto;
    }
    
    .form-control-text {
        min-width: 120px;
    }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h4 class="card-title mb-0">Form Input Massal</h4>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Filter Bulan Tahun -->
            <form action="{{ route('potentials.input-kompetisi') }}" method="GET" class="d-flex align-items-center gap-2">
                <select name="month" class="form-select" style="min-width: 140px;" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}" @selected($month == sprintf('%02d', $m))>
                            {{ \Carbon\Carbon::create()->month((int) $m)->locale('id')->monthName }}
                        </option>
                    @endforeach
                </select>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    @foreach(range(date('Y') - 2, date('Y') + 1) as $y)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('potentials.kompetisi') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                <i class="iconly-boldArrow---Left-2 me-2"></i> Kembali
            </a>
        </div>
    </div>
                
    <div class="card-body px-4 py-4-5 p-0">
        <form action="{{ route('potentials.store-kompetisi') }}" method="POST">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            
            <div class="table-responsive table-container p-3">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-sticky-header text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%" class="text-start">Dealer</th>
                            <th width="20%" class="text-start">Cabang</th>
                            <th width="15%">Kompetitor</th>
                            <th width="15%">Insentif</th>
                            <th width="15%">Harga</th>
                            <th width="15%">Grooming</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dealers as $dealer)
                            <tr>
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle fw-bold">{{ $dealer->dealer }}</td>
                                <td class="align-middle">{{ $dealer->nama_dealer }}</td>
                                <td>
                                    <select name="inputs[{{ $dealer->id }}][kompetitor]" 
                                            class="form-select form-control-text text-input" 
                                            data-type="kompetitor"
                                            onchange="toggleKompetitorMass(this)">
                                        <option value="">- Pilih -</option>
                                        <option value="Ada">Ada</option>
                                        <option value="Tidak Ada">Tidak Ada</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="inputs[{{ $dealer->id }}][insentif]" 
                                            class="form-select form-control-text text-input dep-select" 
                                            data-type="insentif">
                                        <option value="">- Pilih -</option>
                                        <option value="Sangat Unggul" class="opt-sangat-unggul">Sangat Unggul</option>
                                        <option value="Lebih Unggul" class="opt-lain">Lebih Unggul</option>
                                        <option value="Sama dengan Kompetitor" class="opt-lain">Sama dengan Kompetitor</option>
                                        <option value="Di bawah Kompetitor" class="opt-lain">Di bawah Kompetitor</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="inputs[{{ $dealer->id }}][harga]" 
                                            class="form-select form-control-text text-input dep-select" 
                                            data-type="harga">
                                        <option value="">- Pilih -</option>
                                        <option value="Sangat Unggul" class="opt-sangat-unggul">Sangat Unggul</option>
                                        <option value="Lebih Unggul" class="opt-lain">Lebih Unggul</option>
                                        <option value="Sama dengan Kompetitor" class="opt-lain">Sama dengan Kompetitor</option>
                                        <option value="Di bawah Kompetitor" class="opt-lain">Di bawah Kompetitor</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="inputs[{{ $dealer->id }}][grooming]" 
                                            class="form-select form-control-text text-input dep-select" 
                                            data-type="grooming">
                                        <option value="">- Pilih -</option>
                                        <option value="Sangat Unggul" class="opt-sangat-unggul">Sangat Unggul</option>
                                        <option value="Lebih Unggul" class="opt-lain">Lebih Unggul</option>
                                        <option value="Sama dengan Kompetitor" class="opt-lain">Sama dengan Kompetitor</option>
                                        <option value="Di bawah Kompetitor" class="opt-lain">Di bawah Kompetitor</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada cabang yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Sticky Action Bar -->
            <div class="sticky-action-bar d-flex justify-content-end align-items-center gap-3">
                <span class="text-muted small me-auto">Menampilkan {{ count($dealers) }} cabang untuk inputan bulan {{ \Carbon\Carbon::create()->month((int) $month)->locale('id')->monthName }} {{ $year }}</span>
                <button type="button" class="btn btn-light-secondary" onclick="confirmReset(this)">Reset Form</button>
                <button type="button" class="btn btn-primary d-flex align-items-center" id="btn-submit-corrections" onclick="validateAndSubmit()">
                    <i class="iconly-boldSend me-2"></i> Simpan Semua Koreksi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const textInputs = document.querySelectorAll('.text-input');
    
    textInputs.forEach(input => {
        // Paste from Excel
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            let pastedData = (e.clipboardData || window.clipboardData).getData('text');
            let rows = pastedData.split(/\r\n|\n|\r/);
            
            let currentTr = this.closest('tr');
            let tbody = currentTr.closest('tbody');
            let allTrs = Array.from(tbody.querySelectorAll('tr'));
            let startRowIndex = allTrs.indexOf(currentTr);
            let startType = this.getAttribute('data-type');
            
            rows.forEach((row, rowIndex) => {
                if (!row.trim()) return;
                
                let targetTr = allTrs[startRowIndex + rowIndex];
                if (!targetTr) return;
                
                let cols = row.split('\t');
                
                // Urutan kolom dropdown
                let targetTypes = ['kompetitor', 'insentif', 'harga', 'grooming'];
                let startIndex = targetTypes.indexOf(startType);
                
                if (startIndex !== -1) {
                    cols.forEach((colData, colIndex) => {
                        let targetType = targetTypes[startIndex + colIndex];
                        if (targetType) {
                            let select = targetTr.querySelector(`.text-input[data-type="${targetType}"]`);
                            if (select && !select.disabled) {
                                let val = colData.trim();
                                
                                // Pilih option yang text-nya mirip dengan yang di-paste (case insensitive)
                                Array.from(select.options).forEach(opt => {
                                    if (opt.value.toLowerCase() === val.toLowerCase()) {
                                        select.value = opt.value;
                                    }
                                });
                                
                                // Trigger event change
                                select.dispatchEvent(new Event('change'));
                            }
                        }
                    });
                }
            });
        });
    });

    function validateAndSubmit() {
        Swal.fire({
            title: 'Konfirmasi Simpan',
            text: 'Apakah Anda ingin menyimpan data Kompetisi untuk bulan {{ \Carbon\Carbon::create()->month((int) $month)->locale('id')->monthName }} {{ $year }}?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#435ebe',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let submitBtn = document.getElementById('btn-submit-corrections');
                let form = submitBtn.closest('form');
                
                // Animasi Loading
                let originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
                submitBtn.classList.add('disabled');
                
                // Re-enable disabled selects so data is sent
                form.querySelectorAll('select:disabled').forEach(s => s.disabled = false);

                // Submit the form
                form.submit();
            }
        });
    }

    function confirmReset(btn) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Semua angka yang sudah Anda ketik di form ini akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#435ebe',
            confirmButtonText: 'Ya, Reset Form',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').reset();
            }
        });
    }

    function toggleKompetitorMass(selectElement) {
        let val = selectElement.value;
        let tr = selectElement.closest('tr');
        let deps = tr.querySelectorAll('.dep-select');
        
        deps.forEach(select => {
            let optSangatUnggul = select.querySelector('.opt-sangat-unggul');
            let optsLain = select.querySelectorAll('.opt-lain');
            
            if (val === 'Tidak Ada') {
                if (optSangatUnggul) optSangatUnggul.style.display = 'block';
                select.value = 'Sangat Unggul';
                select.disabled = true;
                select.style.backgroundColor = '#e9ecef';
            } else if (val === 'Ada') {
                if (optSangatUnggul) optSangatUnggul.style.display = 'none';
                select.disabled = false;
                select.style.backgroundColor = '';
                
                if (select.value === 'Sangat Unggul') {
                    select.value = ''; // clear value karena Sangat Unggul tidak valid untuk Ada
                }
            } else {
                if (optSangatUnggul) optSangatUnggul.style.display = 'block';
                select.disabled = false;
                select.style.backgroundColor = '';
            }
        });
    }
</script>
@endpush
