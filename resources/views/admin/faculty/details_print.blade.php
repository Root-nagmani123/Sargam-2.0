{{-- Faculty details print sheet.
     Follows the standard print layout (see admin/dashboard/export/student_list_print):
     standalone page + LBSNAA official header + report title block + floating
     toolbar + auto-print, so it matches every other Print in the app. --}}
@php
    $printedOn = $generatedAt ?? now()->format('d M Y, h:i A');

    // Mappings can point at expertise rows that no longer exist — drop those.
    $expertiseNames = $faculty->facultyExpertiseMap
        ->map(fn ($area) => $area->facultyExpertise?->expertise_name)
        ->filter()
        ->values();

    $sectorName = \Illuminate\Support\Facades\DB::table('faculty_sector_master')
        ->where('pk', $faculty->faculty_sector)
        ->value('name');

    $serviceName = \Illuminate\Support\Facades\DB::table('service_master')
        ->where('pk', $faculty->service_master_pk)
        ->value('service_name');

    $address = collect([
        $faculty->countryMaster->country_name ?? null,
        $faculty->stateMaster->state_name ?? null,
        $faculty->districtMaster->district_name ?? null,
        $faculty->cityMaster->city_name ?? null,
    ])->filter()->implode(', ');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faculty Details - LBSNAA MUSSOORIE</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        html, body { margin: 0; padding: 0; color: #1f2937; background: #e9edf2; }

        /* On screen the report sits on a centred "paper" sheet; print resets it. */
        .print-sheet {
            width: 190mm;
            max-width: 100%;
            margin: 18px auto;
            padding: 16mm 12mm;
            background: #fff;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.18);
        }

        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 42px; height: 42px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 42px; height: 42px; }
        .brand-1 { font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }
        .brand-2 { font-size: 15px; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }
        .brand-3 { font-size: 10px; color: #555; margin-top: 2px; }

        .report-title-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 6px;
        }
        .report-meta { font-size: 11px; color: #555; }

        /* Identity strip */
        .identity { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .identity td { border: 0; padding: 0; vertical-align: middle; }
        .identity .photo-cell { width: 110px; }
        .identity .photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #d0d5dd;
            border-radius: 4px;
        }
        .identity .who { padding-left: 12px; }
        .identity .who-name { font-size: 14px; font-weight: 700; color: #0f172a; }
        .identity .who-meta { font-size: 11px; color: #555; margin-top: 3px; }

        /* Section band — matches the blue band used across the standard reports. */
        .section-band {
            background: #004a93;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 5px 8px;
            border: 1px solid #003a75;
            margin-top: 12px;
        }

        table.data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            border: 1px solid #003a75;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
        }
        table.data-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 4px;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) td { background: #fafafa; }

        /* Label / value grid for the single-record sections. */
        table.kv { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.kv td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            vertical-align: top;
        }
        table.kv td.k {
            width: 18%;
            background: #f6f8fb;
            font-weight: bold;
            color: #344054;
        }

        .empty { text-align: center; padding: 12px; color: #6b7280; font-size: 10px; border: 1px solid #e5e7eb; border-top: 0; }

        /* Floating toolbar — never printed. */
        .print-toolbar {
            position: fixed;
            top: 14px;
            right: 18px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        .print-toolbar button {
            font: 600 13px/1 Arial, sans-serif;
            padding: 9px 16px;
            border-radius: 8px;
            border: 1px solid #004a93;
            cursor: pointer;
        }
        .print-toolbar .btn-print { background: #004a93; color: #fff; }
        .print-toolbar .btn-close { background: #fff; color: #004a93; }

        @media print {
            html, body { background: #fff; }
            .print-sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-toolbar { display: none !important; }
            table.data-table thead { display: table-header-group; }
            table.data-table tr { page-break-inside: avoid; }
            .section-band { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="print-sheet">
        @include('admin.partials.pdf_lbsnaa_official_header')

        <div class="report-title-block">
            <h1 class="report-title">Faculty Details</h1>
            <div class="report-meta">
                Generated: {{ $printedOn }}
                &nbsp;|&nbsp; Faculty Code: {{ $faculty->faculty_code ?: '-' }}
            </div>
        </div>

        {{-- Identity --}}
        <table class="identity">
            <tr>
                <td class="photo-cell">
                    <img class="photo"
                         src="{{ $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : asset('images/dummypic.jpeg') }}"
                         alt="Faculty Photo">
                </td>
                <td class="who">
                    <div class="who-name">{{ $faculty->full_name }}</div>
                    <div class="who-meta">
                        {{ $faculty->facultyTypeMaster->faculty_type_name ?? '-' }}
                        @if($faculty->faculty_type == '1' && $faculty->faculty_pa)
                            &nbsp;|&nbsp; Faculty (PA): {{ $faculty->faculty_pa }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Personal --}}
        <div class="section-band">Personal Information</div>
        <table class="kv">
            <tr>
                <td class="k">Gender</td>
                <td>{{ $faculty->gender ?: '-' }}</td>
                <td class="k">Mobile Number</td>
                <td>{{ $faculty->mobile_no ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Email ID</td>
                <td>{{ $faculty->email_id ?: '-' }}</td>
                <td class="k">Current Designation</td>
                <td>{{ $faculty->current_designation ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Current Department</td>
                <td>{{ $faculty->current_department ?: '-' }}</td>
                <td class="k">Address</td>
                <td>{{ $address ?: '-' }}</td>
            </tr>
        </table>

        {{-- Qualifications --}}
        <div class="section-band">Qualification Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:8%;">S. No.</th>
                    <th>Degree</th>
                    <th>University / Institution</th>
                    <th style="width:14%;">Passing Year</th>
                    <th style="width:16%;">Percentage / CGPA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faculty->facultyQualificationMap as $i => $q)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $q->Degree_name ?: '-' }}</td>
                    <td>{{ $q->University_Institution_Name ?: '-' }}</td>
                    <td>{{ $q->Year_of_passing ?: '-' }}</td>
                    <td>{{ $q->Percentage_CGPA ?: '-' }}</td>
                </tr>
                @empty
                <tr><td class="empty" colspan="5">No qualification details recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Experience --}}
        <div class="section-band">Experience Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:8%;">S. No.</th>
                    <th style="width:16%;">Years of Experience</th>
                    <th>Area of Specialisation</th>
                    <th>Institution</th>
                    <th>Position Held</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faculty->facultyExperienceMap as $i => $exp)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $exp->Years_Of_Experience ?: '-' }}</td>
                    <td>{{ $exp->Specialization ?: '-' }}</td>
                    <td>{{ $exp->pre_Institutions ?: '-' }}</td>
                    <td>{{ $exp->Position_hold ?: '-' }}</td>
                </tr>
                @empty
                <tr><td class="empty" colspan="5">No experience details recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Bank --}}
        <div class="section-band">Bank Details</div>
        <table class="kv">
            <tr>
                <td class="k">Bank Name</td>
                <td>{{ $faculty->bank_name ?: '-' }}</td>
                <td class="k">Account Number</td>
                <td>{{ $faculty->Account_No ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">IFSC Code</td>
                <td>{{ $faculty->IFSC_Code ?: '-' }}</td>
                <td class="k"></td>
                <td></td>
            </tr>
        </table>

        {{-- Expertise --}}
        <div class="section-band">Area of Expertise</div>
        <table class="kv">
            <tr>
                <td class="k">Expertise</td>
                <td>{{ $expertiseNames->isNotEmpty() ? $expertiseNames->implode(', ') : '-' }}</td>
            </tr>
        </table>

        {{-- Other --}}
        <div class="section-band">Other Information</div>
        <table class="kv">
            <tr>
                <td class="k">Joining Date</td>
                <td>{{ $faculty->joining_date ? format_date($faculty->joining_date) : '-' }}</td>
                <td class="k">Current Sector</td>
                <td>{{ $sectorName ?: '-' }}</td>
            </tr>
            <tr>
                <td class="k">Service</td>
                <td>{{ $serviceName ?: '-' }}</td>
                <td class="k"></td>
                <td></td>
            </tr>
        </table>
    </div>

    <script>
        // Open the print dialog automatically once the report has rendered.
        window.addEventListener('load', function () {
            window.focus();
            window.setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
