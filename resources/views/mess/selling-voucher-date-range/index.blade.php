@extends('admin.layouts.master')
@section('title', 'Selling Voucher with Date Range')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
{{-- Select2 powers the filter dropdowns. Its JS is global (admin footer); the
     CSS is per-page by convention, and this page did not load it before. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}" />
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
        <button type="button" class="btn svdr-master-export-btn" id="svDownloadBtn"><i class="material-symbols-rounded">download</i><span>Download</span></button>
        <button type="button" class="btn svdr-master-export-btn" id="svPrintBtn"><i class="material-symbols-rounded">print</i><span>Print</span></button>
    </div>

    <div class="card svdr-master-card border-0">
        <div class="card-body">
            {{-- Responsive single-row toolbar: filters auto-apply; overflow into "+Filter"; Columns + search same row --}}
            <div class="d-flex align-items-center gap-2 mb-3 programme-dt-toolbar sv-toolbar">
                <form method="GET" action="{{ route('admin.mess.selling-voucher-date-range.index') }}" id="sellingVoucherFilterForm" class="d-flex align-items-center gap-2 sv-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                    {{-- Every filter lives inside #svdrFilterItems: the overflow
                         manager only collects `.sv-filter-item` from within this
                         wrapper, so anything left outside it can never move into
                         "+Filter" and would pin the row wider than the screen. --}}
                    <div id="svdrFilterItems" class="d-flex align-items-center gap-2 sv-filter-items">
<div class="sv-filter-item" data-filter="status">
                            <label class="sv-filter-item-label">Status</label>
                            <select name="status" id="filter_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Status">
                                <option value="">Status</option>
                                <option value="0" {{ $svSelStatus === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ $svSelStatus === '1' ? 'selected' : '' }}>Final</option>
                                <option value="2" {{ $svSelStatus === '2' ? 'selected' : '' }}>Approved</option>
                            </select>
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
                        <div class="sv-filter-item" data-filter="return_status">
                            <label class="sv-filter-item-label">Return Status</label>
                            <select name="return_status" id="filter_return_status" class="form-select sv-filter-select-native sv-auto-filter" aria-label="Return status">
                                <option value="" {{ $selectedReturnStatus === '' ? 'selected' : '' }}>Return Status</option>
                                <option value="returned" {{ $selectedReturnStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                                <option value="not_returned" {{ $selectedReturnStatus === 'not_returned' ? 'selected' : '' }}>Not returned</option>
                            </select>
                        </div>
                        <div class="sv-filter-item" data-filter="date">
                            <label class="sv-filter-item-label">Date Range</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" name="start_date" id="filter_start_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('start_date') }}" aria-label="Start date">
                                <span class="sv-filter-dash">–</span>
                                <input type="date" name="end_date" id="filter_end_date" class="form-control sv-filter-date sv-auto-filter" value="{{ request('end_date') }}" aria-label="End date" @if(request()->filled('start_date')) min="{{ request('start_date') }}" @endif>
                            </div>
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
                    @include('mess.partials.search-toggle', ['tableId' => 'sellingVoucherDateRangeTable'])
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
            logoUrl: @json(asset('images/lbsnaa_logo.jpg')),
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
        <div class="modal-content">
            <form action="{{ route('admin.mess.selling-voucher-date-range.store') }}" method="POST" id="addReportForm"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="client_id" id="drClientId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addReportModalLabel">Add Selling Voucher with Date Range</h5>
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

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="dr-label">Client Type<span class="dr-req">*</span></label>
                            <div class="dr-radio-row">
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
                            <label class="dr-label">Payment Type<span class="dr-req">*</span></label>
                            <select name="payment_type" class="form-select " required>
                                <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit</option>
                                <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash</option>
                                <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>UPI</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="drClientNameWrap" style="display:none;">
                            <label class="dr-label">Client Name<span class="dr-req">*</span></label>
                            <select name="client_type_pk" class="form-select " id="drClientNameSelect">
                                <option value="">Select Client</option>
                                @foreach($clientNamesByType as $type => $list)
                                @foreach($list as $c)
                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                    data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                @endforeach
                                @endforeach
                            </select>
                            <select id="drOtCourseSelect" class="form-select " style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">
                                    {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                            <select id="drCourseSelect" class="form-select " style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">
                                    {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="drNameFieldWrap" style="display:none;">
                            <label class="dr-label">Name<span class="dr-req">*</span></label>
                            <input type="text" name="client_name" id="drClientNameInput" class="form-control "
                                value="{{ old('client_name') }}" placeholder="e.g. John Doe" required>
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
                            <label class="dr-label">Transfer from Store<span class="dr-req">*</span></label>
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
                            <label class="dr-label">Remarks/ Reference Number/ Order By</label>
                            <input type="text" name="remarks" class="form-control " value="{{ old('remarks') }}"
                                placeholder="e.g. Lorem ipsum dolor">
                        </div>
                    </div>

                    <div class="dr-items-box">
                        <div class="dr-item-details-table-wrap">
                            <table class="table dr-items-table" id="addReportItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 200px;">Item<span class="dr-req">*</span></th>
                                        <th style="min-width: 70px;">Unit</th>
                                        <th style="min-width: 100px;">Available Qty</th>
                                        <th style="min-width: 90px;">Issue Qty</th>
                                        <th style="min-width: 90px;">Left Qty</th>
                                        <th style="min-width: 130px;">Issue Date</th>
                                        <th style="min-width: 90px;">Rate<span class="dr-req">*</span></th>
                                        <th style="min-width: 100px;">Line Total</th>
                                        <th style="min-width: 86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="addModalItemsBody">
                                    <tr class="dr-item-row">
                                        <td>
                                            <select name="items[0][item_subcategory_id]" class="form-select  dr-item-select" required>
                                                <option value="">Item</option>
                                                @foreach($itemSubcategories as $s)
                                                <option value="{{ $s['id'] }}"
                                                    data-unit="{{ e($s['unit_measurement'] ?? '') }}"
                                                    data-rate="{{ e($s['standard_cost'] ?? 0) }}">
                                                    {{ e($s['item_name'] ?? '-') }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="items[0][unit]" class="form-control  dr-unit" readonly placeholder="-"></td>
                                        <td><input type="text" name="items[0][available_quantity]" class="form-control  dr-avail bg-light" readonly placeholder="-"></td>
                                        <td>
                                            <input type="text" name="items[0][quantity]" class="form-control  dr-qty" placeholder="-" required>
                                            <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                        </td>
                                        <td><input type="text" class="form-control  dr-left bg-light" readonly placeholder="-"></td>
                                        <td><input type="date" name="items[0][issue_date]" class="form-control  dr-issue-date" value="{{ date('Y-m-d') }}"></td>
                                        <td><input type="number" name="items[0][rate]" class="form-control  dr-rate" step="0.01" min="0" placeholder="-" required></td>
                                        <td><input type="text" class="form-control  dr-total bg-light" readonly placeholder="-"></td>
                                        <td class="dr-act-cell">
                                            <button type="button" class="dr-icon-btn dr-icon-btn--remove dr-remove-row" disabled title="Remove line" aria-label="Remove line">&minus;</button>
                                            <button type="button" class="dr-icon-btn dr-icon-btn--add dr-add-row" title="Add line" aria-label="Add line">+</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="dr-total-bar">Total: <span id="addModalGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn dr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn dr-btn-primary">Add Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Selling Voucher with Date Range Modal --}}
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewReportModalLabel">View Selling Voucher with Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="dr-label">Client Type</label>
                        <p class="dr-value mb-0" id="viewClientType">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Client Name</label>
                        <p class="dr-value mb-0" id="viewClientName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Payment Type</label>
                        <p class="dr-value mb-0" id="viewPaymentType">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Request Date</label>
                        <p class="dr-value mb-0" id="viewRequestDate">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Transfer from Store</label>
                        <p class="dr-value mb-0" id="viewStoreName">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Reference Number</label>
                        <p class="dr-value mb-0" id="viewReferenceNumber">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Order By</label>
                        <p class="dr-value mb-0" id="viewOrderBy">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="dr-label">Status</label>
                        <p class="mb-0"><span class="dr-status-pill dr-status-pill--pending" id="viewStatus">-</span></p>
                    </div>
                    <div class="col-12" id="viewRemarksWrap" style="display:none;">
                        <label class="dr-label">Remarks</label>
                        <p class="dr-value mb-0" id="viewRemarks"></p>
                    </div>
                </div>

                <div class="dr-items-box" id="viewReportItemsCard">
                    <div class="table-responsive">
                        <table class="table dr-items-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 180px;">Item</th>
                                    <th style="min-width: 70px;">Unit</th>
                                    <th style="min-width: 90px;">Issue Qty</th>
                                    <th style="min-width: 90px;">Return Qty</th>
                                    <th style="min-width: 90px;">Rate</th>
                                    <th style="min-width: 100px;">Line Total</th>
                                    <th style="min-width: 110px;">Issue Date</th>
                                </tr>
                            </thead>
                            <tbody id="viewReportItemsBody"></tbody>
                        </table>
                    </div>
                    <div class="dr-total-bar">Total: <span id="viewReportGrandTotal">0.00</span>/-</div>
                </div>

                <div class="dr-meta">
                    Created: <span id="viewCreatedAt">-</span>
                    <span class="ms-3" id="viewUpdatedAtWrap" style="display:none;">Last Updated: <span id="viewUpdatedAt"></span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn dr-btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn dr-btn-primary btn-print-view-modal" data-print-target="#viewReportModal" title="Print">Print</button>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="returnItemModalLabel">Transfer To</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="dr-label">Person / Buyer</label>
                            <p class="dr-value mb-0" id="returnClientName">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="dr-label">Transfer From Store</label>
                            <p class="dr-value mb-0" id="returnTransferFromStore">-</p>
                        </div>
                    </div>

                    <div class="dr-items-box">
                        <div class="table-responsive">
                            <table class="table dr-items-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 160px;">Item</th>
                                        <th style="min-width: 90px;">Issued Qty</th>
                                        <th style="min-width: 70px;">Unit</th>
                                        <th style="min-width: 100px;">Issue Date</th>
                                        <th style="min-width: 110px;">Return Qty</th>
                                        <th style="min-width: 140px;">Return Date</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItemModalBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn dr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn dr-btn-primary">Update</button>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="editReportModalLabel">Edit Selling Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 edit-client-identity-locked">
                            <label class="dr-label">Client Type<span class="dr-req">*</span></label>
                            <div class="dr-radio-row">
                                @foreach($clientTypes as $slug => $label)
                                <div class="form-check">
                                    <input class="form-check-input edit-dr-client-type-radio" type="radio"
                                        name="client_type_slug" id="edit_dr_ct_{{ $slug }}" value="{{ $slug }}" required>
                                    <label class="form-check-label" for="edit_dr_ct_{{ $slug }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="dr-label">Payment Type<span class="dr-req">*</span></label>
                            <select name="payment_type" class="form-select  edit-payment-type" required>
                                <option value="1">Credit</option>
                                <option value="0">Cash</option>
                                <option value="2">UPI</option>
                            </select>
                        </div>
                        <div class="col-md-4 edit-client-identity-locked" id="editDrClientNameWrap" style="display:none;">
                            <label class="dr-label">Client Name<span class="dr-req">*</span></label>
                            <select name="client_type_pk" class="form-select  edit-client-type-pk" id="editDrClientNameSelect">
                                <option value="">Select Client</option>
                                @foreach($clientNamesByType as $type => $list)
                                @foreach($list as $c)
                                <option value="{{ $c->id }}" data-type="{{ $c->client_type }}"
                                    data-client-name="{{ strtolower($c->client_name ?? '') }}">{{ $c->client_name }}</option>
                                @endforeach
                                @endforeach
                            </select>
                            <select id="editDrOtCourseSelect" class="form-select " style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">
                                    {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                            <select id="editDrCourseSelect" class="form-select " style="display:none;">
                                <option value="">Select Course</option>
                                @foreach($otCourses ?? [] as $course)
                                <option value="{{ $course->pk }}" data-course-name="{{ e($course->course_name) }}">
                                    {{ e($course->course_name) }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 edit-client-identity-locked" id="editDrNameFieldWrap" style="display:none;">
                            <label class="dr-label">Name<span class="dr-req">*</span></label>
                            <input type="text" name="client_name" class="form-control  edit-client-name bg-light"
                                id="editDrClientNameInput" placeholder="e.g. John Doe" required readonly>
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
                            <label class="dr-label">Remarks/ Reference Number/ Order By</label>
                            <input type="text" name="remarks" class="form-control  edit-remarks"
                                placeholder="e.g. Lorem ipsum dolor">
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

                    <div class="dr-items-box">
                        <div class="dr-item-details-table-wrap">
                            <table class="table dr-items-table" id="editReportItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 140px;">Store</th>
                                        <th style="min-width: 180px;">Item<span class="dr-req">*</span></th>
                                        <th style="min-width: 70px;">Unit</th>
                                        <th style="min-width: 100px;">Available Qty</th>
                                        <th style="min-width: 90px;">Issue Qty</th>
                                        <th style="min-width: 90px;">Left Qty</th>
                                        <th style="min-width: 130px;">Issue Date</th>
                                        <th style="min-width: 90px;">Rate<span class="dr-req">*</span></th>
                                        <th style="min-width: 100px;">Line Total</th>
                                        <th style="min-width: 86px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editModalItemsBody"></tbody>
                            </table>
                        </div>
                        <div class="dr-total-bar">Total: <span id="editModalGrandTotal">0.00</span>/-</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn dr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn dr-btn-primary">Update Selling Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- The ~4,200-line inline copy of this page's JS lived here. It was a stale
    duplicate of public/js/mess-selling-voucher-date-range.js (loaded above with
    window.SVDR_CFG), so every handler ran twice - "Add Item" appended two rows.
    Removed; the external file is now the single copy. --}}
@endsection