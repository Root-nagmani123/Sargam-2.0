@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.coe-page .coe-act--reject { color:#e5141a; }
.ccdc-readonly-date { background:var(--ds-surface-2); }
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Examination Drive Configuration" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Examination Drive (CC)']">
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.cc-examination-drive.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.cc-examination-drive.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" id="ccdcDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="ccdcPrintBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="ccdcFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="ccdcFilterTerm" class="form-select" aria-label="Term">
                            <option value="">Term</option>
                            @foreach ($terms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="ccdcFilterPhase" class="form-select" aria-label="Phase">
                            <option value="">Phase</option>
                            @foreach ($phases as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="ccdcFilterType" class="form-select" aria-label="Examination Type">
                            <option value="">Examination Type</option>
                            @foreach ($examTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="ccdcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#ccdcColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="ccdcDtSearch" class="programme-dt-search d-none" data-dt-search-for="ccdcTable"></div>
                    <button type="button" id="ccdcSearchToggle" class="coe-search-toggle"
                            aria-controls="ccdcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="ccdcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. NO.</th>
                                <th>DRIVE NAME</th>
                                <th>EXAMINATION TYPE</th>
                                <th>TERM</th>
                                <th>COURSE</th>
                                <th>ACADEMIC SESSION</th>
                                <th>FACULTY</th>
                                <th class="coe-col-center">SUB-COORDINATES</th>
                                <th class="coe-col-center">COMPONENTS</th>
                                <th>START DATE</th>
                                <th>END DATE</th>
                                <th class="coe-col-center">STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Rows loaded via Yajra server-side AJAX --}}
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="ccdcTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="ccdcColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="ccdcColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
{{-- ── Approve: Exam Drive Date Confirmation ─────────────────────────────── --}}
<div class="modal fade" id="ccdcApproveModal" tabindex="-1" aria-labelledby="ccdcApproveLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="ccdcApproveForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="ccdcApproveLabel">Exam Drive Date Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="ccdcStart">Start Date<span class="coe-req">*</span></label>
                            <input type="date" id="ccdcStart" class="form-control coe-control ccdc-readonly-date" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="ccdcEnd">End Date<span class="coe-req">*</span></label>
                            <input type="date" id="ccdcEnd" class="form-control coe-control ccdc-readonly-date" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="ccdcProposedStart">Proposed Start Exam Date<span class="coe-req">*</span></label>
                            <input type="date" id="ccdcProposedStart" name="proposed_start_date" class="form-control coe-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="ccdcProposedEnd">Proposed End Exam Date<span class="coe-req">*</span></label>
                            <input type="date" id="ccdcProposedEnd" name="proposed_end_date" class="form-control coe-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="ccdcApproveSubmit">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    var dt = CoeGrid.init({
        table: '#ccdcTable',
        title: 'Examination Drive Configuration',
        snoCol: 0,
        order: [[1, 'asc']],
        noSort: [0, 12],
        exclude: [12],
        serverSide: true,
        processing: true,
        ajax: { url: @json(route('admin.coe.cc-examination-drive.index')), data: function (d) { d.scope = @json($scope); } },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex' },
            { data: 'name', name: 'name' },
            { data: 'exam_type', name: 'exam_type' },
            { data: 'term', name: 'term' },
            { data: 'course', name: 'course' },
            { data: 'academic_session', name: 'academic_session' },
            { data: 'faculty', name: 'faculty' },
            { data: 'sub_coords', name: 'sub_coords' },
            { data: 'components', name: 'components' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions' }
        ],
        filters: { '#ccdcFilterCourse': 4, '#ccdcFilterTerm': 3, '#ccdcFilterType': 2 },
        reset: '#ccdcResetFilters',
        download: '#ccdcDownloadBtn',
        print: '#ccdcPrintBtn',
        searchToggle: '#ccdcSearchToggle',
        searchSlot: '#ccdcDtSearch',
        colvisGrid: '#ccdcColumnToggleGrid',
        colvisKey: 'coeCcExamDrive:hiddenColumns:v1',
        empty: { title: 'No Drives', text: 'No examination drives of the courses you coordinate yet.' }
    });

    var CSRF = @json(csrf_token());
    var BASE = @json(url('admin/coe/cc-examination-drive'));
    var $table = $('#ccdcTable');
    var $modal = $('#ccdcApproveModal');
    var driveId = null;

    function fail(xhr, fallback) {
        var r = xhr.responseJSON || {};
        var msg = r.errors ? Object.values(r.errors)[0][0] : (r.message || fallback);
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    }

    /* Approve: confirm the drive dates and the proposed exam dates. */
    $table.on('click', '.coe-act--approve', function () {
        var $tr = $(this).closest('tr');
        driveId = $tr.attr('data-coe-id');
        $('#ccdcStart').val($tr.attr('data-cc-start') || '');
        $('#ccdcEnd').val($tr.attr('data-cc-end') || '');
        $('#ccdcProposedStart').val($tr.attr('data-cc-proposed-start') || '');
        $('#ccdcProposedEnd').val($tr.attr('data-cc-proposed-end') || '');
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $('#ccdcApproveForm').on('submit', function (e) {
        e.preventDefault();
        var start = $('#ccdcProposedStart').val(), end = $('#ccdcProposedEnd').val();
        if (!start || !end) {
            Swal.fire({ icon: 'warning', title: 'Dates needed', text: 'Enter the proposed start and end exam dates.' });
            return;
        }
        if (end < start) {
            Swal.fire({ icon: 'warning', title: 'Check the dates', text: 'The proposed end exam date cannot be before the start.' });
            return;
        }
        var $btn = $('#ccdcApproveSubmit').prop('disabled', true);
        $.ajax({ url: BASE + '/' + driveId + '/approve', method: 'PUT', headers: { 'X-CSRF-TOKEN': CSRF },
                 data: { proposed_start_date: start, proposed_end_date: end } })
            .done(function (res) {
                bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                dt.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Approved', text: res.message });
            })
            .fail(function (xhr) { fail(xhr, 'Could not approve the drive.'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* Reject: a reason is required; it shows on the status badge. */
    $table.on('click', '.coe-act--reject', function () {
        var id = $(this).closest('tr').attr('data-coe-id');
        Swal.fire({
            icon: 'warning', title: 'Reject examination drive?', input: 'textarea',
            inputLabel: 'Reason', inputPlaceholder: 'Why is this drive rejected?', inputAttributes: { maxlength: 500 },
            showCancelButton: true, confirmButtonText: 'Reject', confirmButtonColor: '#e5141a',
            inputValidator: function (v) { return $.trim(v) ? null : 'Enter the reason for rejecting.'; }
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({ url: BASE + '/' + id + '/reject', method: 'PUT', headers: { 'X-CSRF-TOKEN': CSRF },
                     data: { remarks: $.trim(r.value) } })
                .done(function (res) {
                    dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Rejected', text: res.message });
                })
                .fail(function (xhr) { fail(xhr, 'Could not reject the drive.'); });
        });
    });
});

</script>
@endpush
