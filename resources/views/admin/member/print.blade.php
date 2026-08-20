{{-- Branded print sheet for ONE member.

     The listing's Print (admin/exports/branded_print) prints the whole filtered
     table; this prints a single profile. Same letterhead, same rule, same footer,
     so the two read as one family — but a table of rows and a sheet of labelled
     facts need different bodies, which is why this is its own view rather than a
     column list handed to the shared one.

     Props:
       $member        the EmployeeMaster row
       $sections      MemberController::memberProfileSections() — the same array
                      the View screen renders, so print cannot drift from screen
       $assignedRoles Collection of ['role_name' => …]
       $exportDate    already-formatted generation timestamp --}}
@php
    $appellation = optional($member->appellationMaster)->appettation_name;
    $fullName = trim(collect([$appellation, $member->first_name, $member->middle_name, $member->last_name])
        ->map(fn ($part) => trim((string) $part))
        ->filter()
        ->implode(' '));

    $isActive = (int) $member->status === 1;
    $designation = optional($member->designation)->designation_name;
    $department = optional($member->department)->department_name;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $fullName !== '' ? $fullName : 'Member' }} — LBSNAA</title>
    <style>
        /* margin:0, NOT a real margin — see admin/exports/branded_print.blade.php.
           A non-zero @page margin is where Chrome draws its own date/time, page
           title, URL and "1/1"; the sheet's margin lives in the body padding. */
        @page { size: A4 portrait; margin: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 16px;
        }

        /* ── Branded header: emblem + LBSNAA logo left, institution centre ── */
        table.bx-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.bx-hdr td { vertical-align: middle; padding: 0; }
        table.bx-hdr .bx-logo { width: 130px; white-space: nowrap; }
        table.bx-hdr .bx-logo img { height: 52px; width: auto; object-fit: contain; }
        table.bx-hdr .bx-logo img + img { margin-left: 6px; }
        table.bx-hdr .bx-centre { text-align: center; padding: 0 8px; }

        .bx-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .bx-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .bx-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .bx-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .bx-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        /* ── Identity band: name, id/designation/department, status ── */
        .mp-ident {
            background: #eef2f8;
            padding: 8px 10px;
            margin-bottom: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .mp-ident__name { font-size: 13px; font-weight: bold; color: #003366; }
        .mp-ident__meta { font-size: 10px; color: #4b5563; margin-top: 3px; }
        .mp-ident__meta span + span::before { content: ' · '; }
        .mp-status { font-size: 10px; font-weight: bold; }
        .mp-status--on { color: #146c43; }
        .mp-status--off { color: #b02a37; }

        /* ── Sections: a heading, then two label/value columns ── */
        .mp-section-title {
            font-size: 11px;
            font-weight: bold;
            color: #ffffff;
            background: #003366;
            padding: 5px 8px;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.mp-facts { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        table.mp-facts td { border: 1px solid #dee2e6; padding: 5px 8px; vertical-align: top; }
        table.mp-facts td.mp-label { width: 22%; color: #4b5563; font-weight: bold; background: #f8f9fa;
            -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.mp-facts td.mp-value { width: 28%; word-wrap: break-word; overflow-wrap: break-word; }
        table.mp-facts td.mp-value--wide { width: 78%; }
        .mp-empty { color: #9ca3af; }

        /* A section must not be split across a page break mid-heading. */
        .mp-block { page-break-inside: avoid; }
        .mp-roles { font-size: 10px; }

        .bx-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

        @media print {
            /* Carries the page margin that @page gave up. */
            body { padding: 12mm 10mm; }
        }
    </style>
</head>
<body onload="window.print();">

    <table class="bx-hdr">
        <tr>
            <td class="bx-logo">
                <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
            </td>
            <td class="bx-centre">
                <div class="bx-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="bx-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
            <td class="bx-logo"></td>
        </tr>
    </table>

    <div class="bx-rule"></div>

    <div class="bx-title">Employee Details</div>
    <div class="bx-meta">Generated: {{ $exportDate }}</div>

    <div class="mp-ident">
        <div class="mp-ident__name">{{ $fullName !== '' ? $fullName : 'Member' }}</div>
        <div class="mp-ident__meta">
            <span>{{ filled($member->emp_id) ? 'ID ' . $member->emp_id : 'No employee ID' }}</span>
            @if (filled($designation))<span>{{ $designation }}</span>@endif
            @if (filled($department))<span>{{ $department }}</span>@endif
            <span class="mp-status {{ $isActive ? 'mp-status--on' : 'mp-status--off' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>

    {{-- Two label/value pairs per row, except after the '__wide' marker, where a
         single value spans the rest of the row — long addresses are unreadable in
         a quarter-width cell. --}}
    @foreach ($sections as $sectionTitle => $fields)
        @php
            $wide = false;
            $narrow = [];
            $wideFields = [];
            foreach ($fields as $label => $value) {
                if ($label === '__wide') { $wide = true; continue; }
                if ($wide) { $wideFields[$label] = $value; } else { $narrow[$label] = $value; }
            }
            $narrowPairs = array_chunk($narrow, 2, true);
        @endphp
        <div class="mp-block">
            <h2 class="mp-section-title">{{ $sectionTitle }}</h2>
            <table class="mp-facts">
                @foreach ($narrowPairs as $pair)
                    <tr>
                        @foreach ($pair as $label => $value)
                            <td class="mp-label">{{ $label }}</td>
                            <td class="mp-value">{!! filled($value) ? e($value) : '<span class="mp-empty">&mdash;</span>' !!}</td>
                        @endforeach
                        {{-- An odd final pair leaves one empty cell rather than a
                             ragged row the borders would draw short. --}}
                        @if (count($pair) === 1)
                            <td class="mp-label"></td>
                            <td class="mp-value"></td>
                        @endif
                    </tr>
                @endforeach
                @foreach ($wideFields as $label => $value)
                    <tr>
                        <td class="mp-label">{{ $label }}</td>
                        <td class="mp-value mp-value--wide" colspan="3">{!! filled($value) ? e($value) : '<span class="mp-empty">&mdash;</span>' !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <div class="mp-block">
        <h2 class="mp-section-title">Assigned Roles</h2>
        <table class="mp-facts">
            <tr>
                {{-- assignedRoles() returns a Collection, so this must be isEmpty(),
                     not empty() — an object is never "empty". --}}
                <td class="mp-value mp-roles" colspan="4">
                    @if ($assignedRoles->isEmpty())
                        <span class="mp-empty">No roles assigned</span>
                    @else
                        {{ collect($assignedRoles)->pluck('role_name')->filter()->implode(',  ') }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="bx-foot">Sargam 2.0 · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
