{{--
    Shared markup for Sale Voucher Report (category-wise print slip).
    Used by: main report view, PDF export, standalone print view.
    Expects: $sectionsToShow, $fromDateFormatted, $toDateFormatted, $otCourses, $grandTotal, $filtersApplied (optional),
    $printPageBreakPerBuyer (bool).
--}}
@php
    $showBrandingHeader = (bool) ($showBrandingHeader ?? false);
    $dompdfSafeTables = (bool) ($dompdfSafeTables ?? false);
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
@endphp

@if($sectionsToShow->isEmpty())
    <div class="alert {{ isset($filtersApplied) && $filtersApplied ? 'alert-info' : 'alert-warning' }} mb-0 cw-slip-empty">
        @if(isset($filtersApplied) && $filtersApplied)
            No selling vouchers found for the selected filters.
        @else
            <strong>Apply filters to view report.</strong> Select date range and/or client type / buyer name, then click <strong>Apply Filters</strong> to see data.
        @endif
    </div>
@else
    @foreach($sectionsToShow as $groupedSections)
        <div class="print-page-wrap {{ ($printPageBreakPerBuyer && ! $loop->last) ? 'print-page-break' : '' }}">
            <div class="report-header text-center mb-2 print-slip-page">
                @if($showBrandingHeader)
                    @if($dompdfSafeTables)
                        <table class="lbsnaa-branding-table">
                            <tr>
                                <td class="lbsnaa-branding-emblem">
                                    <img src="{{ $emblemSrc }}" alt="Emblem of India" class="lbsnaa-header-logo">
                                </td>
                                <td class="lbsnaa-branding-lines">
                                    <div class="lbsnaa-brand-line-1">Government of India</div>
                                    <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                                    <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                                </td>
                                <td class="lbsnaa-branding-logo">
                                    <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo" class="lbsnaa-header-logo lbsnaa-header-logo-right">
                                </td>
                            </tr>
                        </table>
                    @else
                        <div class="lbsnaa-header-row">
                            <div class="lbsnaa-brand-left">
                                <div class="lbsnaa-logo-wrap">
                                    <img src="{{ $emblemSrc }}" alt="Emblem of India" class="lbsnaa-header-logo">
                                </div>
                                <div class="lbsnaa-brand-lines">
                                    <div class="lbsnaa-brand-line-1">Government of India</div>
                                    <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                                    <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                                </div>
                            </div>
                            <div class="lbsnaa-brand-right">
                                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo" class="lbsnaa-header-logo lbsnaa-header-logo-right">
                            </div>
                        </div>
                    @endif
                @endif
                <h3 class="report-mess-title mb-1">OFFICER'S MESS LBSNAA MUSSOORIE</h3>
                <div class="report-title-bar">
                    Sale Voucher Report
                    @if(request('from_date') || request('to_date'))
                        Between {{ $fromDateFormatted }} To {{ $toDateFormatted }}
                    @endif
                </div>
            </div>

            @forelse($groupedSections as $groupKey => $sectionVouchers)
                @php
                    $first = $sectionVouchers->first();
                    $buyerName = $first->client_name ?? ($first->clientTypeCategory->client_name ?? 'N/A');
                    $clientTypeLabel = $first->clientTypeCategory
                        ? ucfirst($first->clientTypeCategory->client_type)
                        : ucfirst($first->client_type_slug ?? 'N/A');
                    $slug = $first->client_type_slug ?? '';
                    $typeSuffix = ($slug === 'employee') ? 'Employee' : (($slug === 'ot') ? 'OT' : ucfirst($slug));
                    if (!$typeSuffix) {
                        $typeSuffix = 'N/A';
                    }
                    $courseDisplay = null;
                    if (in_array($slug, ['course', 'ot'], true) && isset($otCourses) && $otCourses->isNotEmpty()) {
                        $selectedCourse = $otCourses->firstWhere('pk', $first->client_type_pk ?? null);
                        if ($selectedCourse) {
                            $courseDisplay = $selectedCourse->course_name;
                        }
                    }
                @endphp
                <div class="print-slip-section print-slip-page mb-4">
                    <table class="report-details-table mb-2" style="width:100%;border-collapse:collapse;margin-bottom:10px;border:1px solid #dee2e6;border-radius:3px;">
                        <tr>
                            <td style="width:50%;padding:8px 10px;background:#f8f9fa;vertical-align:middle;font-weight:600;border:0;">
                                BUYER NAME : {{ $buyerName }}- {{ $typeSuffix }}
                            </td>
                            <td style="width:50%;padding:8px 10px;background:#f8f9fa;vertical-align:middle;font-weight:600;text-align:right;border:0;">
                                CLIENT TYPE : <strong>
                                    {{ $clientTypeLabel }}
                                    @if($courseDisplay)
                                        [{{ $courseDisplay }}]
                                    @endif
                                </strong>
                            </td>
                        </tr>
                    </table>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 print-slip-table align-middle">
                            <thead>
                                <tr>
                                    <th class="th-slip-no">Slip No.</th>
                                    <th class="th-buyer">Buyer Name</th>
                                    <th class="th-remark">Remark</th>
                                    <th class="th-item">Item Name</th>
                                    <th class="th-date">Request Date</th>
                                    <th class="th-qty">Quantity</th>
                                    <th class="th-price">Price</th>
                                    <th class="th-amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sectionTotal = 0; @endphp
                                @foreach($sectionVouchers as $voucher)
                                    @php
                                        $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk ?? 0, 6, '0', STR_PAD_LEFT));
                                        $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
                                        $rowCount = max(1, $voucher->items->count());
                                    @endphp
                                    @if($voucher->items->isEmpty())
                                        <tr>
                                            <td class="text-center">{{ $requestNo }}</td>
                                            <td class="buyer-name-cell">{{ $buyerName }}</td>
                                            <td>{{ $voucher->remarks ?? '—' }}</td>
                                            @if($dompdfSafeTables)
                                                <td></td><td></td><td></td><td></td>
                                                <td class="text-center text-muted">No line items</td>
                                            @else
                                                <td colspan="5" class="text-center text-muted">No line items</td>
                                            @endif
                                        </tr>
                                    @else
                                        @foreach($voucher->items as $itemIndex => $item)
                                            @php
                                                $issueQty = (float) ($item->quantity ?? 0);
                                                $returnQty = (float) ($item->return_quantity ?? 0);
                                                $netQty = max(0, $issueQty - $returnQty);
                                                $rate = (float) ($item->rate ?? 0);
                                                $itemAmount = $netQty * $rate;
                                                $sectionTotal += $itemAmount;
                                                $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                                                $itemIssueDate = $item->issue_date ?? null;
                                                $itemIssueDateFormatted = $itemIssueDate
                                                    ? ($itemIssueDate instanceof \Carbon\Carbon
                                                        ? $itemIssueDate->format('d-m-Y')
                                                        : \Carbon\Carbon::parse($itemIssueDate)->format('d-m-Y'))
                                                    : $requestDate;
                                            @endphp
                                            <tr>
                                                @if($dompdfSafeTables)
                                                    <td class="text-center align-middle">{{ $requestNo }}</td>
                                                    <td class="align-middle buyer-name-cell">{{ $buyerName }}</td>
                                                    <td class="align-middle">{{ $voucher->remarks ?? '—' }}</td>
                                                    <td>{{ $itemName }}</td>
                                                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                                                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                                                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                                                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                                                @else
                                                    @if($itemIndex === 0)
                                                        <td class="text-center align-middle" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                                                        <td class="align-middle buyer-name-cell" rowspan="{{ $rowCount }}">{{ $buyerName }}</td>
                                                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                                                    @endif
                                                    <td>{{ $itemName }}</td>
                                                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                                                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                                                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                                                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                <tr class="total-row">
                                    @if($dompdfSafeTables)
                                        <td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td class="text-end"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                                    @else
                                        <td colspan="6"></td>
                                        <td class="text-end"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">No selling vouchers found for the selected filters.</div>
            @endforelse
        </div>
    @endforeach

    <div class="print-grand-total-block mt-2">
        <div class="table-responsive">
            <table class="table table-sm mb-0 print-slip-table align-middle print-grand-total-table">
                <tbody>
                    <tr class="grand-total-row">
                        @if($dompdfSafeTables)
                            <td></td><td></td><td></td><td></td><td></td><td></td>
                            <td class="text-end"><strong>GRAND TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                        @else
                            <td colspan="6"></td>
                            <td class="text-end"><strong>GRAND TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
