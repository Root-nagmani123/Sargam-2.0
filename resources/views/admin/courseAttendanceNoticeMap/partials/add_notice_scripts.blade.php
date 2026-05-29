<script>
(function () {
    function initMnmAddNoticeWizard() {
        if (typeof jQuery === 'undefined' || !document.getElementById('mnmAddNoticeForm')) {
            return;
        }
        var $ = jQuery;

        if ($('#mnmAddNoticeForm').data('mnmWizardBound')) {
            return;
        }
        $('#mnmAddNoticeForm').data('mnmWizardBound', true);

        var $modal = $('#mnmAddNoticeModal');
        var currentStep = 1;
        var $step1 = $('#mnmWizardStep1');
        var $step2 = $('#mnmWizardStep2');
        var $progressFill = $('#mnmWizardProgressFill');
        var $progressBar = $('#mnmWizardProgressBar');
        var $percent = $('#mnmWizardPercent');
        var $next = $('#mnmWizardNext');
        var $submit = $('#mnmWizardSubmit');
        var $cancelDismiss = $('#mnmWizardCancel');
        var $cancelBack = $('#mnmWizardBack');
        var $studentModal = $('#mnmStudentListModal');
        var $modalHost = $('#mnmDualListboxModalHost');
        var $mount = $('#mnmDualListboxMount');
        var dualMoved = false;

        function setLockedSelect($select, placeholder, value, label) {
            $select.empty().append(
                $('<option>', { value: value || '', text: label || placeholder })
            );
            $select.removeClass('is-invalid');
        }

        function resetLockedSelects() {
            setLockedSelect($('#mnm_add_venue_select'), 'Select Venue');
            setLockedSelect($('#mnm_add_faculty_select'), 'Select Faculty');
            $('#mnm_add_venue_id, #mnm_add_faculty_master_pk').val('');
        }

        function initAddNoticeDatePicker() {
            if (typeof flatpickr === 'undefined') {
                return;
            }
            var el = document.getElementById('mnm_add_date_memo_notice');
            if (!el) {
                return;
            }
            if (window.mnmAddNoticeDatePicker) {
                try {
                    window.mnmAddNoticeDatePicker.destroy();
                } catch (e) {}
            }
            var today = new Date();
            window.mnmAddNoticeDatePicker = flatpickr(el, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                defaultDate: today,
                maxDate: today,
                disableMobile: true,
                allowInput: false
            });
        }

        function resetAddNoticeForm() {
            var form = document.getElementById('mnmAddNoticeForm');
            if (form) {
                form.reset();
            }
            $('#mnm_add_subject_master_id').html('<option value="">Select Subject</option>');
            $('#mnm_add_topic_id').html('<option value="">Select Topic</option>');
            resetLockedSelects();
            $('#mnm_add_session_name, #mnm_add_session_name_end_display, #mnm_add_class_session_master_pk').val('');
            $('#select_memo_student').empty();
            $modalHost.find('.dual-listbox').remove();
            $mount.find('.dual-listbox').remove();
            if (typeof window.dualListbox !== 'undefined' && window.dualListbox) {
                try {
                    window.dualListbox.destroy();
                } catch (e) {}
                window.dualListbox = undefined;
            }
            dualMoved = false;
            $('#mnmSelectedStudentsBar').addClass('d-none');
            $('#mnmSelectedPills').empty();
            $('#mnmStudentTriggerLabel').text('Select Students');
            initAddNoticeDatePicker();
            setWizardStep(1);
        }

        function setWizardStep(step) {
            currentStep = step;
            var isStep1 = step === 1;
            $step1.toggleClass('d-none', !isStep1);
            $step2.toggleClass('d-none', isStep1);
            var pct = isStep1 ? 50 : 100;
            $progressFill.css('width', pct + '%');
            $progressBar.attr('aria-valuenow', pct);
            $percent.text(pct + '%');
            $next.toggleClass('d-none', !isStep1);
            $submit.toggleClass('d-none', isStep1);
            $cancelDismiss.toggleClass('d-none', !isStep1);
            $cancelBack.toggleClass('d-none', isStep1);
        }

        function validateStep1() {
            var form = document.getElementById('mnmAddNoticeForm');
            var fields = form.querySelectorAll('#mnmWizardStep1 [required]');
            var valid = true;
            fields.forEach(function (field) {
                if (!field.checkValidity()) {
                    valid = false;
                    field.reportValidity();
                }
            });
            if (!$('#mnm_add_venue_id').val()) {
                valid = false;
                $('#mnm_add_venue_select').addClass('is-invalid');
            } else {
                $('#mnm_add_venue_select').removeClass('is-invalid');
            }
            if (!$('#mnm_add_faculty_master_pk').val()) {
                valid = false;
                $('#mnm_add_faculty_select').addClass('is-invalid');
            } else {
                $('#mnm_add_faculty_select').removeClass('is-invalid');
            }
            return valid;
        }

        $next.on('click', function () {
            if (validateStep1()) {
                setWizardStep(2);
            }
        });

        $cancelBack.on('click', function (e) {
            e.preventDefault();
            setWizardStep(1);
        });

        function moveDualListboxToModal() {
            var $dual = $mount.find('.dual-listbox');
            if (!$dual.length) {
                $dual = $modal.find('.dual-listbox').first();
            }
            if ($dual.length && !dualMoved) {
                $modalHost.append($dual);
                dualMoved = true;
            }
        }

        function moveDualListboxBack() {
            var $dual = $modalHost.find('.dual-listbox');
            if ($dual.length && dualMoved) {
                $mount.prepend($dual);
                dualMoved = false;
            }
        }

        $studentModal.on('show.bs.modal', moveDualListboxToModal);
        $studentModal.on('hidden.bs.modal', function () {
            moveDualListboxBack();
            updateStudentSummary();
        });

        function updateStudentSummary() {
            var $select = $('#select_memo_student');
            var selected = $select.find('option:selected');
            var count = selected.length;
            var $bar = $('#mnmSelectedStudentsBar');
            var $pills = $('#mnmSelectedPills');
            var $label = $('#mnmStudentTriggerLabel');

            if (count > 0) {
                $bar.removeClass('d-none');
                $('#mnmSelectedCount').text(count + ' Selected');
                $label.text(count + ' student(s) selected');
                $pills.empty();
                selected.each(function () {
                    var id = $(this).val();
                    var text = $(this).text();
                    $pills.append(
                        '<span class="badge rounded-pill mnm-student-pill">' +
                        $('<span>').text(text).html() +
                        '<button type="button" class="btn-close btn-close-sm ms-1" data-student-id="' + id + '" aria-label="Remove"></button></span>'
                    );
                });
            } else {
                $bar.addClass('d-none');
                $pills.empty();
                $label.text('Select Students');
            }
        }

        $(document).on('click', '#mnmAddNoticeModal .mnm-student-pill .btn-close', function () {
            var id = $(this).data('student-id');
            $('#select_memo_student option[value="' + id + '"]').prop('selected', false);
            var dl = window.dualListbox;
            if (dl) {
                try {
                    dl.redraw();
                } catch (e) {}
            }
            updateStudentSummary();
        });

        $('#mnmStudentSave').on('click', updateStudentSummary);

        function getDualListbox() {
            return window.dualListbox;
        }

        function initAddNoticeDualListbox() {
            var selectElement = document.getElementById('select_memo_student');
            if (!selectElement || typeof DualListbox === 'undefined') {
                return;
            }
            if (window.dualListbox) {
                try {
                    window.dualListbox.destroy();
                } catch (e) {}
            }
            $modalHost.find('.dual-listbox').remove();
            $mount.find('.dual-listbox').remove();
            window.dualListbox = new DualListbox('#select_memo_student', {
                availableTitle: 'Defaulter Students',
                selectedTitle: 'Selected Students',
                addButtonText: 'Move Right',
                removeButtonText: 'Move Left',
                addAllButtonText: 'Move All Right',
                removeAllButtonText: 'Move All Left',
                draggable: true
            });
        }

        $('#mnmStudentSelectAll').on('click', function () {
            var dl = getDualListbox();
            if (dl) {
                dl.actionAllSelected();
            }
        });

        $('#mnmStudentClearAll').on('click', function () {
            var dl = getDualListbox();
            if (dl) {
                dl.actionAllDeselected();
            }
        });

        $(document).on('change', '#select_memo_student', updateStudentSummary);

        $('#courseSelect').off('change.mnmAddNotice').on('change.mnmAddNotice', function () {
            var courseId = $(this).val();
            if (courseId) {
                $.ajax({
                    url: "{{ route('memo.notice.management.getSubjectByCourse') }}",
                    type: 'GET',
                    data: { course_id: courseId },
                    success: function (response) {
                        $('#mnm_add_subject_master_id').html(response);
                    },
                    error: function () {
                        $('#mnm_add_subject_master_id').html('<option value="">Error loading subjects</option>');
                    }
                });
            } else {
                $('#mnm_add_subject_master_id').html('<option value="">Select Subject</option>');
            }
        });

        $('#mnm_add_subject_master_id').off('change.mnmAddNotice').on('change.mnmAddNotice', function () {
            var subject_master_id = $(this).val();
            var courseId = $('#courseSelect').val();
            if (subject_master_id && courseId) {
                $.ajax({
                    url: "{{ route('memo.notice.management.getTopicBysubject') }}",
                    type: 'GET',
                    data: {
                        subject_master_id: subject_master_id,
                        course_id: courseId
                    },
                    success: function (response) {
                        $('#mnm_add_topic_id').html(response);
                    },
                    error: function () {
                        $('#mnm_add_topic_id').html('<option value="">Error loading topics</option>');
                    }
                });
            } else {
                $('#mnm_add_topic_id').html('<option value="">Select Topic</option>');
            }
            resetLockedSelects();
            $('#mnm_add_session_name, #mnm_add_session_name_end_display, #mnm_add_class_session_master_pk').val('');
        });

        $('#mnm_add_topic_id').off('change.mnmAddNoticeTimetable').on('change.mnmAddNoticeTimetable', function () {
            var topic_id = $(this).val();
            if (topic_id) {
                $.ajax({
                    url: "{{ route('memo.notice.management.gettimetableDetailsBytopic') }}",
                    type: 'GET',
                    data: { topic_id: topic_id },
                    success: function (response) {
                        if (response) {
                            var venueId = response.venue_id || '';
                            var venueName = response.venue_name || 'Select Venue';
                            var facultyId = response.faculty_master || '';
                            var facultyName = response.faculty_name || 'Select Faculty';
                            setLockedSelect($('#mnm_add_venue_select'), 'Select Venue', venueId, venueName);
                            setLockedSelect($('#mnm_add_faculty_select'), 'Select Faculty', facultyId, facultyName);
                            $('#mnm_add_venue_id').val(venueId);
                            $('#mnm_add_faculty_master_pk').val(facultyId);
                            $('#mnm_add_session_name').val(response.shift_name || '');
                            $('#mnm_add_session_name_end_display').val(response.shift_name || '');
                            $('#mnm_add_class_session_master_pk').val(response.shift_name || '');
                        } else {
                            resetLockedSelects();
                            $('#mnm_add_session_name, #mnm_add_session_name_end_display, #mnm_add_class_session_master_pk').val('');
                        }
                    },
                    error: function () {
                        alert('Error fetching timetable details.');
                    }
                });
            } else {
                resetLockedSelects();
                $('#mnm_add_session_name, #mnm_add_session_name_end_display, #mnm_add_class_session_master_pk').val('');
            }
        });

        $('#mnm_add_topic_id').off('change.mnmAddNoticeStudents').on('change.mnmAddNoticeStudents', function () {
            var topic_id = $(this).val();
            if (!topic_id || typeof routes === 'undefined' || !routes.getStudentAttendanceBytopic) {
                return;
            }
            $.ajax({
                url: routes.getStudentAttendanceBytopic,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    topic_id: topic_id
                },
                success: function (response) {
                    if (!response.status) {
                        alert(response.message || 'Error fetching student list.');
                        return;
                    }
                    var currentSelected = $('#select_memo_student').val() || [];
                    $('#select_memo_student').empty();
                    if (response.students && response.students.length > 0) {
                        response.students.forEach(function (student) {
                            var isSelected = currentSelected.indexOf(String(student.pk)) !== -1;
                            $('#select_memo_student').append(
                                $('<option>', {
                                    value: student.pk,
                                    text: student.display_name,
                                    selected: isSelected
                                })
                            );
                        });
                    }
                    initAddNoticeDualListbox();
                    updateStudentSummary();
                },
                error: function () {
                    alert('Error fetching defaulter students.');
                }
            });
        });

        $modal.on('shown.bs.modal', function () {
            if (!window.mnmAddNoticeDatePicker) {
                initAddNoticeDatePicker();
            }
            if (!window.dualListbox && document.getElementById('select_memo_student')) {
                initAddNoticeDualListbox();
            }
        });

        $modal.on('hidden.bs.modal', resetAddNoticeForm);

        if (new URLSearchParams(window.location.search).get('open_add_notice') === '1') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mnmAddNoticeModal')).show();
            if (window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('open_add_notice');
                window.history.replaceState({}, '', url);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMnmAddNoticeWizard);
    } else {
        initMnmAddNoticeWizard();
    }
})();
</script>
