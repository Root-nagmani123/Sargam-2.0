{{-- Branded header for the Sidebar Menu Builder print exports.

     Props:
       $title       report name, e.g. "Sidebar Menu Groups"
       $exportDate  already-formatted generation timestamp
       $filterLine  optional "Search: foo" style line (omit when nothing is filtered)
       $total       optional record count

     Logos are the same pair every branded LBSNAA document uses: national
     emblem, then the academy logo. --}}
@php
    $filterLine = $filterLine ?? null;
    $total = $total ?? null;
@endphp

<table class="sbm-print-hdr">
    <tr>
        <td class="sbm-print-logo">
            <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
        </td>
        <td class="sbm-print-centre">
            <div class="sbm-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
            <div class="sbm-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
        </td>
        {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
        <td class="sbm-print-logo"></td>
    </tr>
</table>

<div class="sbm-print-rule"></div>

<div class="sbm-print-title">{{ $title }}</div>
<div class="sbm-print-meta">Generated: {{ $exportDate }}</div>

@if (filled($filterLine))
    <div class="sbm-print-filters">{!! $filterLine !!}</div>
@endif

@if (! is_null($total))
    <div class="sbm-print-total">Total Records: {{ number_format($total) }}</div>
@endif
