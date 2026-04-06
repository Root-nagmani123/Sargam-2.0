<div class="card border-0 stock-summary-table-root ssr-card">
    <div class="ssr-card-topbar" aria-hidden="true"></div>
    <div class="ssr-toolbar px-3 px-lg-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <div class="ssr-toolbar-icon d-none d-sm-flex" aria-hidden="true">
                <span class="material-symbols-rounded">table_chart</span>
            </div>
            <div class="min-w-0">
                <p class="ssr-toolbar-title mb-0 text-truncate">Stock movement summary</p>
                <p class="ssr-toolbar-sub mb-0 small text-muted">Opening, purchase, sale and closing by item</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <span class="ssr-count-badge">
                <span class="material-symbols-rounded ssr-count-badge-icon" aria-hidden="true">inventory_2</span>
                <span class="ssr-count-badge-label">{{ isset($reportPage) ? $reportPage->total() : (isset($reportData) ? count($reportData) : 0) }}</span>
                <span class="ssr-count-badge-text">items</span>
            </span>
        </div>
    </div>

    <p class="stock-summary-scroll-hint no-print d-md-none mb-0 px-3 pb-2 small" role="note">
        <span class="ssr-scroll-hint-inner">
            <span class="material-symbols-rounded ssr-scroll-hint-icon" aria-hidden="true">swap_horiz</span>
            Swipe sideways for all columns
        </span>
    </p>

    <div
        class="table-responsive table-fit-single-view stock-summary-table-scroll ssr-table-scroller"
        role="region"
        aria-label="Stock movement summary table"
        tabindex="0"
    >
        <table class="table table-fit align-middle mb-0 w-100 stock-summary-data-table ssr-table">
            <thead class="ssr-thead">
                <tr>
                    <th rowspan="2" class="sss-th-fixed text-center align-middle text-nowrap">SR.<br>No.</th>
                    <th rowspan="2" class="sss-th-fixed text-start align-middle">Item Name</th>
                    <th rowspan="2" class="sss-th-fixed text-center align-middle text-nowrap">Unit</th>
                    <th colspan="3" class="sss-grp ssr-grp-opening text-center align-middle">Opening</th>
                    <th colspan="3" class="sss-grp ssr-grp-purchase text-center align-middle">Purchase</th>
                    <th colspan="3" class="sss-grp ssr-grp-sale text-center align-middle">Sale</th>
                    <th colspan="3" class="sss-grp ssr-grp-closing text-center align-middle">Closing</th>
                </tr>
                <tr>
                    <th class="sss-sub ssr-grp-opening text-end text-nowrap">Qty</th>
                    <th class="sss-sub ssr-grp-opening text-end text-nowrap">Rate</th>
                    <th class="sss-sub ssr-grp-opening text-end text-nowrap">Amount</th>
                    <th class="sss-sub ssr-grp-purchase text-end text-nowrap">Qty</th>
                    <th class="sss-sub ssr-grp-purchase text-end text-nowrap">Rate</th>
                    <th class="sss-sub ssr-grp-purchase text-end text-nowrap">Amount</th>
                    <th class="sss-sub ssr-grp-sale text-end text-nowrap">Qty</th>
                    <th class="sss-sub ssr-grp-sale text-end text-nowrap">Rate</th>
                    <th class="sss-sub ssr-grp-sale text-end text-nowrap">Amount</th>
                    <th class="sss-sub ssr-grp-closing text-end text-nowrap">Qty</th>
                    <th class="sss-sub ssr-grp-closing text-end text-nowrap">Rate</th>
                    <th class="sss-sub ssr-grp-closing text-end text-nowrap">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $paginator = $reportPage ?? null;
                    $serialStart = $paginator && method_exists($paginator, 'firstItem') && !is_null($paginator->firstItem())
                        ? $paginator->firstItem()
                        : 1;
                    $rows = $reportPage ?? collect($reportData ?? []);
                @endphp
                @forelse($rows as $index => $item)
                    <tr class="sss-body-row">
                        <td class="text-center text-nowrap ssr-num ssr-cell-fixed">{{ $serialStart + $index }}</td>
                        <td class="text-start fw-medium ssr-item-name ssr-cell-fixed">{{ $item['item_name'] }}</td>
                        <td class="text-center text-nowrap ssr-num ssr-cell-fixed">{{ $item['unit'] ?? '—' }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-opening">{{ number_format($item['opening_qty'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-opening">₹{{ number_format($item['opening_rate'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-opening ssr-amt">₹{{ number_format($item['opening_amount'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-purchase">{{ number_format($item['purchase_qty'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-purchase">₹{{ number_format($item['purchase_rate'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-purchase ssr-amt">₹{{ number_format($item['purchase_amount'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-sale">{{ number_format($item['sale_qty'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-sale">₹{{ number_format($item['sale_rate'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-sale ssr-amt">₹{{ number_format($item['sale_amount'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-closing">{{ number_format($item['closing_qty'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-closing">₹{{ number_format($item['closing_rate'], 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-closing ssr-amt">₹{{ number_format($item['closing_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="p-0 border-0">
                            <div class="ssr-empty-state text-center py-5 px-3">
                                <span class="material-symbols-rounded ssr-empty-icon" aria-hidden="true">inventory</span>
                                <h6 class="ssr-empty-title mb-1">No stock movement</h6>
                                <p class="ssr-empty-text small text-muted mb-0">Nothing recorded for this period and filters.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                @if(($reportTotals['opening_amount'] ?? 0) != 0 ||
                    ($reportTotals['purchase_amount'] ?? 0) != 0 ||
                    ($reportTotals['sale_amount'] ?? 0) != 0 ||
                    ($reportTotals['closing_amount'] ?? 0) != 0)
                    <tr class="sss-totals-row">
                        <td colspan="3" class="text-end fw-bold ssr-totals-label ssr-cell-fixed">
                            Total
                        </td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-opening ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-opening ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap fw-bold ssr-num ssr-grp-opening ssr-amt">₹{{ number_format(($reportTotals['opening_amount'] ?? 0), 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-purchase ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-purchase ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap fw-bold ssr-num ssr-grp-purchase ssr-amt">₹{{ number_format(($reportTotals['purchase_amount'] ?? 0), 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-sale ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-sale ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap fw-bold ssr-num ssr-grp-sale ssr-amt">₹{{ number_format(($reportTotals['sale_amount'] ?? 0), 2) }}</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-closing ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap ssr-num ssr-grp-closing ssr-totals-dash">—</td>
                        <td class="text-end text-nowrap fw-bold ssr-num ssr-grp-closing ssr-amt">₹{{ number_format(($reportTotals['closing_amount'] ?? 0), 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if(isset($reportPage) && $reportPage->hasPages())
        <div class="ssr-pagination-bar px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $reportPage->firstItem() }}–{{ $reportPage->lastItem() }} of {{ $reportPage->total() }}
            </div>
            <div class="ssr-pagination-links">
                {{ $reportPage->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>
