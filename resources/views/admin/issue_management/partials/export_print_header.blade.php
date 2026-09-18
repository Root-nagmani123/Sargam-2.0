{{-- Branded header for the Centcom print exports.

     Props:
       $title       report name, e.g. "Manage Categories"
       $exportDate  already-formatted generation timestamp
       $filterLine  optional "Search: foo" style line (omit when nothing is filtered)
       $total       optional record count

     Logos are the same pair every branded LBSNAA document uses
     (courseAttendanceNoticeMap/conversation.blade.php): national emblem, then
     the academy logo. --}}
@php
    $filterLine = $filterLine ?? null;
    $total      = $total ?? null;
@endphp

<table class="ic-print-hdr">
    <tr>
        <td class="ic-print-logo">
            <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
        </td>
        <td class="ic-print-centre">
            <div class="ic-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
            <div class="ic-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
        </td>
        {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
        <td class="ic-print-logo"></td>
    </tr>
</table>

<div class="ic-print-rule"></div>

<div class="ic-print-title">{{ $title }}</div>
<div class="ic-print-meta">Generated: {{ $exportDate }}</div>

@if (filled($filterLine))
    <div class="ic-print-filters">{!! $filterLine !!}</div>
@endif

@if (! is_null($total))
    <div class="ic-print-total">Total Records: {{ number_format($total) }}</div>
@endif
