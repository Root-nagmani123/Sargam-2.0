{{-- Download (CSV) / Print for a mess master table; handled by mess-column-manager.js. --}}
@props(['tableId', 'title'])
<div class="mess-export-bar no-print">
    <button type="button" class="btn" data-mess-excel-export="{{ $tableId }}"
        data-filename="{{ \Illuminate\Support\Str::slug($title) }}.csv">
        <i class="material-symbols-rounded" aria-hidden="true">download</i>
        <span>Download</span>
    </button>
    <button type="button" class="btn" data-mess-print-table="{{ $tableId }}"
        data-mess-print-template="lbsnaa" data-print-title="{{ $title }}">
        <i class="material-symbols-rounded" aria-hidden="true">print</i>
        <span>Print</span>
    </button>
</div>
