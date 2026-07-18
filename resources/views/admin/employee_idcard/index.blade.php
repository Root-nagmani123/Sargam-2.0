@extends('admin.layouts.master')
@section('title', 'Employee ID Card List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* --- "Time Period" dual-month range calendar (ported from Medical Exemption Report) --- */
.idcp-toggle { min-width: 150px; }
.idcp-toggle.dropdown-toggle::after { margin-left: auto; }
.idcp-menu { min-width: auto; }
.idcp-cal { padding: var(--ds-space-3); }
.idcp-cal-months { display: flex; gap: var(--ds-space-4); }
@media (max-width: 575.98px) { .idcp-cal-months { flex-direction: column; gap: var(--ds-space-3); } }
.idcp-cal-month { width: 232px; }
.idcp-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2); }
.idcp-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.idcp-cal-nav {
    border: 0; background: transparent; width: 28px; height: 28px; border-radius: var(--ds-radius-1);
    color: var(--ds-ink-muted); display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.idcp-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.idcp-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted); padding: 4px 0; }
.idcp-cal-day {
    aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1);
    font-size: 0.8125rem; color: var(--ds-ink); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.idcp-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.idcp-cal-day.is-start, .idcp-cal-day.is-end { background: var(--bs-primary); color: #fff; }
.idcp-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.idcp-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.idcp-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.idcp-cal-footer {
    display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2);
    margin-top: var(--ds-space-3); padding-top: var(--ds-space-3); border-top: 1px solid var(--ds-line);
}
.idcp-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }
</style>
@endpush

@section('content')
<div class="container-fluid idcard-index-page">
    <x-breadcrum title="Employee ID Card List">
        <a href="{{ route('admin.employee_idcard.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add New ID card</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    @php
        $exportParams = array_filter([
            'search' => $search ?? '',
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
            'list_status' => ($list_status ?? 'all') !== 'all' ? ($list_status ?? '') : '',
        ]);
    @endphp

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters (left) · print/download/columns/search (right) --}}
            <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3 mb-4">

                {{-- Filters (applied instantly in-browser — no page reload) --}}
                <div id="idcardFilterForm" class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label for="listStatus" class="form-label small text-muted mb-1">Approval status</label>
                        @php $ls = old('list_status', $list_status ?? 'all'); @endphp
                        <select id="listStatus" class="form-select">
                            <option value="all" {{ $ls === 'all' ? 'selected' : '' }}>All</option>
                            <option value="pending" {{ $ls === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $ls === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $ls === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="issued" {{ $ls === 'issued' ? 'selected' : '' }}>Issued</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Time Period</label>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 idcp-toggle dropdown-toggle"
                                    id="idcardTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                                <span id="idcardTimePeriodLabel">Time Period</span>
                            </button>
                            <div class="dropdown-menu p-0 idcp-menu">
                                <div class="idcp-cal" id="idcardCalendar">
                                    <div class="idcp-cal-months">
                                        <div class="idcp-cal-month" data-month="0"></div>
                                        <div class="idcp-cal-month" data-month="1"></div>
                                    </div>
                                    <div class="idcp-cal-footer">
                                        <span class="idcp-cal-range" id="idcardCalRange">Select a date range</span>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="idcardClearPeriod">Clear</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="idcardApplyPeriod">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Hidden inputs keep the existing filter/export logic (which reads date_from / date_to) working. --}}
                        <input type="hidden" id="dateFrom" value="{{ old('date_from', $dateFrom ?? '') }}">
                        <input type="hidden" id="dateTo" value="{{ old('date_to', $dateTo ?? '') }}">
                    </div>
                    <div>
                        <button type="button" id="idcardClearFilters" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Clear
                        </button>
                    </div>
                </div>

                {{-- Print · Download · Columns · Search --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn programme-dt-btn-columns" id="idcardPrintBtn" title="Print">
                        <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn programme-dt-btn-columns dropdown-toggle" id="idcardDownloadBtn"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                            <i class="bi bi-download" aria-hidden="true"></i> <span>Download</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="idcardDownloadBtn">
                            <li>
                                <a href="#" class="dropdown-item d-flex align-items-center gap-2 py-2 export-link"
                                   data-format="pdf"
                                   data-base-url="{{ route('admin.employee_idcard.export', array_merge(['format' => 'pdf'], $exportParams)) }}">
                                    <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> Download PDF
                                </a>
                            </li>
                            <li>
                                <a href="#" class="dropdown-item d-flex align-items-center gap-2 py-2 export-link"
                                   data-format="xlsx"
                                   data-base-url="{{ route('admin.employee_idcard.export', array_merge(['format' => 'xlsx'], $exportParams)) }}">
                                    <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Download Excel
                                </a>
                            </li>
                        </ul>
                        <span id="exportHeaderLabel" class="d-none">Active (with current filter)</span>
                    </div>
                    <button type="button" class="btn programme-dt-btn-columns" id="idcardBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#idcardColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="idcardDtSearch" class="programme-dt-search" data-dt-search-for="activeIdcardTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div id="active-panel">
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle idcard-index-table programme-dt-table" id="activeIdcardTable">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>ID Card</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Card Type</th>
                                    <th>Request For</th>
                                    {{--<th>Duplication</th>
                                    <th>Extension</th> --}}
                                    <th>Valid Upto</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}"
                                        data-status="{{ $request->status ?? '' }}"
                                        data-ts="{{ $request->created_at ? $request->created_at->timestamp : 0 }}"
                                        class="align-middle">
                                        <td class="fw-medium ps-4">{{ $loop->iteration }}</td>
                                        <td>
                                            @php
                                                $photoExists = $request->photo && \Storage::disk('public')->exists($request->photo);
                                            @endphp
                                            @if($photoExists)
                                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="d-inline-block rounded-2 overflow-hidden shadow-sm">
                                                    <img src="{{ asset('storage/' . $request->photo) }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                                </a>
                                            @else
                                                <img src="{{ asset('images/dummypic.jpeg') }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                            @endif
                                        </td>
                                        <td data-order="{{ $request->created_at ? $request->created_at->timestamp : 0 }}">{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $request->name }}</td>
                                        <td>{{ $request->designation ?? '--' }}</td>
                                        <td>{{ $request->card_type ?? '--' }}</td>
                                        <td>{{ $request->request_for ?? '--' }}</td>
                                        
                                        <td>{{ $request->id_card_valid_upto ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status ?? '') {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                                $statusTooltip = match ($request->status ?? '') {
                                                    'Pending' => $request->pending_status_tooltip ?? 'Pending',
                                                    'Approved' => 'Please collect your ID card from security section',
                                                    default => null,
                                                };
                                            @endphp
                                            <span class="badge rounded-1 bg-{{ $statusClass }}"
                                                  @if($statusTooltip) data-bs-toggle="tooltip" data-bs-placement="top" title="{{ e($statusTooltip) }}" @endif>
                                                {{ $request->status ?? '--' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php $canApplicantEdit = $request->user_may_edit_request ?? false; @endphp
                                            <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}"
                                                   class="programme-action-btn view-details-btn" title="View Details" data-request-id="{{ $request->id }}" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-request-for="{{ $request->request_for ?? '--' }}" data-duplication="{{ $request->duplication_reason ?? '--' }}" data-extension="{{ $request->id_card_valid_upto ?? '--' }}" data-valid-from="{{ $request->id_card_valid_from ?? '' }}" data-id-number="{{ $request->id_card_number ?? '' }}" data-valid-upto="{{ $request->id_card_valid_upto ?? '--' }}" data-status="{{ $request->status ?? '--' }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </a>
                                                @if($canApplicantEdit)
                                                <a href="{{ route('admin.employee_idcard.edit', $request->id) }}"
                                                   class="programme-action-btn" title="Edit" data-bs-toggle="tooltip" data-bs-placement="top">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Archive">
                                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-5 table-empty-state">
                                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inbox</i>
                                                <p class="mb-1 fw-semibold text-body-emphasis">No ID card requests found.</p>
                                                <small class="text-body-secondary mb-4">Click "Generate New ID Card" to create one.</small>
                                                <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-primary rounded-3 px-4 py-2">
                                                    <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                                    Create Request
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div id="idcardDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                         data-dt-footer-for="activeIdcardTable"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="idcardColumnVisibilityModal" tabindex="-1" aria-labelledby="idcardColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="idcardColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="idcardColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Active tab is shown on page load (default tab)
    var activeTabEl = document.getElementById('active-tab');
    if (activeTabEl && typeof bootstrap !== 'undefined') {
        var tabInstance = bootstrap.Tab.getOrCreateInstance(activeTabEl);
        tabInstance.show();
    }
    if (window.location.hash) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    // ===== ID Card Export: Respect current tab (Active / Duplication / Extension / Archive) =====
    function getCurrentIdcardTabKey() {
        var activeBtn = document.querySelector('.idcard-index-tabs .nav-link.active');
        if (!activeBtn) return 'active';
        switch (activeBtn.id) {
            case 'duplication-tab': return 'duplication';
            case 'extension-tab': return 'extension';
            case 'archive-tab': return 'archive';
            default: return 'active';
        }
    }

    function updateExportHeaderLabel() {
        var labelEl = document.getElementById('exportHeaderLabel');
        if (!labelEl) return;
        var tabKey = getCurrentIdcardTabKey();
        var titles = {
            active: 'Active',
            duplication: 'Duplication',
            extension: 'Extension',
            archive: 'Archive'
        };
        var title = titles[tabKey] || 'Active';
        labelEl.textContent = title + ' (with current filter)';
    }

    function initStatusTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            try {
                bootstrap.Tooltip.getOrCreateInstance(el);
            } catch (e) {}
        });
    }

    // Update label on page load and whenever tab is changed
    updateExportHeaderLabel();
    initStatusTooltips();
    document.querySelectorAll('.idcard-index-tabs .nav-link').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            updateExportHeaderLabel();
            initStatusTooltips();
        });
    });

    // Handle Export click: current tab + the live in-browser filters (status / date / search)
    document.querySelectorAll('.export-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var baseUrl = link.dataset.baseUrl || '';
            if (!baseUrl) return;

            try {
                var url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('tab', getCurrentIdcardTabKey());

                // Sync the export with whatever the user has filtered on-screen.
                var statusEl = document.getElementById('listStatus');
                var fromEl = document.getElementById('dateFrom');
                var toEl = document.getElementById('dateTo');
                var statusVal = statusEl ? statusEl.value : 'all';
                var fromVal = fromEl ? fromEl.value : '';
                var toVal = toEl ? toEl.value : '';
                var searchVal = (typeof window.idcardCurrentSearch === 'function') ? window.idcardCurrentSearch() : '';

                if (statusVal && statusVal !== 'all') { url.searchParams.set('list_status', statusVal); } else { url.searchParams.delete('list_status'); }
                if (fromVal) { url.searchParams.set('date_from', fromVal); } else { url.searchParams.delete('date_from'); }
                if (toVal) { url.searchParams.set('date_to', toVal); } else { url.searchParams.delete('date_to'); }
                if (searchVal) { url.searchParams.set('search', searchVal); } else { url.searchParams.delete('search'); }

                window.location.href = url.toString();
            } catch (err) {
                // Fallback if URL API is not available
                var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
                window.location.href = baseUrl + separator + 'tab=' + encodeURIComponent(getCurrentIdcardTabKey());
            }
        });
    });

    // If there are no Duplication/Extension records, redirect those tabs directly to Duplicate ID Card create page
    var duplicationTab = document.getElementById('duplication-tab');
    if (duplicationTab) {
        duplicationTab.addEventListener('click', function(e) {
@if($duplicationRequests->total() === 0)
            e.preventDefault();
            window.location.href = '{{ route('admin.duplicate_idcard.create') }}';
@endif
        });
    }

    function openViewAmendModal(btn) {
        if (!btn || !btn.dataset) return;
        const modal = document.getElementById('viewDetailsModal');
        if (!modal) return;
        const setText = function(id, text) {
            const el = document.getElementById(id);
            if (el) el.textContent = text || '--';
        };
        setText('modalName', btn.dataset.name);
        setText('modalDesignation', btn.dataset.designation);
        setText('modalRequestFor', btn.dataset.requestFor);
        setText('modalCreated', btn.dataset.created);
        setText('modalDuplication', btn.dataset.duplication);
        setText('modalExtension', btn.dataset.extension);
        setText('modalValidUpto', btn.dataset.validUpto || btn.dataset.extension);
        const status = btn.dataset.status || '--';
        const statusClass = { 'Pending': 'warning', 'Approved': 'success', 'Rejected': 'danger', 'Issued': 'primary' }[status] || 'secondary';
        const statusEl = document.getElementById('modalStatus');
        if (statusEl) statusEl.innerHTML = status !== '--' ? '<span class="badge bg-' + statusClass + '">' + status + '</span>' : '--';
        const viewLink = document.getElementById('modalViewFullLink');
        if (viewLink) viewLink.href = btn.dataset.showUrl || '#';
        const amendForm = document.getElementById('amendDupExtForm');
        const requestId = (btn.dataset.requestId || '').toString();
        const isContractual = requestId.startsWith('c-');

        if (isContractual) {
            const errEl = document.getElementById('amendDupExtError');
            const errText = document.getElementById('amendDupExtErrorText');
            if (errText) errText.textContent = 'Duplication/Extension is not supported for contractual ID card requests. Please use Duplicate ID Card Request page.';
            if (errEl) errEl.classList.remove('d-none');
            const dupReasonField = document.getElementById('amendDupReasonField');
            if (dupReasonField) dupReasonField.style.display = 'none';
            const extSection = document.getElementById('amendExtensionSection');
            if (extSection) extSection.style.display = 'none';
            const submitBtn = document.getElementById('amendDupExtSubmitBtn');
            if (submitBtn) submitBtn.disabled = true;
            try { new bootstrap.Modal(modal).show(); } catch (err) { console.error('Modal show:', err); }
            return;
        }

        if (amendForm) amendForm.action = '{{ route("admin.employee_idcard.amendDuplicationExtension", ["id" => "__ID__"]) }}'.replace('__ID__', requestId);
        const setValue = function(id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        setValue('amend_duplication_reason', btn.dataset.duplication);
        setValue('amend_id_card_number', btn.dataset.idNumber);
        setValue('amend_id_card_valid_from', btn.dataset.validFrom);
        setValue('amend_id_card_valid_upto', btn.dataset.validUpto || btn.dataset.extension);
        setValue('amend_fir_receipt', '');
        setValue('amend_payment_receipt', '');
        setValue('amend_extension_document', '');
        setValue('amend_supporting_document', '');
        const errEl2 = document.getElementById('amendDupExtError');
        if (errEl2) errEl2.classList.add('d-none');
        const successEl = document.getElementById('amendDupExtSuccess');
        if (successEl) successEl.classList.add('d-none');
        var requestFor = (btn.dataset.requestFor || '').trim();
        var isExtension = requestFor === 'Extension';
        const dupReasonField2 = document.getElementById('amendDupReasonField');
        if (dupReasonField2) dupReasonField2.style.display = isExtension ? 'none' : '';
        const extSection2 = document.getElementById('amendExtensionSection');
        if (extSection2) extSection2.style.display = isExtension ? 'block' : 'none';
        if (isExtension) setValue('amend_extension_reason', btn.dataset.extensionReason);
        const dupReason = document.getElementById('amend_duplication_reason');
        if (dupReason) dupReason.dispatchEvent(new Event('change'));
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(modal).show();
            } else {
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        } catch (err) {
            console.error('openViewAmendModal:', err);
        }
    }

    var currentAmendBtn = null;
    // Only Duplication / Extension actions should open the Amend modal.
    // Normal "View" links should navigate to full details page.
    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('a.amend-dup-ext-btn, button.idcard-archive-ext-btn, button.idcard-archive-dup-btn');
        if (target) {
            e.preventDefault();
            e.stopPropagation();
            currentAmendBtn = target;
            try {
                openViewAmendModal(target);
            } catch (err) {
                console.error('openViewAmendModal error:', err);
            }
        }
    }, true);

    const amendForm = document.getElementById('amendDupExtForm');
    const amendModal = document.getElementById('viewDetailsModal');

    var reasonDocLabels = {
        '': { label: 'Upload Document', help: 'Select reason above. Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB' },
        'Expired Card': { label: 'Upload Document (Expired Card)', help: 'Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB' },
        'Lost': { label: 'Upload Payment Receipt', help: 'Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB' },
        'Damage': { label: 'Upload Damage Proof / Photo', help: 'Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB' }
    };
    document.getElementById('amend_duplication_reason').addEventListener('change', function() {
        var reason = this.value;
        var firField = document.getElementById('amendFirReceiptField');
        var firInput = document.getElementById('amend_fir_receipt');
        var isLost = reason === 'Lost';
        firField.style.display = isLost ? '' : 'none';
        firInput.required = isLost;
        var texts = reasonDocLabels[reason] || reasonDocLabels[''];
        document.getElementById('amendReasonDocLabel').textContent = texts.label;
        document.getElementById('amend_reason_doc_help').textContent = texts.help;
    });

    if (amendForm) {
        amendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('amendDupExtSubmitBtn');
            const errEl = document.getElementById('amendDupExtError');
            const successEl = document.getElementById('amendDupExtSuccess');
            errEl.classList.add('d-none');
            successEl.classList.add('d-none');
            submitBtn.disabled = true;
            const formData = new FormData(amendForm);
            fetch(amendForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(result) {
                submitBtn.disabled = false;
                if (result.ok && result.data && result.data.success) {
                    var successText = document.getElementById('amendDupExtSuccessText');
                    if (successText) successText.textContent = result.data.message || 'Updated successfully.';
                    successEl.classList.remove('d-none');
                    if (currentAmendBtn) {
                        const row = currentAmendBtn.closest('tr');
                        const allModalBtns = row ? row.querySelectorAll('a.view-details-btn, a.amend-dup-ext-btn') : [];
                        allModalBtns.forEach(function(btn) {
                            btn.dataset.duplication = result.data.data.duplication_reason || '';
                            btn.dataset.extension = result.data.data.id_card_valid_upto || '';
                            btn.dataset.validFrom = result.data.data.id_card_valid_from || '';
                            btn.dataset.idNumber = result.data.data.id_card_number || '';
                            btn.dataset.validUpto = result.data.data.id_card_valid_upto || '';
                        });
                        const dupExtBtns = row ? row.querySelectorAll('a.amend-dup-ext-btn') : [];
                        dupExtBtns.forEach(function(btn) {
                            btn.dataset.duplication = result.data.data.duplication_reason || '';
                            btn.dataset.extension = result.data.data.id_card_valid_upto || '';
                            btn.dataset.validFrom = result.data.data.id_card_valid_from || '';
                            btn.dataset.idNumber = result.data.data.id_card_number || '';
                            const dupBadge = { 'Lost': 'danger', 'Damage': 'warning', 'Expired Card': 'info' }[result.data.data.duplication_reason] || 'secondary';
                            if (btn.dataset.type === 'duplication') {
                                btn.innerHTML = result.data.data.duplication_reason
                                    ? '<span class="badge bg-' + dupBadge + ' text-dark">' + result.data.data.duplication_reason + '</span>'
                                    : '<span class="text-primary">Duplication</span>';
                            } else {
                                btn.innerHTML = result.data.data.id_card_valid_upto
                                    ? '<span class="badge bg-info">' + result.data.data.id_card_valid_upto + '</span>'
                                    : '<span class="text-primary">Extension</span>';
                            }
                        });
                        document.getElementById('modalDuplication').textContent = result.data.data.duplication_reason || '--';
                        document.getElementById('modalExtension').textContent = result.data.data.id_card_valid_upto || '--';
                        document.getElementById('modalValidUpto').textContent = result.data.data.id_card_valid_upto || '--';
                    }
                    setTimeout(function() { bootstrap.Modal.getInstance(amendModal).hide(); }, 800);
                } else {
                    let errMsg = 'Update failed.';
                    if (result.data) {
                        if (result.data.message) errMsg = result.data.message;
                        else if (result.data.errors) errMsg = Object.values(result.data.errors).flat().join(' ');
                    }
                    var errText = document.getElementById('amendDupExtErrorText');
                    if (errText) errText.textContent = errMsg;
                    errEl.classList.remove('d-none');
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                var errText = document.getElementById('amendDupExtErrorText');
                if (errText) errText.textContent = 'An error occurred. Please try again.';
                errEl.classList.remove('d-none');
            });
        });
    }
});
</script>
@endsection

@push('scripts')
<script>
    $(function () {
        var TABLE_ID = '#activeIdcardTable';
        var $table = $(TABLE_ID);

        // No real data rows (only the empty-state CTA) -> skip DataTables so the CTA shows.
        if (!$table.length || $table.find('tbody tr[data-request-id]').length === 0) {
            return;
        }

        var table = $table.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [[2, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                { targets: [0, 1, 9], orderable: false, searchable: false }
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
                // Renumber the S.No column for the current page order.
                var info = this.api().page.info();
                this.api().column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
                // Re-init tooltips for the newly drawn rows.
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    $table.find('[data-bs-toggle="tooltip"]').each(function () {
                        try { bootstrap.Tooltip.getOrCreateInstance(this); } catch (e) {}
                    });
                }
            }
        });

        /* ---- Instant client-side filters (status + date range) — no page reload.
           Reads explicit row attributes (data-status / data-ts) so matching never
           depends on how DataTables extracts text from the status badge. ---- */
        $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
            if (settings.nTable.id !== 'activeIdcardTable') { return true; }

            var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            if (!row) { return true; }

            // Status: exact (case-insensitive) match against the row's data-status.
            var statusVal = ($('#listStatus').val() || 'all').toLowerCase();
            if (statusVal !== 'all') {
                var rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
                if (rowStatus !== statusVal) { return false; }
            }

            // Date range: compare against the row's created timestamp (seconds).
            var from = $('#dateFrom').val();
            var to = $('#dateTo').val();
            if (from || to) {
                var ts = parseInt(row.getAttribute('data-ts') || '0', 10);
                if (!ts) { return false; }
                if (from && ts < Math.floor(new Date(from + 'T00:00:00').getTime() / 1000)) { return false; }
                if (to && ts > Math.floor(new Date(to + 'T23:59:59').getTime() / 1000)) { return false; }
            }
            return true;
        });

        $('#listStatus').on('change', function () { table.draw(); });

        /* ---- "Time Period" dual-month range calendar ----
           Writes the selected start/end into the hidden #dateFrom / #dateTo
           inputs (Y-m-d) so the filter search + export logic keep working. ---- */
        function updateTimePeriodLabel() {
            var from = $('#dateFrom').val();
            var to = $('#dateTo').val();
            $('#idcardTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
        }

        var idcardCal = (function initRangeCalendar() {
            var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
            var view = new Date(); view.setDate(1);
            var startD = null, endD = null;

            function pad(n){ return (n < 10 ? '0' : '') + n; }
            function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function parseYmd(s){ if (!s) { return null; } var p = String(s).split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
            function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }

            function buildMonth(base){
                var year = base.getFullYear(), month = base.getMonth();
                var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
                var daysInMonth = new Date(year, month + 1, 0).getDate();
                var html = '<div class="idcp-cal-head">' +
                    '<button type="button" class="idcp-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                    '<span class="idcp-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                    '<button type="button" class="idcp-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                    '</div><div class="idcp-cal-grid">';
                DOW.forEach(function(d){ html += '<span class="idcp-cal-dow">' + d + '</span>'; });
                for (var i = 0; i < startWeekday; i++) html += '<span></span>';
                for (var day = 1; day <= daysInMonth; day++){
                    var d = new Date(year, month, day);
                    var cls = 'idcp-cal-day';
                    if (startD && endD && d > startD && d < endD) cls += ' in-range';
                    if (sameDay(d, startD)) cls += ' is-start';
                    if (sameDay(d, endD)) cls += ' is-end';
                    html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
                }
                return html + '</div>';
            }

            function render(){
                var left = new Date(view.getFullYear(), view.getMonth(), 1);
                var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                $('#idcardCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
                $('#idcardCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
                $('#idcardCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
                $('#idcardCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
                var label = 'Select a date range';
                if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
                else if (startD) label = ymd(startD) + '  → …';
                $('#idcardCalRange').text(label);
            }

            $('#idcardCalendar').on('click', '.idcp-cal-nav', function(){
                var dir = $(this).data('nav') === 'prev' ? -1 : 1;
                view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
                render();
            });
            $('#idcardCalendar').on('click', '.idcp-cal-day', function(){
                var p = String($(this).data('date')).split('-');
                var d = new Date(+p[0], +p[1] - 1, +p[2]);
                if (!startD || (startD && endD)) { startD = d; endD = null; }
                else if (d < startD) { startD = d; }
                else { endD = d; }
                render();
            });
            $('#idcardApplyPeriod').on('click', function(){
                $('#dateFrom').val(startD ? ymd(startD) : '');
                $('#dateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
                updateTimePeriodLabel();
                table.draw();
                if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('idcardTimePeriodToggle')).hide(); }
            });
            $('#idcardClearPeriod').on('click', function(){
                startD = null; endD = null; render();
                $('#dateFrom').val(''); $('#dateTo').val('');
                updateTimePeriodLabel(); table.draw();
            });

            // Seed from any server-side / old() values.
            startD = parseYmd($('#dateFrom').val());
            endD = parseYmd($('#dateTo').val());
            if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
            render();
            updateTimePeriodLabel();

            return {
                reset: function(){ startD = null; endD = null; view = new Date(); view.setDate(1); render(); }
            };
        })();

        $('#idcardClearFilters').on('click', function () {
            $('#listStatus').val('all');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            if (idcardCal) { idcardCal.reset(); }
            updateTimePeriodLabel();
            table.search('').draw();
        });

        // Expose the live search term so the export handler can include it.
        window.idcardCurrentSearch = function () { return table.search(); };

        /* ---- Search relocation + footer (pagination + "Showing" count) ----
           Handled globally by SargamDataTableUI (public/js/datatable-global-ui.js),
           which relocates the DataTables controls into the
           #idcardDtSearch / #idcardDtFooter slots (data-dt-search-for /
           data-dt-footer-for). We must NOT relocate them here as well — a second
           relocation empties the footer the global pass already populated. ---- */

        /* ---- Column show / hide ---- */
        var colKey = 'idcardGrid:hiddenColumns:v1';
        function getHidden() { try { var a = JSON.parse(localStorage.getItem(colKey) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
        function setHidden(a) { try { localStorage.setItem(colKey, JSON.stringify(a)); } catch (e) {} }

        function setupIdcardColumns(dt) {
            var hidden = getHidden();
            dt.columns().every(function () { var idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
            dt.columns.adjust();

            var $grid = $('#idcardColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var inputId = 'idcardcolvis_' + idx;
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

        /* ---- Print (client-side, data columns only) ---- */
        if (typeof $.fn.dataTable.Buttons !== 'undefined') {
            new $.fn.dataTable.Buttons(table, {
                buttons: [{
                    extend: 'print',
                    className: 'idcard-btn-print',
                    title: 'Employee ID Card Requests',
                    exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7, 8] }
                }]
            });
            $('#idcardPrintBtn').on('click', function () { table.button('.idcard-btn-print').trigger(); });
        }

        setupIdcardColumns(table);
    });
</script>
@endpush
