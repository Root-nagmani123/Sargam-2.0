@extends('admin.layouts.master')

@section('title', 'Laptop Availability Form')

@push('styles')
{{-- COE module layer (coe-*); .coe-form-card gives this page the modal form styling. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $answered = $declaration !== null;
    $locked = $blocked !== null;

    // A first visit defaults every answer to No, which is what the design shows. After a
    // failed submit old() wins; once answered, the stored row does.
    $laptop = old('laptop_required', $answered && $declaration->laptop_required ? '1' : '0') === '1';
    $hasDisability = old('has_disability', $answered && $declaration->has_disability ? '1' : '0') === '1';
    $scriber = old('scriber_required', $answered && $declaration->scriber_required ? '1' : '0') === '1';

    $name = trim((string) $student->display_name) !== ''
        ? $student->display_name
        : trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])));
    $course = $student->couse_short_name ?: ($student->course_name ?: '—');
    $otLabel = $name . ($student->generated_OT_code ? ' - ' . $student->generated_OT_code : '');
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Laptop & Other Assets Availability Form" :showBack="false"
                 :items="['General', 'Examination Centre', 'Laptop Availability']" />

    <x-session_message />

    <div class="card overflow-hidden rounded-3 coe-form-card">
        <div class="card-body p-3 p-md-4">

            @if($locked)
                <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="status">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>{{ $blocked }}</span>
                </div>
            @endif

            <form action="{{ route('admin.coe.laptop-declaration.store') }}" method="POST"
                  id="coeDeclForm" novalidate>
                @csrf

                <div class="row g-3">
                    {{-- What the Examination Section already knows. Disabled — there is
                         nothing to choose, and store() reads the trainee from the session. --}}
                    <div class="col-12 col-md-6">
                        <label for="coeDeclCourse" class="coe-form-label">Course Name<span class="coe-req">*</span></label>
                        <input type="text" class="form-control coe-control" id="coeDeclCourse" value="{{ $course }}" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="coeDeclBatch" class="coe-form-label">Batch<span class="coe-req">*</span></label>
                        <input type="text" class="form-control coe-control" id="coeDeclBatch" value="{{ $student->batch ?: '—' }}" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="coeDeclOt" class="coe-form-label">OT<span class="coe-req">*</span></label>
                        <input type="text" class="form-control coe-control" id="coeDeclOt" value="{{ $otLabel ?: '—' }}" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="coeDeclRoll" class="coe-form-label">Roll Number<span class="coe-req">*</span></label>
                        <input type="text" class="form-control coe-control" id="coeDeclRoll"
                               value="{{ $student->roll_number ?: 'Not allocated yet' }}" disabled>
                    </div>

                    {{-- The gate: No hides the scriber pair, and the server clears it. --}}
                    <div class="col-12">
                        <label for="coeDeclDisability" class="coe-form-label">OT with Disabilities<span class="coe-req">*</span></label>
                        <select class="form-select coe-control @error('has_disability') is-invalid @enderror"
                                id="coeDeclDisability" name="has_disability" @disabled($locked)>
                            <option value="0" @selected(! $hasDisability)>No</option>
                            <option value="1" @selected($hasDisability)>Yes</option>
                        </select>
                        @error('has_disability')<div class="coe-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <span class="coe-form-label" id="coeDeclLaptopLabel">Laptop Required<span class="coe-req">*</span></span>
                        <div class="coe-radio-row" role="radiogroup" aria-labelledby="coeDeclLaptopLabel">
                            <label class="coe-radio"><input type="radio" name="laptop_required" value="1" @checked($laptop) @disabled($locked)> Yes</label>
                            <label class="coe-radio"><input type="radio" name="laptop_required" value="0" @checked(! $laptop) @disabled($locked)> No</label>
                        </div>
                        @error('laptop_required')<div class="coe-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 coe-decl-if-disability {{ $hasDisability ? '' : 'd-none' }}">
                        <span class="coe-form-label" id="coeDeclScriberLabel">Scriber Required<span class="coe-req">*</span></span>
                        <div class="coe-radio-row" role="radiogroup" aria-labelledby="coeDeclScriberLabel">
                            <label class="coe-radio"><input type="radio" name="scriber_required" value="1" @checked($scriber) @disabled($locked)> Yes</label>
                            <label class="coe-radio"><input type="radio" name="scriber_required" value="0" @checked(! $scriber) @disabled($locked)> No</label>
                        </div>
                        @error('scriber_required')<div class="coe-field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Always shown — a No may explain itself too. Only the asterisk follows the answer. --}}
                    <div class="col-12 col-md-6">
                        <label for="coeDeclReason" class="coe-form-label">
                            Reason<span class="coe-req {{ $laptop ? '' : 'd-none' }}" id="coeDeclReasonReq">*</span>
                        </label>
                        <textarea class="form-control coe-control @error('reason') is-invalid @enderror"
                                  id="coeDeclReason" name="reason" rows="3" maxlength="500"
                                  placeholder="eg. Don't have PC" @disabled($locked)>{{ old('reason', $declaration->reason ?? '') }}</textarea>
                        @error('reason')<div class="coe-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 coe-decl-if-disability {{ $hasDisability ? '' : 'd-none' }}">
                        <label for="coeDeclScriberRemarks" class="coe-form-label">
                            Remarks for Scriber<span class="coe-req {{ $scriber ? '' : 'd-none' }}" id="coeDeclRemarksReq">*</span>
                        </label>
                        <textarea class="form-control coe-control @error('scriber_remarks') is-invalid @enderror"
                                  id="coeDeclScriberRemarks" name="scriber_remarks" rows="3" maxlength="500"
                                  placeholder="eg. Need fast writer" @disabled($locked)>{{ old('scriber_remarks', $declaration->scriber_remarks ?? '') }}</textarea>
                        @error('scriber_remarks')<div class="coe-field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                @unless($locked)
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="reset" class="btn coe-btn coe-btn-cancel">Cancel</button>
                        <button type="submit" class="btn coe-btn coe-btn-primary">Submit</button>
                    </div>
                @endunless
            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var $form = $('#coeDeclForm');

    function checked(name) {
        return $form.find('input[name="' + name + '"]:checked').val() === '1';
    }

    function disabilityAsked() {
        return $('#coeDeclDisability').val() === '1';
    }

    function sync() {
        var asked = disabilityAsked();

        $('.coe-decl-if-disability').toggleClass('d-none', !asked);
        if (!asked) {
            // A hidden Yes would post an answer the trainee can no longer see.
            $form.find('input[name="scriber_required"][value="0"]').prop('checked', true);
            $('#coeDeclScriberRemarks').val('');
        }

        var laptop = checked('laptop_required');
        var scriber = asked && checked('scriber_required');

        $('#coeDeclReasonReq').toggleClass('d-none', !laptop);
        $('#coeDeclRemarksReq').toggleClass('d-none', !scriber);
        if (!laptop) { $('#coeDeclReason').removeClass('is-invalid'); }
        if (!scriber) { $('#coeDeclScriberRemarks').removeClass('is-invalid'); }
    }

    $form.on('change', '#coeDeclDisability, input[type="radio"]', sync);
    $form.on('input', 'textarea', function () { $(this).removeClass('is-invalid'); });

    // Reset restores the rendered values; re-sync once the browser has applied them.
    $form.on('reset', function () { window.setTimeout(sync, 0); });

    $form.on('submit', function (e) {
        // The server re-checks the same rules; this only saves a round trip.
        var $missing = $();
        if (checked('laptop_required') && $.trim($('#coeDeclReason').val()) === '') {
            $missing = $missing.add('#coeDeclReason');
        }
        if (disabilityAsked() && checked('scriber_required') && $.trim($('#coeDeclScriberRemarks').val()) === '') {
            $missing = $missing.add('#coeDeclScriberRemarks');
        }
        if ($missing.length) {
            e.preventDefault();
            $missing.addClass('is-invalid').first().trigger('focus');
        }
    });

    sync();
});
</script>
@endpush
