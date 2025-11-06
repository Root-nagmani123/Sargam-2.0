document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('assistant-coordinators-container');
    const addBtn = document.getElementById('add-coordinator');
    const coordinatorSelect = document.querySelector('select[name="coursecoordinator"]');

    if (!container || !addBtn) return;

    let coordinatorIndex = container.querySelectorAll('.assistant-coordinator-row').length || 1;

    function updateAssistantOptions() {
        const assistantSelects = container.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const coordinatorValue = coordinatorSelect ? coordinatorSelect.value : '';

        const selectedAssistantValues = Array.from(assistantSelects)
            .map(function(sel) { return sel.value; })
            .filter(function(v) { return v !== null && v !== ''; });

        assistantSelects.forEach(function(selectEl) {
            const selfValue = selectEl.value;
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
            if (selectEl.value && (selectEl.options[selectEl.selectedIndex]?.disabled || selectEl.options[selectEl.selectedIndex]?.hidden)) {
                selectEl.value = '';
            }
        });
    }

    // Add coordinator functionality
    addBtn.addEventListener('click', function() {
        const prototypeRow = container.querySelector('.assistant-coordinator-row');
        if (!prototypeRow) return;

        const newRow = prototypeRow.cloneNode(true);
        newRow.setAttribute('data-index', coordinatorIndex);

        // Clear values in cloned inputs/selects
        const selects = newRow.querySelectorAll('select[name="assistantcoursecoordinator[]"]');
        const roleSelects = newRow.querySelectorAll('select[name="assistant_coordinator_role[]"]');
        selects.forEach(function(sel){ sel.value = ''; });
        roleSelects.forEach(function(sel){ sel.value = ''; });

        container.appendChild(newRow);
        coordinatorIndex++;

        // Initialize select2 for the new role dropdown if select2 is available
        const newRoleSelect = newRow.querySelector('select[name="assistant_coordinator_role[]"]');
        if (newRoleSelect && typeof $.fn.select2 !== 'undefined') {
            $(newRoleSelect).select2({
                dropdownParent: $(newRow).closest('.card-body, .modal-body, body'),
                width: '100%'
            });
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

    if (coordinatorSelect) {
        coordinatorSelect.addEventListener('change', function() {
            updateAssistantOptions();
        });
    }

    // Initial sync
    updateAssistantOptions();
});


