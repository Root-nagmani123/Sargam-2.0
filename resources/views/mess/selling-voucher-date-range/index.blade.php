@extends('admin.layouts.master')
@section('title', 'Selling Voucher with Date Range')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="{{ asset('css/mess-selling-voucher-date-range.css') }}?v={{ @filemtime(public_path('css/mess-selling-voucher-date-range.css')) }}" />
@endpush

@section('content')
@php
$canDeleteSellingVoucherDateRange = hasRole('Super Admin') || hasRole('Mess-Admin');
$selectedStatuses = collect((array) request()->input('status', []))
->filter(fn ($value) => $value !== null && $value !== '')
->map(fn ($value) => (string) $value)
->values()
->all();
$selectedStores = collect((array) request()->input('store', []))
->filter(fn ($value) => $value !== null && $value !== '')
->map(fn ($value) => (string) $value)
->values()
->all();
$selectedReturnStatus = (string) request()->input('return_status', '');
$selectedClientType = (string) request()->input('client_type', '');
@endphp
<div class="container-fluid py-2 py-lg-3 svdr-master-page">
    <x-breadcrum title="Selling Voucher with Date Range" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addReportModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Add Selling Voucher</span>
        </button>
    </x-breadcrum>

    @php
        $svSelStatus = is_array(request('status')) ? (string) (collect(request('status'))->filter(fn($v)=>$v!=='')->first() ?? '') : (string) request('status', '');
        $svSelStore  = is_array(request('store'))  ? (string) (collect(request('store'))->filter(fn($v)=>$v!=='')->first() ?? '')  : (string) request('store', '');
    @endphp

    {{-- Success/error feedback → global toast (see mess.partials.delete-confirm) --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Download / Print bar (branded server-side exports) --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" class="btn svdr-master-export-btn border-0" id="svDownloadBtn"><i class="material-symbols-rounded">download</i><span>Download</span></button>
        <button type="button" class="btn svdr-master-export-btn border-0" id="svPrintBtn"><i class="material-symbols-rounded">print</i><span>Print</span></button>
    </div>

    <div class="card svdr-master-card border-0">
        <div class="card-body">
            {{-- Responsive single-row toolbar: filters auto-apply; overflow into "+Filter"; Columns + search same row --}}
            <div class="d-flex align-items-center gap-2 mb-3 programme-dt-toolbar sv-toolbar">
                <form method="GET" action="{{ route('admin.mess.selling-voucher-date-range.index') }}" id="sellingVoucherFilterForm" class="d-flex align-items-center gap-2 sv-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0">Filter</span>

                    <div id="svdrFilterItems" class="d-flex align-items-center gap-2 sv-filter-items">
                        <div class="sv-filter-item" data-filter="date">
                            <label class="sv-filter-item-label">Date Range</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" name="start_date" id="filter_start_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('start_date') }}" aria-label="Start date">
                                <span class="sv-filter-dash">–</span>
                                <input type="date" name="end_date" id="filter_end_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('end_date') }}" aria-label="End date" @if(request()->filled('start_date')) min="{{ request('start_date') }}" @endif>
                            </div>
                        </div>

                        <div class="sv-filter-item" data-filter="store">
                            <label class="sv-filter-item-label">Store</label>
                            <select name="store" id="filter_store" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Store">
                                <option value="">Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}" {{ $svSelStore === (string) $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sv-filter-item" data-filter="buyer">
                            <label class="sv-filter-item-label">Buyer Name</label>
                            <select name="buyer_name" id="filter_buyer_name" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Buyer name">
                                <option value="">Buyer Name</option>
                                @foreach(($filterBuyerNames ?? collect()) as $buyerName)
                                @php
                                    $buyerValue = is_array($buyerName) ? (string) ($buyerName['value'] ?? '') : (string) $buyerName;
                                    $buyerLabel = is_array($buyerName) ? (string) ($buyerName['text'] ?? $buyerValue) : (string) $buyerName;
                                @endphp
                                    <option value="{{ $buyerValue }}" {{ (string) ($selectedBuyerName ?? '') === $buyerValue ? 'selected' : '' }}>{{ $buyerLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sv-filter-item" data-filter="status">
                            <label class="sv-filter-item-label">Status</label>
                            <select name="status" id="filter_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Status">
                                <option value="">Status</option>
                                <option value="0" {{ $svSelStatus === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ $svSelStatus === '1' ? 'selected' : '' }}>Final</option>
                                <option value="2" {{ $svSelStatus === '2' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>

                        <div class="sv-filter-item" data-filter="client_type">
                            <label class="sv-filter-item-label">Client Type</label>
                            <select name="client_type" id="filter_client_type" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Client type" data-clears="filter_client_type_pk,filter_buyer_name">
                                <option value="" {{ $selectedClientType === '' ? 'selected' : '' }}>Client Type</option>
                                @foreach(\App\Models\Mess\ClientType::clientTypes() as $slug => $label)
                                    <option value="{{ $slug }}" {{ $selectedClientType === $slug ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sv-filter-item" data-filter="client_type_pk">
                            <label class="sv-filter-item-label">Client Category</label>
                            <select name="client_type_pk" id="filter_client_type_pk" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Client category" data-clears="filter_buyer_name">
                                <option value="">Client Category</option>
                                @foreach(($filterClientTypePkOptions ?? collect()) as $option)
                                    <option value="{{ $option['value'] }}" {{ (string) ($selectedClientTypePk ?? '') === (string) $option['value'] ? 'selected' : '' }}>{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sv-filter-item" data-filter="return_status">
                            <label class="sv-filter-item-label">Return Status</label>
                            <select name="return_status" id="filter_return_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Return status">
                                <option value="" {{ $selectedReturnStatus === '' ? 'selected' : '' }}>Return Status</option>
                                <option value="returned" {{ $selectedReturnStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                                <option value="not_returned" {{ $selectedReturnStatus === 'not_returned' ? 'selected' : '' }}>Not returned</option>
                            </select>
                        </div>
                    </div>

                    <div class="dropdown flex-shrink-0 d-none" id="svdrMoreFilterWrap">
                        <a href="javascript:void(0)" class="sv-more-filters dropdown-toggle" id="svdrMoreFilterToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+ Filter</a>
                        <div class="dropdown-menu sv-more-menu p-3 shadow border rounded-3">
                            <div class="sv-more-header">Filters</div>
                            <div id="svdrMoreFilterItems"></div>
                        </div>
                    </div>

                    <a href="{{ route('admin.mess.selling-voucher-date-range.index') }}" class="btn programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center">Remove Filter</a>
                </form>

                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnSvdrColumns" data-bs-toggle="modal" data-bs-target="#svdrColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="sellingVoucherDateRangeTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table programme-dt-table align-middle mb-0 w-100" id="sellingVoucherDateRangeTable">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Item Name</th>
                                <th scope="col" class="text-end">Item Qty</th>
                                <th scope="col" class="text-end">Return Qty</th>
                                <th scope="col">Transfer From Store</th>
                                <th scope="col">Client Type</th>
                                <th scope="col">Client Name</th>
                                <th scope="col">Payment</th>
                                <th scope="col">Request Date</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="sellingVoucherDateRangeTable"></div>
        </div>
    </div>

    {{-- Column Visibility Modal --}}
    <div class="modal fade" id="svdrColumnVisibilityModal" tabindex="-1" aria-labelledby="svdrColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="svdrColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0"><hr class="mt-0"><div class="row g-3" id="svdrColumnToggleGrid"></div></div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    {{-- Branded delete-confirmation dialog + global success toast --}}
    @include('mess.partials.delete-confirm')

    @include('components.mess-master-datatables', [
    'tableId' => 'sellingVoucherDateRangeTable',
    'searchPlaceholder' => 'Search',
    'actionColumnIndex' => 10,
    'infoLabel' => 'items',
    'searchDelay' => 250,
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.selling-voucher-date-range.datatable'),
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
    'serverSideColumnDefs' => [
        ['className' => 'text-end', 'targets' => [2, 3]],
        ['className' => 'text-center', 'targets' => [9, 10]],
    ],
    ])
    @include('mess.partials.modal-dropdown-stability')

    @push('scripts')
    {{-- Page CSS/JS live in public/css|js/mess-selling-voucher-date-range.*; PHP-derived values are injected below. --}}
    @php
        $__svdrTypePkOptionsBySlug = [];
        foreach (($clientNamesByType ?? collect()) as $__svdrSlug => $__svdrOpts) {
            $__svdrTypePkOptionsBySlug[$__svdrSlug] = collect($__svdrOpts)->map(fn ($o) => [
                'value' => (string) $o->id,
                'text'  => (string) $o->client_name,
            ])->values()->all();
        }
        $__svdrOtCourseOptions = collect($otCourses ?? collect())->map(fn ($c) => [
            'value' => (string) $c->pk,
            'text'  => (string) $c->course_name,
        ])->values()->all();
    @endphp
    <script>
        window.SVDR_CFG = {
            baseUrl: @json(url('admin/mess/selling-voucher-date-range')),
            exportUrl: @json(route('admin.mess.selling-voucher-date-range.export')),
            materialManagementUrl: @json(url('admin/mess/material-management')),
            studentsByCourseUrlTemplate: @json(route('admin.mess.selling-voucher-date-range.students-by-course', ['course_pk' => '__COURSE__'])),
            filterBuyerNamesUrl: @json(route('admin.mess.selling-voucher-date-range.filter-buyer-names')),
            openAddModal: @json((bool) session('open_add_modal')),
            itemSubcategories: @json($itemSubcategories),
            selectedTypePk: @json((string) ($selectedClientTypePk ?? request('client_type_pk', ''))),
            selectedBuyer: @json((string) ($selectedBuyerName ?? request('buyer_name', ''))),
            employees: @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            faculties: @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            messStaff: @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            typePkOptionsBySlug: @json($__svdrTypePkOptionsBySlug, JSON_UNESCAPED_UNICODE),
            otCourseOptions: @json($__svdrOtCourseOptions, JSON_UNESCAPED_UNICODE),
        };
    </script>
    <script src="{{ asset('js/mess-selling-voucher-date-range.js') }}?v={{ @filemtime(public_path('js/mess-selling-voucher-date-range.js')) }}"></script>
    @endpush
</div>

{{-- Choices.js CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
{{-- Choices.js JS --}}
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

{{-- Add Report Modal --}}
<div class="modal fade" id="addReportModal" tabindex="-1" aria-labelledby="addReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.mess.selling-voucher-date-range.store') }}" method="POST" id="addReportForm"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="client_id" id="drClientId" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="addReportModalLabel">ADD Selling Voucher with Date Range
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                        <ul class="mb-0 small">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close " data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    {{-- Voucher Details (exactly same as Add Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label voucher-label">Client Type <span
                                            class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                        <div class="form-check">
                                            <input class="form-check-input dr-client-type-radio" type="radio"
                                                name="client_type_slug" id="dr_ct_{{ $slug }}" value="{{ $slug }}"
                                                {{ old('client_type_slug') === $slug ? 'checked' : '' }} required>
                                            <label class="form-check-label" for="dr_ct_{{ $slug }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Payment Type <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select " required>
                                        <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit
                                        </option>
                                        <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash
                                        </option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI
                                        </option>
                                    </select>
                                    <small class="form-text text-muted" id="drPaymentTypeHint">Cash / UPI /
                                        Credit</small>
                                </div>
                                <div class="col-md-4" id="drClientNameWrap" style="display:none;">
                                    <label class="form-label voucher-label">Client Name <span
                                            class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select " id="drClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                        @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                            data-client-name="{{ strtolower($c->client_name ?? '') }}">
                                            {{ $c->client_name }}</option>
                                        @endforeach
                                        @endforeach
                                    </select>
                                    <select id="drOtCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                    <select id="drCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="drNameFieldWrap" style="display:none;">
                                    <label class="form-label voucher-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="client_name" id="drClientNameInput" class="form-control "
                                        value="{{ old('client_name') }}" placeholder="Client / section / role name"
                                        required>
                                    <datalist id="drCourseBuyerNames"></datalist>
                                    <datalist id="drGenericBuyerNames"></datalist>
                                    <select id="drFacultySelect" class="form-select " style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                        <option value="{{ e($f->full_name) }}" data-pk="{{ $f->pk }}">
                                            {{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drAcademyStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drMessStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="drOtStudentSelect" class="form-select " style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="drCourseNameSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Transfer From Store <span
                                            class="text-danger">*</span></label>
                                    <select name="inve_store_master_pk" class="form-select " required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                        <option value="{{ $store['id'] }}"
                                            {{ old('inve_store_master_pk') == $store['id'] ? 'selected' : '' }}>
                                            {{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Remarks / Reference Number / Order
                                        By</label>
                                    <input type="text" name="remarks" class="form-control " value="{{ old('remarks') }}"
                                        placeholder="Remarks / Reference Number / Order By (optional)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bill upload removed as per requirement --}}

                    {{-- Item Details (exactly same as Add Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                id="addModalAddItemRow">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">add</i>
                                <span>Add Item</span>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="dr-item-details-table-wrap">
                                <table class="table table-bordered table-sm table-hover align-middle mb-0"
                                    id="addReportItemsTable">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Item Name <span class="text-white">*</span></th>
                                            <th>Unit</th>
                                            <th>Available Qty</th>
                                            <th>Issue Qty <span class="text-white">*</span></th>
                                            <th>Left Qty</th>
                                            <th>Issue Date</th>
                                            <th>Rate <span class="text-white">*</span></th>
                                            <th>Total Amount</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="addModalItemsBody">
                                        <tr class="dr-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]"
                                                    class="form-select  dr-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $s)
                                                    <option value="{{ $s['id'] }}"
                                                        data-unit="{{ e($s['unit_measurement'] ?? '') }}"
                                                        data-rate="{{ e($s['standard_cost'] ?? 0) }}">
                                                        {{ e($s['item_name'] ?? '—') }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  dr-unit"
                                                    readonly placeholder="—"></td>
                                            <td><input type="text" name="items[0][available_quantity]"
                                                    class="form-control  dr-avail bg-light" readonly></td>
                                            <td>
                                                <input type="text" name="items[0][quantity]"
                                                    class="form-control  dr-qty" required>
                                                <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.
                                                </div>
                                            </td>
                                            <td><input type="text" class="form-control  dr-left bg-light" readonly></td>
                                            <td><input type="date" name="items[0][issue_date]"
                                                    class="form-control  dr-issue-date" value="{{ date('Y-m-d') }}">
                                            </td>
                                            <td><input type="number" name="items[0][rate]" class="form-control  dr-rate"
                                                    step="0.01" min="0" required></td>
                                            <td><input type="text" class="form-control  dr-total bg-light" readonly>
                                            </td>
                                            <td><button type="button"
                                                    class="btn btn-sm btn-outline-danger dr-remove-row voucher-icon-btn"
                                                    disabled title="Remove">×</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="addModalGrandTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">save</i>
                        <span>Save Selling Voucher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Selling Voucher with Date Range Modal (same columns as Selling Voucher view modal + Issue Date) --}}
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="viewReportModalLabel">View Selling Voucher with Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Voucher Details (exactly same as Selling Voucher view modal) --}}
                <div class="card mb-4 voucher-section-card">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-secondary fw-semibold">Request Date:</th>
                                        <td id="viewRequestDate">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Transfer From Store:</th>
                                        <td id="viewStoreName">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Reference Number:</th>
                                        <td id="viewReferenceNumber">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Order By:</th>
                                        <td id="viewOrderBy">—</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-secondary fw-semibold">Client Type:</th>
                                        <td id="viewClientType">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Client Name:</th>
                                        <td id="viewClientName">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Payment Type:</th>
                                        <td id="viewPaymentType">—</td>
                                    </tr>
                                    <tr>
                                        <th class="text-secondary fw-semibold">Status:</th>
                                        <td id="viewStatus">—</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <p class="mb-0 mt-3" id="viewRemarksWrap" style="display:none;"><strong>Remarks:</strong> <span
                                id="viewRemarks"></span></p>
                    </div>
                </div>
                {{-- Item Details (same as Selling Voucher view modal + one extra column Issue Date) --}}
                <div class="card mb-4 voucher-section-card" id="viewReportItemsCard" style="margin-bottom:30px;">
                    <div class="card-header py-3">
                        <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="voucher-brand-head">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Unit</th>
                                        <th>Issue Qty</th>
                                        <th>Return Qty</th>
                                        <th>Rate</th>
                                        <th>Total</th>
                                        <th>Issue Date</th>
                                    </tr>
                                </thead>
                                <tbody id="viewReportItemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-end">
                        <strong>Grand Total: ₹<span id="viewReportGrandTotal">0.00</span></strong>
                    </div>
                </div>
                <div class="small text-secondary">
                    Created: <span id="viewCreatedAt" class="text-body">—</span>
                    <span class="ms-3" id="viewUpdatedAtWrap" style="display:none;">Last Updated: <span
                            id="viewUpdatedAt" class="text-body"></span></span>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button"
                    class="btn btn-outline-primary btn-print-view-modal d-inline-flex align-items-center gap-1"
                    data-print-target="#viewReportModal" title="Print">
                    <i class="ti ti-printer"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Return Item Modal (Transfer To) --}}
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content">
            <form id="returnItemForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="returnItemModalLabel">Transfer To</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label voucher-label">Person / buyer</label>
                        <p class="mb-0 form-control-plaintext fw-semibold" id="returnClientName">—</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label voucher-label">Transfer From Store</label>
                        <p class="mb-0 form-control-plaintext" id="returnTransferFromStore">—</p>
                    </div>
                    <div class="card voucher-section-card">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Issued Quantity</th>
                                            <th>Item Unit</th>
                                            <th>Item Issue Date</th>
                                            <th>Return Quantity</th>
                                            <th>Return Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnItemModalBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">sync</i>
                        <span>Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Report Modal --}}
<div class="modal fade" id="editReportModal" tabindex="-1" aria-labelledby="editReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <form id="editReportForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="client_id" id="editDrClientId" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="editReportModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Voucher Details (exactly same as Edit Selling Voucher) --}}
                    <div class="card mb-4 voucher-section-card shadow-sm">
                        <div class="card-header py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Voucher Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12 edit-client-identity-locked">
                                    <label class="form-label voucher-label">Client Type <span
                                            class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3 pt-1">
                                        @foreach($clientTypes as $slug => $label)
                                        <div class="form-check">
                                            <input class="form-check-input edit-dr-client-type-radio" type="radio"
                                                name="client_type_slug" id="edit_dr_ct_{{ $slug }}" value="{{ $slug }}"
                                                required>
                                            <label class="form-check-label"
                                                for="edit_dr_ct_{{ $slug }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Payment Type <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-select  edit-payment-type" required>
                                        <option value="1">Credit</option>
                                        <option value="0">Cash</option>
                                        <option value="2">UPI</option>
                                    </select>
                                </div>
                                <div class="col-md-4 edit-client-identity-locked" id="editDrClientNameWrap" style="display:none;">
                                    <label class="form-label voucher-label">Client Name <span
                                            class="text-danger">*</span></label>
                                    <select name="client_type_pk" class="form-select  edit-client-type-pk"
                                        id="editDrClientNameSelect">
                                        <option value="">Select Client Name</option>
                                        @foreach($clientNamesByType as $type => $list)
                                        @foreach($list as $c)
                                        <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                            data-client-name="{{ strtolower($c->client_name ?? '') }}">
                                            {{ $c->client_name }}</option>
                                        @endforeach
                                        @endforeach
                                    </select>
                                    <select id="editDrOtCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrCourseSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}"
                                            data-course-name="{{ e($course->course_name) }}">
                                            {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 edit-client-identity-locked" id="editDrNameFieldWrap" style="display:none;">
                                    <label class="form-label voucher-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="client_name" class="form-control  edit-client-name bg-light"
                                        id="editDrClientNameInput" placeholder="Client / section / role name" required readonly>
                                    <datalist id="editDrCourseBuyerNames"></datalist>
                                    <datalist id="editDrGenericBuyerNames"></datalist>
                                    <select id="editDrFacultySelect" class="form-select " style="display:none;">
                                        <option value="">Select Faculty</option>
                                        @foreach($faculties ?? [] as $f)
                                        <option value="{{ e($f->full_name) }}" data-pk="{{ $f->pk }}">
                                            {{ e($f->full_name_with_code ?? $f->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrAcademyStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Academy Staff</option>
                                        @foreach($employees ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrMessStaffSelect" class="form-select " style="display:none;">
                                        <option value="">Select Mess Staff</option>
                                        @foreach($messStaff ?? [] as $e)
                                        <option value="{{ e($e->full_name_with_department ?? $e->full_name) }}" data-pk="{{ $e->pk }}">
                                            {{ e($e->full_name_with_department ?? $e->full_name) }}</option>
                                        @endforeach
                                    </select>
                                    <select id="editDrOtStudentSelect" class="form-select " style="display:none;">
                                        <option value="">Select Student</option>
                                    </select>
                                    <select id="editDrCourseNameSelect" class="form-select " style="display:none;">
                                        <option value="">Select Course</option>
                                        @foreach($otCourses ?? [] as $course)
                                        <option value="{{ $course->pk }}">{{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label voucher-label">Remarks / Reference Number / Order
                                        By</label>
                                    <input type="text" name="remarks" class="form-control  edit-remarks"
                                        placeholder="Remarks / Reference Number / Order By (optional)">
                                    <select name="inve_store_master_pk" class="form-select edit-store-id d-none" tabindex="-1" aria-hidden="true">
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                        <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="multi_store" id="editMultiStoreFlag" value="0">
                                    <input type="hidden" name="filtered_edit" id="editFilteredEditFlag" value="0">
                                    <div id="editListingFilterHiddens"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Bill upload removed as per requirement --}}
                    <div class="card mb-4 voucher-section-card">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                id="editModalAddItemRow">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">add</i>
                                <span>Add Item</span>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="dr-item-details-table-wrap">
                                <table class="table table-bordered table-sm table-hover align-middle mb-0"
                                    id="editReportItemsTable">
                                    <thead class="voucher-brand-head">
                                        <tr>
                                            <th>Store</th>
                                            <th>Item Name <span class="text-white">*</span></th>
                                            <th>Unit</th>
                                            <th>Available Qty</th>
                                            <th>Issue Qty <span class="text-white">*</span></th>
                                            <th>Left Qty</th>
                                            <th>Issue Date</th>
                                            <th>Rate <span class="text-white">*</span></th>
                                            <th>Total Amount</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editModalItemsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">Grand Total:</span>
                                <span class="fs-5 text-primary fw-bold" id="editModalGrandTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-symbols-rounded" style="font-size: 1rem;">save</i>
                        <span>Update Selling Voucher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection