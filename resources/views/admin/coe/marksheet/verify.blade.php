@extends('admin.layouts.master')

@section('title', 'Verify Document')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.mv-card { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, .5fr); gap: 24px 24px; }
.mv-label { font-size: 13px; text-transform: uppercase; color: var(--ds-ink-muted, #575757); margin-bottom: 4px; }
.mv-value { font-size: 16px; font-weight: 600; color: var(--ds-ink, #171717); word-break: break-word; }
.mv-pass { color: #24d700; }
.mv-fail { color: #ff0025; }
.mv-actions .mv-publish { min-width: 254px; padding: 10px 22px; font-size: 16px; font-weight: 500; border-radius: 6px; }
@media (max-width: 767.98px) { .mv-card { grid-template-columns: 1fr; } .mv-actions .mv-publish { min-width: 0; width: 100%; } }
@media print { .mv-actions, .coe-secondary-actions { display: none !important; } }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Verify Document" :showBack="true"
                 :items="['Setup', 'COE', 'Result Management', 'Verify Document']">
        <button type="button" class="btn coe-btn-header mv-publish" data-all="1">
            <span>Verify &amp; Publish All</span>
        </button>
    </x-breadcrum>

    <div class="d-flex justify-content-end gap-2 mb-3 coe-secondary-actions">
        {{-- Download saves the page as a PDF through the browser's print dialog, as on View Marksheet. --}}
        <button type="button" class="btn programme-dt-btn-columns" onclick="window.print()" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" class="btn programme-dt-btn-columns" onclick="window.print()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    @foreach ($rows as $m)
        <div class="card rounded-3 mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="mv-card">
                    <div><div class="mv-label">Marksheet Number</div><div class="mv-value">{{ $m['number'] }}</div></div>
                    <div><div class="mv-label">Participant Name</div><div class="mv-value">{{ $m['name'] }}</div></div>
                    <div><div class="mv-label">Roll Number</div><div class="mv-value">{{ $m['roll'] }}</div></div>
                    <div><div class="mv-label">Course</div><div class="mv-value">{{ $m['course'] }}</div></div>
                    <div><div class="mv-label">Result</div><div class="mv-value {{ $m['result'] === 'Pass' ? 'mv-pass' : 'mv-fail' }}">{{ $m['result'] }}</div></div>
                    <div><div class="mv-label">Issue Date</div><div class="mv-value">{{ $m['generated_on'] }}</div></div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3 mv-actions">
                    <button type="button" class="btn coe-btn btn-outline-primary mv-publish" data-code="{{ $m['code'] }}">Verify &amp; Publish Marksheet</button>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';
    var PUBLISH_URL = @json(route('admin.coe.marksheet-generation.publish'));
    var BACK_URL = @json(route('admin.coe.marksheet-generation.index'));
    var COURSE = @json($courseKey);
    var ALL = @json(array_column($rows, 'code'));

    // Verify puts the logged-in UDC / AAO / COE signature on the marksheet; the COE's publishes it (MarksheetService::verify()).
    $('.mv-publish').on('click', function () {
        var ots = $(this).data('all') ? ALL : [String($(this).data('code'))];
        Swal.fire({
            icon: 'question', title: 'Verify',
            text: ots.length === 1 ? 'Verify this marksheet? Your signature will be added to it.' : 'Verify these ' + ots.length + ' marksheets? Your signature will be added to them.',
            showCancelButton: true, confirmButtonText: 'Verify'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: PUBLISH_URL, method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ course: COURSE, ots: ots }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Verified', text: res.message }).then(function () { window.location.href = BACK_URL; });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not verified', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    });
});
</script>
@endpush
