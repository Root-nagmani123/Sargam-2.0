{{-- Download / Print bar for mess reports. Each report keeps its own export routes and print handler.
     Params: excelUrl (nullable), pdfUrl (nullable), printOnclick (JS call string) or printId (button id a script binds). --}}
@php
    $excelUrl = $excelUrl ?? null;
    $pdfUrl = $pdfUrl ?? null;
@endphp
<div class="mess-export-bar no-print">
    @if($excelUrl && $pdfUrl)
        <div class="dropdown">
            <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-symbols-rounded" aria-hidden="true">download</i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ $excelUrl }}">Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ $pdfUrl }}">PDF</a></li>
            </ul>
        </div>
    @elseif($excelUrl || $pdfUrl)
        <a href="{{ $excelUrl ?? $pdfUrl }}" class="btn">
            <i class="material-symbols-rounded" aria-hidden="true">download</i>
            <span>Download</span>
        </a>
    @endif
    <button type="button" class="btn" @isset($printId) id="{{ $printId }}" @endisset @isset($printOnclick) onclick="{{ $printOnclick }}" @endisset>
        <i class="material-symbols-rounded" aria-hidden="true">print</i>
        <span>Print</span>
    </button>
</div>
