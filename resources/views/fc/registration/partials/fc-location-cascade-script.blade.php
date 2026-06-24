<script>
(function () {
    function allOptions(select) {
        return Array.from(select.querySelectorAll('option')).filter(function (o) { return o.value !== ''; });
    }

    function filterSelectByData(select, attr, matchValue, keepSelected) {
        var current = keepSelected ? select.value : '';
        allOptions(select).forEach(function (opt) {
            var show = !matchValue || String(opt.getAttribute(attr) || '') === String(matchValue);
            opt.hidden = !show;
            opt.disabled = !show;
            if (!show && opt.selected) {
                opt.selected = false;
            }
        });
        if (current) {
            var still = Array.from(select.options).find(function (opt) {
                return opt.value === current && !opt.disabled;
            });
            if (still) {
                still.selected = true;
            }
        }
    }

    function resetSelect(select) {
        allOptions(select).forEach(function (opt) {
            opt.hidden = false;
            opt.disabled = false;
        });
        select.value = '';
    }

    function findDistrictForState(stateSelect) {
        var stateField = stateSelect.name;
        var scoped = stateSelect.closest('.repeatable-row') || stateSelect.closest('.row.g-3') || stateSelect.closest('.row.g-2') || document;
        // Prefer the district explicitly paired to this state field.
        var exact = scoped.querySelector('.fc-district-select[data-fc-state-field="' + stateField + '"]');
        if (exact) {
            return exact;
        }
        // No explicit pairing: only fall back when the scope is unambiguous, i.e. a
        // single district (or a single unpaired district). NEVER grab another pair's
        // district — that would reset a field the user already filled.
        var districts = Array.prototype.slice.call(scoped.querySelectorAll('.fc-district-select'));
        if (districts.length === 1) {
            return districts[0];
        }
        var unpaired = districts.filter(function (d) { return !d.getAttribute('data-fc-state-field'); });
        return unpaired.length === 1 ? unpaired[0] : null;
    }

    function forceDomicileCascade(stateSelect) {
        if (!stateSelect || stateSelect.name !== 'domicile_state_id') {
            return;
        }
        var scoped = stateSelect.closest('.row.g-3') || document;
        var districtSelect = scoped.querySelector('.fc-district-select[name="domicile_district"]')
            || scoped.querySelector('.fc-district-select[data-fc-state-field="domicile_state_id"]');
        if (!districtSelect) {
            return;
        }
        filterSelectByData(districtSelect, 'data-state-id', stateSelect.value, false);
        if (districtSelect.value && districtSelect.options[districtSelect.selectedIndex]?.disabled) {
            districtSelect.value = '';
        }
    }

    function findStateForCountry(countrySelect) {
        var countryName = countrySelect.name;
        var scoped = countrySelect.closest('.repeatable-row') || countrySelect.closest('.row.g-3') || document;
        return scoped.querySelector('.fc-state-select[data-fc-country-field="' + countryName + '"]');
    }

    document.querySelectorAll('.fc-country-select').forEach(function (countrySelect) {
        countrySelect.addEventListener('change', function () {
            var countryId = this.value;
            var stateSelect = findStateForCountry(this);
            if (!stateSelect) {
                return;
            }
            filterSelectByData(stateSelect, 'data-country-id', countryId, false);
            var districtSelect = findDistrictForState(stateSelect);
            if (districtSelect) {
                resetSelect(districtSelect);
            }
        });
    });

    document.querySelectorAll('.fc-state-select').forEach(function (stateSelect) {
        stateSelect.addEventListener('change', function () {
            var stateId = this.value;
            var districtSelect = findDistrictForState(this);
            if (!districtSelect) {
                return;
            }
            filterSelectByData(districtSelect, 'data-state-id', stateId, false);
            forceDomicileCascade(this);
        });

        var countryField = stateSelect.getAttribute('data-fc-country-field');
        if (countryField) {
            var countrySelect = document.querySelector('[name="' + countryField + '"]');
            if (countrySelect && countrySelect.value) {
                filterSelectByData(stateSelect, 'data-country-id', countrySelect.value, true);
            }
        }
        if (stateSelect.value) {
            var districtSelect = findDistrictForState(stateSelect);
            if (districtSelect) {
                filterSelectByData(districtSelect, 'data-state-id', stateSelect.value, true);
            }
            forceDomicileCascade(stateSelect);
        }
    });
})();
</script>
