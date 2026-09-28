{{--
    Drives the cascading target-audience picker in audience_fields.blade.php.

    Expects $audiencePreset (see the create / edit views) — the selection to
    restore on load, which is the saved notice when editing and old() input after
    a failed validation. Without it every dropdown would reset to blank whenever
    the form bounced back with errors.
--}}
<script>
(function () {
    var ROUTES = {
        courses: @json(route('admin.notice.getCourses')),
        groupTypes: @json(route('admin.notice.getGroupTypes')),
        students: @json(route('admin.notice.getStudents')),
        employees: @json(route('admin.notice.getEmployees'))
    };

    var MODE_ALL = @json(\App\Models\NoticeNotification::MODE_ALL);
    var MODE_INDIVIDUAL = @json(\App\Models\NoticeNotification::MODE_INDIVIDUAL);

    var preset = @json($audiencePreset);

    // Consumed once. After the first load the user's own clicks drive the form,
    // so a later cascade must not silently re-apply a stale saved selection.
    var presetPending = true;

    var $target = $('#targetAudience');
    var $otBox = $('#otAudienceBox');
    var $staffBox = $('#staffAudienceBox');
    var $course = $('#courseSelect');
    var $groupBox = $('#otGroupBox');
    var $group = $('#otGroupSelect');
    var $studentBox = $('#studentBox');
    var $student = $('#studentSelect');
    var $previewBox = $('#otGroupPreviewBox');
    var $department = $('#departmentSelect');
    var $staffScopeBox = $('#staffScopeBox');
    var $staffScope = $('#staffScopeSelect');
    var $employeeBox = $('#employeeBox');
    var $employee = $('#employeeSelect');

    var choicesByEl = {};
    var coursesLoaded = false;

    function isOfficerTrainee(value) {
        return String(value).toLowerCase().indexOf('office trainee') !== -1;
    }

    function isStaffFaculty(value) {
        return String(value).toLowerCase().indexOf('staff/faculty') !== -1;
    }

    /* ---------------- multi-select helpers ---------------- */

    function multiSelect($el) {
        var id = $el.attr('id');

        if (typeof Choices === 'undefined') {
            return null;
        }

        if (!choicesByEl[id]) {
            choicesByEl[id] = new Choices($el[0], {
                removeItemButton: true,
                shouldSort: false,
                searchEnabled: true,
                searchResultLimit: 100,
                renderChoiceLimit: -1,
                itemSelectText: '',
                placeholder: true,
                placeholderValue: 'Search and select...',
                noChoicesText: 'No records found'
            });
        }

        return choicesByEl[id];
    }

    /**
     * Replace a multi-select's options and re-check `selected`.
     *
     * Values are compared as strings: the preset arrives from JSON as numbers on
     * a fresh edit but as strings after a validation bounce.
     */
    function fillMultiSelect($el, items, selected) {
        var picked = (selected || []).map(String);
        var instance = multiSelect($el);

        if (instance) {
            instance.clearStore();
            instance.setChoices(items.map(function (item) {
                return {
                    value: String(item.pk),
                    label: item.label,
                    selected: picked.indexOf(String(item.pk)) !== -1
                };
            }), 'value', 'label', true);
        } else {
            $el.empty();
            items.forEach(function (item) {
                $el.append(
                    $('<option></option>')
                        .val(String(item.pk))
                        .text(item.label)
                        .prop('selected', picked.indexOf(String(item.pk)) !== -1)
                );
            });
        }

        refreshCount($el);
    }

    function selectedValues($el) {
        return ($el.val() || []).map(String);
    }

    function refreshCount($el) {
        var count = selectedValues($el).length;
        var $badge = $el.attr('id') === 'studentSelect' ? $('#studentCount') : $('#employeeCount');
        $badge.text(count + ' selected');
    }

    function setAllOptions($el, selected) {
        var instance = choicesByEl[$el.attr('id')];

        if (instance) {
            var values = $el.find('option').map(function () {
                return this.value;
            }).get();

            if (selected) {
                instance.setChoiceByValue(values);
            } else {
                instance.removeActiveItems();
            }
        } else {
            $el.find('option').prop('selected', selected);
        }

        refreshCount($el);
    }

    function clearMultiSelect($el) {
        var instance = choicesByEl[$el.attr('id')];

        if (instance) {
            instance.clearStore();
        } else {
            $el.empty();
        }

        refreshCount($el);
    }

    function renderPreview(items) {
        var $preview = $('#otGroupPreview');
        $('#otGroupPreviewCount').text(items.length);
        $preview.empty();

        if (!items.length) {
            $preview.append($('<span class="text-muted small"></span>').text('No Officer Trainees mapped to this group.'));
            return;
        }

        items.forEach(function (item) {
            $preview.append($('<span class="audience-chip"></span>').text(item.label));
        });
    }

    /* ---------------- data loading ---------------- */

    function loadCourses(done) {
        if (coursesLoaded) {
            if (done) { done(); }
            return;
        }

        // `include` keeps the notice's saved course in the list even once the
        // course has ended — without it the dropdown would fall back to blank,
        // which silently means "all courses".
        var params = preset.course_master_pk ? { include: preset.course_master_pk } : {};

        $.getJSON(ROUTES.courses, params, function (res) {
            $course.find('option:not(:first)').remove();

            (res.data || []).forEach(function (course) {
                $course.append($('<option></option>').val(String(course.pk)).text(course.course_name));
            });

            coursesLoaded = true;

            if (done) { done(); }
        });
    }

    function loadGroupTypes(courseId, done) {
        // Rebuilt from scratch every time so groups from a previously selected
        // course cannot linger as selectable options.
        $group.find('option').slice(2).remove();

        if (!courseId) {
            if (done) { done(); }
            return;
        }

        $.getJSON(ROUTES.groupTypes, { course_master_pk: courseId }, function (res) {
            (res.data || []).forEach(function (group) {
                $group.append($('<option></option>').val(String(group.pk)).text(group.label));
            });

            if (done) { done(); }
        });
    }

    function loadStudents(courseId, groupId, selected, mode) {
        var params = { course_master_pk: courseId };

        if (groupId) {
            params.group_type_map_pk = groupId;
        }

        $.getJSON(ROUTES.students, params, function (res) {
            var items = res.data || [];

            if (mode === 'preview') {
                renderPreview(items);
            } else {
                fillMultiSelect($student, items, selected);
            }
        });
    }

    function loadEmployees(departmentId, selected) {
        $.getJSON(ROUTES.employees, { department_master_pk: departmentId }, function (res) {
            fillMultiSelect($employee, res.data || [], selected);
        });
    }

    /* ---------------- cascade ---------------- */

    function applyOtGroupSelection(selected) {
        var courseId = $course.val();
        var selection = String($group.val() || MODE_ALL);

        $studentBox.addClass('d-none');
        $previewBox.addClass('d-none');

        if (!courseId) {
            clearMultiSelect($student);
            return;
        }

        if (selection === MODE_INDIVIDUAL) {
            $('#studentBoxLabel').text('Select Officer Trainees');
            $studentBox.removeClass('d-none');
            loadStudents(courseId, null, selected, 'individual');
            return;
        }

        clearMultiSelect($student);

        if (selection !== MODE_ALL) {
            $previewBox.removeClass('d-none');
            loadStudents(courseId, selection, [], 'preview');
        }
    }

    function applyCourseSelection(presetGroup, presetStudents) {
        var courseId = $course.val();

        if (!courseId) {
            $groupBox.addClass('d-none');
            $studentBox.addClass('d-none');
            $previewBox.addClass('d-none');
            $group.val(MODE_ALL);
            clearMultiSelect($student);
            return;
        }

        $groupBox.removeClass('d-none');

        loadGroupTypes(courseId, function () {
            var wanted = presetGroup ? String(presetGroup) : MODE_ALL;

            // A saved group that no longer maps to the course falls back to All
            // rather than leaving the dropdown on a value the form cannot submit.
            if (!$group.find('option[value="' + wanted.replace(/"/g, '\\"') + '"]').length) {
                wanted = MODE_ALL;
            }

            $group.val(wanted);
            applyOtGroupSelection(presetStudents || []);
        });
    }

    function applyStaffScope(selected) {
        var departmentId = $department.val();

        if (!departmentId) {
            $staffScopeBox.addClass('d-none');
            $employeeBox.addClass('d-none');
            $staffScope.val(MODE_ALL);
            clearMultiSelect($employee);
            return;
        }

        $staffScopeBox.removeClass('d-none');

        if (String($staffScope.val()) === MODE_INDIVIDUAL) {
            $employeeBox.removeClass('d-none');
            loadEmployees(departmentId, selected || []);
        } else {
            $employeeBox.addClass('d-none');
            clearMultiSelect($employee);
        }
    }

    function applyTargetAudience() {
        var value = $target.val();
        var usePreset = presetPending;
        presetPending = false;

        if (isOfficerTrainee(value)) {
            $otBox.removeClass('d-none');
            $staffBox.addClass('d-none');
            $department.val('');
            applyStaffScope([]);

            loadCourses(function () {
                if (usePreset && preset.course_master_pk) {
                    var saved = String(preset.course_master_pk);

                    // A course the list cannot offer would leave the dropdown
                    // blank, i.e. silently retarget the notice at every course.
                    if (!$course.find('option[value="' + saved + '"]').length) {
                        $course.append($('<option></option>').val(saved).text('Course #' + saved + ' (not listed)'));
                    }

                    $course.val(saved);
                }

                applyCourseSelection(
                    usePreset ? preset.ot_group_selection : null,
                    usePreset ? preset.student_pks : []
                );
            });

            return;
        }

        $otBox.addClass('d-none');
        $course.val('');
        $group.val(MODE_ALL);
        $groupBox.addClass('d-none');
        $studentBox.addClass('d-none');
        $previewBox.addClass('d-none');
        clearMultiSelect($student);

        if (isStaffFaculty(value)) {
            $staffBox.removeClass('d-none');

            if (usePreset && preset.department_master_pk) {
                $department.val(String(preset.department_master_pk));
            }

            if (usePreset && preset.staff_scope) {
                $staffScope.val(String(preset.staff_scope));
            }

            applyStaffScope(usePreset ? preset.employee_pks : []);
            return;
        }

        $staffBox.addClass('d-none');
        $department.val('');
        $staffScope.val(MODE_ALL);
        $staffScopeBox.addClass('d-none');
        $employeeBox.addClass('d-none');
        clearMultiSelect($employee);
    }

    /* ---------------- wiring ---------------- */

    $target.on('change', applyTargetAudience);
    $course.on('change', function () { applyCourseSelection(null, []); });
    $group.on('change', function () { applyOtGroupSelection([]); });
    $department.on('change', function () {
        $staffScope.val(MODE_ALL);
        applyStaffScope([]);
    });
    $staffScope.on('change', function () { applyStaffScope([]); });

    $(document).on('change', '#studentSelect, #employeeSelect', function () {
        refreshCount($(this));
    });

    $(document).on('click', '.js-audience-select-all', function () {
        setAllOptions($('#' + $(this).data('target')), true);
    });

    $(document).on('click', '.js-audience-clear', function () {
        setAllOptions($('#' + $(this).data('target')), false);
    });

    if (preset.target_audience) {
        $target.val(preset.target_audience);
    }

    applyTargetAudience();
})();
</script>
