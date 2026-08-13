@extends('admin.layouts.master')

@section('title', 'Discipline Memos')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="{{ asset('css/notice-memo-discipline.css') }}?v={{ @filemtime(public_path('css/notice-memo-discipline.css')) ?: time() }}">

<div class="container-fluid disc-page">
    <x-breadcrum title="Discipline Memos" />
    <x-session_message />

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body">
            @php
                $today   = \Carbon\Carbon::today()->toDateString();
                $hasRange = $fromDateFilter || $toDateFilter;
            @endphp

            {{-- Filters — searchable dropdowns + time-period preset; full-page GET reload --}}
            <form method="GET" action="{{ route('memo.discipline.ot_index') }}" id="otFilterForm">
                <div class="disc-filter-bar mb-3">
                    <span class="disc-filter-label">Filters</span>

                    <select class="form-select" id="program_name" name="program_name" aria-label="Program Name">
                        <option value="">Program Name</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->pk }}"
                            {{ (string)$programNameFilter === (string)$course->pk ? 'selected' : '' }}>
                            {{ $course->course_name }}
                        </option>
                        @endforeach
                    </select>

                    <select class="form-select" id="discipline_master_pk" name="discipline_master_pk" aria-label="Discipline Type">
                        <option value="">Discipline Type</option>
                        @foreach($disciplines as $disc)
                        <option value="{{ $disc->discipline_name }}"
                            {{ $disciplineFilter === $disc->discipline_name ? 'selected' : '' }}>
                            {{ $disc->discipline_name }}
                        </option>
                        @endforeach
                    </select>

                    <select class="form-select" id="status" name="status" aria-label="Status">
                        <option value="">Status</option>
                        <option value="1" {{ (string)$statusFilter === '1' ? 'selected' : '' }}>Recorded</option>
                        <option value="2" {{ (string)$statusFilter === '2' ? 'selected' : '' }}>Memo Sent</option>
                        <option value="3" {{ (string)$statusFilter === '3' ? 'selected' : '' }}>Closed</option>
                    </select>

                    <select class="form-select" id="minor_major" name="minor_major" aria-label="Category">
                        <option value="">Category</option>
                        <option value="2" {{ (string)$categoryFilter === '2' ? 'selected' : '' }}>Major</option>
                        <option value="1" {{ (string)$categoryFilter === '1' ? 'selected' : '' }}>Minor</option>
                    </select>

                    <select class="form-select" id="otTimePeriod" aria-label="Time Period">
                        <option value="all" {{ !$hasRange ? 'selected' : '' }}>All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom" {{ $hasRange ? 'selected' : '' }}>Custom Range</option>
                    </select>

                    <input type="date" class="form-control {{ $hasRange ? '' : 'd-none' }}" id="from_date"
                        name="from_date" value="{{ $fromDateFilter }}" max="{{ $today }}" style="max-width:160px;" aria-label="From date">
                    <input type="date" class="form-control {{ $hasRange ? '' : 'd-none' }}" id="to_date"
                        name="to_date" value="{{ $toDateFilter }}" max="{{ $today }}" style="max-width:160px;" aria-label="To date">

                    <a href="{{ route('memo.discipline.ot_index') }}" class="disc-reset">Reset Filters</a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="disc-icon-btn" id="discSearchToggle" aria-label="Search"><i class="bi bi-search"></i></button>
                        <div class="disc-search-wrap {{ $searchFilter ? '' : 'd-none' }}" id="discSearchWrap" style="position:relative;">
                            <input type="text" class="disc-search-input" id="search" name="search" placeholder="Search..."
                                value="{{ $searchFilter }}" autocomplete="off" style="padding-right:1.9rem;">
                            <button type="button" id="discSearchClear" aria-label="Clear search" title="Clear"
                                style="position:absolute;top:50%;right:.35rem;transform:translateY(-50%);border:0;background:transparent;color:#94a3b8;line-height:1;padding:.15rem;{{ $searchFilter ? '' : 'display:none;' }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <hr class="my-3">

            <div class="table-responsive">
                <table class="table align-middle mb-0 text-nowrap" id="otMemosTable">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Program Name</th>
                            <th>Date of Infraction</th>
                            <th>Infraction</th>
                            <th>Category</th>
                            <th class="text-center">Marks Submitted</th>
                            <th class="text-center">Final Marks</th>
                            <th>Remarks</th>
                            <th>Conclusion Remark</th>
                            <th>Created Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    {{-- Rows come from the server-side DataTable (see script below). --}}
                    <tbody></tbody>
                </table>
            </div>


        </div>
    </div>

    {{-- Conversation offcanvas — the reply form + send/poll logic ship inside the
         AJAX-loaded conversation partial, so we only need the shell + loader here. --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic" role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close conversation panel" title="Close"></button>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <div class="chat-body flex-grow-1" id="chatBody" role="log" aria-live="polite" aria-label="Conversation messages">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading conversation...</span>
                        </div>
                        <p class="text-muted">Loading conversation...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function () {

    /* ── Memo list: server-side grid. The filter bar above still submits as a normal
          GET, so its values ride along in the URL and narrow the query first. ── */
    $('#otMemosTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ request()->fullUrl() }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'fw-semibold text-muted' },
            { data: 'program_name', name: 'program_name', orderable: false, className: 'fw-semibold' },
            { data: 'infraction_date', name: 'infraction_date', className: 'text-muted' },
            { data: 'infraction', name: 'infraction', orderable: false },
            { data: 'category', name: 'minor_major', searchable: false },
            { data: 'marks_submitted', name: 'mark_deduction_submit', className: 'text-center fw-semibold text-warning' },
            { data: 'final_marks', name: 'final_mark_deduction', className: 'text-center fw-semibold text-danger' },
            { data: 'remarks_text', name: 'remarks_text', orderable: false, className: 'text-muted' },
            { data: 'conclusion_text', name: 'conclusion_text', orderable: false, className: 'text-muted' },
            { data: 'created', name: 'created', className: 'text-muted' },
            { data: 'status_cell', name: 'status', searchable: false }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        autoWidth: false,
        language: {
            processing: 'Loading data…',
            emptyTable: 'No discipline memos available',
            zeroRecords: 'No discipline memos match your search'
        }
    });

    /* ── Searchable dropdowns (match the admin page) ── */
    if (typeof window.Choices !== 'undefined') {
        ['program_name', 'discipline_master_pk', 'status', 'minor_major', 'otTimePeriod'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || el.dataset.choicesInitialized === 'true') return;
            new Choices(el, {
                shouldSort: false,
                searchEnabled: true,
                searchResultLimit: 50,
                itemSelectText: '',
                allowHTML: false,
                classNames: {
                    containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                    input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                    inputCloned: ['choices__input--cloned'],
                    listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                    item: ['choices__item', 'dropdown-item', 'rounded-0'],
                    itemSelectable: ['choices__item--selectable'],
                    itemDisabled: ['choices__item--disabled', 'disabled'],
                    itemChoice: ['choices__item--choice'],
                    placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                    highlightedState: ['is-highlighted', 'active'],
                    notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2']
                }
            });
            el.dataset.choicesInitialized = 'true';
        });
    }

    function otRunFilter() { document.getElementById('otFilterForm').submit(); }

    // Dropdowns + explicit date pickers reload immediately.
    $('#program_name, #discipline_master_pk, #status, #minor_major, #from_date, #to_date').on('change', otRunFilter);

    /* ── Time Period preset → from/to dates ── */
    function otFmt(d) { return d.toISOString().split('T')[0]; }
    $('#otTimePeriod').on('change', function () {
        var v = $(this).val();
        var today = new Date();
        $('#from_date, #to_date').addClass('d-none');
        if (v === 'custom') {
            $('#from_date, #to_date').removeClass('d-none');
            return; // let the user pick a range, then the date-change handler reloads
        }
        var from = '', to = '';
        if (v === 'today') {
            from = to = otFmt(today);
        } else if (v === 'week') {
            var ws = new Date(today);
            ws.setDate(today.getDate() - today.getDay());
            from = otFmt(ws); to = otFmt(today);
        } else if (v === 'month') {
            from = otFmt(new Date(today.getFullYear(), today.getMonth(), 1)); to = otFmt(today);
        }
        // 'all' → leave both empty (controller returns full history)
        $('#from_date').val(from);
        $('#to_date').val(to);
        otRunFilter();
    });

    /* ── Search: toggle, Enter to search, clear ── */
    $('#discSearchToggle').on('click', function () {
        var $wrap = $('#discSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) { $('#search').trigger('focus'); }
    });
    $('#search').on('input', function () {
        $('#discSearchClear').toggle(this.value.length > 0);
    });
    $('#search').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); otRunFilter(); }
    });
    $('#discSearchClear').on('click', function () {
        var $s = $('#search');
        $s.val('');
        $(this).hide();
        otRunFilter();
    });

    // Open a discipline-memo conversation in the offcanvas (Officer Trainee view).
    $(document).on('click', '.view-conversation', function () {
        var memoId = $(this).data('id');
        var type   = $(this).data('type') || 'OT';

        $('#conversationTopic').text('Discipline Memo Conversation');
        $('#type_side_menu').text('Officer Trainee view');
        $('#userType').val(type);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/memo/discipline/get_conversation_model/' + memoId + '/' + type,
            type: 'GET',
            success: function (res) { $('#chatBody').html(res); },
            error: function () {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            }
        });

        var oc = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        oc.show();
    });
});
</script>
@endpush
