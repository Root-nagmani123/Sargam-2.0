@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());

    $meterReadingPageFlashParts = [];
    if (session('error')) {
        $meterReadingPageFlashParts[] = trim((string) session('error'));
    }
    if ($errors->any()) {
        foreach ($errors->all() as $err) {
            $t = trim((string) $err);
            if ($t !== '' && ! in_array($t, $meterReadingPageFlashParts, true)) {
                $meterReadingPageFlashParts[] = $t;
            }
        }
    }
    $meterReadingPageAlertMessage = ! empty($meterReadingPageFlashParts)
        ? implode("\n\n", $meterReadingPageFlashParts)
        : null;
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page umr-page">
    <x-breadcrum title="Update Meter Reading" :showBack="true" />

    <x-session_message />

    @if($meterReadingPageAlertMessage !== null)
        <div class="alert alert-danger alert-dismissible fade show rounded-1" role="alert">
            <span class="flex-grow-1">{!! nl2br(e($meterReadingPageAlertMessage)) !!}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- The filter form, readings grid and save form all live in the partial
         below — it is the same body the Update Meter Reading modal renders.
         A stale duplicate of that markup used to sit here, which put every
         id on the page twice (getElementById then resolved to the dead
         copy) and left two stray closing tags behind. --}}
    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">
            @include('admin.estate._update_meter_reading_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
{{-- Carries the .umr-form rules this partial was written against (see the
     "Update Meter Reading (filter + readings grid, page and modal)" block).
     Only the modal host used to load it, so the standalone page rendered
     unstyled. --}}
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
.select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
.select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
#updateMeterReadingTable td { vertical-align: middle; font-size: 0.85rem; }
#updateMeterReadingTable .other-dual-stacked .other-dual-col { vertical-align: top; }
#updateMeterReadingTable .other-dual-seg { padding-top: 0.35rem; padding-bottom: 0.35rem; }
#updateMeterReadingTable .other-dual-seg[data-slot="1"] { border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
#updateMeterReadingTable .other-dual-stacked .new-meter-reading,
#updateMeterReadingTable .other-dual-stacked .new-meter-no { max-width: 100%; }
.perm-pair-cb { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
</style>
@endpush

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
<script>
@if ($meterReadingPageAlertMessage !== null)
(function () {
    try { alert(@json($meterReadingPageAlertMessage)); } catch (e) {}
})();
@endif
</script>
@endpush
