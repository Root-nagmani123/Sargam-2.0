@extends('admin.layouts.master')

@section('title', 'House wise Performance')

@section('content')
<style>
/* =====================================================================
   House wise Performance — one block per house, printable as-is.
   ===================================================================== */
.hwp-page { --hwp-navy: #003366; }

.hwp-house {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    margin-bottom: 1.5rem;
}

.hwp-house-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
    padding: .85rem 1.1rem;
    background: var(--hwp-navy);
    color: #fff;
}

/* Explicit white, not inherited: the admin theme sets a dark colour on h1-h6,
   which wins over the band's colour and left the house name near-invisible
   against the navy. */
.hwp-house-name {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: .01em;
    color: #ffffff !important;
}

.hwp-house-date {
    font-size: .8125rem;
    color: #ffffff;
    opacity: .9;
}

.hwp-house-meta { margin-left: auto; display: flex; gap: .5rem; flex-wrap: wrap; }

.hwp-chip {
    display: inline-block;
    padding: .2rem .7rem;
    border-radius: 50rem;
    background: rgba(255, 255, 255, .16);
    font-size: .8125rem;
    font-weight: 600;
    white-space: nowrap;
}

.hwp-table { width: 100%; margin: 0; }
.hwp-table thead th {
    background: #f1f5f9;
    color: #475569;
    text-transform: uppercase;
    font-size: .72rem;
    letter-spacing: .03em;
    font-weight: 700;
    padding: .6rem .9rem;
    border-bottom: 1px solid #e2e8f0;
}
.hwp-table tbody td {
    padding: .6rem .9rem;
    border-bottom: 1px solid #eef2f6;
    vertical-align: middle;
    font-size: .875rem;
}
.hwp-table tbody tr:last-child td { border-bottom: 0; }

.hwp-col-no { width: 4.5rem; text-align: center; color: #64748b; }
.hwp-col-code { width: 9rem; }
.hwp-col-marks { width: 7rem; text-align: center; font-variant-numeric: tabular-nums; }

/* An OT with several deductions gets one row per deduction; the name cell is
   written once and the rest of their rows are visually tied to it. */
.hwp-row-continued td.hwp-col-no,
.hwp-row-continued td.hwp-col-name,
.hwp-row-continued td.hwp-col-code { border-top: 0; color: transparent; }

.hwp-severity {
    display: inline-block;
    margin-left: .4rem;
    padding: .05rem .45rem;
    border-radius: .25rem;
    background: #fef3c7;
    color: #92400e;
    font-size: .7rem;
    font-weight: 700;
}
.hwp-severity--major { background: #fee2e2; color: #991b1b; }

/* A trainee's own subtotal — tinted so it reads as a break between OTs, but
   kept lighter than the house's Final Marks row so the two are not confused. */
.hwp-student-total td {
    background: #eef3fa;
    font-weight: 700;
    color: var(--hwp-navy);
    border-top: 1px solid #c7d6ea !important;
}

.hwp-final {
    background: #f0f4fa;
    font-weight: 700;
    color: var(--hwp-navy);
}
.hwp-final td { border-top: 2px solid var(--hwp-navy) !important; font-size: .9rem; }

.hwp-empty {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 2.5rem 1rem;
    text-align: center;
    color: #64748b;
}

@media print {
    .hwp-noprint, .app-sidebar, .app-header, nav, .breadcrumb { display: none !important; }
    .hwp-house { page-break-inside: avoid; border-color: #999; }
    .hwp-house-head { background: #003366 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .hwp-house-name, .hwp-house-date, .hwp-chip { color: #fff !important; }
    .hwp-final { background: #f0f4fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .hwp-student-total td { background: #eef3fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<div class="container-fluid hwp-page">
    @php
        // The downloads carry whatever the filter is showing, so a shared file
        // matches the screen it came from.
        $exportParams = array_filter(['course' => $courseFilter ?? null]);
    @endphp

    <x-breadcrum title="House wise Performance">
        <div class="d-inline-flex align-items-center gap-2 hwp-noprint flex-wrap">
            @if(($courses ?? collect())->isNotEmpty())
                <select class="form-select" id="hwpCourse" aria-label="Filter by course" style="max-width: 15rem;"
                    onchange="window.location = '{{ route('admin.dashboard.house-wise-performance') }}' + (this.value ? ('?course=' + encodeURIComponent(this.value)) : '');">
                    <option value="">All Courses</option>
                    @foreach($courses as $pk => $name)
                        <option value="{{ $pk }}" {{ (string) ($courseFilter ?? '') === (string) $pk ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            @endif
            <a href="{{ route('admin.dashboard.house-wise-performance', $exportParams + ['format' => 'excel']) }}"
                class="btn btn-outline-success d-inline-flex align-items-center gap-2 rounded-2">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                <span>Excel</span>
            </a>
            <a href="{{ route('admin.dashboard.house-wise-performance', $exportParams + ['format' => 'pdf']) }}"
                class="btn btn-outline-danger d-inline-flex align-items-center gap-2 rounded-2">
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                <span>PDF</span>
            </a>
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 rounded-2"
                onclick="window.print()">
                <i class="material-icons material-symbols-rounded" aria-hidden="true">print</i>
                <span>Print</span>
            </button>
        </div>
    </x-breadcrum>

    @if(! empty($courseFilter) && ($courses ?? collect())->has($courseFilter))
        <p class="text-secondary small mb-3">
            Showing <strong>{{ $courses[$courseFilter] }}</strong> only.
            <a href="{{ route('admin.dashboard.house-wise-performance') }}">Clear filter</a>
        </p>
    @endif

    @if($houses->isEmpty())
        <div class="hwp-empty">
            <i class="bi bi-house fs-1 d-block mb-2 opacity-50" aria-hidden="true"></i>
            <p class="mb-0">No officer trainee on a running course is carrying a closed deduction.</p>
        </div>
    @else
        @foreach($houses as $house)
            <div class="hwp-house">
                <div class="hwp-house-head">
                    <h2 class="hwp-house-name">{{ $house['house'] }}</h2>
                    <span class="hwp-house-date">{{ $generatedOn->format('d M Y') }}</span>
                    <div class="hwp-house-meta">
                        <span class="hwp-chip">{{ $house['student_count'] }} OT{{ $house['student_count'] == 1 ? '' : 's' }}</span>
                        <span class="hwp-chip">Total Marks Deducted: {{ $house['total'] + 0 }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table hwp-table align-middle">
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
                                            <td class="hwp-col-name fw-semibold">{{ $rowIndex === 0 ? $member['name'] : '' }}</td>
                                            <td class="hwp-col-code">{{ $rowIndex === 0 ? $member['ot_code'] : '' }}</td>
                                            <td>
                                                {{ $row['category'] }}
                                                @if(! empty($row['severity']))
                                                    <span class="hwp-severity {{ $row['severity'] === 'Major' ? 'hwp-severity--major' : '' }}">{{ $row['severity'] }}</span>
                                                @endif
                                            </td>
                                            <td class="hwp-col-marks">{{ $row['marks'] + 0 }}</td>
                                        </tr>
                                    @endforeach
                                    {{-- A trainee's own total, where more than one
                                         deduction had to be added up to reach it. --}}
                                    @if($member['rows']->count() > 1)
                                        <tr class="hwp-student-total">
                                            <td class="hwp-col-no"></td>
                                            <td colspan="3" class="text-end">{{ $member['name'] }} — Total Marks</td>
                                            <td class="hwp-col-marks">{{ $member['total'] + 0 }}</td>
                                        </tr>
                                    @endif
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
    @endif
</div>
@endsection
