{{-- Generate Unique Code: the modal and its script, shared by the OT list and one OT's codes.
     Needs $cohorts (active courses, each with its batches) and $activeSubjects; opened by
     #upcGenerateBtn. Course → Batch, as on Roll Numbers. --}}
<div class="modal fade" id="upcGenerateModal" tabindex="-1" aria-labelledby="upcGenerateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="upcGenerateForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="upcGenerateModalLabel">Generate Unique Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenCourse">Course<span class="coe-req">*</span></label>
                            <select id="upcGenCourse" name="course" class="form-select coe-control" required>
                                <option value="">Select Course</option>
                                @foreach ($cohorts as $cohort)
                                    <option value="{{ $cohort['key'] }}">{{ $cohort['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            {{-- The batches the course has OTs in, filled when a course is picked. --}}
                            <label class="coe-form-label" for="upcGenBatch">Batch<span class="coe-req">*</span></label>
                            <select id="upcGenBatch" name="batch" class="form-select coe-control" required disabled>
                                <option value="">Select Batch</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            {{-- Every active subject (Subject Master), independent of the course. --}}
                            <label class="coe-form-label" for="upcGenSubject">Subject<span class="coe-req">*</span></label>
                            <select id="upcGenSubject" name="subject" class="form-select coe-control" required>
                                <option value="">Select Subject</option>
                                @foreach ($activeSubjects as $subject)
                                    <option value="{{ $subject['id'] }}">{{ $subject['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenOtCount">Number of OTs</label>
                            <input type="text" id="upcGenOtCount" class="form-control coe-control" value="" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="upcGenPrefix">Unique Code Prefix<span class="coe-req">*</span></label>
                            <input type="text" id="upcGenPrefix" name="prefix" class="form-control coe-control"
                                   placeholder="eg. GS-01" maxlength="20" required autocomplete="off">
                        </div>
                        <div class="col-sm-6">
                            {{-- Text, not number: leading zeros set the width (0324 → UC-0324). --}}
                            <label class="coe-form-label" for="upcGenStart">Count Start from<span class="coe-req">*</span></label>
                            <input type="text" id="upcGenStart" name="count_start" class="form-control coe-control"
                                   placeholder="eg. 324" maxlength="9" inputmode="numeric" pattern="\d*" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <div class="form-text">
                                One code per OT of the course and batch for the chosen subject, given in name order. An OT that already has a code for the subject keeps it: generating again gives codes only to OTs without one, numbered on from the subject's last code.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="upcGenSubmit">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    'use strict';

    var URL_GENERATE = @json(route('admin.coe.unique-participant-code.generate'));
    var $modal   = $('#upcGenerateModal');
    var BATCHES  = @json($cohorts->mapWithKeys(fn ($c) => [$c['key'] => $c['batches']]));
    var $course  = $('#upcGenCourse');
    var $batch   = $('#upcGenBatch');
    var $subject = $('#upcGenSubject');
    var $prefix  = $('#upcGenPrefix');
    var $start   = $('#upcGenStart');

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    function errorText(xhr) {
        if (xhr.status === 419) { return 'Your session has expired. Reload the page and try again.'; }
        var j = xhr.responseJSON || {};
        if (j.errors) {
            var first = Object.keys(j.errors)[0];
            if (first && j.errors[first][0]) { return j.errors[first][0]; }
        }
        return j.message || 'The request failed. Please try again.';
    }

    $course.select2({ width: '100%', dropdownParent: $modal });
    $batch.select2({ width: '100%', dropdownParent: $modal });
    $subject.select2({ width: '100%', dropdownParent: $modal });

    function fillBatches(key) {
        var list = (key && BATCHES[key]) || [];
        $batch.find('option').not(':first').remove();
        list.forEach(function (b) { $batch.append($('<option>').val(b.batch).attr('data-count', b.count).text(b.batch)); });
        // One batch: pick it; several: let the user choose.
        $batch.prop('disabled', list.length === 0).val(list.length === 1 ? String(list[0].batch) : '').trigger('change');
    }

    $('#upcGenerateBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $modal.on('hidden.bs.modal', function () {
        document.getElementById('upcGenerateForm').reset();
        $course.val('').trigger('change.select2');
        fillBatches('');
        $subject.val('').trigger('change.select2');
        $('#upcGenOtCount').val('');
        $modal.find('.coe-field-error').remove();
        $modal.find('.is-invalid').removeClass('is-invalid');
    });

    $course.on('change', function () {
        fillBatches(this.value);
        if (this.value) { fieldError($course, ''); }
    });
    $batch.on('change', function () {
        var n = $batch.find(':selected').attr('data-count');
        $('#upcGenOtCount').val(this.value ? String(n || 0) : '');
        if (this.value) { fieldError($batch, ''); }
    });
    $subject.on('change', function () { if (this.value) { fieldError($subject, ''); } });
    $prefix.on('input', function () { if ($.trim(this.value)) { fieldError($prefix, ''); } });
    $start.on('input', function () { if ($.trim(this.value)) { fieldError($start, ''); } });

    $('#upcGenerateForm').on('submit', function (e) {
        e.preventDefault();
        var prefix = $.trim($prefix.val());
        var start  = $.trim($start.val());
        var ok = true;
        ok = fieldError($course, $course.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($batch, $batch.val() ? '' : 'Select a batch.') && ok;
        ok = fieldError($subject, $subject.val() ? '' : 'Select a subject.') && ok;
        ok = fieldError($prefix, prefix ? '' : 'Enter a prefix.') && ok;
        ok = fieldError($start, /^\d{1,9}$/.test(start) ? '' : 'Enter the start number in digits.') && ok;
        if (!ok) { return; }

        var $btn = $('#upcGenSubmit').prop('disabled', true);

        $.ajax({
            url: URL_GENERATE, type: 'POST', dataType: 'json',
            data: { course: $course.val(), batch: $batch.val(), subject: $subject.val(), prefix: prefix, count_start: start },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'Accept': 'application/json' }
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Generated', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Could not complete', text: errorText(xhr) });
        }).always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endpush
