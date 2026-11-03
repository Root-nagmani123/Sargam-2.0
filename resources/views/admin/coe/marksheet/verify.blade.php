@extends('admin.layouts.master')

@section('title', 'Verify Document')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.mv-card { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px 24px; }
.mv-label { font-size: 12px; text-transform: uppercase; color: var(--ds-ink-muted, #575757); margin-bottom: 2px; }
.mv-value { font-size: 15px; font-weight: 600; color: var(--ds-ink, #171717); word-break: break-word; }
.mv-pass { color: #24d700; }
.mv-fail { color: #ff0025; }
@media (max-width: 767.98px) { .mv-card { grid-template-columns: 1fr; } }
@media print { .mv-actions, .coe-secondary-actions { display: none !important; } }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Verify Document" :showBack="true"
                 :items="['Setup', 'COE', 'Document Management', 'Marksheet Generation', 'Verify Document']">
        <button type="button" class="btn coe-btn-header mv-publish" data-all="1">
            <span>Verify &amp; Publish All</span>
        </button>
    </x-breadcrum>

    <div class="d-flex justify-content-end gap-2 mb-3 coe-secondary-actions">
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
                    <a href="{{ route('admin.coe.marksheet.show', ['course' => $m['course_key'], 'ot' => $m['code']]) }}"
                       class="btn coe-btn btn-outline-secondary d-inline-flex align-items-center">View</a>
                    <button type="button" class="btn coe-btn btn-outline-primary mv-publish">Verify &amp; Publish Marksheet</button>
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
    // shortcut: publication is not stored yet (sample results); save it once Evaluation & Marks is live.
    $('.mv-publish').on('click', function () {
        Swal.fire({ icon: 'info', title: 'Verify & Publish',
            text: 'Publishing will be saved once Evaluation & Marks is live. These marksheets are built from sample results.' });
    });
});
</script>
@endpush
