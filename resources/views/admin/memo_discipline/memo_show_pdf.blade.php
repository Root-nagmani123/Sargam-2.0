@php
    $logo = $logo ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Discipline Memo — {{ $memo->student->display_name ?? '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 10px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 70px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 46px; max-width: 66px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        .inst-en { font-size: 13px; font-weight: bold; color: #003366; line-height: 1.3; }
        .inst-sub { font-size: 9px; color: #555; }

        .pdf-hdr-border { border-bottom: 2px solid #003366; margin-bottom: 8px; padding-bottom: 4px; }

        .report-title { text-align: center; font-size: 12px; font-weight: bold; color: #004a93; margin: 4px 0; }

        table.info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info-table td { padding: 3px 6px; font-size: 9.5px; vertical-align: top; }
        table.info-table .lbl { color: #6b7280; display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .03em; }
        table.info-table .val { font-weight: bold; color: #1f2937; }

        .section-heading { font-size: 11px; font-weight: bold; color: #003366; margin: 10px 0 4px; border-bottom: 1px solid #cccccc; padding-bottom: 2px; }

        .memo-box { border: 1px solid #cccccc; border-radius: 4px; padding: 10px 12px; margin-bottom: 12px; background: #f9fafb; }
        .memo-box .memo-hdr { text-align: center; margin-bottom: 6px; }
        .memo-box .memo-hdr .course { font-size: 11px; font-weight: bold; }
        .memo-box .memo-hdr .inst { font-size: 9px; color: #555; }
        .memo-box .memo-type { font-weight: bold; margin: 6px 0 2px; }
        .memo-box .memo-date { margin-bottom: 6px; }
        .memo-box .memo-content { font-size: 9.5px; line-height: 1.5; }
        .memo-box .memo-sign { text-align: right; margin-top: 10px; }
        .memo-box .memo-sign img { max-height: 45px; }
        .memo-box .memo-sign .name { font-weight: bold; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #cccccc;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
            font-size: 8.5px;
        }
        table.data-table thead th {
            background: #003366;
            color: #fff;
            font-weight: bold;
            text-align: center;
            border-color: #002244;
        }
        table.data-table tbody tr:nth-child(even) { background: #f4f7fb; }
        .col-name { width: 20%; }
        .col-msg { width: 40%; }
        .col-date { width: 20%; }
        .col-doc { width: 20%; text-align: center; }

        .conclusion-box { border: 1px solid #cccccc; border-radius: 4px; padding: 8px 10px; margin-top: 10px; background: #f0f4fa; }
        .conclusion-box .lbl { color: #6b7280; font-size: 8px; text-transform: uppercase; }
        .conclusion-box .val { font-weight: bold; margin-bottom: 6px; display: block; }

        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #666; }
    </style>
</head>
<body>

    <div class="pdf-hdr-border">
        <table class="pdf-hdr">
            <tr>
                <td class="logo">@if($logo)<img src="{{ $logo }}" alt="">@endif</td>
                <td class="center">
                    <div class="inst-en">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                    <div class="inst-sub">Mussoorie</div>
                </td>
                <td class="logo"></td>
            </tr>
        </table>
        <div class="report-title">DISCIPLINE MEMO — {{ $memo->course->course_name ?? '' }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td><span class="lbl">Date</span><span class="val">{{ $memo->date ? \Carbon\Carbon::parse($memo->date)->format('d/m/Y') : '—' }}</span></td>
            <td><span class="lbl">Discipline</span><span class="val">{{ $memo->discipline->discipline_name ?? '—' }}</span></td>
            <td><span class="lbl">Participant</span><span class="val">{{ $memo->student->display_name ?? '—' }}{{ $memo->student->generated_OT_code ? ' (' . $memo->student->generated_OT_code . ')' : '' }}</span></td>
        </tr>
        @if(!empty($memo->remarks))
        <tr>
            <td colspan="3"><span class="lbl">Remarks</span><span class="val">{{ $memo->remarks }}</span></td>
        </tr>
        @endif
    </table>

    <div class="section-heading">Memo Content</div>
    @if($template)
    <div class="memo-box">
        <div class="memo-hdr">
            <div class="course">{{ $memo->course->course_name ?? '' }}</div>
            <div class="inst">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
        </div>
        <div class="memo-type">DISCIPLINE MEMO</div>
        <div class="memo-date"><strong>Date:</strong> {{ $memo->date ? \Carbon\Carbon::parse($memo->date)->format('d/m/Y') : '—' }}</div>
        <div class="memo-content">{!! $template->content !!}</div>
        @if(!empty($signature))
        <div class="memo-sign"><img src="{{ $signature }}" alt="Signature"></div>
        @endif
        <div class="memo-sign">
            <span class="name">{{ $template->director_name ?? '' }}</span><br>
            <span>{{ $template->director_designation ?? '' }}</span>
        </div>
    </div>
    @else
    <p>No active Discipline Memo template found for this course.</p>
    @endif

    <div class="section-heading">Conversation</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-name">Name</th>
                <th class="col-msg">Conversation</th>
                <th class="col-date">Date &amp; Time</th>
                <th class="col-doc">Document</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($memo->messages as $row)
            <tr>
                <td class="col-name">{{ $row->display_name }}{{ $row->role_name ? ' (' . $row->role_name . ')' : '' }}</td>
                <td class="col-msg">{{ $row->student_decip_incharge_msg }}</td>
                <td class="col-date">{{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}</td>
                <td class="col-doc">{{ $row->doc_upload ? 'Attached' : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;">No conversation found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($memo->status == 3)
    <div class="conclusion-box">
        <span class="lbl">Conclusion Type</span>
        <span class="val">{{ $conclusion_type_name ?? '—' }}</span>
        <span class="lbl">Final Mark Deduction</span>
        <span class="val">{{ $memo->final_mark_deduction ?? '—' }}</span>
        <span class="lbl">Conclusion Remark</span>
        <span class="val">{{ $memo->conclusion_remark ?? '—' }}</span>
    </div>
    @endif

    <div class="footer">LBSNAA — Discipline Memo Report — Generated: {{ now()->format('d-m-Y H:i') }}</div>
</body>
</html>
