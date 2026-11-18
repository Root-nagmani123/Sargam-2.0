{{--
    Flash messages as UX4G Alerts (ux4g-alert ux4g-alert-{success|error}).
    No auto-dismiss: the old 2-second timeout removed messages before they could
    be read (WCAG 2.2.1 Timing Adjustable). Users dismiss them with the close button.
    Errors use role="alert" (announced immediately); success uses role="status".
--}}
@php
    $sargamFlash = [];
    if (session('success')) {
        $sargamFlash[] = ['success', 'Success', session('success'), 'check_circle'];
    }
    if (session('error')) {
        $sargamFlash[] = ['error', 'Error', session('error'), 'error'];
    }
    if (session('errors')) {
        foreach (session('errors')->all() as $sargamFlashError) {
            $sargamFlash[] = ['error', 'Error', $sargamFlashError, 'error'];
        }
    }
@endphp
@foreach ($sargamFlash as [$tone, $title, $message, $icon])
    <div class="ux4g-alert ux4g-alert-{{ $tone }} sargam-flash mb-3" role="{{ $tone === 'error' ? 'alert' : 'status' }}">
        <span class="ux4g-alert-icon"><i class="material-icons material-symbols-rounded" aria-hidden="true">{{ $icon }}</i></span>
        <div class="ux4g-alert-content flex-wrap">
            <strong class="ux4g-alert-title">{{ $title }}</strong>
            <span class="ux4g-alert-message">{{ $message }}</span>
        </div>
        <button type="button" class="ux4g-alert-close" data-sargam-dismiss aria-label="Dismiss message">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">close</i>
        </button>
    </div>
@endforeach
{{-- Validation errors are shown only below each field (no duplicate list at top) --}}
<div id="status-msg"></div>

@if (count($sargamFlash))
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-sargam-dismiss]');
            var alertEl = btn && btn.closest('.ux4g-alert');
            if (!alertEl) { return; }
            alertEl.remove();
            // Keep keyboard focus in the page instead of dropping it on <body> (WCAG 2.4.3).
            var main = document.getElementById('main-content');
            if (main) { main.focus(); }
        });
    </script>
@endif
