{{--
    Estate grid exports — Download (CSV · Excel · PDF) + Print, one control for
    every Estate listing (docs/new-design-index-page.md §1: more than one format
    → Download is a dropdown). Sits above the card, right-aligned.

    The page keeps its own params builder (filters, search, visible columns) and
    binds the items with:

        $(document).on('click', '[data-export-for="{{ $prefix }}"]', function () {
            var params = myExportParams();
            params.format = $(this).data('format');     // csv | excel | pdf
            window.location.href = exportUrl + '?' + $.param(params);
        });

    Server side every format runs the same payload (Concerns\ExportsEstateGrid),
    so CSV, Excel, PDF and Print always carry the same rows and columns.

    @param string $prefix    id prefix, e.g. 'rfe' → #rfeDownloadBtn, #rfePrintBtn
    @param bool   $print     render the Print button (default true)
    @param array  $formats   formats offered (default csv, excel, pdf); a single format
                             renders a plain Download button instead of the menu
    @param string $printHref optional: Print as a plain link instead of a JS button
--}}
@php
    $print = $print ?? true;
    $formats = $formats ?? ['csv', 'excel', 'pdf'];
    $formatMenu = [
        'csv' => ['bi-filetype-csv', 'CSV'],
        'excel' => ['bi-file-earmark-excel', 'Excel'],
        'pdf' => ['bi-filetype-pdf', 'PDF'],
    ];
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 estate-export-actions no-print">
    @if(count($formats) === 1)
    <button type="button" class="btn estate-export-btn" id="{{ $prefix }}DownloadBtn"
        data-export-for="{{ $prefix }}" data-format="{{ $formats[0] }}" title="Download the filtered list ({{ $formatMenu[$formats[0]][1] }})">
        <i class="bi bi-download" aria-hidden="true"></i>
        <span>Download</span>
    </button>
    @else
    <div class="dropdown">
        <button type="button" class="btn estate-export-btn dropdown-toggle" id="{{ $prefix }}DownloadBtn"
            data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="menu" title="Download the filtered list">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end estate-export-menu" aria-labelledby="{{ $prefix }}DownloadBtn">
            @foreach($formats as $fmt)
            <li><button type="button" class="dropdown-item" data-export-for="{{ $prefix }}" data-format="{{ $fmt }}">
                <i class="bi {{ $formatMenu[$fmt][0] }}" aria-hidden="true"></i> {{ $formatMenu[$fmt][1] }}</button></li>
            @endforeach
        </ul>
    </div>
    @endif
    @if($print)
        @if(! empty($printHref))
        <a href="{{ $printHref }}" target="_blank" rel="noopener" class="btn estate-export-btn" id="{{ $prefix }}PrintBtn" title="Print the filtered list">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
        @else
        <button type="button" class="btn estate-export-btn" id="{{ $prefix }}PrintBtn" title="Print the filtered list">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        @endif
    @endif
</div>
