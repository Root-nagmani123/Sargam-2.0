{{--
    Shared markup for Sale Voucher Report (category-wise print slip).
    Used by: main report view, PDF export, standalone print view.
    Expects: $sectionsToShow, $fromDateFormatted, $toDateFormatted, $otCourses, $grandTotal, $filtersApplied (optional),
    $printPageBreakPerBuyer (bool), $freezeSaleVoucherTableHeader (optional, screen-only split header).
--}}
@php
    $showBrandingHeader = (bool) ($showBrandingHeader ?? false);
    $dompdfSafeTables = (bool) ($dompdfSafeTables ?? false);
    $freezeSaleVoucherTableHeader = (bool) ($freezeSaleVoucherTableHeader ?? false);
    $cwSlipUseSplitTable = $freezeSaleVoucherTableHeader && ! $dompdfSafeTables;
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
                    $messClientCategory = $first->clientTypeCategory?->client_name ?? null;
                    $clientTypeDisplay = mess_category_wise_client_type_line_base(
                        $clientTypeLabel,
                        $slug,
                        $buyerName,
                        $messClientCategory
                    );
                @endphp
                <div class="print-slip-section print-slip-page mb-4">
                    {{-- Buyer + client category: side-by-side on wide layout; stacks in narrow/PDF --}}
                    <table class="report-details-table report-buyer-client-banner mb-2" style="width:100%;border-collapse:collapse;margin-bottom:10px;border:1px solid #dee2e6;border-radius:3px;">
                        <tr>
                            <td style="width:50%;padding:8px 10px;background:#f8f9fa;vertical-align:middle;font-weight:600;border:0;">
                                BUYER NAME : {{ $buyerName }}- {{ $typeSuffix }}
                            </td>
                            <td style="width:50%;padding:8px 10px;background:#f8f9fa;vertical-align:middle;font-weight:600;text-align:right;border:0;">
                                CLIENT TYPE : <strong>
                                    {{ $clientTypeDisplay }}
                                    @if($courseDisplay)
                                        [{{ $courseDisplay }}]
                                    @endif
                                </strong>
                            </td>
                        </tr>
                    </table>
                    @if($cwSlipUseSplitTable)
                        <div class="cw-slip-table-split">
                            <div class="cw-slip-table-head-wrap">
                                <table class="table table-sm mb-0 print-slip-table align-middle cw-slip-col-sync">
                                    @include('admin.mess.reports.partials.category-wise-print-slip-colgroup')
                                    <thead>
                                        <tr>
                                            <th class="th-slip-no">Slip No.</th>
                                            <th class="th-remark">Remark</th>
                                            <th class="th-item">Item Name</th>
                                            <th class="th-date">Request Date</th>
                                            <th class="th-qty">Quantity</th>
                                            <th class="th-price">Price</th>
                                            <th class="th-amount">Amount</th>
                                            <th class="th-remark">Remark</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="cw-slip-table-body-scroll">
                                <table class="table table-sm mb-0 print-slip-table align-middle cw-slip-col-sync">
                                    @include('admin.mess.reports.partials.category-wise-print-slip-colgroup')
                                    <tbody>
                                        @include('admin.mess.reports.partials.category-wise-print-slip-section-tbody', [
                                            'sectionVouchers' => $sectionVouchers,
                                            'dompdfSafeTables' => $dompdfSafeTables,
                                        ])
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="table-responsive cw-slip-table-scroll">
                            <table class="table table-sm mb-0 print-slip-table align-middle">
                                <thead>
                                    <tr>
                                        <th class="th-slip-no">Slip No.</th>
                                        <th class="th-remark">Remark</th>
                                        <th class="th-item">Item Name</th>
                                        <th class="th-date">Request Date</th>
                                        <th class="th-qty">Quantity</th>
                                        <th class="th-price">Price</th>
                                        <th class="th-amount">Amount</th>
                                        <th class="th-remark">Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @include('admin.mess.reports.partials.category-wise-print-slip-section-tbody', [
                                        'sectionVouchers' => $sectionVouchers,
                                        'dompdfSafeTables' => $dompdfSafeTables,
                                    ])
                                </tbody>
                            </table>
                        </div>
                    @endif
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
                            <td></td><td></td><td></td><td></td><td></td>
                            <td class="text-end"><strong>GRAND TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                            <td></td>
                        @else
                            <td colspan="5"></td>
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
