{{-- params: title, crumb?, export? (type), exportQuery? (array), printBill? (url), back? (url) --}}
<div class="gtc-card gtc-pagehead">
    <div>
        <div class="gtc-crumb">Home / Guest TA Claim / <b>{{ $crumb ?? $title }}</b></div>
        <h1>{{ $title }}</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        @isset($back)
            <a href="{{ $back }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Back</a>
        @endisset
        @isset($printBill)
            <a href="{{ $printBill }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Print TA bill</a>
        @endisset
        @isset($export)
            @include('guesttaclaim::partials.tools', ['type' => $export, 'query' => $exportQuery ?? request()->query()])
        @endisset
    </div>
</div>
