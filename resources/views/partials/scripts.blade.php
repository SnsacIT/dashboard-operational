<script src="{{ asset('mazer/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('mazer/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('mazer/assets/js/main.js') }}"></script>

{{-- Start Choices --}}
<script src="{{ asset('mazer/assets/vendors/choices.js/choices.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let choiceElements = document.querySelectorAll('select.choices, input.choices');
        choiceElements.forEach(element => {
            // Cek apakah Choices sudah diinisialisasi oleh script bawaan Mazer
            if (element.dataset.choice === 'active' || element.classList.contains('choices__input')) {
                return;
            }
            try {
                new Choices(element, {
                    searchEnabled: true,
                    shouldSort: false,
                    itemSelectText: '',
                    removeItemButton: true,
                });
            } catch (e) {
                console.log("Choices already initialized or invalid element:", e.message);
            }
        });
    });
</script>
{{-- End Choices --}}
{{-- Start Datatable (v2.2.1 Bundled) --}}
<script src="{{ asset('mazer/assets/vendors/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.2.1/b-3.2.0/b-html5-3.2.0/b-print-3.2.0/fc-5.0.4/fh-4.0.1/datatables.min.js"></script>

<script>
    $(document).ready(function() {
        $('.base-table').DataTable({
            "scrollX": true,
            "scrollY": "500px",
            "scrollCollapse": true,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/2.1.5/i18n/id.json"
            }
        });
    });
</script>
{{-- End Datatable --}}


{{-- Start SweetAlert2 --}}
<script src="{{ asset('mazer/assets/vendors/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    function showInfoAlert(message, title = 'Info') {
        Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonColor: '#435ebe',
        });
    }

    function showWarningAlert(message, title = 'Peringatan') {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            confirmButtonColor: '#435ebe',
        });
    }

    function showSuccessAlert(message, title = 'Berhasil') {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonColor: '#198754',
        });
    }

    // Auto-trigger if session has success or error
    @if(session('success'))
        showSuccessAlert("{{ session('success') }}");
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: "{{ session('error') }}",
            confirmButtonColor: '#dc3545',
        });
    @endif
</script>
{{-- End SweetAlert2 --}}

@stack('scripts')