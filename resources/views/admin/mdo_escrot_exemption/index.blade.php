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

.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
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

                    <div class="row mb-3">
                        <div class="col-12 text-end">
                            <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                                aria-label="MDO Exemption Status Filter">
                                <button type="button" class="btn btn-success px-4 fw-semibold active"
                                    id="filterActive" aria-pressed="true">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </button>
                                <button type="button"
                                    class="btn btn-outline-secondary px-4 fw-semibold"
                                    id="filterArchive" aria-pressed="false">
                                    <i class="bi bi-archive me-1"></i> Archive
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="courseFilter" class="form-label mb-1">Programme Name</label>
                            <select id="courseFilter" class="form-select">
                                <option value="">All Programmes</option>
                                @foreach($courses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}" {{ request('course_filter') == $pk ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
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
$(document).on('preXhr.dt', '#mdoescot-table', function (e, settings, data) {
    data.status_filter = window.mdoCurrentFilter || 'active';
    data.course_filter = $('#courseFilter').val();
});

$(document).ready(function() {
    window.mdoCurrentFilter = 'active';
    const currentCourse = '{{ request("course_filter", "") }}';

    // Wait for DataTable to be initialized
    setTimeout(function() {
        var table = $('#mdoescot-table').DataTable();

        // Handle Active button click
        $('#filterActive').on('click', function() {
            setActiveButton($(this));
            window.mdoCurrentFilter = 'active';
            table.ajax.reload();
        });

        // Handle Archive button click
        $('#filterArchive').on('click', function() {
            setActiveButton($(this));
            window.mdoCurrentFilter = 'archive';
            table.ajax.reload();
        });

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

        // Handle Course filter change
        $('#courseFilter').on('change', function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#courseFilter').val('');
            table.ajax.reload();
        });

        function setActiveButton(activeBtn) {
            $('#filterActive')
                .removeClass('btn-success active text-white')
                .addClass('btn-outline-success')
                .attr('aria-pressed', 'false');

            $('#filterArchive')
                .removeClass('btn-secondary active text-white')
                .addClass('btn-outline-secondary')
                .attr('aria-pressed', 'false');

            if (activeBtn.attr('id') === 'filterActive') {
                activeBtn.removeClass('btn-outline-success')
                    .addClass('btn-success text-white active')
                    .attr('aria-pressed', 'true');
            } else {
                activeBtn.removeClass('btn-outline-secondary')
                    .addClass('btn-secondary text-white active')
                    .attr('aria-pressed', 'true');
            }
        }

        // Ensure initial styling reflects default filter
        setActiveButton($('#filterActive'));
    }, 500); // Wait for DataTable initialization
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