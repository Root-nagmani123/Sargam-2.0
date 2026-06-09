<div class="table-responsive rounded-3 border border-light-subtle shadow-sm bg-white stock-purchase-table-wrapper" role="region" aria-label="Stock purchase table" tabindex="0">
    <table class="table table-bordered align-middle mb-0 stock-purchase-table" style="width:100%;">
        <thead class="stock-purchase-thead">
            <tr>
                @include('admin.mess.reports.partials.report-sno-th', ['class' => 'spr-th'])
                <th class="spr-th">Item</th>
                <th class="spr-th">Item Code</th>
                <th class="spr-th text-end">Unit</th>
                <th class="spr-th text-end">Quantity</th>
                <th class="spr-th text-end">Rate</th>
                <th class="spr-th text-end">Tax %</th>
                <th class="spr-th text-end">Tax Amount</th>
                <th class="spr-th text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $lines = $purchaseDetailLines ?? [];
                $lastVendorId = null;
                $lastOrderId = null;
            @endphp
            @forelse($lines as $index => $row)
                @if(($row['vendor_id'] ?? null) !== $lastVendorId)
                    <tr class="vendor-section-header-row">
                        <td colspan="9" class="vendor-section-header small fw-semibold">
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size:1rem;opacity:0.7;" aria-hidden="true">person</span>
                                VENDOR : {{ $row['vendor_name'] }}
                            </span>
                        </td>
                    </tr>
                    @php $lastVendorId = $row['vendor_id']; $lastOrderId = null; @endphp
                @endif
                @if(($row['order_id'] ?? null) !== $lastOrderId)
                    <tr class="bill-header-row">
                        <td colspan="9" class="bill-header small fw-semibold text-white">
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="material-symbols-rounded" style="font-size:1rem;" aria-hidden="true">receipt</span>
                                {{ $row['bill_label'] }}
                            </span>
                        </td>
                    </tr>
                    @php $lastOrderId = $row['order_id']; @endphp
                @endif
                <tr class="spr-item-row">
                    <td class="text-center text-body-secondary small mess-report-sno-cell">@include('admin.mess.reports.partials.report-serial-number', ['start' => $row['line_no'], 'index' => 0])</td>
                    <td class="fw-medium">{{ $row['item_name'] }}</td>
                    <td class="text-body-secondary">{{ $row['item_code'] }}</td>
                    <td class="text-end text-body-secondary">{{ $row['unit'] }}</td>
                    <td class="text-end spr-num">{{ number_format($row['qty'], 2) }}</td>
                    <td class="text-end spr-num">₹{{ number_format($row['rate'], 1) }}</td>
                    <td class="text-end spr-num">{{ number_format($row['tax_percent'], 2) }}%</td>
                    <td class="text-end spr-num">₹{{ number_format($row['tax_amount'], 2) }}</td>
                    <td class="text-end spr-num fw-semibold">₹{{ number_format($row['total'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="p-0 border-0">
                        <div class="spr-empty-state text-center py-5 px-3">
                            <span class="material-symbols-rounded d-block mx-auto mb-2" style="font-size:2.25rem;color:#94a3b8;" aria-hidden="true">shopping_cart_off</span>
                            <h6 class="fw-semibold text-body-secondary mb-2">No purchase details found</h6>
                            <p class="small text-body-tertiary mb-3">No records match the selected period, vendor, and store filters.</p>
                            <span class="badge bg-body-secondary text-body-emphasis rounded-1 px-3 py-2 fw-normal">
                                <span class="material-symbols-rounded align-middle me-1" style="font-size:0.875rem;" aria-hidden="true">lightbulb</span>
                                Try adjusting date range or filter criteria
                            </span>
                        </div>
                    </td>
                </tr>
            @endforelse
            @if(($reportGrandTotalAmount ?? 0) > 0)
                <tr class="grand-total-row fw-bold">
                    <td colspan="8" class="text-end">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="material-symbols-rounded" style="font-size:1rem;" aria-hidden="true">payments</span>
                            Grand Total (all {{ $reportLineCount ?? count($lines) }} lines):
                        </span>
                    </td>
                    <td class="text-end spr-num">₹{{ number_format($reportGrandTotalAmount, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@if(isset($reportPage) && $reportPage->hasPages())
    <div class="ssr-pagination-bar px-3 py-3 border-top bg-white rounded-bottom-3">
        {{ $reportPage->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@endif
