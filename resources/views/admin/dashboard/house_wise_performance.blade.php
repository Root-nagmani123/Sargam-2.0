@extends('admin.layouts.master')

@section('title', 'House wise Performance')

@push('styles')
<style>
/* =====================================================================
   House wise Performance — one panel per house, printable as-is.

   Page chrome is the "new design" (docs/new-design-index-page.md): the
   export row above the card, the programme-dt toolbar, and every table as
   .programme-dt-table inside a .programme-dt-panel — which is what §3d
   prescribes for a report view rather than a paginated grid.

   .programme-dt-table already owns the header fill, cell padding, rules
   and hover, so nothing here restates them. What is left is only what is
   particular to this sheet: the navy house band, the column widths, the
   severity tints, the summary rows, and how it behaves on paper.

   Selectors are scoped under .hwp-page and qualified past
   `.programme-dt-table tbody td` so they win on specificity without
   !important — except where custom.css itself uses !important.
   ===================================================================== */
.hwp-page {
    /* Derived, not invented — see docs/design.md, Layer A. */
    --hwp-band: var(--ds-primary);
    --hwp-band-chip: rgba(255, 255, 255, 0.16);

    /* Severity has no Layer A token: the palette carries brand and ink,
       not status. Major reuses the brand red; Minor stays amber because
       nothing in the system expresses "caution". */
    --hwp-minor-bg: #fef3c7;
    --hwp-minor-ink: #92400e;
    --hwp-major-bg: rgba(var(--ds-secondary-rgb), 0.12);
    --hwp-major-ink: var(--ds-secondary);

    /* Summary rows: a tint of the brand, lighter for an OT's own subtotal
       and a step up for the house's Final Marks, so the two never read as
       the same kind of row. */
    --hwp-subtotal-bg: rgba(var(--bs-primary-rgb), 0.10);
    --hwp-subtotal-line: rgba(var(--bs-primary-rgb), 0.30);
    --hwp-final-bg: rgba(var(--bs-primary-rgb), 0.16);
}

/* Each house is its own panel inside the one card, so they need the gap
   the toolbar's siblings get for free. */
.hwp-page .hwp-house { margin-bottom: var(--ds-space-4); }
.hwp-page .hwp-house:last-of-type { margin-bottom: 0; }

/* The house band — the panel's own heading strip, above its table. */
.hwp-page .hwp-house-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2);
    padding: var(--ds-space-3);
    background: var(--hwp-band);
    color: #fff;
}

/* Explicit white, not inherited: the admin theme sets a dark colour on
   h1-h6, which wins over the band's colour and left the house name
   near-invisible against the navy. */
.hwp-page .hwp-house-name {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.01em;
    color: #ffffff !important;
}

.hwp-page .hwp-house-date {
    font-size: 0.8125rem;
    color: #ffffff;
    opacity: 0.9;
}

.hwp-page .hwp-house-meta {
    margin-left: auto;
    display: flex;
    gap: var(--ds-space-2);
    flex-wrap: wrap;
}

/* rounded-1 (4px), not a pill — the mandate in sargam-app.css:15-20 and
   §1 of the chrome doc. */
.hwp-page .hwp-chip {
    display: inline-block;
    padding: var(--ds-space-1) var(--ds-space-2);
    border-radius: var(--ds-radius);
    background: var(--hwp-band-chip);
    font-size: 0.8125rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Column widths. Column 2 (Student Name) already wraps at 420px from
   custom.css:426; column 4 is long text and would otherwise render as one
   unbroken line and blow the table out sideways — the ⚠️ in §3. */
.hwp-page .programme-dt-table th.hwp-col-no,
.hwp-page .programme-dt-table tbody td.hwp-col-no { width: 4.5rem; text-align: center; }

.hwp-page .programme-dt-table th.hwp-col-code,
.hwp-page .programme-dt-table tbody td.hwp-col-code { width: 9rem; }

.hwp-page .programme-dt-table tbody td.hwp-col-cat { white-space: normal; max-width: 420px; }

.hwp-page .programme-dt-table th.hwp-col-marks,
.hwp-page .programme-dt-table tbody td.hwp-col-marks {
    width: 7rem;
    text-align: center;
    font-variant-numeric: tabular-nums;
}

/* An OT with several deductions gets one row per deduction; the name cell is
   written once and the rest of their rows are visually tied to it. */
.hwp-page .programme-dt-table tbody tr.hwp-row-continued td.hwp-col-no,
.hwp-page .programme-dt-table tbody tr.hwp-row-continued td.hwp-col-name,
.hwp-page .programme-dt-table tbody tr.hwp-row-continued td.hwp-col-code {
    border-top: 0;
    color: transparent;
}

.hwp-page .hwp-severity {
    display: inline-block;
    margin-left: var(--ds-space-1);
    padding: 0 var(--ds-space-1);
    border-radius: var(--ds-radius);
    background: var(--hwp-minor-bg);
    color: var(--hwp-minor-ink);
    font-size: 0.7rem;
    font-weight: 700;
}

.hwp-page .hwp-severity--major {
    background: var(--hwp-major-bg);
    color: var(--hwp-major-ink);
}

/* Summary rows. The tint goes on the cells, not the row: .programme-dt-table
   paints every td with an opaque background, which covers anything set on
   the <tr>. Qualified past the grid's own hover rule so a summary row does
   not light up like a data row. */
.hwp-page .programme-dt-table tbody tr.hwp-student-total td,
.hwp-page .programme-dt-table tbody tr.hwp-student-total:hover td {
    background: var(--hwp-subtotal-bg);
    font-weight: 700;
    color: var(--ds-primary);
    border-top: 1px solid var(--hwp-subtotal-line) !important;
}

.hwp-page .programme-dt-table tbody tr.hwp-final td,
.hwp-page .programme-dt-table tbody tr.hwp-final:hover td {
    background: var(--hwp-final-bg);
    font-weight: 700;
    color: var(--ds-primary);
    border-top: 2px solid var(--hwp-band) !important;
    font-size: 0.9rem;
}

/* Print-only letterhead — hidden on screen by d-none, shown by d-print-block. */
.hwp-page .hwp-print-head {
    margin-bottom: var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 2px solid var(--ds-primary);
    text-align: center;
}

.hwp-page .hwp-print-logo { height: 3rem; width: auto; }

.hwp-page .hwp-print-title {
    margin: var(--ds-space-2) 0 var(--ds-space-1);
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ds-primary);
}

.hwp-page .hwp-print-meta {
    font-size: 0.75rem;
    color: var(--ds-ink-muted);
}

/* =====================================================================
   Paper. The sheet is the letterhead and the house tables — nothing else.

   The admin layout's chrome (header_new's topbar, sidebar_new's icon rail
   and menu panel, the page loader, the breadcrumb card) has no print rules
   of its own, so it is all switched off here; otherwise a landscape print
   lays the report out beside the sidebar and under the "Government of
   India" strip on every page.
   ===================================================================== */
@media print {
    @page { size: A4 portrait; margin: 12mm 10mm; }

    /* `html body` is load-bearing: header_new's own <style>, which comes
       later in the document, forces #sidebarTabContent and the side panel
       to display:block !important on any page 992px or wider — a landscape
       sheet is — so an equally specific rule here loses. */
    html body header.topbar,
    html body #sidebarTabContent,
    html body aside.side-mini-panel.with-vertical,
    html body .left-sidebar,
    html body .sidebar-overlay,
    html body .sargam-loader,
    html body .mess-dt-stale-hint,
    html body .modern-breadcrumb-wrapper,
    .hwp-noprint { display: none !important; }

    /* With the sidebar gone, its reserved gutter and the fixed header's
       offset must go too, or the sheet prints squeezed to the right.
       Anchored on the layout's ids: sidebar-menu-enhanced.css reserves the
       gutter with a five-class !important selector, and spacing-system.css
       pads the container the same way, so only an id outranks them. */
    html, body { height: auto !important; min-height: 0 !important; background: var(--ds-surface) !important; }

    #main-wrapper,
    #main-wrapper > .page-wrapper,
    #main-wrapper .body-wrapper,
    #main-content {
        margin: 0 !important;
        padding: 0 !important;
        /* 100%, not auto: #main-wrapper is a flex row, and an auto-width
           item there shrinks to its content instead of filling the sheet. */
        width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        overflow: visible !important;
        background: var(--ds-surface) !important;
    }

    #main-content .hwp-page.container-fluid { max-width: none; padding: 0 !important; }

    /* Backgrounds are dropped on paper by default, which would leave the
       navy band white with white text on it. The tints carry meaning here
       (band, subtotal, final), so the whole sheet keeps its colours. */
    .hwp-page {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* The card chrome is screen furniture; on paper the sheet is the tables. */
    .hwp-page .card,
    .hwp-page .programme-dt-panel {
        box-shadow: none !important;
        border: 0 !important;
        border-radius: 0 !important;
        overflow: visible !important;
        background: transparent !important;
    }

    .hwp-page .card-body { padding: 0 !important; }

    /* .table-responsive scrolls on screen but CLIPS on paper — anything past
       the viewport width is simply cut off the sheet. */
    .hwp-page .table-responsive { overflow: visible !important; }

    /* A house is allowed to run across pages. Keeping one whole (the old
       page-break-inside: avoid) pushed any house taller than a page onto
       the next sheet, left the first page blank, and then split it anyway.
       Instead: the band never ends a page, the column heads repeat on
       every page, and no single row is cut in half. */
    .hwp-page .hwp-house {
        break-inside: auto;
        margin-bottom: var(--ds-space-4);
        border: 1px solid var(--ds-line) !important;
    }

    .hwp-page .hwp-house-head {
        break-inside: avoid;
        break-after: avoid;
        padding: var(--ds-space-2) var(--ds-space-3);
    }

    .hwp-page .programme-dt-table thead { display: table-header-group; }
    .hwp-page .programme-dt-table tr { break-inside: avoid; }

    /* A house's Final Marks never lands alone at the top of a page. */
    .hwp-page .programme-dt-table tbody tr.hwp-final { break-before: avoid; }

    /* Paper reads at a tighter scale than the screen grid; the theme sets
       td size with !important (styles.css), so this has to as well. */
    .hwp-page .programme-dt-table th,
    .hwp-page .programme-dt-table td {
        padding: var(--ds-space-1) var(--ds-space-2) !important;
        font-size: 0.75rem !important;
        border-bottom: 1px solid var(--ds-line) !important;
        border-radius: 0 !important;
    }

    .hwp-page .programme-dt-table thead th { background: var(--ds-surface-2) !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid hwp-page py-3">
    @php
        // The downloads carry whatever the filter is showing, so a shared file
        // matches the screen it came from.
        $exportParams = array_filter(['course' => $courseFilter ?? null]);

        // The printed sheet carries the same meta line as the PDF download
        // (admin/exports/table_pdf): course when filtered, record count, date.
        $printCourse = $courseFilter ? (($courses ?? collect())[$courseFilter] ?? null) : null;
        $printRecords = collect($houses)->sum(
            fn ($house) => collect($house['members'])->sum(fn ($member) => count($member['rows']))
        );
    @endphp

    <x-breadcrum title="House wise Performance" />

    <div class="hwp-print-head d-none d-print-block">
        <img src="{{ asset('admin_assets/images/logos/logo-web.png') }}" alt="Lal Bahadur Shastri National Academy of Administration" class="hwp-print-logo">
        <div class="hwp-print-title">House wise Performance</div>
        <div class="hwp-print-meta">
            @if($printCourse)Course: {{ $printCourse }} &nbsp;|&nbsp; @endif
            Total Records: {{ $printRecords }} &nbsp;|&nbsp; Generated: {{ $generatedOn->format('d-m-Y H:i') }}
        </div>
    </div>

    {{-- Secondary actions (Download / Print) — above the card, right-aligned
         because the page has no status pills to sit opposite (§1). More than
         one download format, so Download is a dropdown. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 hwp-secondary-actions hwp-noprint">
        <div class="dropdown">
            <button type="button" id="hwpDownloadToggle"
                class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="hwpDownloadToggle">
                <li>
                    <a class="dropdown-item"
                        href="{{ route('admin.dashboard.house-wise-performance', $exportParams + ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item"
                        href="{{ route('admin.dashboard.house-wise-performance', $exportParams + ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary"
            onclick="window.print()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: Filters label → filter → red Reset Filters (§2). There is
                 no Columns modal or DataTables search on a grouped report, so the
                 right-hand side of the toolbar stays empty. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar hwp-noprint">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    @if(($courses ?? collect())->isNotEmpty())
                        <div class="programme-dt-filter-select">
                            <select id="hwpCourse" class="form-select" aria-label="Filter by course"
                                onchange="window.location = '{{ route('admin.dashboard.house-wise-performance') }}' + (this.value ? ('?course=' + encodeURIComponent(this.value)) : '');">
                                <option value="">Course Name</option>
                                @foreach($courses as $pk => $name)
                                    <option value="{{ $pk }}" {{ (string) ($courseFilter ?? '') === (string) $pk ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="button" class="btn programme-dt-btn-reset" id="hwpReset"
                        onclick="window.location = '{{ route('admin.dashboard.house-wise-performance') }}';">
                        Reset Filters
                    </button>
                </div>
            </div>

            @if($houses->isEmpty())
                <div class="ds-empty-state">
                    <i class="bi bi-house fs-1 d-block mb-2 opacity-50" aria-hidden="true"></i>
                    <p class="mb-0">No officer trainee on a running course is carrying a closed deduction.</p>
                </div>
            @else
                @foreach($houses as $house)
                    <div class="programme-dt-panel hwp-house">
                        <div class="hwp-house-head">
                            <h2 class="hwp-house-name">{{ $house['house'] }}</h2>
                            <span class="hwp-house-date">{{ $generatedOn->format('d M Y') }}</span>
                            <div class="hwp-house-meta">
                                <span class="hwp-chip">{{ $house['student_count'] }} OT{{ $house['student_count'] == 1 ? '' : 's' }}</span>
                                <span class="hwp-chip">Total Marks Deducted: {{ $house['total'] + 0 }}</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="hwp-col-no">S. No.</th>
                                        <th scope="col">Student Name</th>
                                        <th scope="col" class="hwp-col-code">OT Code</th>
                                        <th scope="col">Discipline Category</th>
                                        <th scope="col" class="hwp-col-marks">Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Only OTs carrying a final mark reach here; the
                                         controller filters the rest out. --}}
                                    @foreach($house['members'] as $index => $member)
                                            @foreach($member['rows'] as $rowIndex => $row)
                                                <tr class="{{ $rowIndex > 0 ? 'hwp-row-continued' : '' }}">
                                                    <td class="hwp-col-no">{{ $rowIndex === 0 ? $index + 1 : '' }}</td>
                                                    <td class="hwp-col-name">{{ $rowIndex === 0 ? $member['name'] : '' }}</td>
                                                    <td class="hwp-col-code">{{ $rowIndex === 0 ? $member['ot_code'] : '' }}</td>
                                                    <td class="hwp-col-cat">
                                                        {{ $row['category'] }}
                                                        @if(! empty($row['severity']))
                                                            <span class="hwp-severity {{ $row['severity'] === 'Major' ? 'hwp-severity--major' : '' }}">{{ $row['severity'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="hwp-col-marks">{{ $row['marks'] + 0 }}</td>
                                                </tr>
                                            @endforeach
                                            {{-- Every trainee's own total, highlighted. --}}
                                            <tr class="hwp-student-total">
                                                <td class="hwp-col-no"></td>
                                                <td colspan="3" class="text-end">{{ $member['name'] }} — Total Marks</td>
                                                <td class="hwp-col-marks">{{ $member['total'] + 0 }}</td>
                                            </tr>
                                    @endforeach

                                    <tr class="hwp-final">
                                        <td colspan="4" class="text-end">Final Marks — {{ $house['house'] }}</td>
                                        <td class="hwp-col-marks">{{ $house['total'] + 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Footer (§4). Nothing paginates a grouped report, so this is the
                     count alone, in the same slot a grid puts it. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 hwp-noprint">
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_info">
                            Showing {{ $houses->count() }} {{ \Illuminate\Support\Str::plural('house', $houses->count()) }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
