{{-- Download (CSV / Excel / PDF) + Print, right-aligned above the card (doc §1).
     $route, $params (optional), $prefix (anchor id stem, read by AthleticsGrid.init). --}}
@php $params = $params ?? []; @endphp
<div class="dropdown">
    <button type="button" class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
            data-bs-toggle="dropdown" aria-expanded="false">
        <span class="material-icons material-symbols-rounded" aria-hidden="true">download</span><span>Download</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" id="{{ $prefix }}Csv" href="{{ route($route, $params + ['format' => 'csv']) }}">CSV</a></li>
        <li><a class="dropdown-item" id="{{ $prefix }}Excel" href="{{ route($route, $params + ['format' => 'excel']) }}">Excel (.xlsx)</a></li>
        <li><a class="dropdown-item" id="{{ $prefix }}Pdf" href="{{ route($route, $params + ['format' => 'pdf']) }}">PDF</a></li>
    </ul>
</div>
<a href="{{ route($route, $params + ['format' => 'print']) }}" id="{{ $prefix }}Print" target="_blank" rel="noopener"
   class="btn programme-dt-btn-columns border-0 text-primary">
    <span class="material-icons material-symbols-rounded" aria-hidden="true">print</span><span>Print</span>
</a>
