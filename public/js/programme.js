document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('assistant-coordinators-container');
    const addBtn = document.getElementById('add-coordinator');
    const coordinatorSelect = document.querySelector('select[name="coursecoordinator"]');

    // Choices.js instance store (select element -> Choices instance)
    const choicesInstances = new WeakMap();

    function getChoicesInstance(selectEl) {
        return choicesInstances.get(selectEl) || null;
    }

    function initChoices(selectEl, opts) {
        if (!selectEl || typeof Choices === 'undefined') return null;
        const existing = choicesInstances.get(selectEl);
        if (existing) {
            existing.destroy();
            choicesInstances.delete(selectEl);
        }
        const options = Object.assign({
            searchEnabled: true,
            placeholderValue: opts && opts.placeholder ? opts.placeholder : 'Select...',
            itemSelectText: '',
            searchPlaceholderValue: 'Search...',
            shouldSort: true,
            classNames: {
                containerOuter: 'choices',
                containerInner: 'choices__inner',
                input: 'choices__input',
                inputCloned: 'choices__input--cloned',
                list: 'choices__list',
                listItems: 'choices__list--multiple',
                listSingle: 'choices__list--single',
                listDropdown: 'choices__list--dropdown',
                item: 'choices__item',
                itemSelectable: 'choices__item--selectable',
                itemDisabled: 'choices__item--disabled',
                itemChoice: 'choices__item--choice',
                placeholder: 'choices__item--placeholder',
                group: 'choices__group',
                groupHeading: 'choices__heading',
                button: 'choices__button'
            }
        }, opts || {});
        const instance = new Choices(selectEl, options);
        choicesInstances.set(selectEl, instance);
        return instance;
    }

    function getChoiceValue(selectEl) {
        const choice = choicesInstances.get(selectEl);
        if (choice) {
            const v = choice.getValue(true);
            return Array.isArray(v) ? (v[0] !== undefined ? v[0] : '') : (v || '');
        }
        return selectEl ? selectEl.value : '';
    }

    function setChoiceValue(selectEl, value) {
        const choice = choicesInstances.get(selectEl);
        if (choice) {
            choice.removeActiveItems();
            if (value) choice.setChoiceByValue(String(value));
            return;
        }
        if (selectEl) selectEl.value = value || '';
    }

    function destroyChoices(selectEl) {
        const choice = choicesInstances.get(selectEl);
        if (choice) {
            choice.destroy();
            choicesInstances.delete(selectEl);
        }
    }

    function hasChoices(selectEl) {
        return selectEl && choicesInstances.has(selectEl);
    }

    // Initialize Choices for Course Coordinator
    if (coordinatorSelect && coordinatorSelect.classList.contains('choices-select')) {
        initChoices(coordinatorSelect, {
            placeholderValue: 'Search and select coordinator...',
            searchPlaceholderValue: 'Search...'
        });
    }

    // Initialize Choices for existing Assistant Coordinator and Role dropdowns
    const programmeForm = document.querySelector('.programme-create form');
    if (programmeForm && typeof Choices !== 'undefined') {
        programmeForm.querySelectorAll('select.choices-select').forEach(function(select) {
            if (select.name === 'assistantcoursecoordinator[]') {
                initChoices(select, {
                    placeholderValue: 'Search and select assistant coordinator...',
                    searchPlaceholderValue: 'Search...'
                });
            } else if (select.name === 'assistant_coordinator_role[]') {
                initChoices(select, {
                    placeholderValue: 'Select Role',
                    searchPlaceholderValue: 'Search...'
                });
            } else if (select.name === 'supportingsection') {
                initChoices(select, {
                    placeholderValue: 'Select Supporting Section',
                    searchPlaceholderValue: 'Search...'
                });
            }
        });
    }

    if (!container || !addBtn) return;

    let coordinatorIndex = container.querySelectorAll('.assistant-coordinator-row').length || 1;

    function updateAssistantOptions() {
        const assistantSelects = container.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const coordinatorValue = coordinatorSelect ? getChoiceValue(coordinatorSelect) : '';

        const selectedAssistantValues = Array.from(assistantSelects)
            .map(function(sel) { return getChoiceValue(sel); })
            .filter(function(v) { return v !== null && v !== ''; });

        assistantSelects.forEach(function(selectEl) {
            const selfValue = getChoiceValue(selectEl);
            const toDisable = new Set(selectedAssistantValues.filter(function(v) { return v !== selfValue; }));
            if (coordinatorValue) toDisable.add(coordinatorValue);

            Array.from(selectEl.options).forEach(function(opt) {
                if (!opt.value) {
                    opt.disabled = false;
                    opt.hidden = false;
                    return;
                }
                const shouldDisable = toDisable.has(opt.value);
                opt.disabled = shouldDisable;
                opt.hidden = shouldDisable;
            });

            if (selfValue && (selectEl.options[selectEl.selectedIndex] && (selectEl.options[selectEl.selectedIndex].disabled || selectEl.options[selectEl.selectedIndex].hidden))) {
                setChoiceValue(selectEl, '');
            }
        });
    }

    addBtn.addEventListener('click', function() {
        const prototypeRow = container.querySelector('.assistant-coordinator-row');
        if (!prototypeRow) return;

        const prototypeAssistantSelect = prototypeRow.querySelector('select[name="assistantcoursecoordinator[]"]');
        const prototypeRoleSelect = prototypeRow.querySelector('select[name="assistant_coordinator_role[]"]');

        destroyChoices(prototypeAssistantSelect);
        destroyChoices(prototypeRoleSelect);

        const newRow = prototypeRow.cloneNode(true);
        newRow.setAttribute('data-index', coordinatorIndex);

        newRow.querySelectorAll('.choices').forEach(function(el) { el.remove(); });

        const selects = newRow.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const roleSelects = newRow.querySelectorAll('select[name="assistant_coordinator_role[]"]');
        selects.forEach(function(sel) {
            sel.value = '';
            sel.classList.add('choices-select');
        });
        roleSelects.forEach(function(sel) {
            sel.value = '';
            sel.classList.add('choices-select');
        });

        if (prototypeAssistantSelect) {
            initChoices(prototypeAssistantSelect, {
                placeholderValue: 'Search and select assistant coordinator...',
                searchPlaceholderValue: 'Search...'
            });
        }
        if (prototypeRoleSelect) {
            initChoices(prototypeRoleSelect, { placeholderValue: 'Select Role', searchPlaceholderValue: 'Search...' });
        }

        container.appendChild(newRow);
        coordinatorIndex++;

        const newAssistantSelect = newRow.querySelector('select[name="assistantcoursecoordinator[]"]');
        if (newAssistantSelect) {
            initChoices(newAssistantSelect, {
                placeholderValue: 'Search and select assistant coordinator...',
                searchPlaceholderValue: 'Search...'
            });
        }

        const newRoleSelect = newRow.querySelector('select[name="assistant_coordinator_role[]"]');
        if (newRoleSelect) {
            initChoices(newRoleSelect, { placeholderValue: 'Select Role', searchPlaceholderValue: 'Search...' });
        }

        updateAssistantOptions();
    });

    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-coordinator');
        if (!removeBtn) return;

        const row = removeBtn.closest('.assistant-coordinator-row');
        if (!row) return;

        if (container.children.length > 1) {
            row.remove();
            updateAssistantOptions();
        } else {
            alert('At least one assistant coordinator is required.');
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('select[name="assistantcoursecoordinator[]"]')) {
            updateAssistantOptions();
        }
    });

    if (coordinatorSelect) {
        coordinatorSelect.addEventListener('change', function() {
            updateAssistantOptions();
        });
    }

    updateAssistantOptions();

    // Date validation
    const startDateInput = document.querySelector('input[name="startdate"]');
    const endDateInput = document.querySelector('input[name="enddate"]');
    const form = document.querySelector('form[action*="programme.store"]');

    function updateEndDateMin() {
        if (startDateInput && endDateInput) {
            const startDateValue = startDateInput.value;
            if (startDateValue) {
                const startDate = new Date(startDateValue);
                startDate.setDate(startDate.getDate() + 1);
                const minDate = startDate.toISOString().split('T')[0];
                endDateInput.setAttribute('min', minDate);

                if (endDateInput.value && endDateInput.value <= startDateValue) {
                    endDateInput.value = '';
                    endDateInput.setCustomValidity('The end date must be greater than the start date.');
                } else {
                    endDateInput.setCustomValidity('');
                }
            } else {
                endDateInput.removeAttribute('min');
            }
        }
    }

    function validateDates() {
        if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (endDate <= startDate) {
                endDateInput.setCustomValidity('The end date must be greater than the start date.');
                return false;
            }
            endDateInput.setCustomValidity('');
            return true;
        }
        return true;
    }

    if (startDateInput) {
        startDateInput.addEventListener('change', function() { updateEndDateMin(); validateDates(); });
        startDateInput.addEventListener('input', function() { updateEndDateMin(); });
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', function() { validateDates(); });
        endDateInput.addEventListener('input', function() { validateDates(); });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                endDateInput.focus();
                if (!endDateInput.reportValidity) {
                    alert('The end date must be greater than the start date.');
                }
                return false;
            }
        });
    }

    updateEndDateMin();
});
