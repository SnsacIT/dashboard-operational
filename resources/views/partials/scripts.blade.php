<script src="{{ asset('mazer/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('mazer/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('mazer/assets/js/main.js') }}"></script>

{{-- Start Choices --}}
<script src="{{ asset('mazer/assets/vendors/choices.js/choices.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let choiceElements = document.querySelectorAll('.choices');
        choiceElements.forEach(element => {
            new Choices(element, {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
            });
        });
    });
</script>
{{-- End Choices --}}
{{-- Start Datatable (v3.0.0 Bundled) --}}
<script src="{{ asset('mazer/assets/vendors/jquery/jquery.min.js') }}"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-3.0.0/b-3.0.0/b-html5-3.0.0/b-print-3.0.0/fc-5.0.0/fh-4.0.0/datatables.min.js"></script>

<script>
    $(document).ready(function() {
        $('.base-table').DataTable({
            "scrollX": true,
            "scrollY": "500px",
            "scrollCollapse": true,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            }
        });
    });
</script>
{{-- End Datatable --}}

@stack('scripts')