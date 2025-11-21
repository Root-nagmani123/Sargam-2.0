@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
<style>
#searchBox {
    transition: all 0.25s ease;
    width: 0;
    opacity: 0;
}

#searchBox.active {
    width: 200px;
    opacity: 1;
}


/* Header style - deep red + rounded corners */
table.mdodutytable thead th {
    background-color: #b32826 !important;
    color: #fff !important;
    font-weight: 600 !important;
    border: none !important;
    padding: 14px;
    text-align: center !important;
}

table.mdodutytable thead th:first-child {
    border-top-left-radius: 12px;
}

table.mdodutytable thead th:last-child {
    border-top-right-radius: 12px;
}

/* Body rows - clean white, no borders */
table.mdodutytable tbody tr {
    border: none !important;
}

table.mdodutytable tbody td {
    border: none !important;
    padding: 18px 12px !important;
    text-align: center !important;
    font-size: 15px;
    color: #000;
}

/* Remove DataTable default border */
table.dataTable.no-footer {
    border-bottom: none !important;
}

/* Remove row hover highlight (optional) */
table.mdodutytable tbody tr:hover {
    background: #f8f8f8 !important;
}
</style>
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <h4 class="mb-0">MDO Escrot Exemption</h4>
                        </div>

                        <div class="col-md-6 d-flex justify-content-end align-items-center gap-2">

                            <!-- Add Button -->
                            <a href="{{ route('mdo-escrot-exemption.create') }}" class="btn btn-primary">
                                + Add MDO Escrot Exemption
                            </a>

                            <!-- Search Icon -->
                            <button class="btn btn-light bg-transparent border-0" id="toggleSearchBtn">
                                <iconify-icon icon="solar:magnifer-linear" class="fs-5"></iconify-icon>
                            </button>

                            <!-- Hidden Search Input -->
                            <input type="text" id="searchBox" class="form-control d-none" placeholder="Search...">
                        </div>
                    </div>


                    <hr>

                    {!! $dataTable->table([
                    'class' => 'table table-hover align-middle w-100 mdodutytable table-striped'
                    ]) !!}


                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<!-- MDO Escort Exemption Modal -->
<div class="modal fade" id="mdoExemptionModal" tabindex="-1" aria-labelledby="mdoExemptionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    Edit MDO Escrot Exemption for - 
                    <span class="ms-2 fs-5" id="modalStudentName">—</span>
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="mdoExemptionModalBody">
                <!-- Edit form will be loaded here via AJAX -->
            </div>

        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {

    var table = $('#dataTable').DataTable(); // replace with your table ID

    // Toggle search box
    $('#toggleSearchBtn').on('click', function(e) {
        e.stopPropagation(); // prevent auto-hide immediately
        $('#searchBox').toggleClass('d-none active').focus();
    });

    // DataTable Search
    $('#searchBox').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Auto-hide when clicking outside
    $(document).on('click', function(e) {
        var searchBox = $('#searchBox');
        var button = $('#toggleSearchBtn');

        // If search box is visible AND click is outside search/btn
        if (searchBox.hasClass('active') &&
            !searchBox.is(e.target) &&
            !button.is(e.target) &&
            button.has(e.target).length === 0) {
            searchBox.addClass('d-none').removeClass('active').val('');
            table.search('').draw(); // clear DataTable search
        }
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
<script>
$(document).on('click', '.openMdoExemptionModal', function() {

    var pk = $(this).data('id');

    $('#mdoExemptionModalBody').html('<div class="text-center py-5">Loading...</div>');

    $.ajax({
        url: '/mdo-escrot-exemption/edit/' + pk,
        type: 'GET',
        success: function(response) {

            // Inject the HTML into modal body
            $('#mdoExemptionModalBody').html(response);

            // Read student name from the loaded edit view
            let studentName = $('#editStudentName').val();

            // Set in modal header
            $('#modalStudentName').text(studentName);

        },
        error: function() {
            $('#mdoExemptionModalBody').html(
                '<div class="text-danger text-center py-5">Error loading form</div>'
            );
        }
    });

    $('#mdoExemptionModal').modal('show');
});
</script>

{!! $dataTable->scripts() !!}
@endpush