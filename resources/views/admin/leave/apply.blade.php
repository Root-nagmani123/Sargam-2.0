@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', ($readOnly ?? false) ? 'View Leave Application' : (($application ?? null) ? 'Edit Leave Application' : 'Apply Leave'))

@section(hasRole('Officer Trainee') ? 'content' : 'setup_content')

@include('admin.leave.partials.styles')

@php
    $selectedLeaveType = old('leave_type', $leaveType ?? \App\Models\LeaveApplication::TYPE_PT_EXEMPTION);
    $isPt = $selectedLeaveType === \App\Models\LeaveApplication::TYPE_PT_EXEMPTION;
    $formAction = ($application ?? null)
        ? route('leave.update', $application->pk)
        : route('leave.store');
@endphp

<div class="container-fluid py-3 leave-module">
    <x-session_message />

    <div class="row g-3">
        <div class="col-lg-3">
            @include('admin.leave.partials.sidebar', ['ptBalance' => $ptBalance])
        </div>

        <div class="col-lg-9">
            <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1 fw-semibold">{{ ($readOnly ?? false) ? 'View Leave Application' : 'Apply Leave' }}</h2>
                            <div class="small text-muted">Course: {{ $course->course_name ?? 'N/A' }}</div>
                        </div>
                        <a href="{{ route('leave.my-leave') }}" class="btn btn-outline-secondary btn-sm">My Leave</a>
                    </div>

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

                        <div class="leave-type-toggle btn-group mb-4" role="group" aria-label="Leave type">
                            <a href="{{ route('leave.apply', ['leave_type' => 'PT_EXEMPTION']) }}"
                                class="btn btn-outline-primary {{ $isPt ? 'active' : '' }} {{ ($readOnly ?? false) || ($application ?? null) ? 'disabled pe-none' : '' }}">
                                PT Exemption
                            </a>
                            <a href="{{ route('leave.apply', ['leave_type' => 'STATIONED_LEAVE']) }}"
                                class="btn btn-outline-primary {{ ! $isPt ? 'active' : '' }} {{ ($readOnly ?? false) || ($application ?? null) ? 'disabled pe-none' : '' }}">
                                Stationed Leave
                            </a>
                        </div>

                        <input type="hidden" name="leave_type" value="{{ $selectedLeaveType }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nature of Leave <span class="text-danger">*</span></label>
                                <select name="leave_nature_master_pk" class="form-select" required {{ ($readOnly ?? false) ? 'disabled' : '' }}>
                                    <option value="">Select</option>
                                    @foreach($natures as $nature)
                                        <option value="{{ $nature->pk }}"
                                            {{ (string) old('leave_nature_master_pk', $application->leave_nature_master_pk ?? '') === (string) $nature->pk ? 'selected' : '' }}>
                                            {{ $nature->nature_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="from_date" id="from_date" class="form-control" required
                                    value="{{ old('from_date', isset($application) ? $application->from_date?->format('Y-m-d') : '') }}"
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="to_date" id="to_date" class="form-control" required
                                    value="{{ old('to_date', isset($application) ? $application->to_date?->format('Y-m-d') : '') }}"
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Total Days</label>
                                <input type="text" id="total_days_display" class="form-control" readonly
                                    value="{{ old('total_days', isset($application) ? number_format((float) $application->total_days, 1) : '0') }}">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Reason For Exemption <span class="text-danger">*</span></label>
                                <textarea name="reason" rows="3" class="form-control" required
                                    placeholder="Crack Muscles, Sprain Muscles, Fever..."
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>{{ old('reason', $application->reason ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact No. during Exemption</label>
                                <input type="text" name="contact_number" class="form-control" maxlength="15"
                                    value="{{ old('contact_number', $application->contact_number ?? '') }}"
                                    {{ ($readOnly ?? false) ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h3 class="h6 fw-semibold mb-2">Attachment (Optional)</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered attachment-table mb-2">
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
                                                            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment-row">
                                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
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
                                <div class="text-end mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-attachment-row">+ Add More</button>
                                </div>
                            @endif
                        </div>

                        @if($isPt)
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="pt-balance-card p-3 h-100">
                                    <div class="small text-muted">PT Balance</div>
                                    <div class="pt-balance-value">{{ number_format($ptBalance['remaining'], 1) }} Days</div>
                                    <div class="small text-muted">As on {{ $ptBalance['as_on'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-alert p-3 h-100 small">
                                    You can edit or delete the leave application until it is approved by the authority.
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(!($readOnly ?? false))
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <a href="{{ route('leave.my-leave') }}" class="btn btn-light border">Cancel</a>
                            <button type="submit" name="submit_action" value="draft" class="btn btn-outline-primary">Save As Draft</button>
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary">Submit</button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
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
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-attachment-row"><i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i></button></td>
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
