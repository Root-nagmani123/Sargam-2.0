@extends('admin.layouts.master')

@section('title', 'View Marksheet')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.ms-sheet { max-width: 640px; margin: 0 auto; background: #fff; padding: 28px 24px; color: #000;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08); border-radius: 6px; font-size: 12px; }
.ms-head { text-align: center; line-height: 1.35; }
.ms-head img { width: 64px; height: auto; margin-bottom: 6px; }
.ms-head .ms-hi { font-size: 15px; font-weight: 700; }
.ms-head .ms-en { font-size: 14px; font-weight: 700; margin-top: 6px; }
.ms-head .ms-sub { font-style: italic; font-weight: 600; margin-top: 10px; }
.ms-who { display: flex; justify-content: space-between; gap: 12px; margin: 14px 0 8px; font-weight: 700; }
.ms-who span { font-weight: 400; }
.ms-table { width: 100%; border-collapse: collapse; }
.ms-table th, .ms-table td { border: 1px solid #555; padding: 5px 7px; vertical-align: middle; }
.ms-table th { font-weight: 500; text-align: center; }
.ms-table td.ms-num { text-align: center; white-space: nowrap; }
.ms-table tr.ms-total td { font-weight: 700; }
.ms-signs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 28px; text-align: center; }
.ms-sign-cap { text-align: left; margin-bottom: 6px; }
.ms-sign img { max-width: 120px; max-height: 44px; object-fit: contain; display: block; margin: 0 auto 4px; }
.ms-sign-empty { height: 44px; display: flex; align-items: center; justify-content: center; color: #b54708; font-size: 11px; }
@media print {
    @page { size: A4; margin: 12mm; }
    body * { visibility: hidden !important; }
    .ms-sheet, .ms-sheet * { visibility: visible !important; }
    .ms-sheet { position: absolute; left: 0; top: 0; width: 100%; max-width: none; box-shadow: none; }
}
</style>
@endpush

@section('setup_content')
@php
    $num = fn ($v) => number_format($v, 2);
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
    $grandMax = 0;
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="View Marksheet" :showBack="true"
                 :items="['Setup', 'COE', 'Document Management', 'View Marksheet']">
        @if ($verifying && ! $m['published'])
            <button type="button" class="btn coe-btn-header" id="msPublish"><span>Verify &amp; Publish</span></button>
        @endif
        <button type="button" class="btn coe-btn-header" onclick="window.print()">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
    </x-breadcrum>

    <div class="ms-sheet">
        <div class="ms-head">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA emblem">
            <div class="ms-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी<br>(भारत सरकार)<br>मसूरी- 248179</div>
            <div class="ms-en">Lal Bahadur Shastri National Academy of Administration<br>(Government of India)<br>Mussoorie-248 179</div>
            <div class="ms-sub">Performance during the period of Training (Institutional as well as practical)<br>({{ $m['course'] }})</div>
            <div class="fw-bold mt-1">अंक तालिका/MARKS-SHEET</div>
        </div>

        <div class="ms-who">
            <div>नाम/Name: <span>{{ $m['name'] }}</span></div>
            <div>अनुक्रमांक/Roll No.: <span>{{ $m['roll'] }}</span></div>
            <div>सेवा/Service: <span>{{ $m['service'] }}</span></div>
        </div>

        <table class="ms-table">
            <thead>
                <tr>
                    <th colspan="2">पाठ्यक्रम/Course</th>
                    <th>गतिविधि/Activity</th>
                    <th>कुल अंक/<br>Total Marks</th>
                    <th>प्राप्तांक/<br>Marks obtained</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $name => $s)
                    @foreach ($s['components'] as $component => $max)
                        @php $grandMax += $max; @endphp
                        <tr>
                            @if ($loop->first)
                                <td rowspan="{{ count($s['components']) }}" class="ms-num">{{ $roman[$loop->parent->index] ?? $loop->parent->iteration }}.</td>
                                <td rowspan="{{ count($s['components']) }}">{{ $name }}</td>
                            @endif
                            <td>{{ $component }}</td>
                            <td class="ms-num">{{ $num($max) }}</td>
                            <td class="ms-num">{{ $num($m['marks'][$name][$component]) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="ms-total">
                    <td colspan="3" class="text-center">कुल योग / Grand Total</td>
                    <td class="ms-num">{{ $num($grandMax) }}</td>
                    <td class="ms-num">{{ $num($m['grand']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between mt-2">
            <div>Marksheet No.: <strong>{{ $m['number'] }}</strong></div>
            <div>Result: <strong>{{ $m['result'] }}</strong></div>
        </div>

        <div class="ms-signs">
            @foreach ($signatories as $designation => $caption)
                @php $sig = $signatures->get($designation); @endphp
                <div class="ms-sign">
                    <div class="ms-sign-cap">{{ $caption === 'Controller of Examination' ? '' : $caption.':' }}&nbsp;</div>
                    @if ($sig)
                        <img src="{{ route('admin.coe.signature-master.file', ['signature' => $sig->pk]) }}" alt="Signature of {{ $sig->person_name }}">
                        <div>({{ $sig->person_name }})</div>
                    @else
                        <div class="ms-sign-empty">No active {{ $designation }} signature in Signature Master</div>
                    @endif
                    <div>{{ $caption === 'Controller of Examination' ? $caption : $designation }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';
    // Verify & Publish locks the marksheet; the server refuses it until UDC, AAO and COE signatures are in Signature Master.
    $('#msPublish').on('click', function () {
        Swal.fire({
            icon: 'question', title: 'Verify & Publish', text: 'Publish this marksheet? It cannot be changed afterwards.',
            showCancelButton: true, confirmButtonText: 'Verify & Publish'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: @json(route('admin.coe.marksheet-generation.publish')), method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ course: @json($m['course_key']), ots: [@json($m['code'])] }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Published', text: res.message }).then(function () { window.location.reload(); });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not published', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    });
    @if (request()->boolean('print'))
    // Opened from a Download link: wait for the signature images, then print.
    if (document.readyState === 'complete') { window.print(); } else { $(window).on('load', function () { window.print(); }); }
    @endif
});
</script>
@endpush
