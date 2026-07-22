@extends('admin.layouts.master')
@section('title', 'Visitor Pass Management')
@section('setup_content')
{{-- flatpickr: same pinned version the discipline-memo range picker uses. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
{{-- Choices.js: searchable dropdowns, same library the discipline-memo filters use. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    .vp-daterange-wrap { position: relative; display: inline-block; }
    .vp-daterange-icon { position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%); color: #667085; font-size: 0.9rem; pointer-events: none; }
    .vp-daterange-input { min-height: 38px; min-width: 215px; width: auto; padding: 0.375rem 1.9rem 0.375rem 2.1rem; border: 1px solid #d0d5dd; border-radius: 8px; background-color: #fff; font-size: 0.875rem; color: #344054; cursor: pointer; }
    .vp-daterange-input:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); outline: none; }
    .vp-daterange-clear { position: absolute; right: 0.45rem; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #94a3b8; line-height: 1; padding: 0.15rem; cursor: pointer; }
    .vp-daterange-clear:hover { color: #475467; }
    .vp-daterange-clear[hidden] { display: none; }
    .flatpickr-calendar.vp-flatpickr { box-shadow: 0 0.5rem 1.75rem rgba(16, 42, 76, 0.18); border-radius: 0.75rem; }
    .vp-flatpickr .flatpickr-day.selected, .vp-flatpickr .flatpickr-day.startRange, .vp-flatpickr .flatpickr-day.endRange { background: #004a93; border-color: #004a93; color: #fff; }
    .vp-flatpickr .flatpickr-day.inRange { background: #e7f0fa; border-color: #e7f0fa; box-shadow: -5px 0 0 #e7f0fa, 5px 0 0 #e7f0fa; }
    .vp-flatpickr .flatpickr-day.today { border-color: #004a93; }
    .vp-flatpickr .flatpickr-day:hover { background: #eef3f9; }
    .vp-filter-bar { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .vp-filter-label { color: #667085; font-weight: 600; font-size: 0.875rem; margin-right: 0.25rem; }
    .vp-reset { border: 1px solid #f04438; color: #f04438; background: #fff; font-weight: 600; font-size: 0.875rem; border-radius: 8px; min-height: 38px; padding: 0.375rem 1rem; display: inline-flex; align-items: center; text-decoration: none; }
    .vp-reset:hover { background: #fef3f2; color: #f04438; }
    .vp-icon-btn { border: 1px solid #d0d5dd; background: #fff; color: #344054; border-radius: 8px; min-height: 38px; padding: 0.375rem 0.85rem; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; }
    .vp-icon-btn:hover { background: #f9fafb; }
    .vp-search-input { min-height: 38px; border: 1px solid #d0d5dd; border-radius: 8px; padding: 0.375rem 0.75rem; font-size: 0.875rem; max-width: 220px; }
    .vp-filter-bar .choices { margin-bottom: 0; max-width: 220px; min-width: 160px; }
    .vp-filter-bar .choices__inner { min-height: 38px; padding: 4px 7.5px 4px 10px; background-image: none; }
</style>
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Visitor Pass Management'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Visitor Pass Management</h4>
                <a href="{{ route('admin.security.visitor_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Register New Visitor
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @php
                $today = \Carbon\Carbon::today()->toDateString();
            @endphp
            <form method="GET" action="{{ route('admin.security.visitor_pass.index') }}" id="vpFilterForm" class="vp-filter-bar mb-3">
                <span class="vp-filter-label">Filters</span>

                <select class="form-select" id="status" name="status" aria-label="Status">
                    <option value="">Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="checked_out" {{ $statusFilter === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                </select>

                <select class="form-select" id="employee_master_pk" name="employee_master_pk" aria-label="Host Employee">
                    <option value="">Host Employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->pk }}" {{ (string) $employeeFilter === (string) $emp->pk ? 'selected' : '' }}>
                            {{ $emp->first_name }} {{ $emp->last_name }}
                        </option>
                    @endforeach
                </select>

                <div class="vp-daterange-wrap">
                    <i class="bi bi-calendar3 vp-daterange-icon"></i>
                    <input type="text" class="form-control vp-daterange-input" id="vpDateRange"
                        placeholder="All dates" autocomplete="off" aria-label="Filter by date range">
                    <button type="button" class="vp-daterange-clear" id="vpDateRangeClear"
                        aria-label="Clear date range" title="Clear date range"
                        {{ ($fromDateFilter || $toDateFilter) ? '' : 'hidden' }}>
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <input type="hidden" id="from_date" name="from_date" value="{{ $fromDateFilter }}">
                <input type="hidden" id="to_date" name="to_date" value="{{ $toDateFilter }}">

                <a href="{{ route('admin.security.visitor_pass.index') }}" class="vp-reset">Reset Filters</a>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button type="button" class="vp-icon-btn" id="vpSearchToggle" aria-label="Search"><i class="bi bi-search"></i></button>
                    <div class="{{ $searchFilter ? '' : 'd-none' }}" id="vpSearchWrap" style="position:relative;">
                        <input type="text" class="vp-search-input" id="search" name="search"
                            placeholder="Search pass #, name, company..." value="{{ $searchFilter }}" autocomplete="off" style="padding-right:1.9rem;">
                        <button type="button" id="vpSearchClear" aria-label="Clear search" title="Clear"
                            style="position:absolute;top:50%;right:.35rem;transform:translateY(-50%);border:0;background:transparent;color:#94a3b8;line-height:1;padding:.15rem;{{ $searchFilter ? '' : 'display:none;' }}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Pass #</th>
                            <th>Visitor(s)</th>
                            <th>Company</th>
                            <th>Purpose</th>
                            <th>Host Employee</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visitorPasses as $pass)
                            <tr>
                                <td>{{ $pass->pass_number }}</td>
                                <td>
                                    @if($pass->visitorNames->count() > 0)
                                        @foreach($pass->visitorNames->take(2) as $vn)
                                            {{ $vn->visitor_name }}@if(!$loop->last),@endif
                                        @endforeach
                                        @if($pass->visitorNames->count() > 2)
                                            <small class="text-muted">(+{{ $pass->visitorNames->count() - 2 }} more)</small>
                                        @endif
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>{{ $pass->company ?? '--' }}</td>
                                <td>{{ Str::limit($pass->purpose, 30) }}</td>
                                <td>{{ $pass->employee ? $pass->employee->first_name . ' ' . $pass->employee->last_name : '--' }}</td>
                                <td>{{ $pass->in_time ? $pass->in_time->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    @if($pass->out_time)
                                        {{ $pass->out_time->format('d-m-Y H:i') }}
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.visitor_pass.show', encrypt($pass->pk)) }}" class="text-info" title="View">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                        </a>
                                        @if(!$pass->out_time)
                                            <form action="{{ route('admin.security.visitor_pass.checkout', encrypt($pass->pk)) }}" method="POST" onsubmit="return confirm('Mark visitor as checked out?')">
                                                @csrf
                                                <button type="submit" class="btn btn-link p-0 text-warning" title="Check Out">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">logout</i>
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.security.visitor_pass.edit', encrypt($pass->pk)) }}" class="text-success" title="Edit">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                            </a>
                                        @endif
                                        <form action="{{ route('admin.security.visitor_pass.delete', encrypt($pass->pk)) }}" method="POST" onsubmit="return confirm('Delete this visitor pass?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No visitor passes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $visitorPasses->links() }}
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Searchable dropdowns (Status, Host Employee) — same Choices.js setup the
    // discipline-memo filters use, so typing narrows the 448 active employees
    // instead of scrolling a plain <select>.
    ['status', 'employee_master_pk'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el || typeof window.Choices === 'undefined') return;
        var choice = new Choices(el, {
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
        el.addEventListener('change', function () { vpForm.submit(); });
    });

    // Local Y-m-d. NOT toISOString(), which converts to UTC first and so reports
    // the previous day for any date picked while east of UTC (IST is +5:30).
    function vpFmt(d) {
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    var vpFrom = document.getElementById('from_date');
    var vpTo = document.getElementById('to_date');
    var vpClearBtn = document.getElementById('vpDateRangeClear');
    var vpForm = document.getElementById('vpFilterForm');

    function vpSyncClearBtn() {
        if (vpClearBtn) vpClearBtn.hidden = !(vpFrom.value || vpTo.value);
    }

    var vpRangePicker = null;
    if (window.flatpickr) {
        vpRangePicker = flatpickr('#vpDateRange', {
            mode: 'range',
            showMonths: 2,
            dateFormat: 'Y-m-d',
            maxDate: "{{ $today }}",
            defaultDate: [vpFrom.value, vpTo.value].filter(Boolean),
            onReady: function (selectedDates, dateStr, inst) {
                inst.calendarContainer.classList.add('vp-flatpickr');
            },
            onChange: function (selectedDates) {
                // Range mode fires once on the first click too. Wait for both ends,
                // otherwise every range would filter twice — once as a bogus
                // single-day range on the way to the real one.
                if (selectedDates.length === 1) return;
                if (selectedDates.length === 2) {
                    vpFrom.value = vpFmt(selectedDates[0]);
                    vpTo.value = vpFmt(selectedDates[1]);
                } else {
                    vpFrom.value = '';
                    vpTo.value = '';
                }
                vpSyncClearBtn();
                vpForm.submit();
            }
        });
    }

    // Clearing = submit both dates present but EMPTY, which the controller reads
    // as "no date filter" (all dates) rather than as a first load (today only).
    if (vpClearBtn) {
        vpClearBtn.addEventListener('click', function () {
            if (vpRangePicker) vpRangePicker.clear(false);
            vpFrom.value = '';
            vpTo.value = '';
            vpSyncClearBtn();
            vpForm.submit();
        });
    }

    /* ── Search: toggle, debounced live filtering, clear ── */
    var vpSearchToggle = document.getElementById('vpSearchToggle');
    var vpSearchWrap = document.getElementById('vpSearchWrap');
    var vpSearchInput = document.getElementById('search');
    var vpSearchClear = document.getElementById('vpSearchClear');

    vpSearchToggle.addEventListener('click', function () {
        vpSearchWrap.classList.toggle('d-none');
        if (!vpSearchWrap.classList.contains('d-none')) vpSearchInput.focus();
    });

    var vpSearchTimer = null;
    vpSearchInput.addEventListener('input', function () {
        vpSearchClear.style.display = vpSearchInput.value ? '' : 'none';
        clearTimeout(vpSearchTimer);
        vpSearchTimer = setTimeout(function () { vpForm.submit(); }, 500);
    });

    vpSearchClear.addEventListener('click', function () {
        vpSearchInput.value = '';
        vpSearchClear.style.display = 'none';
        vpForm.submit();
    });
});
</script>
@endsection
