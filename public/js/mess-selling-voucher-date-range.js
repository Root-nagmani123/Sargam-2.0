/* Selling Voucher (Date Range) — extracted from index.blade.php.
   All Blade-derived values are injected via window.SVDR_CFG (defined inline in the blade).
   Kept at top-level (no IIFE / no 'use strict') to preserve the original inline-script
   scoping: declarations here stay script-global exactly as they were before extraction. */
var SVDR_CFG = window.SVDR_CFG || {};

/* ===== push script 1: table reload helper ===== */
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
        var $ = window.jQuery;
        var $table = $('#sellingVoucherDateRangeTable');
        if (!$table.length) return;
        window.reloadSellingVoucherDateRangeTable = function() {
            if ($.fn.DataTable.isDataTable($table)) { $table.DataTable().ajax.reload(null, false); }
        };
    });

/* ===== push script 2: download/print export ===== */
    (function () {
        var TABLE_ID = 'sellingVoucherDateRangeTable';
        var BASE = SVDR_CFG.exportUrl;
        var $ = window.jQuery;
        function buildUrl(format, inline) {
            var params = ['format=' + format];
            var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID)) ? $('#' + TABLE_ID).DataTable() : null;
            var search = dt ? dt.search() : '';
            if (search) params.push('search=' + encodeURIComponent(search));
            var form = document.getElementById('sellingVoucherFilterForm');
            if (form) {
                new FormData(form).forEach(function (value, key) {
                    if (key === 'refresh') return;
                    if (value !== '' && value !== null) params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                });
            }
            var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function') ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
            if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));
            if (inline) params.push('inline=1');
            return BASE + '?' + params.join('&');
        }
        var d = document.getElementById('svDownloadBtn');
        if (d) d.addEventListener('click', function () { window.location.href = buildUrl('excel', false); });
        var p = document.getElementById('svPrintBtn');
        if (p) p.addEventListener('click', function () { window.open(buildUrl('pdf', true), '_blank'); });
    })();

/* ===== push script 3: column-visibility bridge ===== */
    (function () {
        var TABLE_ID = 'sellingVoucherDateRangeTable';
        var $ = window.jQuery;
        var grid = document.getElementById('svdrColumnToggleGrid');
        var modalEl = document.getElementById('svdrColumnVisibilityModal');
        if (!$ || !grid || !modalEl) return;
        function getMgr() { return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function') ? window.MessColumnManager.get(TABLE_ID) : null; }
        function visibleCount(mgr) { return mgr.baseColumns.filter(function (c) { return mgr.state.visibility[String(c.index)] !== false; }).length; }
        function buildGrid() {
            var mgr = getMgr();
            if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;
            grid.innerHTML = '';
            (mgr.state.order || []).forEach(function (idx) {
                var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
                if (!col) return;
                var isVisible = mgr.state.visibility[String(col.index)] !== false;
                var inputId = 'svdrcolvis_' + col.index;
                var cell = document.createElement('div'); cell.className = 'col-12 col-sm-6 col-md-4';
                var label = document.createElement('label');
                label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
                label.setAttribute('for', inputId);
                var cb = document.createElement('input'); cb.type = 'checkbox'; cb.className = 'form-check-input m-0'; cb.id = inputId; cb.checked = isVisible;
                if (col.locked) cb.disabled = true;
                cb.addEventListener('change', function () {
                    var m = getMgr(); if (!m) return;
                    if (!cb.checked && visibleCount(m) <= 1) { cb.checked = true; window.alert('At least one column must remain visible.'); return; }
                    m.state.visibility[String(col.index)] = cb.checked; m.saveState(); m.apply();
                });
                var span = document.createElement('span'); span.textContent = col.label;
                label.appendChild(cb); label.appendChild(span); cell.appendChild(label); grid.appendChild(cell);
            });
            return true;
        }
        modalEl.addEventListener('show.bs.modal', function () {
            if (buildGrid()) return;
            var tries = 0;
            var timer = setInterval(function () { if (buildGrid() || ++tries > 20) clearInterval(timer); }, 100);
        });
    })();

/* ===== push script 4: auto-filter + overflow ===== */
    (function () {
        var form = document.getElementById('sellingVoucherFilterForm');
        if (!form) return;
        form.addEventListener('change', function (e) {
            var t = e.target;
            if (!t || !t.classList || !t.classList.contains('sv-auto-filter')) return;
            var clears = t.getAttribute('data-clears');
            if (clears) { clears.split(',').forEach(function (id) { var el = document.getElementById(id.trim()); if (el) el.value = ''; }); }
            form.submit();
        });

        var itemsWrap = document.getElementById('svdrFilterItems');
        var moreWrap = document.getElementById('svdrMoreFilterWrap');
        var moreMenu = document.getElementById('svdrMoreFilterItems');
        var moreToggle = document.getElementById('svdrMoreFilterToggle');
        if (!itemsWrap || !moreWrap || !moreMenu || !moreToggle) return;
        var allItems = Array.prototype.slice.call(itemsWrap.querySelectorAll('.sv-filter-item'));
        function fits() { return form.scrollWidth <= form.clientWidth + 1; }
        function layout() {
            allItems.forEach(function (it) { itemsWrap.appendChild(it); });
            moreWrap.classList.add('d-none');
            if (fits()) { return; }
            moreWrap.classList.remove('d-none');
            var moved = 0;
            for (var i = allItems.length - 1; i >= 0; i--) {
                if (fits()) break;
                moreMenu.insertBefore(allItems[i], moreMenu.firstChild);
                moved++;
            }
            moreToggle.textContent = moved > 0 ? ('+' + moved + ' Filter') : '+ Filter';
            if (moved === 0) { moreWrap.classList.add('d-none'); }
        }
        var raf = null;
        function scheduleLayout() { if (raf) return; raf = window.requestAnimationFrame(function () { raf = null; layout(); }); }
        layout();
        window.addEventListener('resize', scheduleLayout);
        window.setTimeout(layout, 150);
        window.setTimeout(layout, 500);
    })();

/* ===== main script (modals, add/edit/return, filter cascade) ===== */
(function() {
    let itemSubcategories = SVDR_CFG.itemSubcategories;
    let filteredItems = itemSubcategories;
    const baseUrl = SVDR_CFG.baseUrl;
    let addRowIndex = 1;
    let editRowIndex = 0;
    let currentStoreId = null;
    let editCurrentStoreId = null;
    let editCurrentStoreName = '';

    function safeFocus(el) {
        if (!el || typeof el.focus !== 'function') return;
        try {
            el.focus({
                preventScroll: true
            });
        } catch (e) {
            try {
                el.focus();
            } catch (e2) {}
        }
    }

    // Prevent "jump to top" while clicking/focusing inside scrollable modals.
    // This guards against scroll resets caused by focus management, overflow toggles, and dropdown portals.
    function installModalScrollGuard(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var last = {
            winTop: 0,
            bodyTop: 0,
            has: false
        };

        function capture() {
            var body = modal.querySelector('.modal-body');
            last.winTop = (typeof window !== 'undefined') ? (window.scrollY || window.pageYOffset || 0) : 0;
            last.bodyTop = body ? body.scrollTop : 0;
            last.has = true;
        }

        function restoreSoon() {
            if (!last.has) return;
            var body = modal.querySelector('.modal-body');

            function restoreOnce() {
                try {
                    window.scrollTo(0, last.winTop);
                } catch (e) {}
                if (body) body.scrollTop = last.bodyTop;
            }
            requestAnimationFrame(restoreOnce);
            setTimeout(restoreOnce, 0);
            setTimeout(restoreOnce, 50);
            setTimeout(restoreOnce, 150);
        }

        modal.addEventListener('pointerdown', function() {
            capture();
            // Some scroll resets happen *after* the click (focus trap / dropdown open / overflow changes).
            restoreSoon();
        }, true);
        modal.addEventListener('focusin', function() {
            capture();
            restoreSoon();
        }, true);
    }

    // We intentionally do NOT toggle modal overflow classes on dropdown open.
    // That pattern was causing the modal-body to jump to the top.

    /**
     * Item rows: Choices list is position:absolute inside nested overflow/table contexts.
     * Pin the panel to the viewport so it is not clipped by modal/table/card.
     */
    function bindDrItemChoicesFixedDropdown(selectEl, choices, api) {
        var modalBody = null;
        var placeScheduled = false;

        function getDropdownEl() {
            return choices.dropdown && choices.dropdown.element;
        }

        function getModalRect() {
            var modal = selectEl.closest ? selectEl.closest('.modal') : null;
            var dialog = modal ? modal.querySelector('.modal-dialog') : null;
            return dialog ? dialog.getBoundingClientRect() : null;
        }

        function place() {
            var dd = getDropdownEl();
            var wrap = api.wrapper;
            if (!dd || !wrap || !wrap.classList.contains('is-open')) return;
            var inner = wrap.querySelector('.choices__inner');
            if (!inner) return;
            var r = inner.getBoundingClientRect();
            var selectRect = selectEl.getBoundingClientRect ? selectEl.getBoundingClientRect() : null;
            var mr = getModalRect();
            var flipped = wrap.classList.contains('is-flipped');
            var margin = 8;
            var spaceBelow = window.innerHeight - r.bottom - margin * 2;
            var spaceAbove = r.top - margin * 2;
            // Use the actual select width (stable) instead of wrapper/table influenced width.
            var baseWidth = (selectRect && selectRect.width) ? selectRect.width : r.width;
            var width = Math.max(180, baseWidth);
            if (mr) {
                width = Math.min(width, Math.max(180, mr.width - margin * 2));
            }
            var leftMin = mr ? (mr.left + margin) : margin;
            var leftMax = mr ? (mr.right - width - margin) : (window.innerWidth - width - margin);
            var leftBase = (selectRect && typeof selectRect.left === 'number') ? selectRect.left : r.left;
            var left = Math.max(leftMin, Math.min(leftBase, leftMax));
            dd.classList.add('dr-item-choices-dropdown-fixed');
            dd.style.setProperty('position', 'fixed', 'important');
            dd.style.setProperty('left', left + 'px', 'important');
            dd.style.setProperty('width', width + 'px', 'important');
            dd.style.setProperty('min-width', width + 'px', 'important');
            dd.style.setProperty('max-width', width + 'px', 'important');
            dd.style.setProperty('max-height', Math.max(120, flipped ? spaceAbove : spaceBelow) + 'px',
                'important');
            dd.style.setProperty('z-index', '200000', 'important');
            if (flipped) {
                dd.style.setProperty('top', 'auto', 'important');
                dd.style.setProperty('bottom', (window.innerHeight - r.top + 2) + 'px', 'important');
            } else {
                dd.style.setProperty('top', (r.bottom + 2) + 'px', 'important');
                dd.style.setProperty('bottom', 'auto', 'important');
            }
        }

        function onScrollOrResize() {
            if (placeScheduled) return;
            placeScheduled = true;
            requestAnimationFrame(function() {
                placeScheduled = false;
                place();
            });
        }

        function onShow() {
            modalBody = selectEl.closest('.modal-body');
            requestAnimationFrame(function() {
                place();
                requestAnimationFrame(place);
            });
            setTimeout(place, 0);
            setTimeout(place, 80);
            window.addEventListener('resize', onScrollOrResize, {
                passive: true
            });
            document.addEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.addEventListener('scroll', onScrollOrResize, {
                passive: true
            });
        }

        function onHide() {
            var dd = getDropdownEl();
            if (dd) {
                dd.classList.remove('dr-item-choices-dropdown-fixed');
                ['position', 'left', 'top', 'right', 'bottom', 'width', 'min-width', 'max-width', 'max-height',
                    'z-index'
                ].forEach(function(p) {
                    dd.style.removeProperty(p);
                });
            }
            window.removeEventListener('resize', onScrollOrResize);
            document.removeEventListener('scroll', onScrollOrResize, true);
            if (modalBody) modalBody.removeEventListener('scroll', onScrollOrResize);
            modalBody = null;
        }
        selectEl.addEventListener('showDropdown', onShow);
        selectEl.addEventListener('hideDropdown', onHide);
    }

    /**
     * Item dropdown: allow Tab (in addition to Enter) to select the currently highlighted option.
     * Reuses Choices' own Enter handler so selection behaviour is identical, then lets Tab move focus on.
     */
    function bindDrItemChoicesTabSelect(selectEl, choices, api) {
        if (!api || !api.wrapper) return;
        api.wrapper.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab' || e.shiftKey) return;
            var dropdown = choices.dropdown ? choices.dropdown.element : null;
            var isOpen = dropdown && dropdown.classList && dropdown.classList.contains('is-active');
            if (!isOpen) return;
            var highlighted = dropdown.querySelector('.choices__item--selectable.is-highlighted');
            if (!highlighted) return;
            // Trigger Choices' native selection of the highlighted option (same as pressing Enter).
            var enter = new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true });
            e.target.dispatchEvent(enter);
            // Default Tab behaviour proceeds, moving focus to the next field.
        });
    }

    // Note: we only pin ITEM selects dropdowns to viewport (see bindDrItemChoicesFixedDropdown).
    // For normal selects (store/payment/name), we keep default positioning so width stays aligned.

    document.addEventListener('DOMContentLoaded', function() {
        installModalScrollGuard('addReportModal');
        installModalScrollGuard('editReportModal');
    });

    // Native Choices.js instance helper (keeps legacy alias for existing logic).
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyExactChoicesSearchFilter(api, dropdownEl, rawQuery) {
        if (!dropdownEl) return;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;
        choiceItems.forEach(function(item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var show = !query || label === query;
            item.style.display = show ? '' : 'none';
        });
    }

    function createChoicesInstance(selectEl, settings) {
        if (!selectEl || typeof window.Choices === 'undefined') return null;
        if (selectEl.choicesInstance) return selectEl.choicesInstance;
        settings = settings || {};
        var isMulti = !!selectEl.multiple;

        var choiceConfig = {
            allowHTML: false,
            itemSelectText: '',
            shouldSort: false,
            searchEnabled: settings.searchEnabled !== false,
            searchChoices: settings.searchChoices !== false,
            searchFloor: typeof settings.searchFloor === 'number' ? settings.searchFloor : 0,
            searchResultLimit: typeof settings.maxOptions === 'number' ? settings.maxOptions : -1,
            placeholder: true,
            placeholderValue: settings.placeholder || (selectEl.getAttribute('data-placeholder') || selectEl
                .getAttribute('placeholder') || ''),
            searchPlaceholderValue: '',
            removeItemButton: isMulti,
            closeDropdownOnSelect: typeof settings.closeDropdownOnSelect === 'boolean' ? settings
                .closeDropdownOnSelect : !isMulti
        };

        var choices = new window.Choices(selectEl, choiceConfig);
        var api = {
            _choices: choices,
            selectEl: selectEl,
            settings: settings,
            activeOption: null,
            items: [],
            wrapper: choices.containerOuter ? choices.containerOuter.element : null,
            control_input: null,
            getValue: function() {
                if (!this.selectEl) return isMulti ? [] : '';
                if (isMulti) {
                    try {
                        var values = this._choices.getValue(true);
                        if (Array.isArray(values)) return values.map(String).filter(Boolean);
                        return values ? [String(values)] : [];
                    } catch (e) {
                        return Array.from(this.selectEl.selectedOptions || []).map(function(option) {
                            return option.value;
                        }).filter(Boolean);
                    }
                }
                return this.selectEl.value || '';
            },
            setValue: function(v) {
                this._choices.removeActiveItems();

                if (isMulti) {
                    var values = Array.isArray(v) ? v : (v !== '' && v !== null && typeof v !==
                        'undefined' ? [v] : []);
                    values.forEach(function(value) {
                        if (value === '' || value === null || typeof value === 'undefined') return;
                        try {
                            this._choices.setChoiceByValue(String(value));
                        } catch (e) {}
                    }, this);
                } else {
                    var value = (v === null || typeof v === 'undefined') ? '' : String(v);
                    if (value !== '') this._choices.setChoiceByValue(value);
                }

                this.syncItems();
            },
            clear: function() {
                this._choices.removeActiveItems();
                this.syncItems();
            },
            addOption: function(opt) {
                if (!opt) return;
                var val = (opt.value === null || typeof opt.value === 'undefined') ? '' : String(opt.value);
                this._choices.setChoices([{
                    value: val,
                    label: opt.text || val,
                    selected: false,
                    disabled: false
                }], 'value', 'label', false);
            },
            destroy: function() {
                if (this._choices) this._choices.destroy();
                if (this.selectEl) {
                    this.selectEl.choicesInstance = null;
                    this.selectEl.tomselect = null;
                }
            },
            setTextboxValue: function(v) {
                if (this.control_input) this.control_input.value = v || '';
            },
            onSearchChange: function() {},
            refreshOptions: function() {},
            syncItems: function() {
                var v = this.getValue();
                if (isMulti) {
                    this.items = Array.isArray(v) ? v.map(String) : [];
                } else {
                    this.items = (v === '' || v === null || typeof v === 'undefined') ? [] : [String(v)];
                }
            }
        };
        api.control_input = api.wrapper ? api.wrapper.querySelector('input.choices__input--cloned') : null;
        if (api.wrapper && api.wrapper.classList) api.wrapper.classList.add('ts-wrapper');
        if (choices.dropdown && choices.dropdown.element && choices.dropdown.element.classList) {
            choices.dropdown.element.classList.add('ts-dropdown');
        }
        api.syncItems();

        selectEl.addEventListener('change', function() {
            api.syncItems();
        });
        selectEl.addEventListener('showDropdown', function() {
            if (api.control_input) {
                applyExactChoicesSearchFilter(api, choices.dropdown ? choices.dropdown.element : null, api
                    .control_input.value || '');
            }
            if (typeof settings.onDropdownOpen === 'function') {
                settings.onDropdownOpen.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        selectEl.addEventListener('hideDropdown', function() {
            if (typeof settings.onDropdownClose === 'function') {
                settings.onDropdownClose.call(api, choices.dropdown ? choices.dropdown.element : null);
            }
        });
        if (typeof settings.onInitialize === 'function') settings.onInitialize.call(api);

        if (selectEl.classList.contains('dr-item-select') || selectEl.classList.contains('edit-dr-item-select')) {
            bindDrItemChoicesFixedDropdown(selectEl, choices, api);
            bindDrItemChoicesTabSelect(selectEl, choices, api);
        }

        selectEl.choicesInstance = api;
        selectEl.tomselect = api; // legacy alias until full cleanup
        return api;
    }

    var clientNameOptionsAdd = [];
    var clientNameOptionsEdit = [];
    document.addEventListener('DOMContentLoaded', function() {
        var addSel = document.getElementById('drClientNameSelect');
        if (addSel) {
            addSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsAdd.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
        var editSel = document.getElementById('editDrClientNameSelect');
        if (editSel) {
            editSel.querySelectorAll('option[value]').forEach(function(opt) {
                clientNameOptionsEdit.push({
                    value: opt.value,
                    text: (opt.textContent || '').trim(),
                    type: ((opt.dataset.type || '').toLowerCase().trim()),
                    clientName: ((opt.dataset.clientName || '').toLowerCase().trim())
                });
            });
        }
    });

    function rebuildClientNameSelect(selectEl, optionsList, slug) {
        if (!selectEl || !Array.isArray(optionsList)) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = optionsList.filter(function(o) {
            return (o.type || '').toLowerCase().trim() === slugLower;
        });
        if (selectEl.tomselect) {
            try {
                selectEl.tomselect.destroy();
            } catch (e) {}
        }
        if (selectEl.id === 'drClientNameSelect') addModalTomSelectInstances.client = null;
        selectEl.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', ((o.type || '').toLowerCase().trim()));
            opt.setAttribute('data-client-name', ((o.clientName || '').toLowerCase().trim()));
            selectEl.appendChild(opt);
        });
        if (typeof Choices !== 'undefined') {
            var inst = createChoicesInstance(selectEl, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
            if (selectEl.id === 'drClientNameSelect') addModalTomSelectInstances.client = inst;
        }
    }

    function rebuildEditClientNameSelect(slug) {
        var editSel = document.getElementById('editDrClientNameSelect');
        if (!editSel || !clientNameOptionsEdit.length) return;
        var slugLower = (slug || '').toLowerCase().trim();
        var filtered = clientNameOptionsEdit.filter(function(o) {
            return (o.type || '').toLowerCase().trim() === slugLower;
        });
        if (editSel.tomselect) {
            try {
                editSel.tomselect.destroy();
            } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        editSel.innerHTML = '<option value="">Select Client Name</option>';
        filtered.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-type', ((o.type || '').toLowerCase().trim()));
            opt.setAttribute('data-client-name', ((o.clientName || '').toLowerCase().trim()));
            editSel.appendChild(opt);
        });
        if (typeof Choices !== 'undefined') {
            editModalTomSelectInstances.client = createChoicesInstance(editSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
    }

    function getSelectValue(select) {
        if (!select) return '';
        return select.tomselect ? select.tomselect.getValue() : select.value;
    }

    function setSelectValue(select, value) {
        if (!select) return;
        var v = (value === null || value === undefined) ? '' : String(value);
        if (select.tomselect) select.tomselect.setValue(v);
        else select.value = v;
    }

    /** After Choices.js init on Edit Date-Range modal, apply API values to instances (store, payment, client, course, staff name). */
    function syncEditDrChoicesFromVoucher(v, slug) {
        slug = String(slug || 'employee').toLowerCase();
        var paySel = document.querySelector('#editReportModal select.edit-payment-type');
        if (paySel && paySel.tomselect) {
            try { paySel.tomselect.setValue(String(v.payment_type ?? 1)); } catch (e) {}
        }
        var storeSel = document.querySelector('#editReportModal select.edit-store-id');
        if (v && v.filtered_view) {
            applyEditFilteredStoreDisplay(v);
        } else if (storeSel) {
            applyEditFilteredStoreDisplay(v || {});
        }
        var ecs = document.getElementById('editDrClientNameSelect');
        if (ecs && ecs.tomselect && slug !== 'ot' && slug !== 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { ecs.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var eot = document.getElementById('editDrOtCourseSelect');
        if (eot && eot.tomselect && slug === 'ot' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { eot.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var edc = document.getElementById('editDrCourseSelect');
        if (edc && edc.tomselect && slug === 'course' && v.client_type_pk != null && String(v.client_type_pk) !== '') {
            try { edc.tomselect.setValue(String(v.client_type_pk)); } catch (e) {}
        }
        var cn = String(v.client_name || '').trim();
        if (cn) {
            ['editDrFacultySelect', 'editDrAcademyStaffSelect', 'editDrMessStaffSelect'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el || !el.tomselect) return;
                try { el.tomselect.setValue(cn); } catch (e) {}
            });
        }
    }

    function getSelectSelectedOption(select) {
        if (!select) return null;
        const val = getSelectValue(select);
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == val) return select.options[i];
        }
        return null;
    }

    function setSelectVisible(select, visible) {
        if (!select) return;
        var wrapper = null;
        if (select.tomselect && select.tomselect.wrapper) wrapper = select.tomselect.wrapper;
        if (!wrapper && select.parentElement) {
            var p = select.parentElement;
            if (p.classList && p.classList.contains('ts-wrapper')) wrapper = p;
            else if (p.parentElement && p.parentElement.classList && p.parentElement.classList.contains(
                    'ts-wrapper')) wrapper = p.parentElement;
        }
        if (wrapper) wrapper.style.display = visible ? '' : 'none';
        else select.style.display = visible ? 'block' : 'none';
    }

    var addModalTomSelectInstances = {
        payment: null,
        client: null,
        store: null
    };
    var editModalTomSelectInstances = {
        payment: null,
        client: null,
        store: null
    };

    function destroyAddModalTomSelects() {
        if (addModalTomSelectInstances.payment) {
            try {
                addModalTomSelectInstances.payment.destroy();
            } catch (e) {}
            addModalTomSelectInstances.payment = null;
        }
        if (addModalTomSelectInstances.client) {
            try {
                addModalTomSelectInstances.client.destroy();
            } catch (e) {}
            addModalTomSelectInstances.client = null;
        }
        if (addModalTomSelectInstances.store) {
            try {
                addModalTomSelectInstances.store.destroy();
            } catch (e) {}
            addModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#addReportModal select').forEach(function(el) {
            if (el.tomselect) {
                try {
                    el.tomselect.destroy();
                } catch (e) {}
            }
        });
    }

    function destroyEditModalTomSelects() {
        if (editModalTomSelectInstances.payment) {
            try {
                editModalTomSelectInstances.payment.destroy();
            } catch (e) {}
            editModalTomSelectInstances.payment = null;
        }
        if (editModalTomSelectInstances.client) {
            try {
                editModalTomSelectInstances.client.destroy();
            } catch (e) {}
            editModalTomSelectInstances.client = null;
        }
        if (editModalTomSelectInstances.store) {
            try {
                editModalTomSelectInstances.store.destroy();
            } catch (e) {}
            editModalTomSelectInstances.store = null;
        }
        document.querySelectorAll('#editReportModal select').forEach(function(el) {
            if (el.tomselect) {
                try {
                    el.tomselect.destroy();
                } catch (e) {}
            }
        });
    }

    function createBlankSearchConfig(extra) {
        return Object.assign({
            allowEmptyOption: true,
            dropdownParent: 'body',
            searchField: ['text'],
            controlInput: '<input>',
            highlight: false,
            onInitialize: function() {
                this.activeOption = null;
            },
            onDropdownOpen: function(dropdown) {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                self._modalDropdownState = helper && modalEl ? helper.onOpen(modalEl) : null;
                if (!self._modalDropdownState && modalBody) self._modalDropdownState = {
                    scrollTop: modalBody.scrollTop
                };

                function clearInputAndCursor() {
                    var prevWinTop = (typeof window !== 'undefined') ? (window.scrollY || window
                        .pageYOffset || 0) : 0;
                    // Choices dropdown me visible cloned input ko priority do.
                    var input = (dropdown && dropdown.querySelector('input.choices__input--cloned')) ||
                        (dropdown && dropdown.querySelector('input')) ||
                        self.control_input;
                    if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                    if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                    if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                    if (input) {
                        // Ensure search field is visible in all modal dropdown contexts (including Edit modal).
                        input.style.display = 'block';
                        input.style.visibility = 'visible';
                        input.style.opacity = '1';
                        input.value = '';
                        safeFocus(input);
                        try {
                            input.setSelectionRange(0, 0);
                        } catch (e) {}
                        input.scrollLeft = 0;
                    }
                    // Some browsers still scroll on focus; restore window position.
                    if (typeof window !== 'undefined') {
                        requestAnimationFrame(function() {
                            try {
                                window.scrollTo(0, prevWinTop);
                            } catch (e) {}
                        });
                        setTimeout(function() {
                            try {
                                window.scrollTo(0, prevWinTop);
                            } catch (e) {}
                        }, 0);
                    }
                    if (helper && modalEl) {
                        helper.keepScroll(modalEl, self._modalDropdownState);
                    } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                        .scrollTop === 'number') {
                        modalBody.scrollTop = self._modalDropdownState.scrollTop;
                    }
                }
                // Agar clearOnOpen true hai to har open par selection bhi hatao
                if (self.settings && self.settings.clearOnOpen) {
                    self.clear(true);
                }
                clearInputAndCursor();
                setTimeout(clearInputAndCursor, 0);
                setTimeout(clearInputAndCursor, 50);
                setTimeout(clearInputAndCursor, 100);
                if (dropdown) {
                    setTimeout(function() {
                        var opts = dropdown.querySelectorAll(
                            '.option.active, .option.selected, .option[aria-selected="true"]'
                        );
                        opts.forEach(function(opt) {
                            opt.classList.remove('active');
                            opt.classList.remove('selected');
                            opt.setAttribute('aria-selected', 'false');
                        });
                    }, 0);
                }
            },
            onDropdownClose: function() {
                var self = this;
                var modalEl = self.input && self.input.closest ? self.input.closest('.modal') : null;
                var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                var helper = window.MessModalDropdownStability;
                if (helper && modalEl) {
                    helper.onClose(modalEl, self._modalDropdownState);
                } else if (modalBody && self._modalDropdownState && typeof self._modalDropdownState
                    .scrollTop === 'number') {
                    modalBody.scrollTop = self._modalDropdownState.scrollTop;
                }
                self._modalDropdownState = null;
            }
        }, extra || {});
    }

    function createItemSelectConfig() {
        return createBlankSearchConfig({
            placeholder: 'Select Item',
            maxOptions: null,
            clearOnOpen: false,
            searchEnabled: true,
            searchChoices: true,
            searchFloor: 0
        });
    }

    function ensureChoicesInitializedForItemSelect(selectEl) {
        if (!selectEl || typeof Choices === 'undefined') return null;
        if (selectEl.tomselect) return selectEl.tomselect;
        return createChoicesInstance(selectEl, createItemSelectConfig());
    }

    function initAddModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var paymentSel = document.querySelector('#addReportModal select[name="payment_type"]');
        if (paymentSel && !paymentSel.tomselect) {
            addModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('drClientNameSelect');
        var clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        var slug = clientTypeRadio ? (clientTypeRadio.value || '').toLowerCase() : 'employee';
        if (clientSel && slug !== 'ot' && slug !== 'course' && clientNameOptionsAdd.length) {
            rebuildClientNameSelect(clientSel, clientNameOptionsAdd, slug);
        } else if (clientSel && !clientSel.tomselect) {
            addModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = document.querySelector('#addReportModal select[name="inve_store_master_pk"]');
        if (storeSel && !storeSel.tomselect) {
            addModalTomSelectInstances.store = createChoicesInstance(storeSel, createBlankSearchConfig({
                placeholder: 'Select Store',
                clearOnOpen: true
            }));
        }
        var nameSelectIds = ['drOtCourseSelect', 'drCourseSelect', 'drFacultySelect', 'drAcademyStaffSelect',
            'drMessStaffSelect', 'drOtStudentSelect', 'drCourseNameSelect'
        ];
        nameSelectIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (!sel || sel.tomselect) return;
            var ph = id.indexOf('Faculty') !== -1 ? 'Select Faculty' : id.indexOf('Academy') !== -1 ?
                'Select Academy Staff' : id.indexOf('Mess') !== -1 ? 'Select Mess Staff' : id.indexOf(
                    'OtStudent') !== -1 ? 'Select Student' : 'Select Course';
            createChoicesInstance(sel, createBlankSearchConfig({
                placeholder: ph,
                clearOnOpen: true
            }));
        });
        var otCourseSel = document.getElementById('drOtCourseSelect');
        var drCourseSel = document.getElementById('drCourseSelect');
        setSelectVisible(otCourseSel, slug === 'ot');
        setSelectVisible(drCourseSel, slug === 'course');
        if (clientSel) setSelectVisible(clientSel, slug !== 'ot' && slug !== 'course');
        document.querySelectorAll('#addModalItemsBody .dr-item-select').forEach(function(select) {
            if (select.tomselect) return;
            createChoicesInstance(select, createItemSelectConfig());
        });
        if (typeof updateDrNameField === 'function') updateDrNameField();
        var addChecked = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        if (addChecked) {
            var w1 = document.getElementById('drClientNameWrap');
            var w2 = document.getElementById('drNameFieldWrap');
            if (w1) w1.style.display = '';
            if (w2) w2.style.display = '';
        }
    }

    // Defensive init: if an item select is clicked before Choices is attached,
    // initialize immediately and open searchable dropdown.
    document.addEventListener('mousedown', function(e) {
        var selectEl = e.target && e.target.closest ? e.target.closest(
            '#addModalItemsBody .dr-item-select, #editModalItemsBody .edit-dr-item-select') : null;
        if (!selectEl || selectEl.tomselect || typeof Choices === 'undefined') return;

        // Keep modal scroll position stable while we initialize and open the dropdown.
        var modalBody = selectEl.closest ? selectEl.closest('.modal') : null;
        modalBody = modalBody ? modalBody.querySelector('.modal-body') : null;
        var prevTop = modalBody ? modalBody.scrollTop : 0;
        var prevWinTop = (typeof window !== 'undefined') ? (window.scrollY || window.pageYOffset || 0) : 0;

        e.preventDefault();
        var inst = ensureChoicesInitializedForItemSelect(selectEl);
        setTimeout(function() {
            if (inst && inst._choices && typeof inst._choices.showDropdown === 'function') {
                inst._choices.showDropdown();
            }
            var wrapper = inst && inst.wrapper ? inst.wrapper : null;
            var input = wrapper ? wrapper.querySelector(
                '.choices__list--dropdown .choices__input--cloned') : null;
            if (input) {
                input.style.display = 'block';
                safeFocus(input);
            }

            if (modalBody) {
                requestAnimationFrame(function() {
                    modalBody.scrollTop = prevTop;
                });
                setTimeout(function() {
                    modalBody.scrollTop = prevTop;
                }, 0);
            }
            requestAnimationFrame(function() {
                try {
                    window.scrollTo(0, prevWinTop);
                } catch (e) {}
            });
            setTimeout(function() {
                try {
                    window.scrollTo(0, prevWinTop);
                } catch (e) {}
            }, 0);
        }, 0);
    }, true);

    function initEditModalTomSelects() {
        if (typeof Choices === 'undefined') return;
        var paymentSel = document.querySelector('#editReportModal select.edit-payment-type');
        if (paymentSel && !paymentSel.tomselect) {
            editModalTomSelectInstances.payment = createChoicesInstance(paymentSel, createBlankSearchConfig({
                placeholder: 'Payment Type',
                clearOnOpen: true
            }));
        }
        var clientSel = document.getElementById('editDrClientNameSelect');
        var editRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        var editSlug = editRadio ? (editRadio.value || '').toLowerCase() : 'employee';
        if (clientSel && editSlug !== 'ot' && editSlug !== 'course' && clientNameOptionsEdit.length) {
            var preservedPk = getSelectValue(clientSel) || '';
            rebuildEditClientNameSelect(editSlug);
            clientSel = document.getElementById('editDrClientNameSelect');
            if (clientSel && preservedPk) {
                if (clientSel.tomselect) clientSel.tomselect.setValue(preservedPk);
                else clientSel.value = preservedPk;
            }
        } else if (clientSel && !clientSel.tomselect) {
            editModalTomSelectInstances.client = createChoicesInstance(clientSel, createBlankSearchConfig({
                placeholder: 'Select Client Name',
                clearOnOpen: true
            }));
        }
        var storeSel = document.querySelector('#editReportModal select.edit-store-id');
        // Store field is hidden in Edit modal; keep native select value only (no Choices/TomSelect).
        if (storeSel && storeSel.tomselect) {
            try { storeSel.tomselect.destroy(); } catch (e) {}
        }
        var editNameInpForInit = document.getElementById('editDrClientNameInput');
        var nameValForInit = (editNameInpForInit && editNameInpForInit.value) ? String(editNameInpForInit.value)
            .trim() : '';
        if (nameValForInit) {
            var fn = document.getElementById('editDrFacultySelect');
            var an = document.getElementById('editDrAcademyStaffSelect');
            var mn = document.getElementById('editDrMessStaffSelect');
            if (fn) fn.value = nameValForInit;
            if (an) an.value = nameValForInit;
            if (mn) mn.value = nameValForInit;
        }
        var editNameIds = ['editDrOtCourseSelect', 'editDrCourseSelect', 'editDrFacultySelect',
            'editDrAcademyStaffSelect', 'editDrMessStaffSelect', 'editDrOtStudentSelect',
            'editDrCourseNameSelect'
        ];
        editNameIds.forEach(function(id) {
            var sel = document.getElementById(id);
            if (!sel || sel.tomselect) return;
            var ph = id.indexOf('Faculty') !== -1 ? 'Select Faculty' : id.indexOf('Academy') !== -1 ?
                'Select Academy Staff' : id.indexOf('Mess') !== -1 ? 'Select Mess Staff' : 'Select Course';
            createChoicesInstance(sel, createBlankSearchConfig({
                placeholder: ph,
                clearOnOpen: true
            }));
        });
        document.querySelectorAll('#editModalItemsBody .edit-dr-item-select').forEach(function(select) {
            if (select.tomselect) return;
            createChoicesInstance(select, createItemSelectConfig());
        });
        if (typeof updateEditDrNameField === 'function') updateEditDrNameField();
        var editChecked = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        if (editChecked) {
            var ew1 = document.getElementById('editDrClientNameWrap');
            var ew2 = document.getElementById('editDrNameFieldWrap');
            if (ew1) ew1.style.display = '';
            if (ew2) ew2.style.display = '';
            var es = (editChecked.value || '').toLowerCase();
            var ec = document.getElementById('editDrClientNameSelect');
            var eo = document.getElementById('editDrOtCourseSelect');
            var ed = document.getElementById('editDrCourseSelect');
            if (es === 'ot') {
                setSelectVisible(ec, false);
                setSelectVisible(eo, true);
                setSelectVisible(ed, false);
            } else if (es === 'course') {
                setSelectVisible(ec, false);
                setSelectVisible(eo, false);
                setSelectVisible(ed, true);
            } else {
                setSelectVisible(ec, true);
                setSelectVisible(eo, false);
                setSelectVisible(ed, false);
            }
        }
        if (typeof updateEditDrNameField === 'function') updateEditDrNameField();
        var editNameInp = document.getElementById('editDrClientNameInput');
        var savedName = (editNameInp && editNameInp.value) ? String(editNameInp.value).trim() : '';

        function syncEditNameValue() {
            var val = (document.getElementById('editDrClientNameInput') || {}).value;
            if (val !== undefined && val !== null) val = String(val).trim();
            if (!val) return;
            [document.getElementById('editDrFacultySelect'), document.getElementById('editDrAcademyStaffSelect'),
                document.getElementById('editDrMessStaffSelect')
            ].forEach(function(sel) {
                if (!sel) return;
                var wrapper = (sel.tomselect && sel.tomselect.wrapper) ? sel.tomselect.wrapper : (sel
                    .parentElement && sel.parentElement.classList && sel.parentElement.classList
                    .contains('ts-wrapper') ? sel.parentElement : null);
                if (wrapper && wrapper.style.display !== 'none') {
                    if (sel.tomselect) {
                        sel.tomselect.setValue(val);
                        if (!sel.tomselect.items || sel.tomselect.items.length === 0) {
                            sel.tomselect.addOption({
                                value: val,
                                text: val
                            });
                            sel.tomselect.setValue(val);
                        }
                    } else {
                        sel.value = val;
                    }
                }
            });
        }
        syncEditNameValue();
        setTimeout(syncEditNameValue, 0);
        setTimeout(syncEditNameValue, 80);
        setTimeout(syncEditNameValue, 200);
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Choices === 'undefined') return;
        var filterStatus = document.querySelector('form[method="GET"] select[name="status[]"]');
        var filterStore = document.querySelector('form[method="GET"] select[name="store[]"]');

        function setFilterDropdownState(instance, isOpen) {
            if (!instance || !instance.selectEl || !instance.selectEl.closest) return;
            var filterCard = instance.selectEl.closest('.selling-voucher-filter');
            if (!filterCard) return;
            filterCard.classList.toggle('dropdown-open', !!isOpen);
        }

        function createFilterChoicesConfig(placeholder) {
            return {
                placeholder: placeholder,
                closeDropdownOnSelect: false,
                onInitialize: function() {
                    this.activeOption = null;
                },
                onDropdownOpen: function(dropdown) {
                    var self = this;
                    setFilterDropdownState(self, true);

                    function clearInputAndCursor() {
                        var input = self.control_input || (dropdown && dropdown.querySelector('input'));
                        if (typeof self.setTextboxValue === 'function') self.setTextboxValue('');
                        if (typeof self.onSearchChange === 'function') self.onSearchChange('');
                        if (typeof self.refreshOptions === 'function') self.refreshOptions(false);
                        if (input) {
                            input.value = '';
                            input.focus();
                            try {
                                input.setSelectionRange(0, 0);
                            } catch (e) {}
                            input.scrollLeft = 0;
                        }
                    }

                    clearInputAndCursor();
                    setTimeout(clearInputAndCursor, 0);

                    if (dropdown) {
                        setTimeout(function() {
                            var opts = dropdown.querySelectorAll('.option.active');
                            opts.forEach(function(opt) {
                                opt.classList.remove('active');
                            });
                        }, 0);
                    }
                },
                onDropdownClose: function() {
                    setFilterDropdownState(this, false);
                }
            };
        }

        if (filterStatus) {
            if (filterStatus.tomselect) filterStatus.tomselect.destroy();
            createChoicesInstance(filterStatus, createFilterChoicesConfig('All Statuses'));
        }

        if (filterStore) {
            if (filterStore.tomselect) filterStore.tomselect.destroy();
            createChoicesInstance(filterStore, createFilterChoicesConfig('All Stores'));
        }
    });

    function enforceQtyWithinAvailable(row, availSelector, qtySelector) {
        if (!row) return;
        const availEl = row.querySelector(availSelector);
        const qtyEl = row.querySelector(qtySelector);
        if (!availEl || !qtyEl) return;

        const avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        qtyEl.max = String(avail);

        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > avail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
    }

    function getBaseAvailableForItem(itemId) {
        if (!itemId) return 0;
        const item = filteredItems.find(function(i) {
            return String(i.id) === String(itemId);
        });
        return item ? (parseFloat(item.available_quantity) || 0) : 0;
    }

    function refreshAllAvailable() {
        const rows = document.querySelectorAll('#addModalItemsBody .dr-item-row');
        const usedByItem = {};

        rows.forEach(function(row) {
            const select = row.querySelector('.dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            const availInp = row.querySelector('.dr-avail');
            const leftInp = row.querySelector('.dr-left');
            if (!itemId || !availInp) return;

            const base = getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, base - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
        });
    }

    function fetchStoreItems(storeId, callback) {
        if (!storeId) {
            filteredItems = itemSubcategories;
            if (callback) callback();
            return;
        }
        // Reuse the same store-items endpoint as Selling Voucher (material-management)
        const url = SVDR_CFG.materialManagementUrl + '/store/' + encodeURIComponent(storeId) +
            '/items';
        fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(t) {
                        var msg = (r.status === 500 && t) ? t : ('Server returned ' + r.status);
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(function(data) {
                if (Array.isArray(data)) {
                    filteredItems = data;
                } else if (data && data.error) {
                    throw new Error(data.error);
                } else {
                    filteredItems = [];
                }
                if (callback) callback();
            })
            .catch(function(err) {
                console.error('Store items fetch failed:', err);
                filteredItems = itemSubcategories || [];
                if (callback) callback();
                alert(
                    'Could not load store-specific items. Showing all items; available quantity may not reflect this store.'
                    );
            });
    }

    function updateAddItemDropdowns() {
        const rows = document.querySelectorAll('#addModalItemsBody .dr-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.dr-item-select');
            if (!select) return;

            const currentValue = getSelectValue(select);
            if (select.tomselect) {
                try {
                    select.tomselect.destroy();
                } catch (e) {}
            }
            select.innerHTML = '<option value="">Select Item</option>';

            filteredItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (typeof Choices !== 'undefined') createChoicesInstance(select, createItemSelectConfig());
            updateAddRowUnit(row);
        });
    }

    function updateEditItemDropdowns() {
        const rows = document.querySelectorAll('#editModalItemsBody .edit-dr-item-row');
        rows.forEach(row => {
            const select = row.querySelector('.edit-dr-item-select');
            if (!select) return;

            const currentValue = getSelectValue(select);
            if (select.tomselect) {
                try {
                    select.tomselect.destroy();
                } catch (e) {}
            }
            select.innerHTML = '<option value="">Select Item</option>';

            const sourceItems = Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems :
                itemSubcategories;
            sourceItems.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.item_name || '—';
                option.setAttribute('data-unit', item.unit_measurement || '');
                option.setAttribute('data-rate', item.standard_cost || 0);
                option.setAttribute('data-available', item.available_quantity || 0);
                if (item.price_tiers && item.price_tiers.length > 0) {
                    option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
                }
                if (item.id == currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (typeof Choices !== 'undefined') createChoicesInstance(select, createItemSelectConfig());
            const o = getSelectSelectedOption(select);
            const unitInp = row.querySelector('.edit-dr-unit');
            const rateInp = row.querySelector('.edit-dr-rate');
            const availInp = row.querySelector('.edit-dr-avail');
            if (unitInp) unitInp.value = (o && o.dataset.unit) ? o.dataset.unit : '—';
            if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
            if (availInp && o && o.dataset.available) availInp.value = o.dataset.available;
            updateEditRowLeft(row);
            updateEditRowTotal(row);
        });
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    function getAddRowHtml(index) {
        const options = filteredItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') +
                '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity ||
                    0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g,
                    '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + '>' + (s.item_name || '—').replace(/</g,
                '&lt;') + '</option>';
        }).join('');
        return '<tr class="dr-item-row">' +
            '<td><select name="items[' + index +
            '][item_subcategory_id]" class="form-select  dr-item-select" required><option value="">Select Item</option>' +
            options + '</select></td>' +
            '<td><input type="text" name="items[' + index +
            '][unit]" class="form-control  dr-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index +
            '][available_quantity]" class="form-control  dr-avail bg-light" readonly></td>' +
            '<td><input type="number" name="items[' + index +
            '][quantity]" class="form-control  dr-qty" step="0.01" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  dr-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="date" name="items[' + index +
            '][issue_date]" class="form-control  dr-issue-date" value="' + new Date().toISOString().slice(0, 10) +
            '"></td>' +
            '<td><input type="number" name="items[' + index +
            '][rate]" class="form-control  dr-rate" step="0.01" min="0" required></td>' +
            '<td><input type="text" class="form-control  dr-total bg-light" readonly></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger dr-remove-row voucher-icon-btn" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateAddRowUnit(row) {
        const sel = row.querySelector('.dr-item-select');
        const opt = getSelectSelectedOption(sel);
        const unitInp = row.querySelector('.dr-unit');
        const rateInp = row.querySelector('.dr-rate');
        const availInp = row.querySelector('.dr-avail');
        if (unitInp) unitInp.value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        // Only auto-set rate if user has not manually overridden it
        if (rateInp && rateInp.dataset.manualRate !== '1' && opt && opt.dataset.rate) {
            rateInp.value = opt.dataset.rate;
        }
        if (availInp && opt && opt.dataset.available) availInp.value = opt.dataset.available;
        if (availInp) availInp.readOnly = true;
        refreshAllAvailable();
        enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
    }

    function updateAddRowLeft(row) {
        const avail = parseFloat(row.querySelector('.dr-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
        const leftInp = row.querySelector('.dr-left');
        if (leftInp) leftInp.value = Math.max(0, avail - qty).toFixed(2);
    }

    function calcDrFifoAmount(tiers, qty) {
        if (!tiers || tiers.length === 0 || qty <= 0) return null;
        let remaining = qty;
        let amount = 0;
        for (let i = 0; i < tiers.length && remaining > 0; i++) {
            const take = Math.min(remaining, parseFloat(tiers[i].quantity) || 0);
            amount += take * (parseFloat(tiers[i].unit_price) || 0);
            remaining -= take;
        }
        return remaining <= 0 ? amount : null;
    }

    function updateAddRowTotal(row) {
        const qty = parseFloat(row.querySelector('.dr-qty').value) || 0;
        const rateInp = row.querySelector('.dr-rate');
        let rate = parseFloat(rateInp.value) || 0;
        const isManualRate = rateInp && rateInp.dataset.manualRate === '1';
        const totalInp = row.querySelector('.dr-total');
        const sel = row.querySelector('.dr-item-select');
        const opt = getSelectSelectedOption(sel);
        const tiersJson = opt && opt.getAttribute('data-price-tiers');
        const tiers = tiersJson ? (function() {
            try {
                return JSON.parse(tiersJson);
            } catch (e) {
                return null;
            }
        })() : null;
        let total;
        if (!isManualRate && tiers && tiers.length > 0 && qty > 0) {
            const fifoAmount = calcDrFifoAmount(tiers, qty);
            if (fifoAmount !== null) {
                total = fifoAmount;
                rate = qty > 0 ? total / qty : 0;
                rateInp.value = rate.toFixed(2);
            } else {
                total = qty * rate;
            }
        } else {
            total = qty * rate;
        }
        if (totalInp) totalInp.value = (total || 0).toFixed(2);
        updateAddRowLeft(row);
        enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
    }

    function updateAddGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
            const totalInp = row.querySelector('.dr-total');
            if (totalInp && totalInp.value) sum += parseFloat(totalInp.value);
        });
        document.getElementById('addModalGrandTotal').textContent = '₹' + sum.toFixed(2);
    }

    // Store selection change in ADD modal
    const addStoreSelect = document.querySelector('#addReportModal select[name="inve_store_master_pk"]');
    if (addStoreSelect) {
        addStoreSelect.addEventListener('change', function() {
            const storeId = getSelectValue(this);
            currentStoreId = storeId;

            console.log('Store changed:', storeId); // Debug log

            if (!storeId) {
                filteredItems = itemSubcategories;
                updateAddItemDropdowns();
                return;
            }

            fetchStoreItems(storeId, function() {
                console.log('Filtered items count:', filteredItems.length); // Debug log
                updateAddItemDropdowns();
            });
        });
    }

    document.getElementById('addModalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('addModalItemsBody');
        const div = document.createElement('div');
        div.innerHTML = '<table><tbody>' + getAddRowHtml(addRowIndex) + '</tbody></table>';
        const newTr = div.querySelector('tr');
        tbody.appendChild(newTr);
        addRowIndex++;
        var newItemSelect = newTr.querySelector('.dr-item-select');
        if (newItemSelect && typeof Choices !== 'undefined') {
            createChoicesInstance(newItemSelect, createItemSelectConfig());
        }
        updateAddRowUnit(newTr);
        newTr.querySelector('.dr-avail').addEventListener('input', function() {
            updateAddRowLeft(newTr);
        });
        newTr.querySelector('.dr-qty').addEventListener('input', function() {
            refreshAllAvailable();
            updateAddRowTotal(newTr);
            updateAddGrandTotal();
        });
        newTr.querySelector('.dr-rate').addEventListener('input', function() {
            // Mark that the user has manually set the rate so it is not auto-overwritten
            this.dataset.manualRate = '1';
            updateAddRowTotal(newTr);
            updateAddGrandTotal();
        });
        newTr.querySelector('.dr-item-select').addEventListener('change', function() {
            // On item change, allow auto-rate again until user edits manually
            const rateInp = newTr.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '';
            updateAddRowUnit(newTr);
        });
        newTr.querySelector('.dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            refreshAllAvailable();
            updateAddGrandTotal();
            const rows = tbody.querySelectorAll('.dr-item-row');
            if (rows.length === 1) rows[0].querySelector('.dr-remove-row').disabled = true;
        });
        tbody.querySelectorAll('.dr-remove-row').forEach(function(btn) {
            btn.disabled = tbody.querySelectorAll('.dr-item-row').length <= 1;
        });
    });

    document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
        row.querySelector('.dr-item-select').addEventListener('change', function() {
            const rateInp = row.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '';
            updateAddRowUnit(row);
        });
        row.querySelector('.dr-avail').addEventListener('input', function() {
            updateAddRowLeft(row);
        });
        row.querySelector('.dr-qty').addEventListener('input', function() {
            refreshAllAvailable();
            updateAddRowTotal(row);
            updateAddGrandTotal();
        });
        row.querySelector('.dr-rate').addEventListener('input', function() {
            const rateInp = row.querySelector('.dr-rate');
            if (rateInp) rateInp.dataset.manualRate = '1';
            updateAddRowTotal(row);
            updateAddGrandTotal();
        });
    });

    document.getElementById('addModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row && document.getElementById('addModalItemsBody').querySelectorAll('.dr-item-row')
                .length > 1) {
                row.remove();
                refreshAllAvailable();
                updateAddGrandTotal();
            }
        }
    });

    // Delegate input/change on items tbody so Available Qty updates in real time when qty/rate change in any row
    const addModalItemsBodyEl = document.getElementById('addModalItemsBody');
    if (addModalItemsBodyEl) {
        addModalItemsBodyEl.addEventListener('input', function(e) {
            if (e.target.classList.contains('dr-qty') || e.target.classList.contains('dr-rate')) {
                const row = e.target.closest('.dr-item-row');
                if (row) {
                    refreshAllAvailable();
                    updateAddRowTotal(row);
                    updateAddGrandTotal();
                }
            }
        });
        addModalItemsBodyEl.addEventListener('change', function(e) {
            if (e.target.classList.contains('dr-qty') || e.target.classList.contains('dr-rate')) {
                const row = e.target.closest('.dr-item-row');
                if (row) {
                    refreshAllAvailable();
                    updateAddRowTotal(row);
                    updateAddGrandTotal();
                }
            }
        });
    }

    // Delegate input/change from add modal so Left Qty + Total update when qty/rate change
    const addReportModalEl = document.getElementById('addReportModal');
    if (addReportModalEl) {
        function onAddModalQtyOrRateInput(e) {
            if (!e.target.matches('.dr-avail, .dr-qty, .dr-rate')) return;
            const row = e.target.closest('.dr-item-row');
            if (!row) return;
            refreshAllAvailable();
            updateAddRowTotal(row);
            updateAddGrandTotal();
        }
        addReportModalEl.addEventListener('input', onAddModalQtyOrRateInput);
        addReportModalEl.addEventListener('change', onAddModalQtyOrRateInput);
    }

    // Enter key inside Item Details table triggers Add Item (and prevents form submit)
    // Dropdown (Choices.js) me Enter/Tab apna normal behaviour rakhega
    const addReportItemsTable = document.getElementById('addReportItemsTable');
    if (addReportModalEl && addReportItemsTable) {
        addReportModalEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const activeEl = document.activeElement;
            if (!activeEl || !addReportItemsTable.contains(activeEl)) return;

            // Agar Choices.js dropdown open hai (input inside .choices.is-open), toh Enter ko normal rehne do
            var choicesWrap = activeEl.closest('.choices.is-open, .ts-wrapper.is-open');
            if (choicesWrap) return; // dropdown ka apna behaviour chalega

            e.preventDefault();

            // Kisi bhi input field se Enter press => new row append karo
            const addBtn = document.getElementById('addModalAddItemRow');
            if (addBtn) {
                addBtn.click();
                // Naye row ki pehli editable field pe focus
                setTimeout(function() {
                    const tbody = document.getElementById('addModalItemsBody');
                    const lastRow = tbody ? tbody.querySelector('.dr-item-row:last-child') : null;
                    if (lastRow) {
                        const firstSelect = lastRow.querySelector('.dr-item-select');
                        if (firstSelect && firstSelect.tomselect && firstSelect.tomselect.wrapper) {
                            firstSelect.tomselect.wrapper.querySelector('.choices__inner')?.click();
                        } else if (firstSelect) {
                            firstSelect.focus();
                        }
                    }
                }, 100);
            }
        });
    }

    // Add modal: Client Type + Client Name -> Name field (Faculty / Academy Staff / Mess Staff dropdown when Employee)
    function updateDrNameField() {
        const clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('drClientNameSelect');
        const nameInput = document.getElementById('drClientNameInput');
        const facultySelect = document.getElementById('drFacultySelect');
        const academyStaffSelect = document.getElementById('drAcademyStaffSelect');
        const messStaffSelect = document.getElementById('drMessStaffSelect');
        const otStudentSelect = document.getElementById('drOtStudentSelect');
        const drCourseSelect = document.getElementById('drCourseSelect');
        const drCourseNameSelect = document.getElementById('drCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        [facultySelect, academyStaffSelect, messStaffSelect, otStudentSelect, drCourseNameSelect].forEach(function(
            s) {
            if (s) setSelectVisible(s, false);
        });
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = getSelectSelectedOption(clientNameSelect);
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;
        if (isOt) {
            nameInput.style.display = 'none';
            nameInput.removeAttribute('required');
            nameInput.removeAttribute('list');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (otStudentSelect) setSelectVisible(otStudentSelect, true);
            if (drCourseSelect) {
                setSelectVisible(drCourseSelect, false);
                if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                else drCourseSelect.value = '';
                drCourseSelect.removeAttribute('required');
            }
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Name';
            nameInput.setAttribute('required', 'required');
            nameInput.setAttribute('list', 'drCourseBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (otStudentSelect) {
                setSelectVisible(otStudentSelect, false);
                if (otStudentSelect.tomselect) otStudentSelect.tomselect.clear();
                else otStudentSelect.value = '';
                otStudentSelect.removeAttribute('required');
            }
            if (drCourseSelect) setSelectVisible(drCourseSelect, true);
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            nameInput.setAttribute('list', 'drGenericBuyerNames');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ?
                    showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) {
                    sel.setAttribute('required', 'required');
                    var nameVal = (nameInput.value || '').trim();
                    if (sel.tomselect) sel.tomselect.setValue(nameVal);
                    else sel.value = nameVal;
                    if (getSelectValue(sel)) nameInput.value = getSelectValue(sel);
                    if (nameVal && sel.tomselect) setTimeout(function() {
                        sel.tomselect.setValue(nameVal);
                    }, 0);
                } else {
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                }
            });
            if (otStudentSelect) {
                setSelectVisible(otStudentSelect, false);
                if (otStudentSelect.tomselect) otStudentSelect.tomselect.clear();
                else otStudentSelect.value = '';
                otStudentSelect.removeAttribute('required');
            }
            if (drCourseSelect) {
                setSelectVisible(drCourseSelect, false);
                if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                else drCourseSelect.value = '';
                drCourseSelect.removeAttribute('required');
            }
            if (drCourseNameSelect) {
                setSelectVisible(drCourseNameSelect, false);
                if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                else drCourseNameSelect.value = '';
                drCourseNameSelect.removeAttribute('required');
            }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }
    }
    document.querySelectorAll('#addReportModal .dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var clientNameWrap = document.getElementById('drClientNameWrap');
            var nameFieldWrap = document.getElementById('drNameFieldWrap');
            var clientIdInput = document.getElementById('drClientId');
            if (clientNameWrap) clientNameWrap.style.display = '';
            if (nameFieldWrap) nameFieldWrap.style.display = '';
            if (clientIdInput) clientIdInput.value = '';

            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('drClientNameSelect');
            const otCourseSelect = document.getElementById('drOtCourseSelect');
            const otStudentSelect = document.getElementById('drOtStudentSelect');
            const drCourseSelect = document.getElementById('drCourseSelect');
            const drCourseNameSelect = document.getElementById('drCourseNameSelect');
            const nameInput = document.getElementById('drClientNameInput');
            if (isOt) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, true);
                    otCourseSelect.setAttribute('required', 'required');
                    otCourseSelect.setAttribute('name', 'client_type_pk');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, true);
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                    otStudentSelect.setAttribute('required', 'required');
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, false);
                    drCourseSelect.removeAttribute('required');
                    drCourseSelect.removeAttribute('name');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'none';
                    nameInput.value = '';
                    nameInput.removeAttribute('required');
                }
            } else if (isCourse) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, true);
                    drCourseSelect.setAttribute('required', 'required');
                    drCourseSelect.setAttribute('name', 'client_type_pk');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Course name';
                    nameInput.setAttribute('required', 'required');
                }
            } else {
                if (clientSelect) {
                    setSelectVisible(clientSelect, true);
                    clientSelect.setAttribute('required', 'required');
                    clientSelect.setAttribute('name', 'client_type_pk');
                    rebuildClientNameSelect(clientSelect, clientNameOptionsAdd, this.value);
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (drCourseSelect) {
                    setSelectVisible(drCourseSelect, false);
                    drCourseSelect.removeAttribute('required');
                    drCourseSelect.removeAttribute('name');
                    if (drCourseSelect.tomselect) drCourseSelect.tomselect.clear();
                    else drCourseSelect.value = '';
                }
                if (drCourseNameSelect) {
                    setSelectVisible(drCourseNameSelect, false);
                    drCourseNameSelect.removeAttribute('required');
                    if (drCourseNameSelect.tomselect) drCourseNameSelect.tomselect.clear();
                    else drCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                }
            }
            updateDrNameField();
        });
    });

    function loadAddDrBuyerNames() {
        const clientTypeRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('drClientNameSelect');
        const drCourseSelect = document.getElementById('drCourseSelect');
        const nameInput = document.getElementById('drClientNameInput');
        const courseDl = document.getElementById('drCourseBuyerNames');
        const genericDl = document.getElementById('drGenericBuyerNames');
        if (!clientTypeRadio || !nameInput || !courseDl || !genericDl) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        let pk = '';
        let targetDl = null;

        if (slug === 'course') {
            pk = drCourseSelect ? getSelectValue(drCourseSelect) : '';
            targetDl = courseDl;
            nameInput.setAttribute('list', 'drCourseBuyerNames');
            genericDl.innerHTML = '';
        } else if (slug === 'section' || slug === 'other') {
            pk = clientNameSelect ? getSelectValue(clientNameSelect) : '';
            targetDl = genericDl;
            nameInput.setAttribute('list', 'drGenericBuyerNames');
            courseDl.innerHTML = '';
        } else {
            nameInput.removeAttribute('list');
            courseDl.innerHTML = '';
            genericDl.innerHTML = '';
            return;
        }

        targetDl.innerHTML = '';
        if (!pk) return;

        fetch(baseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' +
                encodeURIComponent(pk), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
            .then(r => r.json())
            .then(function(data) {
                targetDl.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    targetDl.appendChild(opt);
                });
            })
            .catch(function() {
                targetDl.innerHTML = '';
            });
    }
    document.getElementById('drOtCourseSelect').addEventListener('change', function() {
        const coursePk = getSelectValue(this);
        const otStudentSelect = document.getElementById('drOtStudentSelect');
        const nameInput = document.getElementById('drClientNameInput');
        const clientIdInput = document.getElementById('drClientId');
        if (!otStudentSelect || !nameInput) return;
        if (clientIdInput) clientIdInput.value = '';
        if (otStudentSelect.tomselect) {
            try {
                otStudentSelect.tomselect.destroy();
            } catch (e) {}
        }
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        const selectedOpt = getSelectSelectedOption(this);
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName :
            '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Student'
            });
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                (data.students || []).forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = s.display_name || '';
                    opt.textContent = s.display_name || '—';
                    opt.dataset.pk = s.pk || '';
                    otStudentSelect.appendChild(opt);
                });
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            })
            .catch(function() {
                otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            });
    });
    document.getElementById('drOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        const clientIdInput = document.getElementById('drClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    document.getElementById('drCourseSelect').addEventListener('change', function() {
        loadAddDrBuyerNames();
    });
    document.getElementById('drClientNameSelect').addEventListener('change', function() {
        const clientIdInput = document.getElementById('drClientId');
        if (clientIdInput) clientIdInput.value = '';
        updateDrNameField();
        loadAddDrBuyerNames();
    });
    document.getElementById('drFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        const clientIdInput = document.getElementById('drClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    const drAcademyStaffEl = document.getElementById('drAcademyStaffSelect');
    if (drAcademyStaffEl) drAcademyStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        const clientIdInput = document.getElementById('drClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = this.value || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    const drMessStaffEl = document.getElementById('drMessStaffSelect');
    if (drMessStaffEl) drMessStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('drClientNameInput');
        const clientIdInput = document.getElementById('drClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = this.value || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    const addChecked = document.querySelector('#addReportModal .dr-client-type-radio:checked');
    if (addChecked) addChecked.dispatchEvent(new Event('change'));
    loadAddDrBuyerNames();

    // Edit modal: same Faculty / Academy Staff / Mess Staff dropdown logic
    function isEditClientIdentityFrozen() {
        var modal = document.getElementById('editReportModal');
        return !!(modal && modal.classList.contains('edit-client-identity-frozen'));
    }

    function freezeEditClientIdentityFields(v) {
        var modal = document.getElementById('editReportModal');
        if (!modal) return;
        modal.classList.add('edit-client-identity-frozen');

        var nameInput = document.getElementById('editDrClientNameInput');
        if (nameInput) {
            nameInput.readOnly = true;
            nameInput.classList.add('bg-light');
            nameInput.removeAttribute('list');
            if (v && v.client_name != null) {
                nameInput.value = String(v.client_name || '');
            }
        }

        var clientIdInput = document.getElementById('editDrClientId');
        if (clientIdInput && v && v.client_id != null && String(v.client_id) !== '') {
            clientIdInput.value = String(v.client_id);
        }
    }

    function unfreezeEditClientIdentityFields() {
        var modal = document.getElementById('editReportModal');
        if (modal) modal.classList.remove('edit-client-identity-frozen');
    }

    function updateEditDrNameField() {
        const clientTypeRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editDrClientNameSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        const facultySelect = document.getElementById('editDrFacultySelect');
        const academyStaffSelect = document.getElementById('editDrAcademyStaffSelect');
        const messStaffSelect = document.getElementById('editDrMessStaffSelect');
        const editDrCourseSelect = document.getElementById('editDrCourseSelect');
        const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
        if (!clientTypeRadio || !clientNameSelect || !nameInput) return;
        [facultySelect, academyStaffSelect, messStaffSelect, document.getElementById('editDrOtStudentSelect'),
            editDrCourseNameSelect
        ].forEach(function(s) {
            if (s) setSelectVisible(s, false);
        });
        const isEmployee = (clientTypeRadio.value || '').toLowerCase() === 'employee';
        const isOt = (clientTypeRadio.value || '').toLowerCase() === 'ot';
        const isCourse = (clientTypeRadio.value || '').toLowerCase() === 'course';
        const opt = getSelectSelectedOption(clientNameSelect);
        const clientNameVal = (opt && opt.dataset.clientName) ? opt.dataset.clientName : '';
        const isFaculty = clientNameVal === 'faculty';
        const isAcademyStaff = clientNameVal === 'academy staff';
        const isMessStaff = clientNameVal === 'mess staff';
        const showFaculty = isEmployee && isFaculty;
        const showAcademyStaff = isEmployee && isAcademyStaff;
        const showMessStaff = isEmployee && isMessStaff;
        const showAny = showFaculty || showAcademyStaff || showMessStaff;
        if (isOt) {
            nameInput.style.display = 'block';
            nameInput.readOnly = true;
            nameInput.placeholder = 'Buyer name (OT)';
            nameInput.removeAttribute('required');
            nameInput.removeAttribute('list');
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            var editOtStu2 = document.getElementById('editDrOtStudentSelect');
            if (editOtStu2) {
                setSelectVisible(editOtStu2, false);
                editOtStu2.removeAttribute('required');
            }
            if (editDrCourseSelect) {
                setSelectVisible(editDrCourseSelect, false);
                if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                else editDrCourseSelect.value = '';
                editDrCourseSelect.removeAttribute('required');
            }
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
        } else if (isCourse) {
            nameInput.style.display = 'block';
            nameInput.placeholder = 'Name';
            nameInput.setAttribute('required', 'required');
            if (!isEditClientIdentityFrozen()) {
                nameInput.setAttribute('list', 'editDrCourseBuyerNames');
            } else {
                nameInput.removeAttribute('list');
            }
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (sel) {
                    setSelectVisible(sel, false);
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                    sel.removeAttribute('required');
                }
            });
            if (editDrCourseSelect) setSelectVisible(editDrCourseSelect, true);
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
        } else {
            nameInput.style.display = showAny ? 'none' : 'block';
            nameInput.removeAttribute('required');
            if (!isEditClientIdentityFrozen()) {
                nameInput.setAttribute('list', 'editDrGenericBuyerNames');
            } else {
                nameInput.removeAttribute('list');
            }
            [facultySelect, academyStaffSelect, messStaffSelect].forEach(function(sel) {
                if (!sel) return;
                const show = sel === facultySelect ? showFaculty : (sel === academyStaffSelect ?
                    showAcademyStaff : showMessStaff);
                setSelectVisible(sel, show);
                sel.removeAttribute('required');
                if (show) {
                    sel.setAttribute('required', 'required');
                    var nameVal = (nameInput.value || '').trim();
                    if (sel.tomselect) sel.tomselect.setValue(nameVal);
                    else sel.value = nameVal;
                    if (getSelectValue(sel)) nameInput.value = getSelectValue(sel);
                    if (nameVal && sel.tomselect) setTimeout(function() {
                        sel.tomselect.setValue(nameVal);
                    }, 0);
                } else {
                    if (sel.tomselect) sel.tomselect.clear();
                    else sel.value = '';
                }
            });
            if (editDrCourseSelect) {
                setSelectVisible(editDrCourseSelect, false);
                if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                else editDrCourseSelect.value = '';
                editDrCourseSelect.removeAttribute('required');
            }
            if (editDrCourseNameSelect) {
                setSelectVisible(editDrCourseNameSelect, false);
                if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                else editDrCourseNameSelect.value = '';
                editDrCourseNameSelect.removeAttribute('required');
            }
            if (!showAny) nameInput.setAttribute('required', 'required');
        }

        // Always keep Name non-editable on Edit.
        nameInput.readOnly = true;
        nameInput.classList.add('bg-light');
        nameInput.removeAttribute('list');
    }

    function loadEditDrBuyerNames() {
        const clientTypeRadio = document.querySelector('#editReportModal .edit-dr-client-type-radio:checked');
        const clientNameSelect = document.getElementById('editDrClientNameSelect');
        const drCourseSelect = document.getElementById('editDrCourseSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        const courseDl = document.getElementById('editDrCourseBuyerNames');
        const genericDl = document.getElementById('editDrGenericBuyerNames');
        if (!clientTypeRadio || !nameInput || !courseDl || !genericDl) return;

        const slug = (clientTypeRadio.value || '').toLowerCase();
        let pk = '';
        let targetDl = null;

        if (slug === 'course') {
            pk = drCourseSelect ? getSelectValue(drCourseSelect) : '';
            targetDl = courseDl;
            nameInput.setAttribute('list', 'editDrCourseBuyerNames');
            genericDl.innerHTML = '';
        } else if (slug === 'section' || slug === 'other') {
            pk = clientNameSelect ? getSelectValue(clientNameSelect) : '';
            targetDl = genericDl;
            nameInput.setAttribute('list', 'editDrGenericBuyerNames');
            courseDl.innerHTML = '';
        } else {
            nameInput.removeAttribute('list');
            courseDl.innerHTML = '';
            genericDl.innerHTML = '';
            return;
        }

        targetDl.innerHTML = '';
        if (!pk) return;

        fetch(baseUrl + '/buyer-names?client_type_slug=' + encodeURIComponent(slug) + '&client_type_pk=' +
                encodeURIComponent(pk), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
            .then(r => r.json())
            .then(function(data) {
                targetDl.innerHTML = '';
                (data.buyers || []).forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b;
                    targetDl.appendChild(opt);
                });
            })
            .catch(function() {
                targetDl.innerHTML = '';
            });
    }
    document.querySelectorAll('#editReportModal .edit-dr-client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (isEditClientIdentityFrozen()) return;
            var editClientNameWrap = document.getElementById('editDrClientNameWrap');
            var editNameFieldWrap = document.getElementById('editDrNameFieldWrap');
            var clientIdInput = document.getElementById('editDrClientId');
            if (editClientNameWrap) editClientNameWrap.style.display = '';
            if (editNameFieldWrap) editNameFieldWrap.style.display = '';
            if (clientIdInput) clientIdInput.value = '';

            const isOt = (this.value || '').toLowerCase() === 'ot';
            const isCourse = (this.value || '').toLowerCase() === 'course';
            const clientSelect = document.getElementById('editDrClientNameSelect');
            const otCourseSelect = document.getElementById('editDrOtCourseSelect');
            const otStudentSelect = document.getElementById('editDrOtStudentSelect');
            const editDrCourseSelect = document.getElementById('editDrCourseSelect');
            const editDrCourseNameSelect = document.getElementById('editDrCourseNameSelect');
            const nameInput = document.getElementById('editDrClientNameInput');
            if (isOt) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, true);
                    otCourseSelect.setAttribute('required', 'required');
                    otCourseSelect.setAttribute('name', 'client_type_pk');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, true);
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select course first</option>';
                    otStudentSelect.setAttribute('required', 'required');
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, false);
                    editDrCourseSelect.removeAttribute('required');
                    editDrCourseSelect.removeAttribute('name');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'none';
                    nameInput.value = '';
                    nameInput.removeAttribute('required');
                }
            } else if (isCourse) {
                if (clientSelect) {
                    setSelectVisible(clientSelect, false);
                    clientSelect.removeAttribute('required');
                    if (clientSelect.tomselect) clientSelect.tomselect.clear();
                    else clientSelect.value = '';
                    clientSelect.removeAttribute('name');
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, true);
                    editDrCourseSelect.setAttribute('required', 'required');
                    editDrCourseSelect.setAttribute('name', 'client_type_pk');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.value = '';
                    nameInput.placeholder = 'Course name';
                    nameInput.setAttribute('required', 'required');
                }
            } else {
                if (clientSelect) {
                    setSelectVisible(clientSelect, true);
                    clientSelect.setAttribute('required', 'required');
                    clientSelect.setAttribute('name', 'client_type_pk');
                    rebuildEditClientNameSelect(this.value);
                }
                if (otCourseSelect) {
                    setSelectVisible(otCourseSelect, false);
                    otCourseSelect.removeAttribute('required');
                    otCourseSelect.removeAttribute('name');
                    if (otCourseSelect.tomselect) otCourseSelect.tomselect.clear();
                    else otCourseSelect.value = '';
                }
                if (otStudentSelect) {
                    setSelectVisible(otStudentSelect, false);
                    otStudentSelect.removeAttribute('required');
                    if (otStudentSelect.tomselect) {
                        try {
                            otStudentSelect.tomselect.destroy();
                        } catch (e) {}
                    }
                    otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                    if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                        allowEmptyOption: true,
                        dropdownParent: 'body',
                        placeholder: 'Select Student'
                    });
                }
                if (editDrCourseSelect) {
                    setSelectVisible(editDrCourseSelect, false);
                    editDrCourseSelect.removeAttribute('required');
                    editDrCourseSelect.removeAttribute('name');
                    if (editDrCourseSelect.tomselect) editDrCourseSelect.tomselect.clear();
                    else editDrCourseSelect.value = '';
                }
                if (editDrCourseNameSelect) {
                    setSelectVisible(editDrCourseNameSelect, false);
                    editDrCourseNameSelect.removeAttribute('required');
                    if (editDrCourseNameSelect.tomselect) editDrCourseNameSelect.tomselect.clear();
                    else editDrCourseNameSelect.value = '';
                }
                if (nameInput) {
                    nameInput.style.display = 'block';
                    nameInput.placeholder = 'Client / section / role name';
                    nameInput.setAttribute('required', 'required');
                }
            }
            updateEditDrNameField();
            loadEditDrBuyerNames();
        });
    });
    document.getElementById('editDrOtCourseSelect').addEventListener('change', function() {
        const coursePk = getSelectValue(this);
        const otStudentSelect = document.getElementById('editDrOtStudentSelect');
        const nameInput = document.getElementById('editDrClientNameInput');
        const clientIdInput = document.getElementById('editDrClientId');
        if (!otStudentSelect || !nameInput) return;
        if (clientIdInput) clientIdInput.value = '';
        if (otStudentSelect.tomselect) {
            try {
                otStudentSelect.tomselect.destroy();
            } catch (e) {}
        }
        otStudentSelect.innerHTML = '<option value="">Loading...</option>';
        const selectedOpt = getSelectSelectedOption(this);
        nameInput.value = (selectedOpt && selectedOpt.dataset.courseName) ? selectedOpt.dataset.courseName :
            '';
        if (!coursePk) {
            otStudentSelect.innerHTML = '<option value="">Select course first</option>';
            if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                allowEmptyOption: true,
                dropdownParent: 'body',
                placeholder: 'Select Student'
            });
            return;
        }
        fetch(baseUrl + '/students-by-course/' + coursePk, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                otStudentSelect.innerHTML = '<option value="">Select Student</option>';
                (data.students || []).forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = s.display_name || '';
                    opt.textContent = s.display_name || '—';
                    opt.dataset.pk = s.pk || '';
                    otStudentSelect.appendChild(opt);
                });
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            })
            .catch(function() {
                otStudentSelect.innerHTML = '<option value="">Error loading students</option>';
                if (typeof Choices !== 'undefined') createChoicesInstance(otStudentSelect, {
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    placeholder: 'Select Student'
                });
            });
    });
    document.getElementById('editDrOtStudentSelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        const clientIdInput = document.getElementById('editDrClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    document.getElementById('editDrCourseSelect').addEventListener('change', function() {
        loadEditDrBuyerNames();
    });
    document.getElementById('editDrClientNameSelect').addEventListener('change', function() {
        const clientIdInput = document.getElementById('editDrClientId');
        if (clientIdInput) clientIdInput.value = '';
        updateEditDrNameField();
        loadEditDrBuyerNames();
    });
    document.getElementById('editDrFacultySelect').addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        const clientIdInput = document.getElementById('editDrClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    const editDrAcademyStaffEl = document.getElementById('editDrAcademyStaffSelect');
    if (editDrAcademyStaffEl) editDrAcademyStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        const clientIdInput = document.getElementById('editDrClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });
    const editDrMessStaffEl = document.getElementById('editDrMessStaffSelect');
    if (editDrMessStaffEl) editDrMessStaffEl.addEventListener('change', function() {
        const inp = document.getElementById('editDrClientNameInput');
        const clientIdInput = document.getElementById('editDrClientId');
        const selectedOpt = getSelectSelectedOption(this);
        if (inp) inp.value = getSelectValue(this) || '';
        if (clientIdInput && selectedOpt) clientIdInput.value = selectedOpt.dataset.pk || '';
    });

    function ensureEditClientTypePkOption(selectEl, clientTypePk, label) {
        if (!selectEl || clientTypePk === null || typeof clientTypePk === 'undefined' || String(clientTypePk) === '') {
            return;
        }
        const pk = String(clientTypePk);
        const exists = Array.from(selectEl.options || []).some(function(opt) {
            return String(opt.value) === pk;
        });
        if (exists) {
            return;
        }
        const opt = document.createElement('option');
        opt.value = pk;
        opt.textContent = (label && String(label).trim()) ? String(label).trim() : ('Category #' + pk);
        selectEl.appendChild(opt);
    }

    function syncEditListingFilterHiddens() {
        const wrap = document.getElementById('editListingFilterHiddens');
        if (!wrap) return;
        wrap.innerHTML = '';
        const params = new URLSearchParams(window.location.search || '');
        // NOTE: do NOT sync client_type_pk — it collides with edit form field name
        // and overwrites the voucher client category on submit.
        const keys = ['start_date', 'end_date', 'client_type', 'buyer_name', 'return_status'];
        keys.forEach(function(key) {
            const values = params.getAll(key);
            if (!values.length) return;
            values.forEach(function(val) {
                if (val === null || val === '') return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = val;
                wrap.appendChild(input);
            });
        });
        params.getAll('store[]').concat(params.getAll('store')).forEach(function(val) {
            if (val === null || val === '') return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'store[]';
            input.value = val;
            wrap.appendChild(input);
        });
        params.getAll('status[]').concat(params.getAll('status')).forEach(function(val) {
            if (val === null || val === '') return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'status[]';
            input.value = val;
            wrap.appendChild(input);
        });
    }

    function prepareEditFormForSubmit(formEl) {
        if (!formEl) return;
        syncEditListingFilterHiddens();

        // Ensure Choices-backed selects keep a submittable native value.
        formEl.querySelectorAll('select').forEach(function(sel) {
            if (sel.disabled) {
                sel.disabled = false;
            }
            if (sel.tomselect && typeof sel.tomselect.getValue === 'function') {
                try {
                    const v = sel.tomselect.getValue();
                    const normalized = Array.isArray(v) ? (v[0] || '') : (v || '');
                    if (normalized !== '') {
                        sel.value = String(normalized);
                    }
                } catch (e) {}
            }
        });

        // Guarantee new-row store_id is posted even if Choices UI desyncs.
        formEl.querySelectorAll('#editModalItemsBody .edit-dr-item-row').forEach(function(row) {
            const storeSel = row.querySelector('.edit-dr-store-select');
            if (!storeSel) return;
            let storeVal = '';
            if (storeSel.tomselect && typeof storeSel.tomselect.getValue === 'function') {
                try {
                    const v = storeSel.tomselect.getValue();
                    storeVal = Array.isArray(v) ? (v[0] || '') : (v || '');
                } catch (e) {
                    storeVal = storeSel.value || '';
                }
            } else {
                storeVal = storeSel.value || '';
            }
            storeSel.disabled = false;
            storeSel.value = storeVal;
            // Keep a single source of truth hidden input for store_id.
            let backup = row.querySelector('input.edit-dr-store-id-backup');
            if (!backup) {
                backup = document.createElement('input');
                backup.type = 'hidden';
                backup.className = 'edit-dr-store-id-backup';
                storeSel.insertAdjacentElement('afterend', backup);
            }
            backup.name = storeSel.getAttribute('name') || '';
            backup.value = storeVal;
            storeSel.removeAttribute('name');
        });

        // Ensure legacy client_type_pk is posted even if dropdown options were re-seeded.
        if (formEl.dataset && formEl.dataset.clientTypePk) {
            let pkInput = formEl.querySelector('select[name="client_type_pk"], input[name="client_type_pk"]');
            const legacyPk = String(formEl.dataset.clientTypePk);
            if (pkInput) {
                if (!pkInput.value || String(pkInput.value) === '') {
                    if (pkInput.tagName === 'SELECT') {
                        ensureEditClientTypePkOption(pkInput, legacyPk, formEl.dataset.clientName || '');
                    }
                    pkInput.value = legacyPk;
                    if (pkInput.tomselect && typeof pkInput.tomselect.setValue === 'function') {
                        try { pkInput.tomselect.setValue(legacyPk); } catch (e) {}
                    }
                }
            } else {
                const hiddenPk = document.createElement('input');
                hiddenPk.type = 'hidden';
                hiddenPk.name = 'client_type_pk';
                hiddenPk.value = legacyPk;
                formEl.appendChild(hiddenPk);
            }
        }
    }

    function applyEditFilteredStoreDisplay(v) {
        const editStoreSelect = document.querySelector('#editReportModal select.edit-store-id');
        const editMultiStoreFlag = document.getElementById('editMultiStoreFlag');
        const editFilteredEditFlag = document.getElementById('editFilteredEditFlag');
        const sid = v ? (v.store_id || v.inve_store_master_pk || '') : '';

        if (editMultiStoreFlag) {
            editMultiStoreFlag.value = (v && v.multi_store) ? '1' : '0';
        }
        if (editFilteredEditFlag) {
            editFilteredEditFlag.value = (v && v.filtered_view) ? '1' : '0';
        }

        editCurrentStoreName = (v && (v.store_name_display || v.store_name))
            ? String(v.store_name_display || v.store_name)
            : '';

        if (editStoreSelect) {
            editStoreSelect.classList.add('d-none');
            editStoreSelect.removeAttribute('required');
            if (sid !== '') {
                editStoreSelect.value = String(sid);
            }
        }

        syncEditListingFilterHiddens();
    }

    function getEditStoreOptionsHtml(selectedStoreId) {
        const storeSelect = document.querySelector('#editReportModal select.edit-store-id');
        let html = '<option value="">Select Store</option>';
        if (!storeSelect) {
            return html;
        }
        Array.from(storeSelect.options).forEach(function(opt) {
            if (!opt.value) {
                return;
            }
            const selected = String(selectedStoreId || '') === String(opt.value) ? ' selected' : '';
            html += '<option value="' + String(opt.value).replace(/"/g, '&quot;') + '"' + selected + '>' +
                String(opt.textContent || '').replace(/</g, '&lt;') + '</option>';
        });
        return html;
    }

    function fillEditRowItemOptions(row, items, selectedItemId) {
        const select = row ? row.querySelector('.edit-dr-item-select') : null;
        if (!select) {
            return;
        }
        const currentValue = selectedItemId != null ? String(selectedItemId) : getSelectValue(select);
        if (select.tomselect) {
            try {
                select.tomselect.destroy();
            } catch (e) {}
        }
        select.innerHTML = '<option value="">Select Item</option>';
        (items || []).forEach(function(item) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.item_name || '—';
            option.setAttribute('data-unit', item.unit_measurement || '');
            option.setAttribute('data-rate', item.standard_cost || 0);
            option.setAttribute('data-available', item.available_quantity || 0);
            if (item.price_tiers && item.price_tiers.length > 0) {
                option.setAttribute('data-price-tiers', JSON.stringify(item.price_tiers));
            }
            if (String(item.id) === String(currentValue)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        if (typeof Choices !== 'undefined') {
            createChoicesInstance(select, createItemSelectConfig());
        }
    }

    function bindEditRowStoreSelect(row) {
        const storeSel = row ? row.querySelector('.edit-dr-store-select') : null;
        if (!storeSel || storeSel.dataset.bound === '1') {
            return;
        }
        storeSel.dataset.bound = '1';
        storeSel.addEventListener('change', function() {
            const storeId = getSelectValue(this);
            const itemSelect = row.querySelector('.edit-dr-item-select');
            const unitInp = row.querySelector('.edit-dr-unit');
            const availInp = row.querySelector('.edit-dr-avail');
            const rateInp = row.querySelector('.edit-dr-rate');
            const qtyInp = row.querySelector('.edit-dr-qty');
            if (itemSelect) {
                setSelectValue(itemSelect, '');
            }
            if (unitInp) unitInp.value = '—';
            if (availInp) availInp.value = '';
            if (rateInp) rateInp.value = '';
            if (qtyInp) qtyInp.value = '';
            updateEditRowTotal(row);
            updateEditGrandTotal();
            if (!storeId) {
                fillEditRowItemOptions(row, [], '');
                refreshEditAllAvailable();
                return;
            }
            fetchStoreItems(storeId, function() {
                fillEditRowItemOptions(row, filteredItems, '');
                refreshEditAllAvailable();
            });
        });
    }

    // Edit modal row helpers
    function getEditRowHtml(index, item) {
        item = item || {};
        const isExistingLine = !!(item.id);
        const sourceItems = (!isExistingLine && !(item.store_id || editCurrentStoreId))
            ? []
            : (Array.isArray(filteredItems) && filteredItems.length > 0 ? filteredItems : itemSubcategories);
        let options = sourceItems.map(s => {
            let attrs = 'data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') +
                '" data-rate="' + (s.standard_cost || 0) + '" data-available="' + (s.available_quantity ||
                    0) + '"';
            if (s.price_tiers && s.price_tiers.length > 0) {
                attrs += ' data-price-tiers="' + (JSON.stringify(s.price_tiers) || '').replace(/"/g,
                    '&quot;') + '"';
            }
            return '<option value="' + s.id + '" ' + attrs + (item.item_subcategory_id == s.id ?
                ' selected' : '') + '>' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>';
        });
        if (item.item_subcategory_id && !sourceItems.some(function(s) {
                return String(s.id) === String(item.item_subcategory_id);
            })) {
            options.unshift('<option value="' + item.item_subcategory_id + '" selected data-unit="' + (item
                    .unit || '').replace(/"/g, '&quot;') + '" data-rate="' + (item.rate != null ? item
                    .rate : 0) + '" data-available="' + (item.available_quantity != null ? item
                    .available_quantity : 0) + '">' + (item.item_name || '—').replace(/</g, '&lt;') +
                '</option>');
        }
        const optionsHtml = options.join('');
        const avail = item.available_quantity != null ? item.available_quantity : '';
        const qty = item.quantity != null ? item.quantity : '';
        const rate = item.rate != null ? item.rate : '';
        const issueDate = item.issue_date || '';
        const total = (qty && rate) ? (parseFloat(qty) * parseFloat(rate)).toFixed(2) : '';
        const left = (avail !== '' && qty !== '') ? Math.max(0, parseFloat(avail) - parseFloat(qty)).toFixed(2) :
            '';
        const originalQtyAttr = (item.quantity != null && item.quantity !== '') ? (' data-original-qty="' + (
            parseFloat(item.quantity) || 0) + '"') : '';
        const lineIdField = item.id ? ('<input type="hidden" name="items[' + index + '][line_id]" value="' +
            item.id + '">') : '';
        const storeId = item.store_id || (!isExistingLine ? '' : (editCurrentStoreId || ''));
        const storeName = String(item.store_name || (!isExistingLine ? '' : editCurrentStoreName) || '—')
            .replace(/</g, '&lt;').replace(/"/g, '&quot;');
        let storeCell = '';
        if (isExistingLine) {
            storeCell = '<td class="text-wrap text-break small">' +
                '<input type="hidden" name="items[' + index + '][store_id]" value="' +
                String(storeId || '').replace(/"/g, '&quot;') + '">' + storeName + '</td>';
        } else {
            storeCell = '<td><select name="items[' + index +
                '][store_id]" class="form-select edit-dr-store-select" required>' +
                getEditStoreOptionsHtml(storeId) + '</select></td>';
        }
        return '<tr class="edit-dr-item-row"' + originalQtyAttr + '>' +
            storeCell +
            '<td>' + lineIdField + '<select name="items[' + index +
            '][item_subcategory_id]" class="form-select  edit-dr-item-select" required><option value="">Select Item</option>' +
            optionsHtml + '</select></td>' +
            '<td><input type="text" name="items[' + index +
            '][unit]" class="form-control  edit-dr-unit" readonly placeholder="—" value="' + (item.unit || '')
            .replace(/"/g, '&quot;') + '"></td>' +
            '<td><input type="text" name="items[' + index +
            '][available_quantity]" class="form-control  edit-dr-avail bg-light" value="' + avail +
            '" readonly></td>' +
            '<td><input type="text" name="items[' + index +
            '][quantity]" class="form-control  edit-dr-qty" required value="' + qty +
            '"><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control  edit-dr-left bg-light" readonly value="' + left +
            '"></td>' +
            '<td><input type="date" name="items[' + index +
            '][issue_date]" class="form-control  edit-dr-issue-date"' + (isExistingLine ? '' :
                ' required') + ' value="' + issueDate + '"></td>' +
            '<td><input type="text" name="items[' + index +
            '][rate]" class="form-control  edit-dr-rate" required value="' + rate + '"></td>' +
            '<td><input type="text" class="form-control  edit-dr-total bg-light" readonly value="' + total +
            '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger edit-dr-remove-row voucher-icon-btn" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateEditRowLeft(row) {
        const avail = parseFloat(row.querySelector('.edit-dr-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
        const leftInp = row.querySelector('.edit-dr-left');
        if (leftInp) leftInp.value = Math.max(0, avail - qty).toFixed(2);
    }

    /**
     * Recalculate Available Qty and Left Qty for all rows in the Edit modal (Selling Voucher with Date Range).
     * Effective base per item = current stock + sum of original qtys (from this voucher) for that item.
     * Then each row gets available = base - already used in previous rows (same logic as Add mode).
     */
    function refreshEditAllAvailable() {
        const rows = document.querySelectorAll('#editModalItemsBody .edit-dr-item-row');
        if (!rows.length) return;

        const effectiveBaseByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.edit-dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            if (!itemId) return;
            const originalQty = parseFloat(row.getAttribute('data-original-qty')) || 0;
            if (!effectiveBaseByItem.hasOwnProperty(itemId)) {
                effectiveBaseByItem[itemId] = getBaseAvailableForItem(itemId);
            }
            effectiveBaseByItem[itemId] += originalQty;
        });

        const usedByItem = {};
        rows.forEach(function(row) {
            const select = row.querySelector('.edit-dr-item-select');
            const itemId = select ? getSelectValue(select) : '';
            const availInp = row.querySelector('.edit-dr-avail');
            const leftInp = row.querySelector('.edit-dr-left');
            if (!itemId || !availInp) return;

            const effectiveBase = effectiveBaseByItem[itemId] != null ? effectiveBaseByItem[itemId] :
                getBaseAvailableForItem(itemId);
            const alreadyUsed = usedByItem[itemId] || 0;
            const availableForRow = Math.max(0, effectiveBase - alreadyUsed);

            availInp.value = availableForRow.toFixed(2);

            const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
            if (leftInp) {
                leftInp.value = Math.max(0, availableForRow - qty).toFixed(2);
            }

            usedByItem[itemId] = alreadyUsed + qty;
            enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
        });
    }

    function updateEditRowTotal(row) {
        const qty = parseFloat(row.querySelector('.edit-dr-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.edit-dr-rate').value) || 0;
        const totalInp = row.querySelector('.edit-dr-total');
        if (totalInp) totalInp.value = (qty * rate).toFixed(2);
        updateEditRowLeft(row);
        enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
    }

    function updateEditGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#editModalItemsBody .edit-dr-item-row').forEach(function(row) {
            const totalInp = row.querySelector('.edit-dr-total');
            if (totalInp && totalInp.value) sum += parseFloat(totalInp.value);
        });
        document.getElementById('editModalGrandTotal').textContent = '₹' + sum.toFixed(2);
    }

    document.getElementById('editModalAddItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editModalItemsBody');
        const trContent = getEditRowHtml(editRowIndex, {});
        const div = document.createElement('div');
        div.innerHTML = '<table><tbody>' + trContent + '</tbody></table>';
        const newTr = div.querySelector('tr');
        tbody.appendChild(newTr);
        editRowIndex++;
        bindEditRowStoreSelect(newTr);
        // Use native store select (no Choices) so store_id always submits reliably.
        const sel = newTr.querySelector('.edit-dr-item-select');
        if (sel && typeof Choices !== 'undefined') createChoicesInstance(sel, createItemSelectConfig());
        const opt = getSelectSelectedOption(sel);
        newTr.querySelector('.edit-dr-unit').value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
        const initAvailInp = newTr.querySelector('.edit-dr-avail');
        if (initAvailInp && opt && opt.dataset.available) {
            initAvailInp.value = opt.dataset.available;
        }
        refreshEditAllAvailable();
        newTr.querySelector('.edit-dr-avail').addEventListener('input', function() {
            updateEditRowLeft(newTr);
        });
        newTr.querySelector('.edit-dr-qty').addEventListener('input', function() {
            refreshEditAllAvailable();
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-rate').addEventListener('input', function() {
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-item-select').addEventListener('change', function() {
            const o = getSelectSelectedOption(this);
            newTr.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit :
                '—';
            const rateInp = newTr.querySelector('.edit-dr-rate');
            if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
            const availInp = newTr.querySelector('.edit-dr-avail');
            if (availInp && o && o.dataset.available) {
                availInp.value = o.dataset.available;
            }
            refreshEditAllAvailable();
            updateEditRowTotal(newTr);
            updateEditGrandTotal();
        });
        newTr.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
            newTr.remove();
            refreshEditAllAvailable();
            updateEditGrandTotal();
        });
    });

    document.getElementById('editModalItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-dr-remove-row')) {
            const row = e.target.closest('tr');
            if (row) {
                row.remove();
                refreshEditAllAvailable();
                updateEditGrandTotal();
            }
        }
    });

    // Edit modal keyboard flow:
    // - Dropdowns: Enter behaves like Tab (next focus)
    // - Last row Rate: Enter appends a new row
    // - Other fields: block Enter to avoid accidental submit
    const editReportFormKeydownEl = document.getElementById('editReportForm');
    const editModalItemsBodyEl = document.getElementById('editModalItemsBody');
    if (editReportFormKeydownEl && editModalItemsBodyEl) {
        function getNextEditFocusable(currentEl) {
            const modalEl = document.getElementById('editReportModal');
            if (!modalEl) return null;
            const focusable = Array.from(modalEl.querySelectorAll('input, select, textarea, button, [tabindex]'))
                .filter(function(el) {
                    if (el.disabled) return false;
                    if (el.getAttribute('tabindex') === '-1') return false;
                    if (el.type === 'hidden') return false;
                    if (el.offsetParent === null) return false;
                    return true;
                });
            const currentIndex = focusable.indexOf(currentEl);
            return currentIndex >= 0 && currentIndex < focusable.length - 1 ? focusable[currentIndex + 1] : null;
        }

        function focusNextFromDropdown(activeEl) {
            const wrapper = activeEl && activeEl.closest ? activeEl.closest('.ts-wrapper') : null;
            if (wrapper) {
                const nextEl = getNextEditFocusable(wrapper);
                if (nextEl && typeof nextEl.focus === 'function') nextEl.focus();
                return true;
            }
            if (activeEl && activeEl.matches && activeEl.matches('select')) {
                const nextEl = getNextEditFocusable(activeEl);
                if (nextEl && typeof nextEl.focus === 'function') nextEl.focus();
                return true;
            }
            return false;
        }

        editReportFormKeydownEl.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            const activeEl = document.activeElement;
            if (!activeEl || !editReportFormKeydownEl.contains(activeEl)) return;

            // Agar Choices.js dropdown open hai, toh Enter ko normal rehne do
            var choicesWrap = activeEl.closest('.choices.is-open, .ts-wrapper.is-open');
            if (choicesWrap) return;

            // Item table ke andar kisi bhi field se Enter => new row append
            const row = activeEl.closest('.edit-dr-item-row');
            if (row) {
                e.preventDefault();
                const addBtn = document.getElementById('editModalAddItemRow');
                if (addBtn) {
                    addBtn.click();
                    setTimeout(function() {
                        const lastRow = editModalItemsBodyEl.querySelector('.edit-dr-item-row:last-child');
                        if (lastRow) {
                            const storeSelect = lastRow.querySelector('.edit-dr-store-select');
                            const firstSelect = storeSelect || lastRow.querySelector('.edit-dr-item-select');
                            if (firstSelect && firstSelect.tomselect && firstSelect.tomselect.wrapper) {
                                var inner = firstSelect.tomselect.wrapper.querySelector('.choices__inner');
                                if (inner) inner.click();
                            } else if (firstSelect) {
                                firstSelect.focus();
                            }
                        }
                    }, 100);
                }
                return;
            }

            if (focusNextFromDropdown(activeEl)) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
        }, true);
    }

    // View report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-view-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        const viewQuery = window.location.search || '';
        fetch(baseUrl + '/' + reportId + viewQuery, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                const v = data.voucher;
                document.getElementById('viewReportModalLabel').textContent =
                    'View Selling Voucher with Date Range #' + (v.id || reportId);
                document.getElementById('viewRequestDate').textContent = v.request_date || '—';
                document.getElementById('viewStoreName').textContent = v.store_name || '—';
                document.getElementById('viewReferenceNumber').textContent = v.reference_number || '—';
                document.getElementById('viewOrderBy').textContent = v.order_by || '—';
                document.getElementById('viewClientType').textContent = v.client_type || '—';
                document.getElementById('viewClientName').textContent = (v.client_name_text || v
                    .client_name || '—');
                document.getElementById('viewPaymentType').textContent = v.payment_type || '—';
                const statusEl = document.getElementById('viewStatus');
                statusEl.innerHTML = v.status === 0 ?
                    '<span class="badge rounded-1 text-bg-warning">Pending</span>' : (v.status === 2 ?
                        '<span class="badge rounded-1 text-bg-success">Approved</span>' : (v.status ===
                            4 ? '<span class="badge rounded-1 text-bg-primary">Completed</span>' :
                            '<span class="badge rounded-1 text-bg-secondary">' + (v.status_label || v
                                .status) + '</span>'));
                if (v.remarks) {
                    document.getElementById('viewRemarksWrap').style.display = 'block';
                    document.getElementById('viewRemarks').textContent = v.remarks;
                } else {
                    document.getElementById('viewRemarksWrap').style.display = 'none';
                }
                // Bill display removed; keep view logic resilient if elements are absent
                const tbody = document.getElementById('viewReportItemsBody');
                tbody.innerHTML = '';
                if (data.has_items && data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        tbody.insertAdjacentHTML('beforeend', '<tr><td>' + (item.item_name ||
                                '—') + '</td><td>' + (item.unit || '—') + '</td><td>' + item
                            .quantity + '</td><td>' + (item.return_quantity || 0) +
                            '</td><td>₹' + item.rate + '</td><td>₹' + item.amount +
                            '</td><td>' + (item.issue_date || '—') + '</td></tr>');
                    });
                    document.getElementById('viewReportGrandTotal').textContent = data.grand_total ||
                        '0.00';
                    document.getElementById('viewReportItemsCard').style.display = 'block';
                } else {
                    document.getElementById('viewReportItemsCard').style.display = 'none';
                }
                document.getElementById('viewCreatedAt').textContent = v.created_at || '—';
                if (v.updated_at) {
                    document.getElementById('viewUpdatedAtWrap').style.display = 'inline';
                    document.getElementById('viewUpdatedAt').textContent = v.updated_at;
                } else {
                    document.getElementById('viewUpdatedAtWrap').style.display = 'none';
                }
                new bootstrap.Modal(document.getElementById('viewReportModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load report.');
            });
    }, true);

    // Return item modal (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-return-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        const returnQuery = window.location.search || '';
        fetch(baseUrl + '/' + reportId + '/return' + returnQuery, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(function(data) {
                var clientEl = document.getElementById('returnClientName');
                if (clientEl) {
                    clientEl.textContent = data.client_name || '—';
                }
                document.getElementById('returnTransferFromStore').textContent = data.store_name || '—';
                const issueDate = data.issue_date || '';
                const todayYmd = new Date().toISOString().slice(0, 10);
                const tbody = document.getElementById('returnItemModalBody');
                tbody.innerHTML = '';
                function ymdToDmY(ymd) {
                    if (!ymd) return '—';
                    var p = String(ymd).split('-');
                    if (p.length !== 3) return ymd;
                    return p[2] + '/' + p[1] + '/' + p[0];
                }
                (data.items || []).forEach(function(item, i) {
                    const id = (item.id != null) ? item.id : '';
                    const name = (item.item_name || '—').replace(/</g, '&lt;').replace(/"/g,
                        '&quot;');
                    const qty = item.quantity != null ? item.quantity : '';
                    const unit = (item.unit || '—').replace(/</g, '&lt;');
                    const retQty = item.return_quantity != null ? item.return_quantity : 0;
                    const retDate = item.return_date || '';
                    const issuedQty = parseFloat(qty) || 0;
                    const rowIssueYmd = (item.issue_date || issueDate || '').trim();
                    const issueDisp = ymdToDmY(rowIssueYmd);
                    tbody.insertAdjacentHTML('beforeend',
                        '<tr><td>' + name + '<input type="hidden" name="items[' + i +
                        '][id]" value="' + id + '"></td><td>' + qty + '</td><td>' + unit +
                        '</td><td class="text-nowrap">' + issueDisp + '</td>' +
                        '<td><input type="number" name="items[' + i +
                        '][return_quantity]" class="form-control  dr-return-qty" step="0.01" min="0" max="' +
                        issuedQty + '" data-issued="' + issuedQty + '" value="' + retQty +
                        '"><div class="invalid-feedback">Return Qty cannot exceed Issued Qty.</div></td>' +
                        '<td><input type="date" name="items[' + i +
                        '][return_date]" class="form-control  dr-return-date" max="' +
                        todayYmd + '" ' + (rowIssueYmd ? ('min="' + rowIssueYmd +
                            '" data-issue-date="' + rowIssueYmd + '"') : '') + ' value="' +
                        retDate +
                        '"><div class="invalid-feedback">Return date must be between issue date and today.</div></td></tr>'
                    );
                });
                document.getElementById('returnItemForm').action = baseUrl + '/' + reportId + '/return' + returnQuery;
                new bootstrap.Modal(document.getElementById('returnItemModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load return data.');
            });
    }, true);

    function enforceReturnQtyWithinIssued(inputEl) {
        if (!inputEl) return;
        const issued = parseFloat(inputEl.dataset.issued) || 0;
        const raw = inputEl.value;
        const val = parseFloat(raw);
        inputEl.max = String(issued);
        if (raw === '' || Number.isNaN(val)) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (val > issued) {
            inputEl.setCustomValidity('Return Qty cannot exceed Issued Qty.');
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
        }
    }

    function enforceReturnDateNotBeforeIssue(inputEl) {
        if (!inputEl) return;
        const issue = inputEl.dataset.issueDate || '';
        const raw = inputEl.value;
        const today = new Date().toISOString().slice(0, 10);
        inputEl.max = today;
        if (!raw) {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
            return;
        }
        if (raw > today) {
            inputEl.setCustomValidity('Return date cannot be in the future.');
            inputEl.classList.add('is-invalid');
            return;
        }
        if (issue && raw < issue) {
            inputEl.setCustomValidity('Return date cannot be earlier than issue date.');
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.setCustomValidity('');
            inputEl.classList.remove('is-invalid');
        }
    }

    const returnItemModalBody = document.getElementById('returnItemModalBody');
    if (returnItemModalBody) {
        returnItemModalBody.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('dr-return-qty')) {
                enforceReturnQtyWithinIssued(e.target);
            }
            if (e.target && e.target.classList.contains('dr-return-date')) {
                enforceReturnDateNotBeforeIssue(e.target);
            }
        });
    }

    const returnItemForm = document.getElementById('returnItemForm');
    if (returnItemForm) {
        returnItemForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.querySelectorAll('.dr-return-qty').forEach(enforceReturnQtyWithinIssued);
            this.querySelectorAll('.dr-return-date').forEach(enforceReturnDateNotBeforeIssue);
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            var formData = new FormData(this);
            formData.append('_method', 'PUT');
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(function(r) {
                    return r.json().then(function(payload) {
                        return { ok: r.ok, payload: payload };
                    });
                })
                .then(function(result) {
                    if (!result.ok || !result.payload || !result.payload.success) {
                        throw new Error((result.payload && result.payload.message) || 'Failed to update return.');
                    }
                    var modalEl = document.getElementById('returnItemModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    var clientName = result.payload.client_name || '';
                    var clientId = result.payload.client_id != null ? String(result.payload.client_id) : '';
                    var filterBuyer = document.getElementById('filter_buyer_name');
                    var filterReturn = document.querySelector('select[name="return_status"]');
                    var buyerFilterValue = clientId || clientName;
                    if (buyerFilterValue && filterBuyer) {
                        var hasOpt = Array.from(filterBuyer.options).some(function(o) {
                            return o.value === buyerFilterValue;
                        });
                        if (!hasOpt) {
                            var opt = document.createElement('option');
                            opt.value = buyerFilterValue;
                            opt.textContent = clientName || buyerFilterValue;
                            filterBuyer.appendChild(opt);
                        }
                        filterBuyer.value = buyerFilterValue;
                    }
                    if (filterReturn) {
                        filterReturn.value = 'returned';
                    }
                    var url = new URL(window.location.href);
                    if (buyerFilterValue) {
                        url.searchParams.set('buyer_name', buyerFilterValue);
                    }
                    url.searchParams.set('return_status', 'returned');
                    window.history.replaceState({}, '', url.toString());
                    if (typeof window.reloadSellingVoucherDateRangeTable === 'function') {
                        window.reloadSellingVoucherDateRangeTable();
                    }
                    alert(result.payload.message || 'Return updated successfully.');
                })
                .catch(function(err) {
                    alert(err.message || 'Failed to update return.');
                })
                .finally(function() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        }, true);
    }

    function buildEditItemsTable(items) {
        const tbody = document.getElementById('editModalItemsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        editRowIndex = 0;
        (items || []).forEach(function(item) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, item));
            editRowIndex++;
        });
        if (tbody.querySelectorAll('.edit-dr-item-row').length === 0) {
            tbody.insertAdjacentHTML('beforeend', getEditRowHtml(editRowIndex, {}));
            editRowIndex++;
        }
        tbody.querySelectorAll('.edit-dr-item-row').forEach(function(row) {
            bindEditRowStoreSelect(row);
            // Native store select only — avoid Choices submit issues for store_id.
            row.querySelector('.edit-dr-avail').addEventListener('input', function() {
                updateEditRowLeft(row);
            });
            row.querySelector('.edit-dr-qty').addEventListener('input', function() {
                refreshEditAllAvailable();
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-rate').addEventListener('input', function() {
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-item-select').addEventListener('change', function() {
                const o = getSelectSelectedOption(this);
                row.querySelector('.edit-dr-unit').value = (o && o.dataset.unit) ? o.dataset.unit :
                    '—';
                const rateInp = row.querySelector('.edit-dr-rate');
                if (rateInp && o && o.dataset.rate) rateInp.value = o.dataset.rate;
                const availInp = row.querySelector('.edit-dr-avail');
                if (availInp && o && o.dataset.available) availInp.value = o.dataset.available;
                refreshEditAllAvailable();
                updateEditRowTotal(row);
                updateEditGrandTotal();
            });
            row.querySelector('.edit-dr-remove-row').addEventListener('click', function() {
                row.remove();
                refreshEditAllAvailable();
                updateEditGrandTotal();
            });
        });
        refreshEditAllAvailable();
        updateEditGrandTotal();
    }

    // Edit report (mousedown ensures single-tap works with DataTables)
    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-report');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const reportId = btn.getAttribute('data-report-id');
        const editQuery = window.location.search || '';
        // Keep update URL clean; listing filters go as hidden fields to avoid
        // colliding with voucher fields (e.g. client_type_pk).
        document.getElementById('editReportForm').action = baseUrl + '/' + reportId;
        fetch(baseUrl + '/' + reportId + '/edit' + editQuery, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json().then(data => ({
                ok: r.ok,
                data
            })))
            .then(function({
                ok,
                data
            }) {
                if (!ok) {
                    alert(data && data.error ? data.error : 'Failed to load report for edit.');
                    return;
                }
                destroyEditModalTomSelects();
                const v = data.voucher;
                document.getElementById('editReportModalLabel').textContent = 'Edit Selling Voucher #' +
                    (v.id || reportId);
                applyEditFilteredStoreDisplay(v);
                const editFormEl = document.getElementById('editReportForm');
                if (editFormEl) {
                    editFormEl.dataset.clientTypePk = (v.client_type_pk != null ? String(v.client_type_pk) : '');
                    editFormEl.dataset.clientName = (v.client_name != null ? String(v.client_name) : '');
                }
                var editClientIdEl = document.getElementById('editDrClientId');
                if (editClientIdEl) {
                    editClientIdEl.value = (v.client_id != null && String(v.client_id) !== '') ? String(v.client_id) : '';
                }
                document.querySelector('.edit-remarks').value = v.remarks || '';
                const editRefNumEl = document.querySelector('.edit-reference-number');
                if (editRefNumEl) editRefNumEl.value = v.reference_number || '';
                const editOrderByEl = document.querySelector('.edit-order-by');
                if (editOrderByEl) editOrderByEl.value = v.order_by || '';
                var editSvBillPathEl = document.getElementById('editSvCurrentBillPath');
                if (editSvBillPathEl) {
                    if (v.bill_path) {
                        var billFileName = v.bill_path.split('/').pop() || v.bill_path;
                        editSvBillPathEl.textContent = billFileName;
                        editSvBillPathEl.setAttribute('title', billFileName);
                    } else {
                        editSvBillPathEl.textContent = 'No file chosen';
                        editSvBillPathEl.removeAttribute('title');
                    }
                }
                var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
                if (editSvBillFileInputEl) editSvBillFileInputEl.value = '';
                var editDrRemoveBillFlagEl = document.getElementById('editDrRemoveBillFlag');
                if (editDrRemoveBillFlagEl) editDrRemoveBillFlagEl.value = '0';
                var editBillLinkEl = document.getElementById('editCurrentBillLink');
                if (editBillLinkEl) {
                    if (v.bill_url) {
                        editBillLinkEl.innerHTML = 'Current bill: <a href="' + v.bill_url +
                            '" target="_blank" rel="noopener" class="text-primary">View Bill</a>';
                    } else {
                        editBillLinkEl.innerHTML = '';
                    }
                }
                document.getElementById('editDrClientNameInput').value = v.client_name || '';
                document.getElementById('editDrFacultySelect').value = v.client_name || '';
                const editAcademyEl = document.getElementById('editDrAcademyStaffSelect');
                if (editAcademyEl) editAcademyEl.value = v.client_name || '';
                const editMessEl = document.getElementById('editDrMessStaffSelect');
                if (editMessEl) editMessEl.value = v.client_name || '';
                const editOtCourseEl = document.getElementById('editDrOtCourseSelect');
                if (editOtCourseEl) editOtCourseEl.value = v.client_type_pk || '';
                const editDrCourseEl = document.getElementById('editDrCourseSelect');
                if (editDrCourseEl) editDrCourseEl.value = v.client_type_pk || '';
                const editDrCourseNameEl = document.getElementById('editDrCourseNameSelect');
                if (editDrCourseNameEl) editDrCourseNameEl.value = v.client_type_pk || '';
                document.querySelector('.edit-payment-type').value = String(v.payment_type ?? 1);
                const slug = v.client_type_slug || 'employee';
                document.querySelectorAll('.edit-dr-client-type-radio').forEach(function(radio) {
                    radio.checked = (radio.value === slug);
                });
                var editWrap1 = document.getElementById('editDrClientNameWrap');
                var editWrap2 = document.getElementById('editDrNameFieldWrap');
                if (editWrap1) editWrap1.style.display = '';
                if (editWrap2) editWrap2.style.display = '';
                const isOt = slug === 'ot';
                const isCourse = slug === 'course';
                let editClientSelect = document.getElementById('editDrClientNameSelect');
                const editOtSelect = document.getElementById('editDrOtCourseSelect');
                const editCourseSelect = document.getElementById('editDrCourseSelect');
                const editCourseNameSelect = document.getElementById('editDrCourseNameSelect');
                const editNameInp = document.getElementById('editDrClientNameInput');
                if (isOt) {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'none';
                        editClientSelect.removeAttribute('required');
                        editClientSelect.removeAttribute('name');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'block';
                        editOtSelect.setAttribute('required', 'required');
                        editOtSelect.setAttribute('name', 'client_type_pk');
                        editOtSelect.value = v.client_type_pk || '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'none';
                        editCourseSelect.removeAttribute('required');
                        editCourseSelect.removeAttribute('name');
                        editCourseSelect.value = '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.readOnly = true;
                        editNameInp.placeholder = 'Buyer name (OT)';
                        editNameInp.value = v.client_name || '';
                        editNameInp.removeAttribute('required');
                    }
                    var editOtStu = document.getElementById('editDrOtStudentSelect');
                    if (editOtStu) {
                        setSelectVisible(editOtStu, false);
                        editOtStu.removeAttribute('required');
                    }
                } else if (isCourse) {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'none';
                        editClientSelect.removeAttribute('required');
                        editClientSelect.removeAttribute('name');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'none';
                        editOtSelect.removeAttribute('required');
                        editOtSelect.removeAttribute('name');
                        editOtSelect.value = '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'block';
                        editCourseSelect.setAttribute('required', 'required');
                        editCourseSelect.setAttribute('name', 'client_type_pk');
                        editCourseSelect.value = v.client_type_pk || '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.readOnly = true;
                        editNameInp.classList.add('bg-light');
                        editNameInp.placeholder = 'Course name';
                        editNameInp.setAttribute('required', 'required');
                    }
                } else {
                    if (editClientSelect) {
                        editClientSelect.style.display = 'block';
                        editClientSelect.setAttribute('required', 'required');
                        editClientSelect.setAttribute('name', 'client_type_pk');
                        if (clientNameOptionsEdit && clientNameOptionsEdit.length) {
                            rebuildEditClientNameSelect(slug);
                        }
                        editClientSelect = document.getElementById('editDrClientNameSelect');
                        ensureEditClientTypePkOption(editClientSelect, v.client_type_pk, v.client_name);
                        setSelectValue(editClientSelect, v.client_type_pk || '');
                    }
                    if (editOtSelect) {
                        editOtSelect.style.display = 'none';
                        editOtSelect.removeAttribute('required');
                        editOtSelect.removeAttribute('name');
                        editOtSelect.value = '';
                    }
                    if (editCourseSelect) {
                        editCourseSelect.style.display = 'none';
                        editCourseSelect.removeAttribute('required');
                        editCourseSelect.removeAttribute('name');
                        editCourseSelect.value = '';
                    }
                    if (editCourseNameSelect) {
                        editCourseNameSelect.style.display = 'none';
                        editCourseNameSelect.removeAttribute('required');
                        editCourseNameSelect.value = '';
                    }
                    if (editNameInp) {
                        editNameInp.style.display = 'block';
                        editNameInp.readOnly = true;
                        editNameInp.classList.add('bg-light');
                        editNameInp.placeholder = 'Client / section / role name';
                        editNameInp.setAttribute('required', 'required');
                    }
                }
                updateEditDrNameField();
                // Ensure TomSelect instances exist for the final state (and preserve selected values)
                initEditModalTomSelects();
                syncEditDrChoicesFromVoucher(v, slug);
                freezeEditClientIdentityFields(v);
                editCurrentStoreId = v.store_id || v.inve_store_master_pk || '';
                if (!editCurrentStoreName) {
                    var storeOpt = document.querySelector(
                        '#editReportModal select.edit-store-id option[value="' +
                        String(editCurrentStoreId).replace(/"/g, '\\"') + '"]'
                    );
                    editCurrentStoreName = storeOpt ? String(storeOpt.textContent || '').trim() : '';
                }
                const items = data.items || [];
                const openEditModalWithItems = function() {
                    buildEditItemsTable(items);
                    new bootstrap.Modal(document.getElementById('editReportModal')).show();
                };
                if (v.filtered_view) {
                    filteredItems = Array.isArray(itemSubcategories) ? itemSubcategories.slice() : [];
                    openEditModalWithItems();
                } else if (editCurrentStoreId) {
                    fetchStoreItems(editCurrentStoreId, function() {
                        updateEditItemDropdowns();
                        openEditModalWithItems();
                    });
                } else {
                    filteredItems = itemSubcategories;
                    openEditModalWithItems();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load report for edit.');
            });
    }, true);

    // Store selection change in EDIT modal
    const editStoreSelect = document.querySelector('#editReportModal select[name="inve_store_master_pk"]');
    if (editStoreSelect) {
        editStoreSelect.addEventListener('change', function() {
            const storeId = getSelectValue(this);
            editCurrentStoreId = storeId;
            if (!storeId) {
                filteredItems = itemSubcategories;
                updateEditItemDropdowns();
                return;
            }
            fetchStoreItems(storeId, function() {
                updateEditItemDropdowns();
            });
        });
    }

    const editReportModal = document.getElementById('editReportModal');
    if (editReportModal) {
        editReportModal.addEventListener('shown.bs.modal', function() {
            initEditModalTomSelects();
            var editFormEl = document.getElementById('editReportForm');
            freezeEditClientIdentityFields({
                client_name: editFormEl ? editFormEl.dataset.clientName : '',
                client_id: (document.getElementById('editDrClientId') || {}).value || ''
            });
        });
        editReportModal.addEventListener('hidden.bs.modal', function() {
            destroyEditModalTomSelects();
            unfreezeEditClientIdentityFields();
        });
    }

    // Helper: reset Add Selling Voucher (Date Range) form to default state (without closing modal)
    function resetAddReportForm() {
        var addReportModal = document.getElementById('addReportModal');
        if (!addReportModal) return;

        destroyAddModalTomSelects();

        var form = document.getElementById('addReportForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            form.querySelectorAll('.is-invalid').forEach(function(el) {
                el.classList.remove('is-invalid');
            });
        }
        var storeSel = addReportModal.querySelector('select[name="inve_store_master_pk"]');
        if (storeSel) storeSel.value = '';
        var issueDateInp = addReportModal.querySelector('input[name="issue_date"]');
        if (issueDateInp) issueDateInp.value = new Date().toISOString().slice(0, 10);
        var paymentSel = addReportModal.querySelector('select[name="payment_type"]');
        if (paymentSel) paymentSel.value = '1';
        var empRadio = addReportModal.querySelector('.dr-client-type-radio[value="employee"]');
        if (empRadio) {
            empRadio.checked = true;
            empRadio.dispatchEvent(new Event('change'));
        }
        var clientPkSel = addReportModal.querySelector('#drClientNameSelect');
        if (clientPkSel) clientPkSel.value = '';
        var clientIdInp = addReportModal.querySelector('#drClientId');
        if (clientIdInp) clientIdInp.value = '';
        var clientNameInp = document.getElementById('drClientNameInput');
        if (clientNameInp) clientNameInp.value = '';
        addReportModal.querySelectorAll('#drClientNameWrap select, #drNameFieldWrap select').forEach(function(s) {
            if (s && typeof s.value !== 'undefined') s.value = '';
        });
        var billInput = document.getElementById('addDrBillFileInput');
        if (billInput) billInput.value = '';
        var billWrap = document.getElementById('addDrBillFileChosenWrap');
        var billName = document.getElementById('addDrBillFileChosenName');
        if (billWrap) billWrap.classList.add('d-none');
        if (billName) billName.textContent = '';
        var tbody = document.getElementById('addModalItemsBody');
        if (tbody) {
            tbody.innerHTML = getAddRowHtml(0);
            addRowIndex = 1;
            tbody.querySelectorAll('.dr-remove-row').forEach(function(btn) {
                btn.disabled = (tbody.querySelectorAll('.dr-item-row').length <= 1);
            });
            var firstRow = tbody.querySelector('.dr-item-row');
            if (firstRow) {
                firstRow.querySelector('.dr-item-select').addEventListener('change', function() {
                    var rateInp = firstRow.querySelector('.dr-rate');
                    if (rateInp) rateInp.dataset.manualRate = '';
                    updateAddRowUnit(firstRow);
                });
                firstRow.querySelector('.dr-qty').addEventListener('input', function() {
                    refreshAllAvailable();
                    updateAddRowTotal(firstRow);
                    updateAddGrandTotal();
                });
                firstRow.querySelector('.dr-rate').addEventListener('input', function() {
                    // Must match initial row wiring: otherwise FIFO tier logic overwrites rate every keystroke.
                    this.dataset.manualRate = '1';
                    updateAddRowTotal(firstRow);
                    updateAddGrandTotal();
                });
            }
        }
        var grandTotalEl = document.getElementById('addModalGrandTotal');
        if (grandTotalEl) grandTotalEl.textContent = '₹0.00';
    }

    // Reset add modal when closed (so next open starts fresh)
    const addReportModal = document.getElementById('addReportModal');
    if (addReportModal) {
        // Reset ASAP when user closes via X/Cancel/backdrop.
        // (hidden.bs.modal is sometimes late; do both for a reliable "refreshed" feel.)
        addReportModal.addEventListener('hide.bs.modal', function() {
            resetAddReportForm();
            var body = addReportModal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
        });
        addReportModal.addEventListener('hidden.bs.modal', function() {
            resetAddReportForm();
            var body = addReportModal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
        });

        addReportModal.addEventListener('show.bs.modal', function() {
            const storeSelect = addReportModal.querySelector('select[name="inve_store_master_pk"]');
            const preSelectedStore = storeSelect ? getSelectValue(storeSelect) : null;

            console.log('Modal opening, pre-selected store:', preSelectedStore); // Debug log

            // If there's a pre-selected store, fetch its items
            if (preSelectedStore) {
                currentStoreId = preSelectedStore;
                fetchStoreItems(preSelectedStore, function() {
                    console.log('Pre-fetched items for store:', preSelectedStore, 'Count:',
                        filteredItems.length);
                    updateAddItemDropdowns();
                    refreshAllAvailable();
                    document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(
                        row) {
                        updateAddRowTotal(row);
                    });
                    updateAddGrandTotal();
                });
            } else {
                currentStoreId = null;
                filteredItems = itemSubcategories;
                if (storeSelect) storeSelect.value = '';
            }
        });
        addReportModal.addEventListener('shown.bs.modal', function() {
            initAddModalTomSelects();
            var addRadio = document.querySelector('#addReportModal .dr-client-type-radio:checked');
            if (addRadio) {
                setTimeout(function() {
                    addRadio.dispatchEvent(new Event('change'));
                }, 0);
            }
            refreshAllAvailable();
            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
                updateAddRowTotal(row);
            });
            updateAddGrandTotal();
        });
    }

    // After AJAX save (add/edit), refresh the listing DataTable so new rows show immediately.
    // This fetches the current page HTML and swaps DataTable rows (preserves search/paging).
    var isRefreshingSellingVoucherDateRangeTable = false;

    function refreshSellingVoucherDateRangeTable() {
        if (isRefreshingSellingVoucherDateRangeTable) return;
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;

        var $ = window.jQuery;
        var $table = $('#sellingVoucherDateRangeTable');
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) return;

        var dt = $table.DataTable();
        var expectedCols = $table.find('thead tr:first th').length;
        var url = window.location.pathname + window.location.search;

        isRefreshingSellingVoucherDateRangeTable = true;

        fetch(url, {
                headers: {
                    'Accept': 'text/html'
                }
            })
            .then(function(r) {
                return r.text();
            })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newTbody = doc.querySelector('#sellingVoucherDateRangeTable tbody');
                if (!newTbody) return;

                var newRowData = [];
                newTbody.querySelectorAll('tr').forEach(function(tr) {
                    var cells = Array.from(tr.querySelectorAll('td,th'));
                    if (expectedCols && cells.length !== expectedCols)
                        return; // skip colspan/empty rows
                    newRowData.push(cells.map(function(td) {
                        return td.innerHTML;
                    }));
                });

                dt.clear();
                if (newRowData.length) dt.rows.add(newRowData);
                dt.draw(false);
            })
            .catch(function(err) {
                console.error('Failed to refresh selling voucher date-range table', err);
            })
            .finally(function() {
                isRefreshingSellingVoucherDateRangeTable = false;
            });
    }

    // Prevent double submit on Add form (stops double entry on Save Selling Voucher) + AJAX submit
    var addReportFormEl = document.getElementById('addReportForm');
    if (addReportFormEl) {
        addReportFormEl.addEventListener('submit', function(e) {
            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(function(row) {
                enforceQtyWithinAvailable(row, '.dr-avail', '.dr-qty');
            });
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);

        addReportFormEl.addEventListener('submit', function(e) {
            // If the form is invalid, the capture listener above will already have prevented default.
            if (!this.checkValidity()) {
                return;
            }

            e.preventDefault();

            var form = this;
            var btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) {
                return;
            }
            if (btn) {
                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.textContent || '';
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
            }

            var action = form.getAttribute('action') || window.location.href;
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var formData = new FormData(form);
            var csrf = form.querySelector('input[name="_token"]');

            fetch(action, {
                    method: method,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf ? csrf.value : '',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(response) {
                    return response.json().then(function(payload) {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: payload
                        };
                    }).catch(function() {
                        return {
                            ok: response.ok,
                            status: response.status,
                            payload: null
                        };
                    });
                })
                .then(function(res) {
                    var data = res.payload;
                    if (res.ok && data && data.success) {
                        var modalRoot = document.getElementById('addReportModal');
                        var storeSelect = modalRoot ? modalRoot.querySelector(
                            'select[name="inve_store_master_pk"]') : null;
                        var savedStoreId = getSelectValue(storeSelect);

                        resetAddReportForm();

                        function afterAddModalInventoryRefresh() {
                            updateAddItemDropdowns();
                            initAddModalTomSelects();
                            refreshAllAvailable();
                            document.querySelectorAll('#addModalItemsBody .dr-item-row').forEach(
                                function(row) {
                                    updateAddRowTotal(row);
                                });
                            updateAddGrandTotal();
                            var body = modalRoot && modalRoot.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }

                        if (savedStoreId) {
                            if (storeSelect) {
                                storeSelect.value = String(savedStoreId);
                            }
                            currentStoreId = String(savedStoreId);
                            fetchStoreItems(String(savedStoreId), function() {
                                afterAddModalInventoryRefresh();
                            });
                        } else {
                            currentStoreId = null;
                            filteredItems = itemSubcategories;
                            afterAddModalInventoryRefresh();
                        }

                        refreshSellingVoucherDateRangeTable();

                        if (window.toastr && data.message) {
                            toastr.success(data.message);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    } else {
                        var msg = (data && data.message) ? data.message :
                            'Failed to save voucher. Please try again.';
                        if (res.status === 422 && data && data.errors) {
                            try {
                                var firstKey = Object.keys(data.errors)[0];
                                if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                    msg = data.errors[firstKey][0];
                                }
                            } catch (e) {}
                        }
                        alert(msg);
                    }
                })
                .catch(function() {
                    alert('Failed to save voucher. Please try again.');
                })
                .finally(function() {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = btn.dataset.originalText || 'Save Selling Voucher';
                    }
                });
        });
    }

    // Add modal: show selected bill file name and Remove button
    var addDrBillFileInputEl = document.getElementById('addDrBillFileInput');
    if (addDrBillFileInputEl) {
        addDrBillFileInputEl.addEventListener('change', function() {
            var wrap = document.getElementById('addDrBillFileChosenWrap');
            var nameEl = document.getElementById('addDrBillFileChosenName');
            if (wrap && nameEl) {
                if (this.files && this.files[0]) {
                    nameEl.textContent = this.files[0].name;
                    wrap.classList.remove('d-none');
                } else {
                    nameEl.textContent = '';
                    wrap.classList.add('d-none');
                }
            }
        });
    }
    var addDrBillFileRemoveEl = document.getElementById('addDrBillFileRemove');
    if (addDrBillFileRemoveEl) {
        addDrBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('addDrBillFileInput');
            var wrap = document.getElementById('addDrBillFileChosenWrap');
            var nameEl = document.getElementById('addDrBillFileChosenName');
            if (input) input.value = '';
            if (nameEl) nameEl.textContent = '';
            if (wrap) wrap.classList.add('d-none');
        });
    }

    // Edit modal: show selected file name in same field when user picks a new bill
    var editSvBillFileInputEl = document.getElementById('editSvBillFileInput');
    if (editSvBillFileInputEl) {
        editSvBillFileInputEl.addEventListener('change', function() {
            var pathEl = document.getElementById('editSvCurrentBillPath');
            var removeFlag = document.getElementById('editDrRemoveBillFlag');
            if (pathEl) pathEl.textContent = this.files && this.files[0] ? this.files[0].name :
                'No file chosen';
            if (removeFlag) removeFlag.value = '0';
        });
    }
    var editDrBillFileRemoveEl = document.getElementById('editDrBillFileRemove');
    if (editDrBillFileRemoveEl) {
        editDrBillFileRemoveEl.addEventListener('click', function() {
            var input = document.getElementById('editSvBillFileInput');
            var pathEl = document.getElementById('editSvCurrentBillPath');
            var removeFlag = document.getElementById('editDrRemoveBillFlag');
            if (input) input.value = '';
            if (pathEl) pathEl.textContent = 'No file chosen';
            if (removeFlag) removeFlag.value = '1';
        });
    }

    // Prevent double submit on Edit form
    var editReportFormEl = document.getElementById('editReportForm');
    if (editReportFormEl) {
        editReportFormEl.addEventListener('submit', function(e) {
            prepareEditFormForSubmit(this);
            document.querySelectorAll('#editModalItemsBody .edit-dr-item-row').forEach(function(row) {
                enforceQtyWithinAvailable(row, '.edit-dr-avail', '.edit-dr-qty');
            });
            // Re-validate after preparing store_id hidden fields.
            const newStoreRows = this.querySelectorAll('#editModalItemsBody .edit-dr-store-select');
            let storeMissing = false;
            newStoreRows.forEach(function(sel) {
                const backup = sel.parentElement
                    ? sel.parentElement.querySelector('input.edit-dr-store-id-backup')
                    : null;
                const val = (backup && backup.value) ? backup.value : (sel.value || '');
                if (!val) {
                    storeMissing = true;
                    sel.setCustomValidity('Please select a store.');
                } else {
                    sel.setCustomValidity('');
                }
            });
            if (storeMissing || !this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
        }, true);
        editReportFormEl.addEventListener('submit', function() {
            var btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = 'Updating...';
            }
        });
    }

    // Open add modal on validation error
    if (SVDR_CFG.openAddModal) {
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('addReportModal'));
            modal.show();
        });
    }

    // Filter: End Date must not be before Start Date
    document.addEventListener('DOMContentLoaded', function() {
        var filterStart = document.getElementById('filter_start_date');
        var filterEnd = document.getElementById('filter_end_date');
        var filterType = document.getElementById('filter_client_type');
        var filterTypePk = document.getElementById('filter_client_type_pk');
        var filterBuyer = document.getElementById('filter_buyer_name');
        var selectedTypePk = SVDR_CFG.selectedTypePk;
        var selectedBuyer = SVDR_CFG.selectedBuyer;
        var employees = SVDR_CFG.employees || [];
        var faculties = SVDR_CFG.faculties || [];
        var messStaff = SVDR_CFG.messStaff || [];
        var typePkOptionsBySlug = SVDR_CFG.typePkOptionsBySlug || {};
        var otCourseOptions = SVDR_CFG.otCourseOptions || [];

        function fillSelect(selectEl, options, placeholder, selectedValue) {
            if (!selectEl) return;
            selectEl.innerHTML = '';
            var defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = placeholder;
            selectEl.appendChild(defaultOpt);
            (options || []).forEach(function(option) {
                var opt = document.createElement('option');
                opt.value = String(option.value || '');
                opt.textContent = String(option.text || '');
                if (selectedValue !== undefined && selectedValue !== null && String(selectedValue) === opt.value) {
                    opt.selected = true;
                }
                selectEl.appendChild(opt);
            });
        }

        function setBuyerOptions(options, preserveSelection) {
            fillSelect(filterBuyer, (options || []).map(function(option) {
                if (typeof option === 'string') {
                    return { value: option, text: option };
                }
                return {
                    value: String((option && option.value) || ''),
                    text: String((option && option.text) || ''),
                };
            }).filter(function(option) {
                return option.value !== '' && option.text !== '';
            }), 'All persons', preserveSelection ? selectedBuyer : '');
        }

        function getFilterParamsForBuyerList() {
            var form = document.getElementById('sellingVoucherFilterForm');
            var params = new URLSearchParams();
            if (!form) {
                return params;
            }
            Array.from(form.elements).forEach(function(el) {
                if (!el.name || el === filterBuyer) {
                    return;
                }
                if (el.disabled) {
                    return;
                }
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (!el.checked) {
                        return;
                    }
                    params.append(el.name, el.value);
                    return;
                }
                if (el.tagName === 'SELECT' && el.multiple) {
                    Array.from(el.selectedOptions).forEach(function(opt) {
                        if (opt.value !== '') {
                            params.append(el.name, opt.value);
                        }
                    });
                    return;
                }
                if (el.value !== '') {
                    params.append(el.name, el.value);
                }
            });
            return params;
        }

        function loadBuyerOptions(preserveSelection) {
            if (!filterBuyer) {
                return;
            }
            var slug = filterType ? String(filterType.value || '') : '';
            var pk = filterTypePk ? String(filterTypePk.value || '') : '';

            if (slug === 'employee' && pk) {
                var selectedLabel = ((filterTypePk.options[filterTypePk.selectedIndex] || {}).text || '').toLowerCase().trim();
                if (selectedLabel === 'academy staff') {
                    setBuyerOptions(employees, preserveSelection);
                } else if (selectedLabel === 'faculty') {
                    setBuyerOptions(faculties, preserveSelection);
                } else if (selectedLabel === 'mess staff') {
                    setBuyerOptions(messStaff, preserveSelection);
                } else {
                    setBuyerOptions([], preserveSelection);
                }
                return;
            }

            if (slug === 'ot' && pk) {
                var otUrl = SVDR_CFG.studentsByCourseUrlTemplate.replace('__COURSE__', encodeURIComponent(pk));
                fetch(otUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(response) { return response.ok ? response.json() : { students: [] }; })
                    .then(function(payload) {
                        var buyers = (payload.students || []).map(function(student) {
                            return student.display_name || '';
                        }).filter(Boolean);
                        setBuyerOptions(buyers, preserveSelection);
                    })
                    .catch(function() {
                        setBuyerOptions([], preserveSelection);
                    });
                return;
            }

            var params = getFilterParamsForBuyerList();
            fetch(SVDR_CFG.filterBuyerNamesUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function(response) { return response.ok ? response.json() : { buyers: [] }; })
                .then(function(payload) {
                    var buyers = (payload.buyers || []).map(function(name) {
                        return String(name || '').trim();
                    }).filter(Boolean);
                    setBuyerOptions(buyers, preserveSelection);
                })
                .catch(function() {
                    setBuyerOptions([], preserveSelection);
                });
        }

        function loadTypePkOptions(preserveSelection) {
            if (!filterType || !filterTypePk) return;
            var slug = String(filterType.value || '');
            var options = [];
            if (slug === 'ot' || slug === 'course') {
                options = otCourseOptions;
            } else if (slug && typePkOptionsBySlug[slug]) {
                options = typePkOptionsBySlug[slug];
            }
            fillSelect(filterTypePk, options, 'All categories', preserveSelection ? selectedTypePk : '');
            loadBuyerOptions(preserveSelection);
        }

        if (filterStart && filterEnd) {
            filterStart.addEventListener('change', function() {
                filterEnd.min = this.value || '';
                if (filterEnd.value && this.value && filterEnd.value < this.value) {
                    filterEnd.value = this.value;
                }
            });
        }
        if (filterType && filterTypePk && filterBuyer) {
            filterType.addEventListener('change', function() {
                selectedTypePk = '';
                selectedBuyer = '';
                loadTypePkOptions(false);
            });
            filterTypePk.addEventListener('change', function() {
                selectedBuyer = '';
                loadBuyerOptions(false);
            });
            loadTypePkOptions(true);
        } else if (filterBuyer) {
            loadBuyerOptions(true);
        }

        var filterForm = document.getElementById('sellingVoucherFilterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                if (typeof window.reloadSellingVoucherDateRangeTable === 'function') {
                    setTimeout(window.reloadSellingVoucherDateRangeTable, 0);
                }
            });
        }
    });

    // Print View modal content (Selling Voucher Date Range) – correct design with standard header
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-print-view-modal');
        if (!btn) return;
        var sel = btn.getAttribute('data-print-target');
        if (!sel) return;
        var modalEl = document.querySelector(sel);
        if (!modalEl) return;
        var content = modalEl.querySelector('.modal-content');
        if (!content) return;
        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) {
            alert('Please allow popups to print.');
            return;
        }
        var title = (modalEl.querySelector('.modal-title') || {}).textContent ||
            'Selling Voucher (Date Range)';
        var printedOn = new Date();
        var dateStr = printedOn.getDate().toString().padStart(2, '0') + '/' + (printedOn.getMonth() + 1)
            .toString().padStart(2, '0') + '/' + printedOn.getFullYear() + ', ' + printedOn
            .toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        var bodyContent = content.innerHTML.replace(/<button[^>]*btn-close[^>]*>[\s\S]*?<\/button>/gi, '');
        var printHeader =
            '<div class="print-doc-header" style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #2c3e50;">' +
            '<div style="font-size:16px;font-weight:700;color:#1a1a1a;margin-bottom:4px;">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div style="background:#495057;color:#fff;padding:6px 12px;font-size:13px;display:inline-block;margin:4px 0;">Selling Voucher (Date Range)</div>' +
            '<div style="font-size:11px;color:#6c757d;margin-top:6px;">Printed on ' + dateStr +
            '</div></div>';
        var printCss =
            '<style>@page{size:A4;margin:14mm;}body{font-family:Arial,sans-serif;font-size:12px;color:#212529;padding:0 12px;margin:0;background:#fff;}.print-doc-header{-webkit-print-color-adjust:exact;print-color-adjust:exact;}.modal-header{border-bottom:1px solid #dee2e6;padding-bottom:8px;margin-bottom:12px;}.modal-body{color:#212529;}.card{margin-bottom:14px;page-break-inside:avoid;}.card-header{font-weight:600;font-size:12px;margin-bottom:8px;}.card-body table th,.card-body table td{border:1px solid #adb5bd;padding:6px 8px;}table{width:100%;border-collapse:collapse;font-size:11px;}thead th{background:#af2910!important;color:#fff!important;border-color:#8b2009;font-weight:600;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.card-footer{font-weight:600;padding-top:8px;}.btn-close,.modal-footer{display:none!important;}@media print{body{padding:0;}}</style>';
        win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title.replace(/</g,
                '&lt;') + '</title>' + printCss + '</head><body>' + printHeader +
            '<div class="modal-content-wrap">' + bodyContent + '</div></body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() {
            win.print();
            win.close();
        }, 350);
    });
})();
