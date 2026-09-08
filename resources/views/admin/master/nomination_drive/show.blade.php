@extends('admin.layouts.master')

@section('title', 'View ' . $drive->drive_name)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid cs-master-page nd-view-page">
    {{-- The drive name is the parent crumb; this page is its View. --}}
    <x-breadcrum :title="'View ' . $drive->drive_name"
                 :items="[
                     ['label' => 'Home', 'url' => route('admin.dashboard')],
                     ['label' => 'Communications', 'url' => null],
                     ['label' => 'Club/ Society', 'url' => null],
                     ['label' => $drive->drive_name, 'url' => route('master.nomination.drive.index')],
                     ['label' => 'View', 'url' => null],
                 ]"
                 :show-back="true" />

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('master.nomination.drive.nominees.export', ['id' => $driveId]) }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('master.nomination.drive.nominees.print', ['id' => $driveId]) }}"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ndvBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ndvColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="ndvDtSearch" class="programme-dt-search" data-dt-search-for="nominationdriveview-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Client-side DataTable: a drive's nominee list is bounded
                         by its course roll, so all rows are rendered and the
                         global enhancer supplies search + pagination chrome. --}}
                    <table id="nominationdriveview-table"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Society Name</th>
                                <th>Post</th>
                                <th>Nominee Name</th>
                                <th class="text-center">OT Code</th>
                                <th class="text-center">No of Nominated By</th>
                                <th class="text-center">Required Nomination</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $row->club_society_name }}</td>
                                    <td>{{ $row->post_name }}</td>
                                    <td>{{ $row->nominee_name ?: '-' }}</td>
                                    <td class="text-center">{{ $row->ot_code ?: '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-link p-0 nd-nominators-link"
                                                data-society="{{ $row->club_society_master_pk }}"
                                                data-role="{{ $row->club_society_role_master_pk }}"
                                                data-nominee="{{ $row->nominee_student_pk }}"
                                                data-name="{{ $row->nominee_name }}">
                                            {{ $row->nominated_by_count }}
                                        </button>
                                    </td>
                                    <td class="text-center">{{ $row->required_nomination }}</td>
                                    <td class="text-center">
                                        @php
                                            $status = strtolower((string) $row->status);
                                            $cls = $status === 'accepted'
                                                ? 'programme-status-badge--active'
                                                : ($status === 'withdrawn' ? 'programme-status-badge--inactive' : 'nd-status-pending');
                                        @endphp
                                        <span class="badge rounded-1 programme-status-badge {{ $cls }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($canWithdraw && $status !== 'withdrawn')
                                            <button type="button" class="cs-action-btn nd-withdraw-btn"
                                                    data-id="{{ encrypt($row->nomination_pk) }}"
                                                    data-name="{{ $row->nominee_name }}"
                                                    aria-label="Withdraw nomination">
                                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                                <span>Withdraw</span>
                                            </button>
                                        @else
                                            <span class="text-body-secondary small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-body-secondary py-5">
                                        No nominations received for this drive yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="ndvDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="nominationdriveview-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ndvColumnVisibilityModal" tabindex="-1" aria-labelledby="ndvColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ndvColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="ndvColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        var TABLE_ID = '#nominationdriveview-table';
        var hasRows = $(TABLE_ID + ' tbody tr').length > 0
                   && $(TABLE_ID + ' tbody tr td[colspan]').length === 0;

        // Guard the init on an empty table: DataTables throws on a placeholder
        // row whose colspan does not match the header count.
        if (hasRows) {
            $(TABLE_ID).DataTable({
                responsive: true,
                autoWidth: false,
                ordering: false,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                order: [],
                language: {
                    search: '',
                    searchPlaceholder: 'Search',
                    paginate: { previous: '‹', next: '›' },
                    lengthMenu: 'Showing _MENU_',
                    info: 'of _TOTAL_ items',
                    infoEmpty: 'of 0 items',
                    infoFiltered: 'of _MAX_ items'
                }
            });

            // Renumber S. No. on every draw so it follows the visible page.
            var table = $(TABLE_ID).DataTable();
            table.on('draw.dt', function () {
                var start = table.page.info().start;
                table.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = start + i + 1;
                });
            });
            table.draw(false);

            /* ---- Column show / hide ---- */
            var $grid = $('#ndvColumnToggleGrid');
            var key = 'ndvGrid:hiddenColumns:v1';
            function hiddenCols() {
                try {
                    var raw = localStorage.getItem(key);
                    var arr = raw ? JSON.parse(raw) : [];
                    return Array.isArray(arr) ? arr : [];
                } catch (e) { return []; }
            }
            var hidden = hiddenCols();
            table.columns().every(function () {
                this.visible(hidden.indexOf(this.index()) === -1, false);
            });
            table.columns.adjust();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var inputId = 'ndvcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function () {
                    var h = hiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); }
                    else { if (pos === -1) h.push(idx); }
                    try { localStorage.setItem(key, JSON.stringify(h)); } catch (e) {}
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Withdraw a nomination ---- */
        var withdrawUrlTemplate = "{{ route('master.nomination.drive.nomination.withdraw', ['id' => '__ID__']) }}";

        $(document).on('click', '.nd-withdraw-btn', function () {
            var $btn = $(this);
            var name = $btn.data('name') || 'this nomination';

            Swal.fire({
                title: 'Withdraw nomination?',
                text: 'Withdraw the nomination for ' + name + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, withdraw',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                $btn.prop('disabled', true);

                $.ajax({
                    url: withdrawUrlTemplate.replace('__ID__', encodeURIComponent($btn.data('id'))),
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        Swal.fire('Withdrawn', (res && res.message) || 'Nomination withdrawn.', 'success');
                        setTimeout(function () { window.location.reload(); }, 900);
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message : 'Could not withdraw this nomination.';
                        Swal.fire('Error!', msg, 'error');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    });
</script>
@endpush
