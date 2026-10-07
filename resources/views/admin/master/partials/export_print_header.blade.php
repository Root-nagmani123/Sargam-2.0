{{-- Branded header for the Master-module print exports.

     Props:
       $title       report name, e.g. "Appellation Master"
       $exportDate  already-formatted generation timestamp
       $filterLine  optional "Search: foo  |  Status: Active" line (omit when nothing is filtered)
       $total       optional record count

     Logos are the same pair every branded LBSNAA document uses: national
     emblem, then the academy logo. --}}
@php
    $filterLine = $filterLine ?? null;
    $total      = $total ?? null;
@endphp

<table class="mst-print-hdr">
    <tr>
        <td class="mst-print-logo">
            <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
        </td>
        <td class="mst-print-centre">
            <div class="mst-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
            <div class="mst-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
        </td>
        {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
        <td class="mst-print-logo"></td>
    </tr>
</table>

<div class="mst-print-rule"></div>

<div class="mst-print-title">{{ $title }}</div>
<div class="mst-print-meta">Generated: {{ $exportDate }}</div>

@if (filled($filterLine))
    <div class="mst-print-filters">{{ $filterLine }}</div>
@endif

@if (! is_null($total))
    <div class="mst-print-total">Total Records: {{ number_format($total) }}</div>
@endif
