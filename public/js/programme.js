document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('assistant-coordinators-container');
    const addBtn = document.getElementById('add-coordinator');
    const coordinatorSelect = document.querySelector('select[name="coursecoordinator"]');

    // Initialize Select2 for Course Coordinator dropdown with search functionality
    if (coordinatorSelect && typeof DropdownSearch !== 'undefined') {
        DropdownSearch.init(coordinatorSelect, {
            placeholder: 'Search and select coordinator...',
            allowClear: true
        });
    }

    // Initialize Select2 for existing Assistant Coordinator dropdowns
    if (typeof DropdownSearch !== 'undefined') {
        const existingAssistantSelects = document.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        existingAssistantSelects.forEach(function(select) {
            DropdownSearch.init(select, {
                placeholder: 'Search and select assistant coordinator...',
                allowClear: false
            });
        });
    }

    if (!container || !addBtn) return;

    let coordinatorIndex = container.querySelectorAll('.assistant-coordinator-row').length || 1;

    function updateAssistantOptions() {
        const assistantSelects = container.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const $coordinatorSelect = $(coordinatorSelect);
        const isCoordinatorSelect2 = typeof DropdownSearch !== 'undefined' && coordinatorSelect && $coordinatorSelect.hasClass('select2-hidden-accessible');
        const coordinatorValue = coordinatorSelect ? (isCoordinatorSelect2 
            ? DropdownSearch.getValue(coordinatorSelect) 
            : coordinatorSelect.value) : '';

        const selectedAssistantValues = Array.from(assistantSelects)
            .map(function(sel) { 
                const $sel = $(sel);
                if (typeof DropdownSearch !== 'undefined' && $sel.hasClass('select2-hidden-accessible')) {
                    return DropdownSearch.getValue(sel);
                }
                return sel.value; 
            })
            .filter(function(v) { return v !== null && v !== ''; });

        assistantSelects.forEach(function(selectEl) {
            const $select = $(selectEl);
            const isSelect2 = typeof DropdownSearch !== 'undefined' && $select.hasClass('select2-hidden-accessible');
            const selfValue = isSelect2 ? DropdownSearch.getValue(selectEl) : selectEl.value;
            const toDisable = new Set(selectedAssistantValues.filter(function(v){ return v !== selfValue; }));
            if (coordinatorValue) {
                toDisable.add(coordinatorValue);
            }

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

            // If current selection becomes invalid due to coordinator change, reset
            if (selfValue && (selectEl.options[selectEl.selectedIndex]?.disabled || selectEl.options[selectEl.selectedIndex]?.hidden)) {
                if (isSelect2) {
                    DropdownSearch.setValue(selectEl, '', true);
                } else {
                    selectEl.value = '';
                }
            }

            // Trigger Select2 update if it's a Select2 instance
            if (isSelect2) {
                $select.trigger('change.select2');
            }
        });
    }

    // Add coordinator functionality
    addBtn.addEventListener('click', function() {
        const prototypeRow = container.querySelector('.assistant-coordinator-row');
        if (!prototypeRow) return;

        // Destroy Select2 instances on prototype row before cloning to avoid duplicating Select2 wrapper elements
        const prototypeAssistantSelect = prototypeRow.querySelector('select[name="assistantcoursecoordinator[]"]');
        const prototypeRoleSelect = prototypeRow.querySelector('select[name="assistant_coordinator_role[]"]');
        
        let prototypeAssistantSelect2Destroyed = false;
        let prototypeRoleSelect2Destroyed = false;
        
        if (typeof DropdownSearch !== 'undefined') {
            if (prototypeAssistantSelect && $(prototypeAssistantSelect).hasClass('select2-hidden-accessible')) {
                DropdownSearch.destroy(prototypeAssistantSelect);
                prototypeAssistantSelect2Destroyed = true;
            }
            if (prototypeRoleSelect && $(prototypeRoleSelect).hasClass('select2-hidden-accessible')) {
                DropdownSearch.destroy(prototypeRoleSelect);
                prototypeRoleSelect2Destroyed = true;
            }
        }

        // Clone the row (now without Select2 wrappers)
        const newRow = prototypeRow.cloneNode(true);
        newRow.setAttribute('data-index', coordinatorIndex);

        // Remove any Select2 containers that might have been cloned (safety check)
        if (typeof $ !== 'undefined') {
            $(newRow).find('.select2-container').remove();
            $(newRow).find('.select2-dropdown').remove();
        }

        // Clear values in cloned inputs/selects
        const selects = newRow.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const roleSelects = newRow.querySelectorAll('select[name="assistant_coordinator_role[]"]');
        selects.forEach(function(sel){ 
            sel.value = '';
            // Remove any Select2 classes that might have been cloned
            sel.classList.remove('select2-hidden-accessible');
        });
        roleSelects.forEach(function(sel){ 
            sel.value = '';
            sel.classList.remove('select2-hidden-accessible');
        });

        // Re-initialize Select2 on prototype row if it was destroyed
        if (typeof DropdownSearch !== 'undefined') {
            if (prototypeAssistantSelect2Destroyed && prototypeAssistantSelect) {
                DropdownSearch.init(prototypeAssistantSelect, {
                    placeholder: 'Search and select assistant coordinator...',
                    allowClear: false
                });
            }
            if (prototypeRoleSelect2Destroyed && prototypeRoleSelect) {
                DropdownSearch.init(prototypeRoleSelect);
            }
        }

        container.appendChild(newRow);
        coordinatorIndex++;

        // Initialize select2 for the new assistant coordinator dropdown using utility
        const newAssistantSelect = newRow.querySelector('select[name="assistantcoursecoordinator[]"]');
        if (newAssistantSelect && typeof DropdownSearch !== 'undefined') {
            DropdownSearch.init(newAssistantSelect, {
                placeholder: 'Search and select assistant coordinator...',
                allowClear: false
            });
        }

        // Initialize select2 for the new role dropdown using utility
        const newRoleSelect = newRow.querySelector('select[name="assistant_coordinator_role[]"]');
        if (newRoleSelect && typeof DropdownSearch !== 'undefined') {
            DropdownSearch.init(newRoleSelect);
        }

        updateAssistantOptions();
    });

    // Remove coordinator functionality
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-coordinator');
        if (!removeBtn) return;

        const row = removeBtn.closest('.assistant-coordinator-row');
        if (!row) return;

        // Don't allow removing the last coordinator
        if (container.children.length > 1) {
            row.remove();
            updateAssistantOptions();
        } else {
            alert('At least one assistant coordinator is required.');
        }
    });

    // Keep assistant selects in sync on changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('select[name="assistantcoursecoordinator[]"]')) {
            updateAssistantOptions();
        }
    });

    // Handle change events for Course Coordinator (Select2 triggers native change event)
    if (coordinatorSelect) {
        coordinatorSelect.addEventListener('change', function() {
            updateAssistantOptions();
        });
    }

    // Initial sync
    updateAssistantOptions();

    // Date validation: End date must be greater than start date
    const startDateInput = document.querySelector('input[name="startdate"]');
    const endDateInput = document.querySelector('input[name="enddate"]');
    const form = document.querySelector('form[action*="programme.store"]');

    function updateEndDateMin() {
        if (startDateInput && endDateInput) {
            const startDateValue = startDateInput.value;
            if (startDateValue) {
                // Set min attribute to start date + 1 day
                const startDate = new Date(startDateValue);
                startDate.setDate(startDate.getDate() + 1);
                const minDate = startDate.toISOString().split('T')[0];
                endDateInput.setAttribute('min', minDate);
                
                // If end date is already set and is before or equal to start date, clear it
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
            } else {
                endDateInput.setCustomValidity('');
                return true;
            }
        }
        return true;
    }

    // Update end date min when start date changes
    if (startDateInput) {
        startDateInput.addEventListener('change', function() {
            updateEndDateMin();
            validateDates();
        });
        
        // Also trigger on input for real-time feedback
        startDateInput.addEventListener('input', function() {
            updateEndDateMin();
        });
    }

    // Validate when end date changes
    if (endDateInput) {
        endDateInput.addEventListener('change', function() {
            validateDates();
        });
        
        endDateInput.addEventListener('input', function() {
            validateDates();
        });
    }

    // Validate on form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                endDateInput.focus();
                // Show error message
                if (!endDateInput.reportValidity) {
                    alert('The end date must be greater than the start date.');
                }
                return false;
            }
        });
    }

    // Initialize on page load
    updateEndDateMin();
});


