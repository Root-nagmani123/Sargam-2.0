@extends('admin.layouts.master')
@section('title', 'List of Family Members')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* Photo "DOWNLOAD" links inside the grid */
.family-members-page .grid-download-link { font-weight: 600; font-size: 0.8125rem; text-decoration: none; }
.family-members-page .grid-download-link:hover { text-decoration: underline; }
@media print {
    .btn, .breadcrumb, .programme-dt-toolbar, .programme-dt-footer { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid family-members-page py-3">
    <x-breadcrum title="List of Family Members — Parent ID: {{ $parentId ?? '--' }}"></x-breadcrum>
    <x-session_message />

    @php
        // Where approvers came from (query ?from=...) — avoid sending them to user "Request Family ID Card" by mistake.
        $membersFrom = (string) request('from', '');
        $membersBack = match ($membersFrom) {
            'family_approval' => [
                'url' => route('admin.security.family_idcard_approval.index', array_filter([
                    'return' => in_array(request('return'), ['approval2', 'approval3'], true) ? request('return') : null,
                ])),
                'label' => 'Back to Family Approval List',
            ],
            'approval2' => [
                'url' => route('admin.security.employee_idcard_approval.approval2'),
                'label' => 'Back to Approval II',
            ],
            'approval3' => [
                'url' => route('admin.security.employee_idcard_approval.approval3'),
                'label' => 'Back to Approval III',
            ],
            default => [
                'url' => route('admin.family_idcard.index'),
                'label' => 'Back to Request List',
            ],
        };
    @endphp

    {{-- Back (left) · Print (right) — above the card --}}
    <div class="d-flex flex-wrap justify-content-end align-items-end gap-3 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="membersPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="membersBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#membersColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="membersDtSearch" class="programme-dt-search" data-dt-search-for="familyMembersTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="familyMembersTable">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Request Date</th>
                                <th>Guardians Details</th>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Relation</th>
                                <th>Date of Birth</th>
                                <th>Individual Photo</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Family Photo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($members as $index => $member)
                                @php
                                    $reqTs = $member->created_at ? \Carbon\Carbon::parse($member->created_at)->timestamp : 0;
                                    $dobTs = $member->dob ? \Carbon\Carbon::parse($member->dob)->timestamp : 0;
                                    $vfTs = $member->valid_from ? \Carbon\Carbon::parse($member->valid_from)->timestamp : 0;
                                    $vtTs = $member->valid_to ? \Carbon\Carbon::parse($member->valid_to)->timestamp : 0;
                                @endphp
                                <tr class="member-row">
                                    <td class="fw-medium ps-3">{{ $index + 1 }}</td>
                                    <td data-order="{{ $reqTs }}">{{ $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('d-m-Y') : '--' }}</td>
                                    <td>
                                        @if(!empty($member->guardian_name))
                                            <strong>{{ $member->guardian_name }}</strong>
                                            @if(!empty($member->guardian_designation))
                                                <br><small class="text-muted">{{ $member->guardian_designation }}</small>
                                            @endif
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ $parentId ?? '--' }} / {{ $member->id ?? $member->fml_id_apply ?? '--' }}</td>
                                    <td>{{ $member->name ?? '--' }}</td>
                                    <td>{{ $member->relation ?? '--' }}</td>
                                    <td data-order="{{ $dobTs }}">{{ $member->dob ? \Carbon\Carbon::parse($member->dob)->format('d-m-Y') : '--' }}</td>
                                    <td>
                                        @php
                                            $indPath = $member->id_photo_path ?: $member->family_photo;
                                            $indExists = $indPath && \Storage::disk('public')->exists($indPath);
                                        @endphp
                                        @if($indExists)
                                            <a href="{{ asset('storage/' . $indPath) }}" target="_blank" class="grid-download-link text-primary">DOWNLOAD</a>
                                        @elseif($indPath)
                                            <span class="text-warning small">No file available</span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td data-order="{{ $vfTs }}">{{ $member->valid_from ? \Carbon\Carbon::parse($member->valid_from)->format('d-m-Y') : '--' }}</td>
                                    <td data-order="{{ $vtTs }}">{{ $member->valid_to ? \Carbon\Carbon::parse($member->valid_to)->format('d-m-Y') : '--' }}</td>
                                    <td>
                                        @php
                                            $famPath = $member->family_photo;
                                            $famExists = $famPath && \Storage::disk('public')->exists($famPath);
                                        @endphp
                                        @if($famExists)
                                            <a href="{{ asset('storage/' . $famPath) }}" target="_blank" class="grid-download-link text-primary">DOWNLOAD</a>
                                        @elseif($famPath)
                                            <span class="text-warning small">No file available</span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ $member->status_label ?? 'Pending' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">group</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No family members found for this request.</p>
                                            <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-primary rounded-1 px-4 py-2 mt-2">Back to List</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="familyMembersTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="membersColumnVisibilityModal" tabindex="-1" aria-labelledby="membersColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="membersColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="membersColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var TABLE_ID = '#familyMembersTable';
    var $table = $(TABLE_ID);

    // No real data rows (only the empty-state) -> skip DataTables so the CTA shows.
    if (!$table.length || $table.find('tbody tr.member-row').length === 0) { return; }

    var table = $table.DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        columnDefs: [
            { targets: [0, 7, 10], orderable: false, searchable: false }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search',
            paginate: { previous: '‹', next: '›' },
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: 'of _MAX_ items'
        },
        drawCallback: function () {
            var info = this.api().page.info();
            this.api().column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        }
    });

    /* ---- Print (client-side) ---- */
    if (typeof $.fn.dataTable.Buttons !== 'undefined') {
        new $.fn.dataTable.Buttons(table, {
            buttons: [{
                extend: 'print',
                className: 'members-btn-print',
                title: 'Family Members (Parent ID: {{ $parentId ?? '--' }})',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 8, 9, 11] }
            }]
        });
        $('#membersPrintBtn').on('click', function () { table.button('.members-btn-print').trigger(); });
    }

    /* ---- Column show / hide ---- */
    var colKey = 'familyMembersGrid:hiddenColumns:v1';
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(colKey) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(colKey, JSON.stringify(a)); } catch (e) {} }

    function setupColumns(dt) {
        var hidden = getHidden();
        dt.columns().every(function () { var idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
        dt.columns.adjust();

        var $grid = $('#membersColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'memberscolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
            $cb.on('change', function () {
                var h = getHidden(); var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                setHidden(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
            });
            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label); $grid.append($cell);
        });
    }

    setupColumns(table);
});
</script>
@endpush
