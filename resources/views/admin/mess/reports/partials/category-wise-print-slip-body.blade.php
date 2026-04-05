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
                    $buyerName = $first->client_name ?? ($first->clientTypeCategory?->client_name ?? 'N/A');
                    $rawClientType = $first->clientTypeCategory
                        ? (string) $first->clientTypeCategory->client_type
                        : (string) ($first->client_type_slug ?? 'N/A');
                    $clientTypeLabel = strtolower($rawClientType) === 'ot' ? 'OT' : ucfirst($rawClientType);
                    $clientSectionName = $first->clientTypeCategory?->client_name ?? null;
                    $slug = $first->client_type_slug ?? '';
                    $typeSuffix = ($slug === 'employee') ? 'Employee' : (($slug === 'ot') ? 'OT' : ucfirst((string) $slug));
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
                    {{-- Buyer + client category: side-by-side on wide layout; stacks in narrow/PDF --}}
                    <table class="report-details-table report-buyer-client-banner mb-2" style="width:100%;border-collapse:collapse;margin-bottom:10px;border:1px solid #dee2e6;border-radius:3px;">
                        <tr>
                            <td colspan="2" style="padding:0;background:#f8f9fa;border:0;vertical-align:top;">
                                <table style="width:100%;border-collapse:collapse;border:0;">
                                    <tr>
                                        <td class="report-banner-client" style="width:52%;padding:10px 12px;border:0;border-right:1px solid #dee2e6;vertical-align:top;">
                                            <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:#6c757d;font-weight:700;margin-bottom:6px;">Client type &amp; nearest category</div>
                                            <div style="font-weight:700;color:#1a1a1a;line-height:1.35;font-size:0.95rem;">
                                                @if($clientSectionName)
                                                    {{ $clientSectionName }}
                                                    <span style="font-weight:600;color:#495057;">({{ $clientTypeLabel }})</span>
                                                @else
                                                    {{ $clientTypeLabel }}
                                                    @if($typeSuffix !== 'N/A' && strcasecmp($typeSuffix, $clientTypeLabel) !== 0)
                                                        <span style="font-weight:600;color:#495057;"> — {{ $typeSuffix }}</span>
                                                    @endif
                                                @endif
                                                @if($courseDisplay)
                                                    <div style="margin-top:6px;font-weight:600;font-size:0.9rem;">Course: {{ $courseDisplay }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="report-banner-buyer" style="width:48%;padding:10px 12px;border:0;vertical-align:top;text-align:right;">
                                            <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.06em;color:#6c757d;font-weight:700;margin-bottom:6px;">Buyer name</div>
                                            <div style="font-weight:700;font-size:1.05rem;color:#004a93;line-height:1.3;">{{ $buyerName }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 print-slip-table align-middle">
                            <thead>
                                <tr>
                                    <th class="th-slip-no">Slip No.</th>
                                    <th class="th-item">Item Name</th>
                                    <th class="th-date">Request Date</th>
                                    <th class="th-qty">Quantity</th>
                                    <th class="th-price">Price</th>
                                    <th class="th-amount">Amount</th>
                                    <th class="th-remark">Remark</th>
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
                                            <td colspan="5" class="text-center text-muted">No line items</td>
                                            <td>{{ $voucher->remarks ?? '—' }}</td>
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
                                                    @if($itemIndex === 0)
                                                        <td class="text-center align-middle" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                                                    @endif
                                                    <td>{{ $itemName }}</td>
                                                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                                                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                                                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                                                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                                                    @if($itemIndex === 0)
                                                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                                                    @endif
                                                @else
                                                    @if($itemIndex === 0)
                                                        <td class="text-center align-middle" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                                                    @endif
                                                    <td>{{ $itemName }}</td>
                                                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                                                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                                                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                                                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                                                    @if($itemIndex === 0)
                                                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                <tr class="total-row">
                                    @if($dompdfSafeTables)
                                        <td></td><td></td><td></td><td></td>
                                        <td class="text-end"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                                        <td></td>
                                    @else
                                        <td colspan="4"></td>
                                        <td class="text-end"><strong>TOTAL</strong></td>
                                        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                                        <td></td>
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
                            <td></td><td></td><td></td><td></td>
                            <td class="text-end"><strong>GRAND TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                            <td></td>
                        @else
                            <td colspan="4"></td>
                            <td class="text-end"><strong>GRAND TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                            <td></td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
