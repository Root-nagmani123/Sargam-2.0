@extends('admin.layouts.master')

@section('title', 'Discipline Memo - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/memo-discipline-admin.css') }}?v={{ @filemtime(public_path('css/memo-discipline-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $mdFromDate = $fromDateFilter ?: \Carbon\Carbon::today()->toDateString();
    $mdToDate = $toDateFilter ?: \Carbon\Carbon::today()->toDateString();
@endphp
<div class="container-fluid md-discipline-page">

    <x-breadcrum title="Discipline Memo">
        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training-Induction'))
        <a href="{{ route('memo.discipline.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Discipline Memo</span>
        </a>
        @endif
    </x-breadcrum>

    <x-session_message />
    <div class="d-flex justify-content-end mb-3 md-toolbar-top">
                <button type="button" class="btn md-btn-download" id="mdExportCsvBtn" aria-label="Download table data">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
            </div>
    <div class="card md-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-3 programme-dt-toolbar md-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label flex-shrink-0">Filters</span>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="program_name" class="visually-hidden">Program Name</label>
                            <select class="form-select" id="program_name" name="program_name" aria-label="Program Name">
                                <option value="">Program Name</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}
                                    data-course-code="{{ $course->course_code ?? '' }}">
                                    {{ $course->course_name }}
                                    @if(isset($course->course_code) && $course->course_code)
                                    ({{ $course->course_code }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="status" class="visually-hidden">Status</label>
                            <select class="form-select" id="status" name="status" aria-label="Status">
                                <option value="">Status</option>
                                <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Recorded</option>
                                <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Closed</option>
                                <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>

                        <input type="hidden" id="from_date" name="from_date" value="{{ $mdFromDate }}">
                        <input type="hidden" id="to_date" name="to_date" value="{{ $mdToDate }}">

                        <div class="programme-dt-filter-select md-time-period-filter md-time-period-range d-none d-lg-block position-relative flex-shrink-0">
                            <label for="md_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="md_time_period_picker"
                                class="form-control md-time-period-input"
                                placeholder="Time Period"
                                value=""
                                readonly
                                autocomplete="off"
                                aria-label="Time period">
                        </div>

                        <div class="md-time-period-mobile d-flex d-lg-none flex-wrap gap-2 w-100">
                            <div class="programme-dt-filter-select md-date-mobile-wrap flex-fill">
                                <label for="md_from_date_mobile" class="visually-hidden">From Date</label>
                                <input type="date"
                                    id="md_from_date_mobile"
                                    class="form-control md-date-mobile"
                                    value="{{ $mdFromDate }}"
                                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                                    aria-label="From date">
                            </div>
                            <div class="programme-dt-filter-select md-date-mobile-wrap flex-fill">
                                <label for="md_to_date_mobile" class="visually-hidden">To Date</label>
                                <input type="date"
                                    id="md_to_date_mobile"
                                    class="form-control md-date-mobile"
                                    value="{{ $mdToDate }}"
                                    max="{{ \Carbon\Carbon::today()->toDateString() }}"
                                    aria-label="To date">
                            </div>
                        </div>

                        <a href="{{ route('memo.discipline.index') }}" class="btn programme-dt-btn-reset flex-shrink-0">
                            Reset Filters
                        </a>
                    </div>

                    <div class="md-table-search-slot ms-xl-auto flex-shrink-0">
                        <div class="dropdown">
                            <button type="button"
                                class="btn md-search-trigger"
                                id="mdSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search records">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 md-search-menu">
                                <label for="search" class="form-label small text-secondary mb-2">Search</label>
                                <div class="input-group">
                                    <input type="search"
                                        class="form-control md-search-input shadow-none"
                                        id="search"
                                        name="search"
                                        placeholder="Student name, discipline, remarks..."
                                        value="{{ $searchFilter }}"
                                        autocomplete="off"
                                        aria-label="Search memos">
                                    <button type="submit" class="btn btn-primary" id="mdSearchSubmit" aria-label="Apply search">
                                        <i class="bi bi-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="programme-dt-panel md-dt-panel">
                <div class="table-responsive md-dt-scroll">
                    <table id="mdDisciplineTable" class="table table-hover align-middle mb-0 programme-dt-table md-dt-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-nowrap">#</th>
                            <th scope="col">Program Name</th>
                            <th scope="col">Participant Name</th>
                            <th scope="col" class="text-nowrap">Date</th>
                            <th scope="col">Discipline</th>
                            <th scope="col" class="text-center text-nowrap">Submitted</th>
                            <th scope="col" class="text-center text-nowrap">Final</th>
                            <th scope="col">Remarks</th>
                            <th scope="col" class="text-nowrap">Status</th>
                            @if(! hasRole('Student-OT'))
                            <th scope="col" class="text-end text-nowrap">Action</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @if ($memos->isEmpty())
                        <tr>
                            <td colspan="{{ !hasRole('Student-OT') ? 10 : 9 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <span class="fw-medium">No memo records available</span>
                            </td>
                        </tr>
                        @else
                        @foreach ($memos as $index => $memo)
                        <tr>
                            <!-- Serial -->
                            <td class="fw-semibold text-muted">
                                {{ $memos->firstItem() + $index }}
                            </td>

                            <!-- Program -->
                            <td class="fw-medium">{{ $memo->course->course_name ?? 'N/A' }}</td>

                            <!-- Participant -->
                            <td class="md-col-participant">{{ $memo->student->display_name ?? 'N/A' }}</td>

                            <!-- Date -->
                            <td class="text-muted">
                                {{ \Carbon\Carbon::parse($memo->date)->format('d M Y') }}
                            </td>

                            <!-- Discipline -->
                            <td>
                                {{ $memo->discipline->discipline_name ?? 'N/A' }}
                            </td>

                            <!-- Marks -->
                            <td class="text-center fw-semibold text-warning">
                                {{ $memo->mark_deduction_submit }}
                            </td>

                            <td class="text-center fw-semibold text-danger">
                                {{ $memo->final_mark_deduction }}
                            </td>

                            <!-- Remarks -->
                            <td class="text-muted small md-col-remarks">
                                {{ $memo->remarks ?? '-' }}
                            </td>

                            <!-- Status -->
                            <td class="sticky-status">
                                @if ($memo->status == 1)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i> Recorded
                                </span>
                                @elseif ($memo->status == 2)
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="bi bi-envelope me-1"></i> Memo Sent
                                </span>
                                <div class="mt-1 d-flex gap-2">
                                    <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}"
                                        class="link-primary small fw-medium">
                                        View Memo
                                    </a>

                                    <a class="text-success view-conversation" data-bs-toggle="offcanvas"
                                        data-bs-target="#chatOffcanvas" data-id="{{ $memo->pk }}"
                                        data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training-Induction')) ? 'admin' : 'OT' }}">
                                        <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                    </a>
                                </div>
                                @else
                                <a class="text-success view-conversation" data-bs-toggle="offcanvas"
                                    data-bs-target="#chatOffcanvas" data-id="{{ $memo->pk }}"
                                    data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training-Induction')) ? 'admin' : 'OT' }}">
                                    <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                </a>
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-x-circle me-1"></i> Closed
                                </span>
                                @endif
                            </td>

                            <!-- Action -->
                            @if(! hasRole('Student-OT'))
                            <td class="text-end">
                                @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin')
                                || hasRole('Training-Induction'))
                                @if($memo->status == 1)
                                <button class="btn btn-sm btn-outline-primary" data-discipline="{{ $memo->pk }}"
                                    id="sendMemoBtn">
                                    <i class="bi bi-envelope-paper me-1"></i> Send
                                </button>
                                @elseif($memo->status == 2)
                                <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}"
                                    class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle me-1"></i> Close
                                </a>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="md-pagination-nav">
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count md-dt-count mb-0">
                        Showing {{ $memos->firstItem() ?? 0 }}
                        to {{ $memos->lastItem() ?? 0 }}
                        of {{ $memos->total() }} items
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic"
        role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close conversation panel"
                title="Close">
            </button>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <!-- Chat Body with Enhanced Styling -->
            <div class="chat-body flex-grow-1" id="chatBody" role="log" aria-live="polite"
                aria-label="Conversation messages">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;

    function formatYmd(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function initMdDesktopTimePeriod() {
        if (window.mdTimePeriodPicker || typeof flatpickr === 'undefined') {
            return;
        }
        if (!document.getElementById('md_time_period_picker')) {
            return;
        }
        var fromVal = $('#from_date').val();
        var toVal = $('#to_date').val();
        window.mdTimePeriodPicker = flatpickr('#md_time_period_picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd.m.Y',
            defaultDate: [fromVal, toVal],
            maxDate: 'today',
            showMonths: window.innerWidth >= 1200 ? 2 : 1,
            locale: { rangeSeparator: ' to ' },
            onChange: function (selectedDates) {
                if (selectedDates[0]) {
                    $('#from_date').val(formatYmd(selectedDates[0]));
                }
                if (selectedDates.length > 1 && selectedDates[1]) {
                    $('#to_date').val(formatYmd(selectedDates[1]));
                } else if (selectedDates[0]) {
                    $('#to_date').val(formatYmd(selectedDates[0]));
                }
                updateFilterCount();
            },
            onClose: function () {
                if ($('#from_date').val() && $('#to_date').val()) {
                    $('#filterForm').submit();
                }
            }
        });
    }

    function destroyMdDesktopTimePeriod() {
        if (window.mdTimePeriodPicker) {
            window.mdTimePeriodPicker.destroy();
            window.mdTimePeriodPicker = null;
        }
    }

    function syncMdMobileDatesFromHidden() {
        $('#md_from_date_mobile').val($('#from_date').val());
        $('#md_to_date_mobile').val($('#to_date').val());
    }

    function applyMdTimePeriodMode() {
        if (window.matchMedia('(min-width: 992px)').matches) {
            initMdDesktopTimePeriod();
            if (window.mdTimePeriodPicker) {
                window.mdTimePeriodPicker.setDate([$('#from_date').val(), $('#to_date').val()], false);
            }
        } else {
            destroyMdDesktopTimePeriod();
            syncMdMobileDatesFromHidden();
        }
    }

    $('#md_from_date_mobile, #md_to_date_mobile').on('change', function () {
        $('#from_date').val($('#md_from_date_mobile').val());
        $('#to_date').val($('#md_to_date_mobile').val());
        $('#filterForm').submit();
    });

    applyMdTimePeriodMode();
    var mdDesktopMq = window.matchMedia('(min-width: 992px)');
    if (typeof mdDesktopMq.addEventListener === 'function') {
        mdDesktopMq.addEventListener('change', applyMdTimePeriodMode);
    } else if (typeof mdDesktopMq.addListener === 'function') {
        mdDesktopMq.addListener(applyMdTimePeriodMode);
    }

    function updateFilterCount() {
        var form = document.getElementById('filterForm');
        if (!form) return;
        var activeCount = 0;
        if ($('#program_name').val()) activeCount++;
        if ($('#status').val()) activeCount++;
        if ($('#search').val() && $('#search').val().trim() !== '') activeCount++;
        if ($('#from_date').val() || $('#to_date').val()) activeCount++;
        var el = document.getElementById('activeFilterCount');
        if (el) el.textContent = activeCount;
    }

    window.clearFilters = function () {
        var form = document.getElementById('filterForm');
        if (!form) return;
        form.querySelectorAll('select').forEach(function (select) { select.value = ''; });
        var search = document.getElementById('search');
        if (search) search.value = '';
        var today = formatYmd(new Date());
        $('#from_date').val(today);
        $('#to_date').val(today);
        syncMdMobileDatesFromHidden();
        if (window.mdTimePeriodPicker) {
            window.mdTimePeriodPicker.setDate([today, today], true);
        }
        form.submit();
    };

    window.removeFilter = function (filterName) {
        var input = document.querySelector('[name="' + filterName + '"]');
        if (input) input.value = '';
        document.getElementById('filterForm').submit();
    };

    window.removeDateFilters = function () {
        var today = formatYmd(new Date());
        $('#from_date').val(today);
        $('#to_date').val(today);
        syncMdMobileDatesFromHidden();
        document.getElementById('filterForm').submit();
    };

    updateFilterCount();
    $('#filterForm select, #search').on('change input', updateFilterCount);

    $('#search').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });

    var disciplineChoicesIds = ['program_name', 'status'];

    function initDisciplineChoices() {
        if (typeof window.Choices === 'undefined') return;
        disciplineChoicesIds.forEach(function (id) {
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

    initDisciplineChoices();

    $('#program_name, #status').on('change', function () {
        $('#filterForm').submit();
    });

    $('#mdExportCsvBtn').on('click', function () {
        var table = document.getElementById('mdDisciplineTable');
        if (!table) return;
        var rows = table.querySelectorAll('tr');
        var csv = [];
        rows.forEach(function (row) {
            var cols = row.querySelectorAll('th, td');
            var rowData = [];
            cols.forEach(function (col) {
                var text = (col.innerText || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                rowData.push('"' + text + '"');
            });
            if (rowData.length) csv.push(rowData.join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'discipline-memo.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });

    $(document).on('click', '#sendMemoBtn', function () {
        var discipline = $(this).data('discipline');

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to send the memo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004a93',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, send it!'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('memo.discipline.sendMemo') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        discipline_pk: discipline
                    },
                    success: function () {
                        Swal.fire('Sent!', 'The memo has been sent.', 'success').then(function () {
                            location.reload();
                        });
                    },
                    error: function () {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.view-conversation', function () {
        var memoId = $(this).data('id');
        var type = $(this).data('type');

        $('#conversationTopic').text('Topic: Discipline Conversation');
        $('#type_side_menu').text(type);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/memo/discipline/get_conversation_model/' + memoId + '/' + type,
            type: 'GET',
            success: function (res) {
                $('#chatBody').html(res);
            },
            error: function () {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            }
        });

        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('chatOffcanvas')).show();
    });
});
</script>
@endpush

@endsection