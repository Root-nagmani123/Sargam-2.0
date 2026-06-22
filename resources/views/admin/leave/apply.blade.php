@extends('admin.layouts.master')

@section('title', ($readOnly ?? false) ? 'View Leave Application' : (($application ?? null) ? 'Edit Leave Application' : 'Apply Leave'))

@section('content')

@include('admin.leave.partials.styles')

@php
    $selectedLeaveType = old('leave_type', $leaveType ?? \App\Models\LeaveApplication::TYPE_PT_EXEMPTION);
    $isPt = $selectedLeaveType === \App\Models\LeaveApplication::TYPE_PT_EXEMPTION;
    $formAction = ($application ?? null)
        ? route('leave.update', $application->pk)
        : route('leave.store');
    $pageTitle = ($readOnly ?? false) ? 'View Leave Application' : (($application ?? null) ? 'Edit Leave Application' : 'Apply Leave');
@endphp

<div class="container-fluid py-3 leave-module">
    <x-breadcrum :title="$pageTitle" />
    <x-session_message />

    @if(empty($course_is_running))
        <div class="alert alert-warning py-2 small mb-3">
            Your enrolled course has ended. Leave applications use your latest course enrollment.
        </div>
    @endif

    <div class="card leave-apply-card border-0">
        <div class="card-body p-3 p-md-4">
            <h2 class="leave-apply-title">{{ $pageTitle }}</h2>
            <hr class="leave-apply-divider">

            @if($readOnly ?? false)
                <div class="alert alert-info py-2 small mb-3">
                    Status: <strong>{{ $application->status_label }}</strong>
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="leave-apply-form">
                @csrf
                @if($application ?? null)
                    @method('PUT')
                @endif

                <input type="hidden" name="leave_type" value="{{ $selectedLeaveType }}">

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="leave-form-row">
                            <label class="leave-form-label">Leave Type</label>
                            <div class="leave-form-field">
                                <div class="leave-type-tabs" role="group" aria-label="Leave type">
                                    <a href="{{ route('leave.apply', ['leave_type' => 'PT_EXEMPTION']) }}"
                                        class="btn {{ $isPt ? 'active' : '' }} {{ ($readOnly ?? false) || ($application ?? null) ? 'disabled pe-none' : '' }}">
                                        PT Exemption
                                    </a>
                                    <a href="{{ route('leave.apply', ['leave_type' => 'STATIONED_LEAVE']) }}"
                                        class="btn {{ ! $isPt ? 'active' : '' }} {{ ($readOnly ?? false) || ($application ?? null) ? 'disabled pe-none' : '' }}">
                                        Stationed Leave
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="leave-form-row">
                            <label class="leave-form-label">Nature of Leave <span class="text-danger">*</span></label>
                            <div class="leave-form-field">
                                <select name="leave_nature_master_pk" class="form-select" required {{ ($readOnly ?? false) ? 'disabled' : '' }}>
                                    <option value="">--Select--</option>
                                    @foreach($natures as $nature)
                                        <option value="{{ $nature->pk }}"
                                            {{ (string) old('leave_nature_master_pk', $application->leave_nature_master_pk ?? '') === (string) $nature->pk ? 'selected' : '' }}>
                                            {{ $nature->nature_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="leave-form-row">
                            <label class="leave-form-label">Start Date <span class="text-danger">*</span></label>
                            <div class="leave-form-field">
                                <div class="leave-date-wrap">
                                    <input type="date" name="from_date" id="from_date" class="form-control" required
                                        value="{{ old('from_date', isset($application) ? $application->from_date?->format('Y-m-d') : '') }}"
                                        {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                                    <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                                </div>
                            </div>
                        </div>

                        <div class="leave-form-row">
                            <label class="leave-form-label">End Date <span class="text-danger">*</span></label>
                            <div class="leave-form-field">
                                <div class="leave-date-wrap">
                                    <input type="date" name="to_date" id="to_date" class="form-control" required
                                        value="{{ old('to_date', isset($application) ? $application->to_date?->format('Y-m-d') : '') }}"
                                        {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                                    <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                                </div>
                            </div>
                        </div>

                        <div class="leave-form-row">
                            <label class="leave-form-label">Total Days <span class="text-danger">*</span></label>
                            <div class="leave-form-field" style="max-width: 140px;">
                                <input type="text" id="total_days_display" class="form-control" readonly
                                    value="{{ old('total_days', isset($application) ? number_format((float) $application->total_days, 1) : '0') }}">
                            </div>
                        </div>

                        <div class="leave-form-row align-top">
                            <label class="leave-form-label pt-2">Reason For Exemption <span class="text-danger">*</span></label>
                            <div class="leave-form-field">
                                <textarea name="reason" rows="3" class="form-control" required
                                    placeholder="Crack Muscles, Sprain Muscles, Fever..."
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>{{ old('reason', $application->reason ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="leave-form-row">
                            <label class="leave-form-label">Contact No. during Exemption <span class="text-danger">*</span></label>
                            <div class="leave-form-field">
                                <input type="text" name="contact_number" class="form-control" maxlength="15"
                                    placeholder="Enter Contact Number"
                                    value="{{ old('contact_number', $application->contact_number ?? '') }}"
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>

                    @if($isPt)
                    <div class="col-lg-4">
                        <div class="leave-aside-card">
                            <div class="leave-aside-head">
                                <i class="material-icons material-symbols-rounded">calendar_month</i>
                                <span>PT Balance</span>
                            </div>
                            <div class="pt-balance-value">{{ number_format($ptBalance['remaining'], 1) }} Days</div>
                            <div class="pt-balance-as-on">(As on {{ $ptBalance['as_on'] }})</div>
                        </div>

                        <div class="leave-aside-card">
                            <div class="leave-aside-head">
                                <i class="material-icons material-symbols-rounded">info</i>
                                <span>Note</span>
                            </div>
                            <p class="leave-note-text">
                                You can edit or delete the leave application until it is approved by the authority.
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="col-lg-4">
                        <div class="leave-aside-card">
                            <div class="leave-aside-head">
                                <i class="material-icons material-symbols-rounded">info</i>
                                <span>Note</span>
                            </div>
                            <p class="leave-note-text">
                                You can edit or delete the leave application until it is approved by the authority.
                            </p>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="mt-4 pt-2">
                    <h3 class="attachment-section-title">Attachment (Optional)</h3>
                    <div class="table-responsive">
                        <table class="table attachment-table mb-2">
                            <thead>
                                <tr>
                                    <th style="width:8%;">S. No.</th>
                                    <th style="width:30%;">Attachment Title</th>
                                    <th style="width:42%;">Attachment Name</th>
                                    <th style="width:20%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attachment-rows">
                                @if(($application ?? null) && $application->attachments->isNotEmpty())
                                    @foreach($application->attachments as $index => $attachment)
                                        <tr>
                                            <td class="attach-serial">{{ $index + 1 }}</td>
                                            <td>{{ $attachment->attachment_title }}</td>
                                            <td>
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                                    {{ $attachment->original_file_name }}
                                                </a>
                                                @if(!($readOnly ?? false))
                                                    <input type="hidden" name="existing_attachments[]" value="{{ $attachment->pk }}">
                                                @endif
                                            </td>
                                            <td>
                                                @if(!($readOnly ?? false))
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-row border-0 p-1">
                                                        <i class="material-icons material-symbols-rounded text-danger" style="font-size:20px;">delete</i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr id="attachment-empty-row">
                                        <td colspan="4" class="text-center text-muted py-3">No attachments added.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(!($readOnly ?? false))
                        <div class="text-end">
                            <button type="button" class="btn btn-draft btn-sm" id="add-attachment-row">+ Add More</button>
                        </div>
                    @endif
                </div>

                @if(!($readOnly ?? false))
                <div class="leave-actions">
                    <a href="{{ route('leave.my-leave') }}" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="submit_action" value="draft" class="btn btn-draft">Save As Draft</button>
                    <button type="submit" name="submit_action" value="submit" class="btn btn-submit text-white">Submit</button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let attachmentIndex = 0;

    function updateTotalDays() {
        const from = $('#from_date').val();
        const to = $('#to_date').val();
        if (!from || !to) {
            $('#total_days_display').val('0');
            return;
        }
        const start = new Date(from);
        const end = new Date(to);
        if (end < start) {
            $('#total_days_display').val('0');
            return;
        }
        const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        $('#total_days_display').val(diff);
    }

    $('#from_date, #to_date').on('change', updateTotalDays);
    updateTotalDays();

    $('#add-attachment-row').on('click', function () {
        $('#attachment-empty-row').remove();
        const row = `
            <tr>
                <td class="attach-serial"></td>
                <td><input type="text" name="attachments[${attachmentIndex}][title]" class="form-control form-control-sm" placeholder="Medical Certificate"></td>
                <td><input type="file" name="attachments[${attachmentIndex}][file]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm border-0 p-1 remove-attachment-row"><i class="material-icons material-symbols-rounded text-danger" style="font-size:20px;">delete</i></button></td>
            </tr>
        `;
        $('#attachment-rows').append(row);
        attachmentIndex++;
        refreshAttachmentSerial();
    });

    $(document).on('click', '.remove-attachment-row', function () {
        $(this).closest('tr').remove();
        refreshAttachmentSerial();
        if ($('#attachment-rows tr').length === 0) {
            $('#attachment-rows').html('<tr id="attachment-empty-row"><td colspan="4" class="text-center text-muted py-3">No attachments added.</td></tr>');
        }
    });

    function refreshAttachmentSerial() {
        $('#attachment-rows tr').not('#attachment-empty-row').each(function (idx) {
            $(this).find('.attach-serial').text(idx + 1);
        });
    }
});
</script>
@endpush
