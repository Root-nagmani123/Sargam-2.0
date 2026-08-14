{{-- Filter toolbar + bills table for "Generate Invoice & Process Payment".
    Rendered on its own page; the element IDs are what public/js/
    process-mess-bills-employee.js binds to, so they must not change. --}}
                {{-- Filters auto-apply; whatever does not fit collapses into "+N Filter". --}}
                <form id="addModalFilterForm" class="mb-3">
                    @csrf
                    <div class="d-flex align-items-center gap-2 pmbe-toolbar pmbe-modal-toolbar">
                        <div class="d-flex align-items-center gap-2 pmbe-filter-form" id="pmbeModalFilterForm">
                            <span class="programme-dt-filters-label flex-shrink-0 align-self-center">Filter</span>
                            <div id="pmbeModalFilterItems" class="d-flex align-items-center gap-2 pmbe-filter-items">
                                <div class="pmbe-filter-item" data-filter="dates">
                                    <label class="pmbe-filter-item-label" for="modal_date_from">Date range</label>
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="text" name="modal_date_from" id="modal_date_from" class="form-control pmbe-filter-date pmbe-modal-auto-filter"
                                               value="{{ now()->startOfMonth()->format('d-m-Y') }}" placeholder="Select date" autocomplete="off" required>
                                        <span class="pmbe-filter-dash">–</span>
                                        <input type="text" name="modal_date_to" id="modal_date_to" class="form-control pmbe-filter-date pmbe-modal-auto-filter"
                                               value="{{ now()->endOfMonth()->format('d-m-Y') }}" placeholder="Select date" autocomplete="off" required>
                                    </div>
                                </div>
                                <div class="pmbe-filter-item" data-filter="status">
                                    <label class="pmbe-filter-item-label" for="modal_status">Status</label>
                                    <select id="modal_status" class="form-select pmbe-filter-select pmbe-modal-auto-filter" data-placeholder="Status">
                                        <option value="">Status</option>
                                        <option value="unpaid">Unpaid</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                                <div class="pmbe-filter-item" data-filter="buyer">
                                    <label class="pmbe-filter-item-label" for="modal_buyer_name">Buyer</label>
                                    <select name="modal_buyer_name" id="modal_buyer_name" class="form-select pmbe-filter-select pmbe-modal-auto-filter" data-placeholder="Buyer">
                                        <option value="">Buyer</option>
                                        @php $selectedModalBuyerNames = (array) ($buyerName ?? request('buyer_name', [])); @endphp
                                        @if(($clientType ?? request('client_type')) === 'course' && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                            @foreach($courseBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                        @elseif(($clientType ?? request('client_type')) === 'other' && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                            @foreach($otherBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                        @elseif(($clientType ?? request('client_type')) === 'section' && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                            @foreach($sectionBuyerNames as $buyer)<option value="{{ $buyer }}" {{ in_array($buyer, $selectedModalBuyerNames, true) ? 'selected' : '' }}>{{ $buyer }}</option>@endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="pmbe-filter-item" data-filter="invoice">
                                    <label class="pmbe-filter-item-label" for="modal_invoice_sent">Invoice Sent</label>
                                    <select id="modal_invoice_sent" class="form-select pmbe-filter-select pmbe-modal-auto-filter" data-placeholder="Invoice Sent">
                                        <option value="">Invoice Sent</option>
                                        <option value="sent">Sent</option>
                                        <option value="not_sent">Not sent</option>
                                    </select>
                                </div>
                                <div class="pmbe-filter-item" data-filter="client_type">
                                    <label class="pmbe-filter-item-label" for="modal_client_type">Client Type</label>
                                    <select name="modal_client_type" id="modal_client_type" class="form-select pmbe-filter-select pmbe-modal-auto-filter" data-placeholder="Client Type" data-clears="modal_client_type_pk,modal_buyer_name">
                                        <option value="">Client Type</option>
                                        @foreach($clientTypes ?? [] as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="pmbe-filter-item" data-filter="client_type_pk">
                                    <label class="pmbe-filter-item-label" for="modal_client_type_pk">Client Category</label>
                                    <select name="modal_client_type_pk" id="modal_client_type_pk" class="form-select pmbe-filter-select pmbe-modal-auto-filter" data-placeholder="Client Category" data-clears="modal_buyer_name">
                                        <option value="">Client Category</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Overflow: filters that don't fit collapse into this "+N Filter" popover --}}
                            <div class="dropdown flex-shrink-0 align-self-center d-none" id="pmbeModalMoreFilterWrap">
                                <a href="javascript:void(0)" class="pmbe-more-filters dropdown-toggle border-0 bg-transparent" id="pmbeModalMoreFilterToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+ Filter</a>
                                <div class="dropdown-menu pmbe-more-menu shadow border rounded-1">
                                    <div class="pmbe-more-header mb-2 d-flex align-items-center justify-content-between">
                                        <span class="fw-semibold text-muted small">Filters</span>
                                        <button type="button" class="btn-close btn-close-sm" aria-label="Close filters" data-pmbe-modal-close-more></button>
                                    </div>
                                    <div id="pmbeModalMoreFilterItems"></div>
                                </div>
                            </div>

                            <button type="button" class="programme-dt-btn-reset flex-shrink-0 align-self-center d-inline-flex align-items-center justify-content-center" id="modalClearFiltersBtn" title="Remove all filters">Remove Filter</button>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0 align-self-center">
                            <button type="button" class="btn programme-dt-btn-columns" id="modalBillsColumnsBtn"
                                data-bs-toggle="modal" data-bs-target="#modalBillsColumnVisibilityModal" title="Show / hide columns">
                                <i class="material-symbols-rounded">view_column</i><span>Columns</span>
                            </button>
                            <div class="pmbe-search-wrap d-flex align-items-center" id="pmbeModalSearchWrap">
                                <button type="button" class="pmbe-search-toggle" id="pmbeModalSearchToggle" aria-expanded="false" aria-label="Search bills" title="Search bills">
                                    <i class="material-symbols-rounded">search</i>
                                </button>
                                <div class="pmbe-modal-search">
                                    <input type="text" id="modalSearch" class="form-control" placeholder="Search bills...">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>


                <div id="modalBillsTableHost" class="table-responsive">
                <table id="modalBillsTable"
                       class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="sno" data-mess-col-original="S.No."><span class="d-inline-flex align-items-center gap-1"><span>S.No.</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th mess-th-sorted" data-sort="buyer_name" data-mess-col-original="Buyer Name"><span class="d-inline-flex align-items-center gap-1"><span>Buyer Name</span><span class="mess-report-sort-icon material-symbols-rounded" aria-hidden="true">arrow_upward</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="invoice_no" data-mess-col-original="Slip Number"><span class="d-inline-flex align-items-center gap-1"><span>Slip Number</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th" data-sort="payment_type" data-mess-col-original="Payment Type"><span class="d-inline-flex align-items-center gap-1"><span>Payment Type</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th text-end" data-sort="total" data-mess-col-original="Total"><span class="d-inline-flex align-items-center gap-1"><span>Total</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold mess-sort-th mess-report-sort-th text-end" data-sort="total_due_amount" data-mess-col-original="Total Due Amount"><span class="d-inline-flex align-items-center gap-1"><span>Total Due Amount</span><span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span></span></th>
                                <th class="text-nowrap py-3 fw-semibold text-center" data-mess-col-original="Status">Status</th>
                                <th class="text-nowrap py-3 fw-semibold text-center" data-mess-col-original="Action">Action</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            {{-- Server-rendered skeleton so the first paint already has the
                                 table's shape; the page JS replaces it with its own on every
                                 fetch. Kept in step with renderModalBillsSkeleton(). --}}
                            @for ($i = 0; $i < 5; $i++)
                                <tr class="modal-bills-skeleton-row" aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">
                                    <td>@if($i === 0)<span class="visually-hidden" role="status">Loading bills</span>@endif<span class="modal-bills-skeleton modal-bills-skeleton--sn"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--buyer"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--invoice"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--payment"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--status"></span></td>
                                    <td><span class="modal-bills-skeleton modal-bills-skeleton--action"></span></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     id="modalPaginationNav">
                    <div class="programme-dt-pagination">
                        <div class="dataTables_paginate paging_full_numbers">
                            <ul class="pagination mb-0" id="modalPaginationList"></ul>
                        </div>
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_length">
                            <label class="mb-0">Showing
                                <select id="modalPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info mb-0" id="modalPaginationInfo">of 0 items</div>
                    </div>
                </div>
