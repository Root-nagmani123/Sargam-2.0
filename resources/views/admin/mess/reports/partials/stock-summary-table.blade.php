<div class="card border-0 shadow-sm stock-summary-table-root">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold text-dark fs-5">Stock Movement Summary</span>
        </div>
        <span class="text-muted stock-summary-table-meta">
            Total items: {{ isset($reportPage) ? $reportPage->total() : (isset($reportData) ? count($reportData) : 0) }}
        </span>
    </div>
    <div class="table-responsive table-fit-single-view">
        <table class="table table-fit align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle">SR.<br>No</th>
                    <th rowspan="2" class="text-center align-middle">Item Name</th>
                    <th rowspan="2" class="align-middle text-end">Unit</th>
                    <th colspan="3" class="text-center">Opening</th>
                    <th colspan="3" class="text-center">Purchase</th>
                    <th colspan="3" class="text-center">Sale</th>
                    <th colspan="3" class="text-center">Closing</th>
                </tr>
                <tr>
                    <!-- Opening -->
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <!-- Purchase -->
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <!-- Sale -->
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <!-- Closing -->
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
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
                    <tr>
                        <td class="text-center">
                            {{ $serialStart + $index }}
                        </td>
                        <td class="fw-bold ssr-item-name">{{ $item['item_name'] }}</td>
                        <td class="text-end">{{ isset($item['unit']) && is_numeric($item['unit']) ? number_format((float)$item['unit'], 2) : ($item['unit'] ?? '—') }}</td>
                        <!-- Opening -->
                        <td class="text-end">{{ number_format($item['opening_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['opening_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['opening_amount'], 2) }}</td>
                        <!-- Purchase -->
                        <td class="text-end">{{ number_format($item['purchase_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['purchase_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['purchase_amount'], 2) }}</td>
                        <!-- Sale -->
                        <td class="text-end">{{ number_format($item['sale_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['sale_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['sale_amount'], 2) }}</td>
                        <!-- Closing -->
                        <td class="text-end">{{ number_format($item['closing_qty'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['closing_rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['closing_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-center py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="d-flex flex-column align-items-center gap-3">
                                <div>
                                    <h6 class="text-muted mb-1">No Stock Movement Found</h6>
                                    <p class="text-muted small mb-0">No transactions recorded for the selected period</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                @if(($reportTotals['opening_amount'] ?? 0) != 0 ||
                    ($reportTotals['purchase_amount'] ?? 0) != 0 ||
                    ($reportTotals['sale_amount'] ?? 0) != 0 ||
                    ($reportTotals['closing_amount'] ?? 0) != 0)
                    <tr class="table-primary fw-bold">
                        <td colspan="3" class="text-end sticky-col sticky-col-total ssr-totals-label" style="letter-spacing: 0.02em;">
                            Total
                        </td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format(($reportTotals['opening_amount'] ?? 0), 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format(($reportTotals['purchase_amount'] ?? 0), 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format(($reportTotals['sale_amount'] ?? 0), 2) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">₹{{ number_format(($reportTotals['closing_amount'] ?? 0), 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if(isset($reportPage) && $reportPage->hasPages())
        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted">
                Showing {{ $reportPage->firstItem() }} to {{ $reportPage->lastItem() }} of {{ $reportPage->total() }} items
            </div>
            <div>
                {{ $reportPage->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>

