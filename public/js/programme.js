document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('assistant-coordinators-container');
    const addBtn = document.getElementById('add-coordinator');
    const coordinatorSelect = document.querySelector('select[name="coursecoordinator"]');
    const rowTemplate = container ? container.querySelector('.assistant-coordinator-row')?.cloneNode(true) : null;
    const choicesInstances = new WeakMap();

    const choicesClassNames = {
        containerOuter: ['choices', 'w-100'],
        containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
        input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
        inputCloned: ['choices__input--cloned'],
        list: ['choices__list'],
        listItems: ['choices__list--multiple'],
        listSingle: ['choices__list--single'],
        listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
        item: ['choices__item', 'dropdown-item', 'rounded-0'],
        itemSelectable: ['choices__item--selectable'],
        itemDisabled: ['choices__item--disabled', 'disabled'],
        itemChoice: ['choices__item--choice'],
        description: ['choices__description', 'small', 'text-muted'],
        placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
        group: ['choices__group'],
        groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
        button: ['choices__button'],
        activeState: ['is-active'],
        focusState: ['is-focused'],
        openState: ['is-open'],
        disabledState: ['is-disabled'],
        highlightedState: ['is-highlighted', 'active'],
        flippedState: ['is-flipped'],
        loadingState: ['is-loading'],
        invalidState: ['is-invalid'],
        notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
        addChoice: ['choices__item--selectable', 'add-choice'],
        noResults: ['has-no-results'],
        noChoices: ['has-no-choices']
    };

    function getPlaceholder(selectEl) {
        if (selectEl.name === 'coursecoordinator') {
            return 'Search and select coordinator...';
        }
        if (selectEl.name === 'assistantcoursecoordinator[]') {
            return 'Search and select assistant coordinator...';
        }
        return selectEl.getAttribute('placeholder') || 'Search and select...';
    }

    function initChoicesForSelect(selectEl) {
        if (!selectEl || typeof Choices === 'undefined') {
            return;
        }
        if (choicesInstances.has(selectEl)) {
            choicesInstances.get(selectEl).destroy();
            choicesInstances.delete(selectEl);
        }

        const instance = new Choices(selectEl, {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            searchPlaceholderValue: getPlaceholder(selectEl),
            classNames: choicesClassNames
        });

        choicesInstances.set(selectEl, instance);
    }

    function initAllDropdownChoices(scope) {
        if (typeof Choices === 'undefined') {
            return;
        }
        const root = scope || document;
        const selects = root.querySelectorAll('select.searchable-dropdown');
        selects.forEach(function (selectEl) {
            initChoicesForSelect(selectEl);
        });
    }

    function reinitAssistantChoices() {
        if (!container) {
            return;
        }
        const assistantSelects = container.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        assistantSelects.forEach(function (selectEl) {
            const selectedValue = selectEl.value;
            initChoicesForSelect(selectEl);
            if (selectedValue) {
                selectEl.value = selectedValue;
                selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    function updateAssistantOptions() {
        if (!container) {
            return;
        }

        const assistantSelects = container.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const coordinatorValue = coordinatorSelect ? coordinatorSelect.value : '';

        const selectedAssistantValues = Array.from(assistantSelects)
            .map(function (sel) { return sel.value; })
            .filter(function (value) { return value !== null && value !== ''; });

        assistantSelects.forEach(function (selectEl) {
            const selfValue = selectEl.value;
            const toDisable = new Set(selectedAssistantValues.filter(function (value) { return value !== selfValue; }));

            if (coordinatorValue) {
                toDisable.add(coordinatorValue);
            }

            Array.from(selectEl.options).forEach(function (optionEl) {
                if (!optionEl.value) {
                    optionEl.disabled = false;
                    optionEl.hidden = false;
                    return;
                }
                const shouldDisable = toDisable.has(optionEl.value);
                optionEl.disabled = shouldDisable;
                optionEl.hidden = shouldDisable;
            });

            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (selfValue && selectedOption && (selectedOption.disabled || selectedOption.hidden)) {
                selectEl.value = '';
            }
        });

        reinitAssistantChoices();
    }

    initAllDropdownChoices(document);

    if (container && addBtn && rowTemplate) {
        let coordinatorIndex = container.querySelectorAll('.assistant-coordinator-row').length || 1;

        addBtn.addEventListener('click', function () {
            const newRow = rowTemplate.cloneNode(true);
            newRow.setAttribute('data-index', coordinatorIndex);

            const assistantSelect = newRow.querySelector('select[name="assistantcoursecoordinator[]"]');
            const roleSelect = newRow.querySelector('select[name="assistant_coordinator_role[]"]');

            if (assistantSelect) {
                assistantSelect.value = '';
                assistantSelect.id = 'assistant_coordinator_' + coordinatorIndex;
            }
            if (roleSelect) {
                roleSelect.value = '';
            }

            container.appendChild(newRow);
            coordinatorIndex += 1;

            initAllDropdownChoices(newRow);
            updateAssistantOptions();
        });

        document.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.remove-coordinator');
            if (!removeBtn) {
                return;
            }

            const row = removeBtn.closest('.assistant-coordinator-row');
            if (!row) {
                return;
            }

            if (container.children.length > 1) {
                const rowSelects = row.querySelectorAll('select.searchable-dropdown');
                rowSelects.forEach(function (selectEl) {
                    const instance = choicesInstances.get(selectEl);
                    if (instance) {
                        instance.destroy();
                        choicesInstances.delete(selectEl);
                    }
                });
                row.remove();
                updateAssistantOptions();
            } else {
                alert('At least one assistant coordinator is required.');
            }
        });

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches('select[name="assistantcoursecoordinator[]"]')) {
                updateAssistantOptions();
            }
        });
    }

    if (coordinatorSelect) {
        coordinatorSelect.addEventListener('change', function () {
            updateAssistantOptions();
        });
    }

    updateAssistantOptions();

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
        startDateInput.addEventListener('change', function () {
            updateEndDateMin();
            validateDates();
        });

        startDateInput.addEventListener('input', function () {
            updateEndDateMin();
        });
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', function () {
            validateDates();
        });

        endDateInput.addEventListener('input', function () {
            validateDates();
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!validateDates()) {
                event.preventDefault();
                endDateInput.focus();
                if (!endDateInput.reportValidity) {
                    alert('The end date must be greater than the start date.');
                }
                return false;
            }
            return true;
        });
    }

    updateEndDateMin();
});


