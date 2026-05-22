@extends('admin.layouts.master')
@section('title', $form->form_name . ' — Report')

@section('setup_content')
<div class="container-fluid px-3">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-bar-chart-line me-2"></i>{{ $form->form_name }}
            </h4>
            <small class="text-muted">
                <code>{{ $form->form_slug }}</code> &nbsp;·&nbsp;
                {{ $totalSteps }} trackable step{{ $totalSteps !== 1 ? 's' : '' }} &nbsp;·&nbsp;
                Tracker table: <code>{{ $form->trackerStorageTable() }}</code>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Form-specific aggregated reports (only available for the original fc-registration form) --}}
            @if($form->form_slug === 'fc-registration')
                <a href="{{ route('admin.reports.service') }}"   class="btn btn-sm btn-outline-primary"><i class="bi bi-briefcase me-1"></i>By Service</a>
                <a href="{{ route('admin.reports.state') }}"     class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>By State</a>
                <a href="{{ route('admin.reports.documents') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-check me-1"></i>Documents</a>
                <a href="{{ route('admin.reports.bank') }}"      class="btn btn-sm btn-outline-primary"><i class="bi bi-bank me-1"></i>Bank Details</a>
                <a href="{{ route('admin.travel.index') }}"      class="btn btn-sm btn-outline-primary"><i class="bi bi-train-front me-1"></i>Travel Plans</a>
                <a href="{{ route('admin.reports.export','overview') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                </a>
            @else
                <a href="{{ route('admin.reports.form.export', $form) }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                </a>
            @endif
            <a href="{{ route('fc-reg.admin.forms.edit', $form) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil-square me-1"></i>Edit Form
            </a>
            <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Forms
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        {{-- Fixed cards --}}
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi bi-people-fill fs-4" style="color:#1a3c6e;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#1a3c6e;">{{ number_format($summary['total']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Total Registered</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi bi-send-check-fill fs-4" style="color:#16a34a;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#16a34a;">{{ number_format($summary['submitted']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Submitted</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi bi-hourglass-split fs-4" style="color:#d97706;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#d97706;">{{ number_format($summary['incomplete']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Incomplete</div>
            </div>
        </div>
        {{-- One card per step --}}
        @foreach($steps as $step)
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi {{ $step->icon ?? 'bi-check-circle-fill' }} fs-4" style="color:#7c3aed;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#7c3aed;">
                    {{ number_format($summary[$step->tracker_column] ?? 0) }}
                </div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">{{ Str::limit($step->step_name, 18) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ request()->url() }}" class="row g-2 align-items-end fc-overview-filter-form">
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="SUBMITTED"  {{ request('status')=='SUBMITTED'?'selected':'' }}>Submitted</option>
                        <option value="INCOMPLETE" {{ request('status')=='INCOMPLETE'?'selected':'' }}>Incomplete</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Service</label>
                    <select name="service_id" class="form-select form-select-sm">
                        <option value="">All Services</option>
                        @foreach($services as $sv)
                            @php $key = $sv->pk ?? $sv->id; @endphp
                            <option value="{{ $key }}" {{ (string)request('service_id') === (string)$key ? 'selected' : '' }}>
                                {{ $sv->service_short_name ?? $sv->service_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control"
                               placeholder="Name / Username / Mobile"
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary btn-sm px-2"><i class="bi bi-search"></i></button>
                        <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-sm px-2"><i class="bi bi-x"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div id="fc-overview-table-wrapper" class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-header bg-white border-bottom py-2 px-3">
            <span class="small fw-semibold">
                Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Service</th>
                        <th>Cadre</th>
                        <th>State</th>
                        <th>Mobile</th>
                        {{-- Dynamic step columns --}}
                        @foreach($steps as $step)
                            <th class="text-center" title="{{ $step->step_name }}">
                                {{ Str::limit($step->step_name, 10) }}
                            </th>
                        @endforeach
                        <th class="text-center">Progress</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $idx => $s)
                    <tr>
                        <td class="px-3">{{ $students->firstItem() + $idx }}</td>
                        <td><code style="font-size:11px">{{ $s->{$userKey} }}</code></td>
                        <td>{{ $s->full_name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">
                                {{ $s->service_code ?? '—' }}
                            </span>
                        </td>
                        <td>{{ $s->cadre ?? '—' }}</td>
                        <td>{{ $s->allotted_state ?? '—' }}</td>
                        <td>{{ $s->mobile_no ?? '—' }}</td>
                        {{-- Dynamic tick/cross per step --}}
                        @foreach($steps as $step)
                            <td class="text-center">
                                @if($s->{$step->tracker_column} ?? false)
                                    <i class="bi bi-check-circle-fill text-success" style="font-size:13px;"></i>
                                @else
                                    <i class="bi bi-circle text-secondary" style="font-size:13px;opacity:.4;"></i>
                                @endif
                            </td>
                        @endforeach
                        {{-- Progress bar --}}
                        <td class="text-center">
                            @if($totalSteps > 0)
                                <div class="progress" style="height:6px;width:60px;margin:auto;">
                                    <div class="progress-bar bg-success"
                                         style="width:{{ ($s->steps_done / $totalSteps) * 100 }}%"></div>
                                </div>
                                <span style="font-size:10px;color:#666;">{{ $s->steps_done }}/{{ $totalSteps }}</span>
                            @else
                                <span class="text-muted" style="font-size:10px;">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($s->status === 'SUBMITTED')
                                <span class="badge bg-success" style="font-size:10px;">Submitted</span>
                            @else
                                <span class="badge bg-warning text-dark" style="font-size:10px;">Incomplete</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.reports.student', $s->{$userKey}) . '?ref=' . urlencode(request()->fullUrl()) }}"
                               class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px;">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 8 + $totalSteps + 3 }}" class="text-center py-4 text-muted">
                            No students found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2 px-3">
            {{ $students->links() }}
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var form    = document.querySelector('.fc-overview-filter-form');
    var wrapper = document.getElementById('fc-overview-table-wrapper');
    var timer;

    function doFetch(url) {
        wrapper.style.opacity = '0.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var doc     = new DOMParser().parseFromString(html, 'text/html');
                var newWrap = doc.getElementById('fc-overview-table-wrapper');
                if (newWrap) {
                    wrapper.innerHTML = newWrap.innerHTML;
                }
                wrapper.style.opacity = '';
                history.pushState(null, '', url);
            })
            .catch(function () { wrapper.style.opacity = ''; });
    }

    function buildUrl() {
        var params = new FormData(form);
        return form.action + '?' + new URLSearchParams(params).toString();
    }

    // Intercept explicit form submit (search button)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        doFetch(buildUrl());
    });

    // Dropdowns fire immediately
    form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', function () { doFetch(buildUrl()); });
    });

    // Search text: debounce 400 ms
    var searchEl = form.querySelector('input[name="search"]');
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { doFetch(buildUrl()); }, 400);
        });
    }

    // Clear button (the ×  link) — intercept and reset
    form.querySelector('a.btn-outline-secondary')?.addEventListener('click', function (e) {
        e.preventDefault();
        form.querySelectorAll('select').forEach(function (s) { s.value = ''; });
        if (searchEl) searchEl.value = '';
        doFetch(form.action);
    });

    // Pagination links inside the wrapper — only intercept links back to this same page
    document.addEventListener('click', function (e) {
        var link = e.target.closest('#fc-overview-table-wrapper a[href]');
        if (!link) { return; }
        // Only intercept pagination links (same path as current page); let eye-icon etc. navigate normally
        try {
            var linkPath = new URL(link.href).pathname;
            if (linkPath !== window.location.pathname) { return; }
        } catch (err) { return; }
        e.preventDefault();
        doFetch(link.href);
    });
})();
</script>
@endpush
