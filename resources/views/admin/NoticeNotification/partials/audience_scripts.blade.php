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
    var MODE_GROUP = @json(\App\Models\NoticeNotification::MODE_GROUP);
    var MODE_INDIVIDUAL = @json(\App\Models\NoticeNotification::MODE_INDIVIDUAL);

    var preset = @json($audiencePreset);

    // Consumed once. After the first load the user's own clicks drive the form,
    // so a later cascade must not silently re-apply a stale saved selection.
    var presetPending = true;

    var $target = $('#targetAudience');
    var $otBox = $('#otAudienceBox');
    var $staffBox = $('#staffAudienceBox');
    var $course = $('#courseSelect');
    var $otScopeBox = $('#otScopeBox');
    var $otScope = $('#otScopeSelect');
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

    // {pk, label} lists backing the two selects whose options do not change with
    // the cascade. Courses arrive by AJAX once; departments are rendered by the
    // server, so they are read out of the DOM before Choices takes the element
    // over (after that the original <option>s are Choices' own bookkeeping).
    var courseItems = null;
    var departmentItems = $department.find('option').map(function () {
        return { pk: this.value, label: $(this).text() };
    }).get();

    var COUNT_BADGES = {
        courseSelect: '#courseCount',
        otGroupSelect: '#otGroupCount',
        studentSelect: '#studentCount',
        departmentSelect: '#departmentCount',
        employeeSelect: '#employeeCount'
    };

    function isOfficerTrainee(value) {
        return String(value).toLowerCase().indexOf('office trainee') !== -1;
    }

    function isStaffFaculty(value) {
        return String(value).toLowerCase().indexOf('staff/faculty') !== -1;
    }

    function asStrings(list) {
        return (list || []).map(String);
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
        var picked = asStrings(selected);
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
        return asStrings($el.val() || []);
    }

    function refreshCount($el) {
        var badge = COUNT_BADGES[$el.attr('id')];
        if (badge) {
            $(badge).text(selectedValues($el).length + ' selected');
        }
    }

    function setAllOptions($el, selected) {
        var instance = choicesByEl[$el.attr('id')];

        if (instance) {
            if (selected) {
                instance.setChoiceByValue($el.find('option').map(function () {
                    return this.value;
                }).get());
            } else {
                instance.removeActiveItems();
            }
        } else {
            $el.find('option').prop('selected', selected);
        }

        refreshCount($el);
        $el.trigger('change');
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
            $preview.append($('<span class="text-muted small"></span>').text('No Officer Trainees mapped to the selected group(s).'));
            return;
        }

        items.forEach(function (item) {
            $preview.append($('<span class="audience-chip"></span>').text(item.label));
        });
    }

    /* ---------------- data loading ---------------- */

    function loadCourses(done) {
        if (courseItems) {
            done(courseItems);
            return;
        }

        // `include` keeps the notice's saved courses in the list even once they
        // have ended — without it they would silently drop out of the selection
        // the next time the notice was saved.
        var params = {};
        var saved = asStrings(preset.course_master_pks || []);

        if (saved.length) {
            params.include = saved;
        }

        $.getJSON(ROUTES.courses, params, function (res) {
            courseItems = (res.data || []).map(function (course) {
                return { pk: String(course.pk), label: course.course_name };
            });

            // Anything the endpoint still could not offer gets a placeholder, so
            // the saved selection survives a re-save instead of vanishing.
            var known = courseItems.map(function (item) { return item.pk; });
            saved.forEach(function (pk) {
                if (known.indexOf(pk) === -1) {
                    courseItems.push({ pk: pk, label: 'Course #' + pk + ' (not listed)' });
                }
            });

            done(courseItems);
        });
    }

    function loadGroupTypes(courseIds, selected, done) {
        if (!courseIds.length) {
            clearMultiSelect($group);
            if (done) { done(); }
            return;
        }

        $.getJSON(ROUTES.groupTypes, { course_master_pks: courseIds }, function (res) {
            fillMultiSelect($group, res.data || [], selected);
            if (done) { done(); }
        });
    }

    function loadStudents(courseIds, groupIds, selected, mode) {
        if (!courseIds.length) {
            return;
        }

        var params = { course_master_pks: courseIds };

        if (groupIds && groupIds.length) {
            params.group_type_map_pks = groupIds;
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

    function loadEmployees(departmentIds, selected) {
        if (!departmentIds.length) {
            return;
        }

        $.getJSON(ROUTES.employees, { department_master_pks: departmentIds }, function (res) {
            fillMultiSelect($employee, res.data || [], selected);
        });
    }

    /* ---------------- cascade ---------------- */

    function applyOtScope(presetGroups, presetStudents) {
        var courseIds = selectedValues($course);
        var scope = String($otScope.val() || MODE_ALL);

        $groupBox.addClass('d-none');
        $studentBox.addClass('d-none');
        $previewBox.addClass('d-none');

        if (!courseIds.length) {
            clearMultiSelect($group);
            clearMultiSelect($student);
            return;
        }

        if (scope === MODE_GROUP) {
            $groupBox.removeClass('d-none');
            clearMultiSelect($student);

            loadGroupTypes(courseIds, presetGroups || [], function () {
                var groupIds = selectedValues($group);

                if (groupIds.length) {
                    $previewBox.removeClass('d-none');
                    loadStudents(courseIds, groupIds, [], 'preview');
                }
            });
            return;
        }

        clearMultiSelect($group);

        if (scope === MODE_INDIVIDUAL) {
            $studentBox.removeClass('d-none');
            loadStudents(courseIds, null, presetStudents || [], 'individual');
            return;
        }

        clearMultiSelect($student);
    }

    function applyCourseSelection(presetScope, presetGroups, presetStudents) {
        var courseIds = selectedValues($course);

        if (!courseIds.length) {
            $otScopeBox.addClass('d-none');
            $groupBox.addClass('d-none');
            $studentBox.addClass('d-none');
            $previewBox.addClass('d-none');
            $otScope.val(MODE_ALL);
            clearMultiSelect($group);
            clearMultiSelect($student);
            return;
        }

        $otScopeBox.removeClass('d-none');

        if (presetScope) {
            $otScope.val(String(presetScope));
        }

        applyOtScope(presetGroups, presetStudents);
    }

    function applyStaffScope(selected) {
        var departmentIds = selectedValues($department);

        if (!departmentIds.length) {
            $staffScopeBox.addClass('d-none');
            $employeeBox.addClass('d-none');
            $staffScope.val(MODE_ALL);
            clearMultiSelect($employee);
            return;
        }

        $staffScopeBox.removeClass('d-none');

        if (String($staffScope.val()) === MODE_INDIVIDUAL) {
            $employeeBox.removeClass('d-none');
            loadEmployees(departmentIds, selected || []);
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
            setAllOptions($department, false);
            applyStaffScope([]);

            loadCourses(function (items) {
                fillMultiSelect($course, items, usePreset ? preset.course_master_pks : []);

                applyCourseSelection(
                    usePreset ? preset.ot_scope : null,
                    usePreset ? preset.group_type_map_pks : [],
                    usePreset ? preset.student_pks : []
                );
            });

            return;
        }

        $otBox.addClass('d-none');
        setAllOptions($course, false);
        $otScope.val(MODE_ALL);
        $otScopeBox.addClass('d-none');
        $groupBox.addClass('d-none');
        $studentBox.addClass('d-none');
        $previewBox.addClass('d-none');
        clearMultiSelect($group);
        clearMultiSelect($student);

        if (isStaffFaculty(value)) {
            $staffBox.removeClass('d-none');

            fillMultiSelect($department, departmentItems, usePreset ? (preset.department_master_pks || []) : []);

            if (usePreset && preset.staff_scope) {
                $staffScope.val(String(preset.staff_scope));
            }

            applyStaffScope(usePreset ? preset.employee_pks : []);
            return;
        }

        $staffBox.addClass('d-none');
        setAllOptions($department, false);
        $staffScope.val(MODE_ALL);
        $staffScopeBox.addClass('d-none');
        $employeeBox.addClass('d-none');
        clearMultiSelect($employee);
    }

    /* ---------------- wiring ---------------- */

    $target.on('change', applyTargetAudience);
    $course.on('change', function () {
        refreshCount($course);
        applyCourseSelection(null, [], []);
    });
    $otScope.on('change', function () { applyOtScope([], []); });
    $group.on('change', function () {
        refreshCount($group);

        var courseIds = selectedValues($course);
        var groupIds = selectedValues($group);

        if (groupIds.length) {
            $previewBox.removeClass('d-none');
            loadStudents(courseIds, groupIds, [], 'preview');
        } else {
            $previewBox.addClass('d-none');
        }
    });
    $department.on('change', function () {
        refreshCount($department);
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
