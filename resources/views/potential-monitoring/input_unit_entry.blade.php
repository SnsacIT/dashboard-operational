@extends('layouts.dashboard')
@section('title', 'Input Unit Entry')
@section('page-title', 'Input Unit Entry')
@section('page-description', 'Input nilai Unit Entry dan RP/UE secara massal untuk tiap cabang.')
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
    
    .form-control-number {
        min-width: 120px;
        text-align: right;
    }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h4 class="card-title mb-0">Form Input Massal</h4>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Filter Bulan Tahun -->
            <form action="{{ route('potentials.input-unit-entry') }}" method="GET" class="d-flex align-items-center gap-2">
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

            <a href="{{ route('potentials.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                <i class="iconly-boldArrow---Left-2 me-2"></i> Kembali
            </a>
        </div>
    </div>
                
                <div class="card-body px-4 py-4-5 p-0">
                    <!-- Nanti form method POST diarahkan ke route store -->
                    <form action="{{ route('potentials.store-unit-entry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        
                        <div class="table-responsive table-container p-3">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-sticky-header text-center">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%" class="text-start">Dealer</th>
                                        <th width="35%" class="text-start">Cabang</th>
                                        <th width="20%">Unit Entry (UE)</th>
                                        <th width="20%">RP / Unit Entry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dealers as $dealer)
                                        <tr>
                                            <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                            <td class="align-middle fw-bold">{{ $dealer->dealer }}</td>
                                            <td class="align-middle">{{ $dealer->nama_dealer }}</td>
                                            <td>
                                                <input type="text" 
                                                       name="inputs[{{ $dealer->id }}][unit_entry]" 
                                                       class="form-control form-control-number currency-input ue-input" 
                                                       placeholder="0"
                                                       data-type="ue">
                                                <div class="invalid-feedback text-start">Hanya angka yang diperbolehkan!</div>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" 
                                                           name="inputs[{{ $dealer->id }}][rp_unit_entry]" 
                                                           class="form-control form-control-number currency-input rp-input" 
                                                           placeholder="0"
                                                           data-type="rp">
                                                    <div class="invalid-feedback text-start">Hanya angka yang diperbolehkan!</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">Tidak ada cabang yang ditemukan.</td>
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
        </div>
    </section>

@push('scripts')
<script>
    // 1. Auto-Format Ribuan
    function formatRupiah(value) {
        let number_string = value.replace(/[^,\d]/g, '').toString();
        let split = number_string.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    // Initialize formatting and event listeners
    const currencyInputs = document.querySelectorAll('.currency-input');
    
    currencyInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            let originalValue = this.value;
            this.value = formatRupiah(originalValue);
            
            // Jika mengandung huruf atau karakter non-angka (selain koma/titik), munculkan warning
            if (/[^\d,.]/.test(originalValue)) {
                this.classList.add('is-invalid');
                
                // Hilangkan pesan error setelah 1.5 detik
                clearTimeout(this.warningTimeout);
                this.warningTimeout = setTimeout(() => {
                    this.classList.remove('is-invalid');
                }, 1500);
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // 2. Keyboard Navigation ala Excel (Arrow Up, Arrow Down, Enter)
        input.addEventListener('keydown', function(e) {
            let type = this.getAttribute('data-type'); // 'ue' or 'rp'
            // Dapatkan semua input dengan tipe yang sama
            let columnInputs = document.querySelectorAll(`.currency-input[data-type="${type}"]`);
            let currentIndex = Array.from(columnInputs).indexOf(this);

            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                let nextInput = columnInputs[currentIndex + 1];
                if (nextInput) nextInput.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                let prevInput = columnInputs[currentIndex - 1];
                if (prevInput) prevInput.focus();
            }
        });

        // 3. Paste from Excel
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            // Ambil data yang di-paste
            let pastedData = (e.clipboardData || window.clipboardData).getData('text');
            
            // Pisahkan berdasarkan baris (newline)
            let rows = pastedData.split(/\r\n|\n|\r/);
            
            // Cari baris saat ini (tempat user mem-paste)
            let currentTr = this.closest('tr');
            let tbody = currentTr.closest('tbody');
            let allTrs = Array.from(tbody.querySelectorAll('tr'));
            let startRowIndex = allTrs.indexOf(currentTr);
            
            // Tentukan dari kolom mana paste dilakukan (ue atau rp)
            let startType = this.getAttribute('data-type');
            
            rows.forEach((row, rowIndex) => {
                if (!row.trim()) return; // skip baris kosong
                
                let targetTr = allTrs[startRowIndex + rowIndex];
                if (!targetTr) return; // jika jumlah baris paste melebihi tabel
                
                // Pisahkan berdasarkan kolom (tab)
                let cols = row.split('\t');
                
                if (startType === 'ue') {
                    // Jika paste di kolom Unit Entry
                    let ueInput = targetTr.querySelector('.ue-input');
                    if (ueInput && cols[0] !== undefined) {
                        ueInput.value = formatRupiah(cols[0].trim());
                    }
                    let rpInput = targetTr.querySelector('.rp-input');
                    if (rpInput && cols[1] !== undefined) {
                        rpInput.value = formatRupiah(cols[1].trim());
                    }
                } else if (startType === 'rp') {
                    // Jika paste di kolom RP
                    let rpInput = targetTr.querySelector('.rp-input');
                    if (rpInput && cols[0] !== undefined) {
                        rpInput.value = formatRupiah(cols[0].trim());
                    }
                }
            });
        });
    });

    function validateAndSubmit() {
        Swal.fire({
            title: 'Konfirmasi Simpan',
            text: 'Apakah Anda ingin menyimpan data Unit Entry untuk bulan {{ \Carbon\Carbon::create()->month((int) $month)->locale('id')->monthName }} {{ $year }}?',
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
                
                // Remove formatting (thousands separators) before submitting
                document.querySelectorAll('.currency-input').forEach(input => {
                    input.value = input.value.replace(/\./g, '');
                });
                
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
</script>
@endpush
@endsection
