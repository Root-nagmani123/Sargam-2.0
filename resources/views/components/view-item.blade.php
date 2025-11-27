<style>
@media print {
    body, html {
        margin: 0 !important;
        padding: 0 !important;
    }
	
    .container-fluid,
    .card,
    .card-body,
    .card-header {
        margin: 0 !important;
        padding-top: 0px !important;
        page-break-before: avoid !important;
    }

    .mb-4, .mt-4, .pt-4, .py-4 {
        margin: 0 !important;
        padding: 0 !important;
    }

    .shadow-sm,
    .shadow,
    .card {
        box-shadow: none !important;
    }
	
	 .col-md-6, 
    .col-12.col-md-6 {
        max-width: 50% !important;
        flex: 0 0 50% !important;
    }

    .row {
        display: flex !important;
        flex-wrap: wrap !important;
    }
}
</style>
@props(['label', 'value', 'isLink' => false])

<div class="col-12 col-md-6 mb-3">
    <div class="border rounded px-3 py-2 bg-white shadow-sm h-100">
        <div class="fw-semibold text-secondary small mb-1">
            {{ $label }}
        </div>
        <div class="text-dark">
            @if($isLink)
                <a href="{{ $value }}" target="_blank" class="text-primary text-decoration-underline">{{ $value }}</a>
            @else
                {{ $value ?? '-' }}
            @endif
        </div>
    </div>
</div>
