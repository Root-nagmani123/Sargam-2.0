@extends('admin.layouts.master')

@section('title', 'View Re-exam Marksheet')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Same sheet as marksheet/show (main marksheet); the table carries Pass / Fail only, no marks. */
.ms-sheet { max-width: 640px; margin: 0 auto; background: #fff; padding: 28px 24px; color: #000;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08); border-radius: 6px; font-size: 12px; }
.ms-head { text-align: center; line-height: 1.35; }
.ms-head img { width: 64px; height: auto; margin-bottom: 6px; }
.ms-head .ms-hi { font-size: 15px; font-weight: 700; }
.ms-head .ms-en { font-size: 14px; font-weight: 700; margin-top: 6px; }
.ms-head .ms-sub { font-style: italic; font-weight: 600; margin-top: 10px; }
.ms-who { display: flex; justify-content: space-between; gap: 12px; margin: 14px 0 8px; font-weight: 700; }
.ms-who span { font-weight: 400; }
.ms-exam { margin: 0 0 8px; }
.ms-table { width: 100%; border-collapse: collapse; }
.ms-table th, .ms-table td { border: 1px solid #555; padding: 5px 7px; vertical-align: middle; }
.ms-table th { font-weight: 500; text-align: center; }
.ms-table td.ms-num { text-align: center; white-space: nowrap; }
.ms-table tr.ms-total td { font-weight: 700; }
.ms-signs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 28px; text-align: center; }
.ms-sign-cap { text-align: left; margin-bottom: 6px; }
.ms-sign img { max-width: 120px; max-height: 44px; object-fit: contain; display: block; margin: 0 auto 4px; }
.ms-sign-empty { height: 44px; display: flex; align-items: center; justify-content: center; color: #b54708; font-size: 11px; }
.ms-sheet { position: relative; isolation: isolate; }
.ms-watermark { position: absolute; left: 50%; top: 50%; width: 55%; max-width: 340px; height: auto;
                transform: translate(-50%, -50%); opacity: .12; z-index: -1; pointer-events: none; user-select: none;
                -webkit-print-color-adjust: exact; print-color-adjust: exact; }
@media print {
    @page { size: A4; margin: 12mm; }
    body * { visibility: hidden !important; }
    .ms-sheet, .ms-sheet * { visibility: visible !important; }
    .ms-sheet { position: absolute; left: 0; top: 0; width: 100%; max-width: none; box-shadow: none; }
}
</style>
@endpush

@section('setup_content')
@php $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X']; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="View Re-exam Marksheet" :showBack="true"
                 :items="['Setup', 'COE', 'Document Management', 'View Re-exam Marksheet']">
        @if ($verifying)
            <button type="button" class="btn coe-btn-header" id="rmsPublish"><span>Verify &amp; Publish</span></button>
        @endif
        <button type="button" class="btn coe-btn-header" onclick="window.print()">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
    </x-breadcrum>

    <div class="ms-sheet">
        <img class="ms-watermark" src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="" aria-hidden="true">
        <div class="ms-head">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA emblem">
            <div class="ms-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी<br>(भारत सरकार)<br>मसूरी- 248179</div>
            <div class="ms-en">Lal Bahadur Shastri National Academy of Administration<br>(Government of India)<br>Mussoorie-248 179</div>
            <div class="ms-sub">Result of the Re-examination<br>({{ $m['course'] }})</div>
            <div class="fw-bold mt-1">पुनः परीक्षा परिणाम/RE-EXAMINATION RESULT</div>
        </div>

        <div class="ms-who">
            <div>नाम/Name: <span>{{ $m['name'] }}</span></div>
            <div>अनुक्रमांक/Roll No.: <span>{{ $m['roll'] }}</span></div>
            <div>सेवा/Service: <span>{{ $m['service'] }}</span></div>
        </div>
        <div class="ms-exam">परीक्षा/Examination: <strong>{{ $m['examination'] }}</strong></div>

        <table class="ms-table">
            <thead>
                <tr>
                    <th style="width: 12%;">क्र.सं./S. No.</th>
                    <th>विषय/Subject</th>
                    <th style="width: 28%;">परिणाम/Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($m['subjects'] as $s)
                    <tr>
                        <td class="ms-num">{{ $roman[$loop->index] ?? $loop->iteration }}.</td>
                        <td>{{ $s['name'] }}</td>
                        <td class="ms-num">{{ $s['result'] }}</td>
                    </tr>
                @endforeach
                <tr class="ms-total">
                    <td colspan="2" class="text-center">कुल परिणाम / Overall Result</td>
                    <td class="ms-num">{{ $m['result'] }}</td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between mt-2">
            <div class="flex-grow-1 me-4">Remarks: <span class="d-inline-block border-bottom border-dark" style="width: 70%;">&nbsp;</span></div>
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
                    @elseif (! $m['published'])
                        <div class="ms-sign-empty"></div>
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
    // Verify puts the logged-in UDC / AAO / COE signature on it; the COE's publishes it (ReexamMarksheetService::verify()).
    $('#rmsPublish').on('click', function () {
        Swal.fire({
            icon: 'question', title: 'Verify', text: 'Verify this re-exam marksheet? Your signature will be added to it.',
            showCancelButton: true, confirmButtonText: 'Verify'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: @json(route('admin.coe.reexam-marksheet.publish')), method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ reexam: @json($m['reexam_key']), ots: [@json($m['code'])] }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Verified', text: res.message }).then(function () { window.location.reload(); });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not verified', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    });
});
</script>
@endpush
