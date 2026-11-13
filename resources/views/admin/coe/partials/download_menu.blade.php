{{-- COE Download button with (CSV) / Excel / PDF choices. Wire the items to CoeGrid:
     CoeGrid.init({ download: '#<prefix>DownloadExcel', pdf: '#<prefix>DownloadPdf', csv: '#<prefix>DownloadCsv', … })
     CSV is listed only when the page passes 'csv' => true (and wires it). --}}
<div class="dropdown">
    <button type="button" class="btn programme-dt-btn-columns dropdown-toggle" data-bs-toggle="dropdown"
            aria-expanded="false" title="Download">
        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if ($csv ?? false)
        <li>
            <button type="button" class="dropdown-item" id="{{ $prefix }}DownloadCsv">
                <i class="bi bi-filetype-csv me-2 text-primary" aria-hidden="true"></i>CSV
            </button>
        </li>
        @endif
        <li>
            <button type="button" class="dropdown-item" id="{{ $prefix }}DownloadExcel">
                <i class="bi bi-file-earmark-excel me-2 text-success" aria-hidden="true"></i>Excel
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item" id="{{ $prefix }}DownloadPdf">
                <i class="bi bi-file-earmark-pdf me-2 text-danger" aria-hidden="true"></i>PDF
            </button>
        </li>
    </ul>
</div>
