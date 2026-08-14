/* Process Mess Bills (Employee) — extracted from index.blade.php.
   Blade-derived values are injected via window.PMBE_CFG (defined inline in the blade).
   Kept top-level (no IIFE / no 'use strict') to preserve original inline-script scoping. */
var PMBE_CFG = window.PMBE_CFG || {};

/* ===== script 1 ===== */
(function () {
    function applyProcessMessBillStats(json) {
        if (!json || !json.stats) return;
        var s = json.stats;
        var fmtInt = function (n) { return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
        var fmtAmt = function (n) {
            var x = Number(n) || 0;
            var parts = x.toFixed(2).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        };
        var elTotal = document.getElementById('process-mess-stats-total-bills');
        var elUnpaid = document.getElementById('process-mess-stats-unpaid');
        var elPaid = document.getElementById('process-mess-stats-paid');
        var elAmt = document.getElementById('process-mess-stats-total-amount');
        var elPaidPct = document.getElementById('process-mess-stats-paid-pct');
        var elDueAmt = document.getElementById('process-mess-stats-total-due-amount');
        if (elTotal) elTotal.textContent = fmtInt(s.total_bills);
        if (elUnpaid) elUnpaid.textContent = fmtInt(s.unpaid_count);
        if (elPaid) elPaid.textContent = fmtInt(s.paid_count);
        if (elAmt) elAmt.textContent = '₹ ' + fmtAmt(s.total_amount);
        if (elDueAmt) elDueAmt.textContent = '₹ ' + fmtAmt(s.total_due_amount);
        if (elPaidPct) {
            var total = Number(s.total_bills) || 0;
            var paid = Number(s.paid_count) || 0;
            var pct = total > 0 ? Math.round((paid / total) * 100) : 0;
            elPaidPct.textContent = pct + '% cleared';
            var elProgress = document.getElementById('process-mess-stats-paid-progress');
            var elProgressBar = document.getElementById('process-mess-stats-paid-progress-bar');
            if (elProgress) elProgress.setAttribute('aria-valuenow', String(pct));
            if (elProgressBar) elProgressBar.style.width = pct + '%';
        }
        if (elDueAmt) elDueAmt.textContent = '₹ ' + fmtAmt(s.total_due_amount);
    }

    window.applyProcessMessBillStats = applyProcessMessBillStats;

    function bindProcessMessBillStatsListener() {
        if (typeof window.jQuery === 'undefined') return;
        var $ = window.jQuery;
        var $table = $('#processMessBillsTable');
        if (!$table.length) return;

        $table.off('xhr.dt.processMessStats').on('xhr.dt.processMessStats', function (e, settings, json) {
            applyProcessMessBillStats(json);
            if (typeof window.prefetchDefaultModalBillsCacheOnce === 'function') {
                window.prefetchDefaultModalBillsCacheOnce();
            }
        });

        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            applyProcessMessBillStats($table.DataTable().settings()[0].json);
        }
    }

    document.addEventListener('DOMContentLoaded', bindProcessMessBillStatsListener);
})();

/* ===== script 2 ===== */
function applyMessSortHeaderIcon(th, isActive, sortDir) {
    if (!th) return;
    var icon = th.querySelector('.mess-report-sort-icon');
    if (!icon) return;
    th.classList.toggle('mess-th-sorted', !!isActive);
    th.classList.toggle('is-sorted', !!isActive);
    icon.classList.add('material-symbols-rounded');
    if (isActive) {
        icon.textContent = sortDir === 'desc' ? 'arrow_downward' : 'arrow_upward';
        icon.classList.remove('mess-report-sort-icon--muted');
        icon.setAttribute('aria-label', sortDir === 'desc' ? 'Sorted descending' : 'Sorted ascending');
    } else {
        icon.textContent = 'unfold_more';
        icon.classList.add('mess-report-sort-icon--muted');
        icon.setAttribute('aria-label', 'Sortable');
    }
}
window.applyMessSortHeaderIcon = applyMessSortHeaderIcon;

/** Column title for print/export — never include Material icon ligature text. */
function messPrintThLabel(th) {
    if (!th) {
        return '';
    }
    var label = (th.getAttribute('data-mess-col-original') || '').trim();
    if (label) {
        return label;
    }
    var clone = th.cloneNode(true);
    clone.querySelectorAll(
        '.mess-report-sort-icon, .material-symbols-rounded, .material-icons, i[class*="material"]'
    ).forEach(function (el) {
        el.remove();
    });
    label = (clone.textContent || '').replace(/\s+/g, ' ').trim();
    return label.replace(/\s+(unfold_more|arrow_upward|arrow_downward)$/i, '');
}
window.messPrintThLabel = messPrintThLabel;

function syncProcessMessBillsTableSortIcons() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
        return;
    }
    var $ = window.jQuery;
    var $table = $('#processMessBillsTable');
    if (!$table.length || !$.fn.DataTable.isDataTable($table)) {
        return;
    }
    var dt = $table.DataTable();
    var order = dt.order();
    var sortCol = order.length ? order[0][0] : -1;
    var sortDir = order.length ? order[0][1] : 'asc';

    $table.find('thead tr').first().children('th.mess-sort-th').each(function () {
        var colIdx = dt.column(this).index();
        if (colIdx == null || colIdx < 0) {
            return;
        }
        applyMessSortHeaderIcon(this, colIdx === sortCol, sortDir);
    });
}
window.syncProcessMessBillsTableSortIcons = syncProcessMessBillsTableSortIcons;

function bindProcessMessBillsTableSortIcons() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
        return false;
    }
    var $table = window.jQuery('#processMessBillsTable');
    if (!$table.length || !window.jQuery.fn.DataTable.isDataTable($table)) {
        return false;
    }
    var dt = $table.DataTable();
    dt.off('order.dt.messSort draw.dt.messSort column-reorder.dt.messSort');
    dt.on('order.dt.messSort draw.dt.messSort column-reorder.dt.messSort', syncProcessMessBillsTableSortIcons);
    syncProcessMessBillsTableSortIcons();
    return true;
}
window.bindProcessMessBillsTableSortIcons = bindProcessMessBillsTableSortIcons;

document.addEventListener('DOMContentLoaded', function () {
    var attempts = 0;
    var timer = setInterval(function () {
        if (bindProcessMessBillsTableSortIcons()) {
            clearInterval(timer);
            return;
        }
        if (++attempts > 60) {
            clearInterval(timer);
        }
    }, 150);
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(document).on('mess:columns:saved', function (e, tableId) {
            if (tableId === 'processMessBillsTable') {
                syncProcessMessBillsTableSortIcons();
            }
        });
    }
});

/* ===== main script ===== */
document.addEventListener('DOMContentLoaded', function() {
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyPartialMatchChoicesSearchFilter(instance, rawQuery) {
        if (!instance || !instance.dropdown || !instance.dropdown.element) return;
        var dropdownEl = instance.dropdown.element;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;

        choiceItems.forEach(function(item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var value = normalizeChoicesSearchText(item.getAttribute('data-value') || '');
            var show = !query || label.indexOf(query) !== -1 || value.indexOf(query) !== -1;
            item.style.display = show ? '' : 'none';
        });
    }

    if (typeof flatpickr !== 'undefined') {
        var dateFromInput = document.getElementById('date_from');
        var dateToInput = document.getElementById('date_to');
        // Prefer data-default-ymd (Y-m-d from server) so nothing can overwrite before we read it.
        function ymdToDate(ymd) {
            if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(String(ymd))) return null;
            var p = String(ymd).split('-');
            return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        }
        function dmyToDate(dmy) {
            var m = (dmy || '').match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
            if (!m) return null;
            return new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
        }
        var defaultFrom = (dateFromInput && dateFromInput.getAttribute('data-default-ymd')) ? ymdToDate(dateFromInput.getAttribute('data-default-ymd')) : (dateFromInput && dateFromInput.value ? dmyToDate(dateFromInput.value) : null);
        var defaultTo = (dateToInput && dateToInput.getAttribute('data-default-ymd')) ? ymdToDate(dateToInput.getAttribute('data-default-ymd')) : (dateToInput && dateToInput.value ? dmyToDate(dateToInput.value) : null);
        // Dual-month range picker → fills the hidden #date_from / #date_to and auto-applies.
        var pmbeRangeEl = document.getElementById('pmbe_date_range');
        if (pmbeRangeEl) {
            var pmbeRangeSubmitTimer = null;
            var pmbeRangePicker = flatpickr(pmbeRangeEl, {
                mode: 'range',
                showMonths: 2,
                dateFormat: 'd-m-Y',
                allowInput: false,
                defaultDate: (defaultFrom && defaultTo) ? [defaultFrom, defaultTo] : null,
                locale: { rangeSeparator: ' – ' },
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        if (dateFromInput) dateFromInput.value = instance.formatDate(selectedDates[0], 'd-m-Y');
                        if (dateToInput) dateToInput.value = instance.formatDate(selectedDates[1], 'd-m-Y');
                        if (pmbeRangeSubmitTimer) clearTimeout(pmbeRangeSubmitTimer);
                        pmbeRangeSubmitTimer = setTimeout(function () {
                            var frm = document.getElementById('mainFilterForm');
                            if (frm) frm.submit();
                        }, 250);
                    } else if (selectedDates.length === 0) {
                        if (dateFromInput) dateFromInput.value = '';
                        if (dateToInput) dateToInput.value = '';
                    }
                }
            });
        }
        flatpickr('#modal_date_from', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_date_to', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_invoice_date', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowPaymentDate', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowChequeDate', { dateFormat: 'd-m-Y', allowInput: true });
    }

    // Initialize Choices.js on all dropdowns within this report
    function initChoicesElement(el) {
        if (!el || typeof window.Choices === 'undefined') return;
        if (el.dataset.choicesInitialized === 'true') return;

        var placeholder = el.getAttribute('data-placeholder') || 'Select';
        // Keep search enabled for all Choices dropdowns.
        var shouldSearch = el.getAttribute('data-search') !== 'false';
        var isMultiple = !!el.multiple;

        var instance = new Choices(el, {
            searchEnabled: shouldSearch,
            // Disable built-in filter; we apply substring match below (typing shows partial matches).
            searchChoices: false,
            removeItemButton: isMultiple,
            itemSelectText: '',
            shouldSort: false,
            position: 'bottom',
            placeholderValue: placeholder,
            allowHTML: false,
            closeDropdownOnSelect: !isMultiple
        });
        if (instance.containerOuter && instance.containerOuter.element && instance.containerOuter.element.classList) {
            instance.containerOuter.element.classList.add('ts-wrapper');
        }
        if (instance.dropdown && instance.dropdown.element && instance.dropdown.element.classList) {
            instance.dropdown.element.classList.add('ts-dropdown');
        }
        function applySearchFilterAfterRender() {
            var typed = (instance.input && instance.input.element) ? (instance.input.element.value || '') : '';
            requestAnimationFrame(function () {
                applyPartialMatchChoicesSearchFilter(instance, typed);
            });
        }
        el.addEventListener('showDropdown', applySearchFilterAfterRender);
        if (instance.input && instance.input.element) {
            instance.input.element.addEventListener('input', function() {
                applySearchFilterAfterRender();
            });
            instance.input.element.addEventListener('keyup', applySearchFilterAfterRender);
        }

        el.dataset.choicesInitialized = 'true';
        el.choicesInstance = instance;
    }

    function refreshChoicesFromSelect(el, selectedValue) {
        console.log('refreshChoicesFromSelect called - el:', el, 'selectedValue:', selectedValue);
        console.log('choicesInstance exists?', !!el.choicesInstance);
        if (!el || !el.choicesInstance) {
            console.warn('No choicesInstance found for element:', el);
            return;
        }
        var instance = el.choicesInstance;
        var values = Array.from(el.options).map(function (o) {
            return { value: o.value, label: o.text, selected: selectedValue != null ? String(o.value) === String(selectedValue) : o.selected };
        });
        console.log('Refreshing choices with', values.length, 'options');
        instance.clearStore();
        instance.setChoices(values, 'value', 'label', true);
        try {
            instance.setChoiceByValue(selectedValue != null ? String(selectedValue) : (el.value || ''));
        } catch (e) {
            console.error('Error setting choice value:', e);
        }
    }

    // ── Toolbar filter pills (Select2) ──────────────────────────────────────
    // Select2's JS ships with the admin footer; this page loads its CSS. These
    // are single-value pills — the design shows one compact "Status / Client
    // Type / Client Category / Buyer" control each, not a chip list.
    // A pill is only as wide as its value, so picking one changes the toolbar's
    // width. Tell the overflow layout, or the row silently scrolls sideways
    // instead of collapsing an item into "+N Filter".
    function pmbeFiltersResized() {
        window.dispatchEvent(new Event('pmbe:filters-resized'));
    }

    function pmbeInitFilterSelect2(el) {
        var $ = window.jQuery;
        if (!el || !$ || !$.fn || !$.fn.select2) return;
        if ($(el).data('select2')) return;

        var first = el.options && el.options[0];
        var label = el.getAttribute('data-placeholder') || (first ? first.text : '') || 'Select';

        var opts = {
            width: 'resolve',
            placeholder: label,
            allowClear: false,
            minimumResultsForSearch: 0,          // always offer the search box
            dropdownCssClass: 'pmbe-filter-select2-dropdown'
        };
        // Select2 appends its panel to <body>, which puts it *behind* a Bootstrap
        // modal — the pill then looks dead because the backdrop eats the click.
        var modal = el.closest('.modal');
        if (modal) opts.dropdownParent = $(modal);

        $(el).select2(opts);

        // containerCssClass does not land on this Select2 build, and `width:
        // resolve` writes an inline pixel width — so tag the container by hand
        // and let the stylesheet's !important width win.
        var s2 = $(el).data('select2');
        if (s2 && s2.$container) s2.$container.addClass('pmbe-filter-select2');

        // Select2 raises jQuery-only events; the auto-apply handler listens for a
        // NATIVE change, which cannot hear those. Bridge it. Bound to Select2's
        // own events rather than 'change' so it cannot re-enter itself.
        $(el).on('select2:select select2:unselect select2:clear', function () {
            el.dispatchEvent(new Event('change', { bubbles: true }));
            pmbeFiltersResized();
        });
    }

    /** Repaint a filter pill after its <option>s were rewritten. */
    function pmbeSyncFilterWidget(el, selected) {
        if (!el) return;
        if (selected !== undefined && selected !== null) {
            var want = Array.isArray(selected) ? (selected[0] || '') : selected;
            if (want && el.value !== String(want)) el.value = String(want);
        }
        var $ = window.jQuery;
        if ($ && $(el).data('select2')) {
            $(el).trigger('change.select2');   // repaint only — no change event
        } else {
            pmbeInitFilterSelect2(el);
        }
        pmbeFiltersResized();
    }

    document
        .querySelectorAll('.process-mess-bills-employee-report select.pmbe-filter-select')
        .forEach(function (el) { pmbeInitFilterSelect2(el); });

    if (typeof window.Choices !== 'undefined') {
        document
            .querySelectorAll('.process-mess-bills-employee-report select.choices-select')
            .forEach(function (el) {
                initChoicesElement(el);
            });
    }

    // The bills panel is rendered on its own page ("Generate Invoice & Process
    // Payment"); the modal is kept for anywhere still opening it. Same IDs, so
    // one init serves both — it just runs on load rather than on shown.bs.modal.
    var addProcessMessBillsModalEl = document.getElementById('addProcessMessBillsModal');
    var pmbeBillsPanelRoot = addProcessMessBillsModalEl || document.getElementById('pmbeBillsPanel');

    function initBillsPanel() {
        if (!pmbeBillsPanelRoot) return;
        // Only the mode-of-payment is still a Choices control; the filter row
        // uses the same Select2 pills as the index.
        initChoicesElement(document.getElementById('modal_mode_of_payment'));
        // In the modal this must wait for shown.bs.modal: Select2 measures a
        // hidden container as zero-width.
        pmbeBillsPanelRoot
            .querySelectorAll('select.pmbe-filter-select')
            .forEach(function (el) { pmbeInitFilterSelect2(el); });
        if (window.pmbeModalLayoutFilters) window.pmbeModalLayoutFilters();

        initModalBillsColumnManager();
        updateModalBillsSortHeaderIcons();

        setTimeout(function() {
            if (typeof fillModalClientTypePk === 'function') {
                fillModalClientTypePk();
            }
        }, 50);

        // No Load Bills button in the design: fetch straight away.
        setTimeout(function () { loadModalBills(1); }, 120);
    }

    if (!addProcessMessBillsModalEl && pmbeBillsPanelRoot) {
        // Standalone page — nothing will ever fire shown.bs.modal.
        setTimeout(initBillsPanel, 0);
    }

    if (addProcessMessBillsModalEl && typeof bootstrap !== 'undefined') {
        addProcessMessBillsModalEl.addEventListener('shown.bs.modal', function () {
            initBillsPanel();
        });
    }

    var modalBillsData = [];
    var modalBillsCurrentPage = 1;
    var modalBillsTotal = 0;
    var modalBillsFrom = 0;
    var modalBillsTo = 0;
    var modalBillsPerPage = 10;      // as applied by the server, not as requested
    var modalBillsSortCol = 'buyer_name';
    var modalBillsSortDir = 'asc';
    var modalAllBuyerNames = PMBE_CFG.allBuyerNames || [];
    var paymentDetailsBillId = null;
    var paymentDetailsDateFrom = null;
    var paymentDetailsDateTo = null;
    var paymentDetailsUrl = PMBE_CFG.paymentDetailsUrlTemplate;
    var printReceiptBaseUrl = PMBE_CFG.printReceiptUrlTemplate;
    var generateInvoiceBaseUrl = PMBE_CFG.generateInvoiceBaseUrl;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }

    /** Prefer Flatpickr's selected date so the modal request matches the picker (avoids stale input vs calendar). */
    function getModalDateYmd(inputId) {
        var el = document.getElementById(inputId);
        if (!el) return '';
        var fp = el._flatpickr;
        if (fp && fp.selectedDates && fp.selectedDates.length > 0 && typeof fp.formatDate === 'function') {
            return fp.formatDate(fp.selectedDates[0], 'Y-m-d');
        }
        return el.value ? toYmd(el.value) : '';
    }

    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('processBillsToastContainer');
        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0 show';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + (message || 'Done') + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(toastEl);
        var t = new bootstrap.Toast(toastEl, { delay: 4000 });
        t.show();
        toastEl.addEventListener('hidden.bs.toast', function() { toastEl.remove(); });
    }

    /** All selected values from a native multi-select or Choices.js instance. */
    function getChoicesMultiValues(el) {
        if (!el) return [];
        if (el.choicesInstance && typeof el.choicesInstance.getValue === 'function') {
            var raw = el.choicesInstance.getValue(true);
            if (Array.isArray(raw)) {
                return raw.map(function (v) { return String(v || '').trim(); }).filter(Boolean);
            }
            if (raw != null && raw !== '') {
                return [String(raw).trim()];
            }
            return [];
        }
        return Array.from(el.selectedOptions || []).map(function (opt) {
            return String(opt.value || '').trim();
        }).filter(Boolean);
    }

    function buildModalBillsDataUrl(options) {
        options = options || {};
        var ct = document.getElementById('modal_client_type');
        var ctp = document.getElementById('modal_client_type_pk');
        var bn = document.getElementById('modal_buyer_name');
        var dateFrom = getModalDateYmd('modal_date_from');
        var dateTo = getModalDateYmd('modal_date_to');
        var clientTypes = getChoicesMultiValues(ct);
        var clientTypePks = getChoicesMultiValues(ctp);
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var modalSearch = (document.getElementById('modalSearch') || {}).value || '';
        var buyerNames = getChoicesMultiValues(bn);
        var page = options.forPrint ? 1 : (options.page != null ? options.page : modalBillsCurrentPage);
        if (options.forPrint) {
            perPage = 10000;
        }
        var url = PMBE_CFG.modalDataUrl + '?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
        url += '&page=' + encodeURIComponent(page) + '&per_page=' + encodeURIComponent(perPage);
        if (options.forPrint) {
            url += '&for_print=1';
        }
        if (modalSearch) {
            url += '&search=' + encodeURIComponent(modalSearch);
        }
        clientTypes.forEach(function (type) {
            url += '&client_type[]=' + encodeURIComponent(type);
        });
        clientTypePks.forEach(function (pk) {
            url += '&client_type_pk[]=' + encodeURIComponent(pk);
        });
        if (buyerNames.length) {
            buyerNames.forEach(function (name) {
                url += '&buyer_name[]=' + encodeURIComponent(name);
            });
        }
        url += '&sort_column=' + encodeURIComponent(modalBillsSortCol || 'buyer_name');
        url += '&sort_dir=' + encodeURIComponent(modalBillsSortDir || 'asc');
        return url;
    }
    window.buildModalBillsDataUrl = buildModalBillsDataUrl;
    // The print function is inline in the view (it writes a literal </script>),
    // so it can only reach these through window.
    window.getFilteredModalBills = function () { return getFilteredModalBills(); };

    function applyModalLifetimeDuePatch(dues) {
        if (!dues || !dues.length) {
            return;
        }
        var byId = {};
        dues.forEach(function (d) {
            if (d && d.id) {
                byId[d.id] = d.total_due_amount;
            }
        });
        (modalBillsData || []).forEach(function (b) {
            if (byId[b.id] !== undefined) {
                b.total_due_amount = byId[b.id];
            }
        });
        document.querySelectorAll('#modalBillsTableBody tr').forEach(function (tr) {
            var cb = tr.querySelector('.modal-bill-check');
            if (!cb) {
                return;
            }
            var id = cb.getAttribute('data-id');
            if (byId[id] === undefined) {
                return;
            }
            var td = tr.querySelector('td.text-end.fw-semibold');
            if (td) {
                td.textContent = byId[id];
            }
        });
    }

    function fetchModalLifetimeDueForCurrentPage() {
        var url = buildModalBillsDataUrl({ page: modalBillsCurrentPage }) + '&lifetime_due_only=1';
        return fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                applyModalLifetimeDuePatch(data.dues || []);
            })
            .catch(function () {});
    }

    function prefetchDefaultModalBillsCache() {
        if (typeof fetch !== 'function' || typeof buildModalBillsDataUrl !== 'function') {
            return;
        }
        var url = buildModalBillsDataUrl({ page: 1 }) + '&skip_lifetime_due=1';
        fetch(url, { credentials: 'same-origin' }).catch(function () {});
    }

    var modalCachePrefetched = false;
    function prefetchDefaultModalBillsCacheOnce() {
        if (modalCachePrefetched) {
            return;
        }
        modalCachePrefetched = true;
        prefetchDefaultModalBillsCache();
    }
    window.prefetchDefaultModalBillsCacheOnce = prefetchDefaultModalBillsCacheOnce;

    function updateModalBillsSortHeaderIcons() {
        document.querySelectorAll('#modalBillsTable .mess-sort-th[data-sort]').forEach(function (th) {
            var col = th.getAttribute('data-sort') || '';
            applyMessSortHeaderIcon(th, col === modalBillsSortCol, modalBillsSortDir);
        });
    }

    function setModalBillsLoading(isLoading) {
        var table = document.getElementById('modalBillsTable');
        var host = document.getElementById('modalBillsTableHost');
        if (table) {
            table.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        }
        if (host) {
            host.classList.toggle('is-loading', !!isLoading);
        }
    }

    function renderModalBillsSkeleton() {
        var tbody = document.getElementById('modalBillsTableBody');
        var paginationInfo = document.getElementById('modalPaginationInfo');
        var paginationList = document.getElementById('modalPaginationList');
        if (!tbody) return;

        // One bar per column of the real row, so nothing shifts when the data
        // lands. Only the first row is announced; the rest are decorative.
        var skeletonRow = function (rowIndex) {
            var srText = rowIndex === 0
                ? '<span class="visually-hidden" role="status">Loading bills</span>'
                : '';
            return '<tr class="modal-bills-skeleton-row" aria-hidden="' + (rowIndex === 0 ? 'false' : 'true') + '">' +
                '<td>' + srText + '<span class="modal-bills-skeleton modal-bills-skeleton--sn"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--buyer"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--invoice"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--payment"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--status"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--action"></span></td>' +
                '</tr>';
        };

        // As many placeholder rows as the page is about to show, capped so a
        // 200-row page does not paint 200 shimmering bars.
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var rows = Math.max(3, Math.min(perPage || 10, 10));
        var html = '';
        for (var i = 0; i < rows; i++) html += skeletonRow(i);
        tbody.innerHTML = html;

        // The footer stays put — it carries the count and page-size control —
        // so its pager gets a placeholder rather than disappearing.
        if (paginationList) {
            paginationList.innerHTML = '<li class="paginate_button page-item disabled">' +
                '<span class="modal-bills-skeleton modal-bills-skeleton--pager"></span></li>';
        }
        if (paginationInfo) paginationInfo.textContent = 'loading…';

        setModalBillsLoading(true);
        applyModalBillsColumnVisibility();
    }

    function loadModalBills(page, options) {
        options = options || {};
        var requestedPage = parseInt(page, 10);
        modalBillsCurrentPage = isNaN(requestedPage) ? 1 : Math.max(1, requestedPage);
        var ct = document.getElementById('modal_client_type');
        var clientTypes = getChoicesMultiValues(ct);
        var modalSearch = (document.getElementById('modalSearch') || {}).value || '';
        var url = buildModalBillsDataUrl({ page: modalBillsCurrentPage, forPrint: !!options.forPrint });
        // Always request lifetime due in the primary payload so the table
        // does not flash interim period due values before async patching.
        var deferLifetimeDue = false;
        renderModalBillsSkeleton();
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                modalBillsData = data.bills || [];
                var pagination = data.pagination || {};
                modalBillsTotal = parseInt(pagination.total || modalBillsData.length || 0, 10);
                modalBillsFrom = parseInt(pagination.from || (modalBillsTotal ? 1 : 0), 10);
                modalBillsTo = parseInt(pagination.to || modalBillsData.length || 0, 10);
                modalBillsCurrentPage = parseInt(pagination.page || modalBillsCurrentPage || 1, 10);
                modalBillsPerPage = parseInt(pagination.per_page, 10) || modalBillsPerPage;
                // Show what was actually applied, so the footer never claims a
                // page size the server refused.
                var perPageSel = document.getElementById('modalPerPage');
                if (perPageSel && String(modalBillsPerPage) !== perPageSel.value
                    && [].some.call(perPageSel.options, function (o) { return o.value === String(modalBillsPerPage); })) {
                    perPageSel.value = String(modalBillsPerPage);
                }
                updateModalBillsSortHeaderIcons();
                renderModalTable();

                // Also refresh Buyer Name dropdown in modal based on loaded bills.
                // IMPORTANT: Only do this when no client type is selected, otherwise it
                // overrides the dependent "Client Type -> Buyer Name" behavior.
                if (clientTypes.length > 0 || modalBillsCurrentPage > 1 || modalSearch) {
                    return;
                }
                try {
                    var buyerSelect = document.getElementById('modal_buyer_name');
                    if (buyerSelect) {
                        var buyers = Array.from(new Set(
                            ((modalAllBuyerNames || []).length ? modalAllBuyerNames : (modalBillsData || [])
                                .map(function (b) { return b.buyer_name || b.client_name || ''; }))
                                .filter(function (name) { return !!name; })
                        ));

                        var keep = buyerSelect.value;
                        buyerSelect.innerHTML = '<option value="">Buyer</option>';

                        buyers.forEach(function (name) {
                            var opt = document.createElement('option');
                            opt.value = name;
                            opt.textContent = name;
                            if (name === keep) opt.selected = true;
                            buyerSelect.appendChild(opt);
                        });

                        var $buyer = window.jQuery;
                        if ($buyer && $buyer(buyerSelect).data('select2')) {
                            $buyer(buyerSelect).trigger('change.select2');   // repaint, no change event
                        } else if (buyerSelect.choicesInstance) {
                            var values = Array.from(buyerSelect.options).map(function (o) {
                                return { value: o.value, label: o.text, selected: o.selected };
                            });
                            buyerSelect.choicesInstance.clearStore();
                            buyerSelect.choicesInstance.setChoices(values, 'value', 'label', true);
                        }
                    }
                } catch (e) {
                    console.error('Failed to refresh modal_buyer_name options:', e);
                }
            })
            .catch(function() {
                modalBillsData = [];
                modalBillsTotal = 0;
                modalBillsFrom = 0;
                modalBillsTo = 0;
                renderModalTable();
                showToast('Failed to load bills.', 'error');
            });
    }

    function focusAddProcessMessBillsModal() {
        var addModalEl = document.getElementById('addProcessMessBillsModal');
        if (!addModalEl || typeof bootstrap === 'undefined') {
            // Standalone page: nothing to re-open, just refresh the list.
            if (pmbeBillsPanelRoot) loadModalBills(modalBillsCurrentPage);
            return;
        }
        var wasVisible = addModalEl.classList.contains('show');
        var addInst = bootstrap.Modal.getOrCreateInstance(addModalEl);
        addInst.show();
        if (wasVisible) loadModalBills();
    }

    /** addEventListener on an element that may not exist on this page. */
    function pmbeOn(id, evt, fn) {
        var el = document.getElementById(id);
        if (el) el.addEventListener(evt, fn);
        return el;
    }

    function pmbeModalStatusOf(b) {
        var due = parseFloat(b.total_due_amount != null ? b.total_due_amount : b.due) || 0;
        var paid = parseFloat(b.paid) || 0;
        if (due <= 0) return 'paid';
        return paid > 0 ? 'partial' : 'unpaid';
    }

    // Status and Invoice Sent are not part of the bills endpoint's query, so
    // they narrow the page that was loaded rather than the whole result set.
    function getFilteredModalBills() {
        var rows = modalBillsData || [];
        var status = (document.getElementById('modal_status') || {}).value || '';
        var sent = (document.getElementById('modal_invoice_sent') || {}).value || '';
        if (status) {
            rows = rows.filter(function (b) { return pmbeModalStatusOf(b) === status; });
        }
        if (sent) {
            rows = rows.filter(function (b) {
                return sent === 'sent' ? !!b.invoice_notification_sent : !b.invoice_notification_sent;
            });
        }
        return rows;
    }

    /**
     * Build the same pager datatable-global-ui.js renders for every other grid:
     * previous / numbered pages / next as .paginate_button .page-item items.
     * This table is paginated by hand, so the markup is produced here instead.
     */
    function updateModalPaginationNav(totalPages, filteredLength) {
        var nav = document.getElementById('modalPaginationNav');
        var list = document.getElementById('modalPaginationList');
        if (!nav || !list) return;

        // The footer also carries "Showing [N] of M items", so it stays visible
        // even when there is only one page.
        nav.classList.remove('d-none');
        if (totalPages <= 1 || !filteredLength) {
            list.innerHTML = '';
            return;
        }

        // A window of pages around the current one, so a long list does not
        // render hundreds of buttons.
        var span = 2;
        var from = Math.max(1, modalBillsCurrentPage - span);
        var to = Math.min(totalPages, modalBillsCurrentPage + span);
        if (to - from < span * 2) {
            from = Math.max(1, to - span * 2);
            to = Math.min(totalPages, from + span * 2);
        }

        var html = '';
        function item(label, page, cls, disabled, active) {
            html += '<li class="paginate_button page-item ' + cls
                + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">'
                + '<a href="javascript:void(0)" class="page-link" data-page="' + page + '">' + label + '</a></li>';
        }

        item('Previous', modalBillsCurrentPage - 1, 'previous', modalBillsCurrentPage <= 1, false);
        if (from > 1) {
            item('1', 1, '', false, false);
            if (from > 2) html += '<li class="paginate_button page-item disabled"><span class="page-link">…</span></li>';
        }
        for (var i = from; i <= to; i++) {
            item(String(i), i, '', false, i === modalBillsCurrentPage);
        }
        if (to < totalPages) {
            if (to < totalPages - 1) html += '<li class="paginate_button page-item disabled"><span class="page-link">…</span></li>';
            item(String(totalPages), totalPages, '', false, false);
        }
        item('Next', modalBillsCurrentPage + 1, 'next', modalBillsCurrentPage >= totalPages, false);

        list.innerHTML = html;
    }

    function formatInvoiceNotificationStatusCell(b) {
        if (!b || !b.invoice_notification_sent) {
            return '<span class="text-muted small">—</span>';
        }
        var readBadge = b.invoice_notification_read
            ? '<span class="badge rounded-1 bg-info-subtle text-info border border-info-subtle fw-semibold">Read</span>'
            : '<span class="badge rounded-1 bg-warning-subtle text-warning border border-warning-subtle fw-semibold">Unread</span>';
        var partialBadge = b.invoice_notification_partial
            ? '<span class="badge rounded-1 bg-primary-subtle text-primary border border-primary-subtle fw-semibold">New items (' + (b.invoice_notification_pending_count || 0) + ')</span>'
            : '';
        var sentLabel = b.invoice_notification_fully_sent
            ? 'Invoice Sent'
            : 'Invoice Sent (partial)';
        return '<div class="d-flex flex-column align-items-center gap-1">' +
            '<span class="badge rounded-1 bg-success-subtle text-success border border-success-subtle fw-semibold">' + sentLabel + '</span>' +
            partialBadge +
            readBadge +
            '</div>';
    }

    function formatInvoiceNotificationStatusText(b) {
        if (!b || !b.invoice_notification_sent) {
            return '—';
        }
        var partial = b.invoice_notification_partial ? ' · New items pending' : '';
        return 'Invoice Sent · ' + (b.invoice_notification_read ? 'Read' : 'Unread') + partial;
    }

    function canSendInvoiceNotification(b) {
        if (!b) return true;
        return !b.invoice_notification_fully_sent;
    }
    window.formatInvoiceNotificationStatusText = formatInvoiceNotificationStatusText;

    function destroyModalBillsDataTableIfAny() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
            return;
        }
        var $ = window.jQuery;
        var $table = $('#modalBillsTable');
        if (!$table.length) {
            return;
        }
        if ($.fn.DataTable.isDataTable($table)) {
            try {
                $table.DataTable().destroy();
            } catch (e) {}
        }
        var $wrapper = $table.closest('.dataTables_wrapper');
        if ($wrapper.length) {
            $table.detach();
            var $host = $('#modalBillsTableHost');
            if ($host.length) {
                $host.empty().append($table);
            } else {
                $wrapper.replaceWith($table);
            }
        }
    }

    function initModalBillsColumnManager() {
        if (typeof window.MessColumnManager === 'undefined' || typeof window.jQuery === 'undefined') {
            return;
        }
        destroyModalBillsDataTableIfAny();

        var $table = window.jQuery('#modalBillsTable');
        if (!$table.length) return;

        if (!window.MessColumnManager.get('modalBillsTable')) {
            window.MessColumnManager.init({
                tableId: 'modalBillsTable',
                mode: 'dom',
                $table: $table,
                colReorder: false,
                lockedColumns: [0],
                // 8 columns now: 0 S.No … 6 Status, 7 Action. Action is not a
                // data column, so it is never toggleable.
                skipColumns: [7]
            });
        } else {
            window.MessColumnManager.get('modalBillsTable').apply();
        }
    }

    function applyModalBillsColumnVisibility() {
        var mgr = window.MessColumnManager && window.MessColumnManager.get('modalBillsTable');
        if (mgr) {
            mgr.apply();
        }
    }

    function pmbeModalMoney(v) {
        var n = parseFloat(String(v == null ? 0 : v).replace(/[^0-9.\-]/g, '')) || 0;
        return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /** Payment status pill, plus an "Invoice Sent" pill when one has gone out. */
    function pmbeModalStatusCell(b) {
        var status = pmbeModalStatusOf(b);
        var label = status === 'paid' ? 'Paid' : (status === 'partial' ? 'Partial' : 'Unpaid');
        var html = '<span class="pmbe-badge pmbe-badge--' + status + '">' + label + '</span>';
        if (b && b.invoice_notification_sent) {
            html += '<span class="pmbe-badge pmbe-badge--sent">' +
                (b.invoice_notification_fully_sent ? 'Invoice Sent' : 'Invoice Sent (partial)') + '</span>';
        }
        return '<div class="pmbe-status-stack">' + html + '</div>';
    }

    function renderModalTable() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        setModalBillsLoading(false);
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        var filtered = getFilteredModalBills();
        var perPage = modalBillsPerPage
            || parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        modalBillsCurrentPage = Math.max(1, Math.min(modalBillsCurrentPage, totalPages || 1));
        var start = modalBillsFrom ? modalBillsFrom - 1 : 0;
        var pageData = filtered;

        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No bills match these filters.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function(b, i) {
                var sn = b.sno || (start + i + 1);
                var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(b.id));
                if (String(b.id || '').indexOf('combined-') === 0) {
                    var receiptDf = b.date_from || getModalDateYmd('modal_date_from') || '';
                    var receiptDt = b.date_to || getModalDateYmd('modal_date_to') || '';
                    printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(receiptDf) + '&date_to=' + encodeURIComponent(receiptDt);
                }
                var name = (b.buyer_name || '').replace(/"/g, '&quot;');
                var invoiceFullySent = !!b.invoice_notification_fully_sent;
                var invoiceTitle = invoiceFullySent
                    ? 'Invoice already sent for all items in this range'
                    : (b.invoice_notification_partial ? 'Send invoice for new item(s)' : 'Generate Invoice');
                return '<tr>' +
                    '<td>' + sn + '</td>' +
                    '<td>' + (b.buyer_name || '—') + '</td>' +
                    '<td>' + (b.invoice_no || '—') + '</td>' +
                    '<td>' + (b.payment_type || '—') + '</td>' +
                    '<td class="text-end">' + pmbeModalMoney(b.total) + '</td>' +
                    '<td class="text-end fw-semibold">' + pmbeModalMoney(b.total_due_amount) + '</td>' +
                    '<td class="text-center">' + pmbeModalStatusCell(b) + '</td>' +
                    '<td class="text-center">' +
                        '<div class="pmbe-act-group">' +
                            '<button type="button" class="pmbe-act pmbe-act--invoice generate-invoice-btn" data-bill-id="' + b.id + '" data-buyer-name="' + name + '" title="' + invoiceTitle + '"' + (invoiceFullySent ? ' disabled data-invoice-sent="1"' : '') + '>' +
                                '<span class="pmbe-act__icon"><i class="material-symbols-rounded">request_quote</i></span>' +
                                '<span class="pmbe-act__label">Invoice</span>' +
                            '</button>' +
                            '<button type="button" class="pmbe-act pmbe-act--payment generate-payment-btn" data-bill-id="' + b.id + '" data-buyer-name="' + name + '" title="Mark as Paid">' +
                                '<span class="pmbe-act__icon"><i class="material-symbols-rounded">currency_rupee</i></span>' +
                                '<span class="pmbe-act__label">Payment</span>' +
                            '</button>' +
                            '<a href="' + printUrl + '" target="_blank" class="pmbe-act pmbe-act--receipt" title="Print receipt">' +
                                '<span class="pmbe-act__icon"><i class="material-symbols-rounded">receipt_long</i></span>' +
                                '<span class="pmbe-act__label">Receipt</span>' +
                            '</a>' +
                        '</div>' +
                    '</td>' +
                    '</tr>';
            }).join('');
        }

        var infoEl = document.getElementById('modalPaginationInfo');
        if (infoEl) infoEl.textContent = 'of ' + modalBillsTotal + ' items';
        updateModalPaginationNav(totalPages, modalBillsTotal);
        updateBulkActionsBar();
        applyModalBillsColumnVisibility();
    }

    function updateBulkActionsBar() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        var bar = document.getElementById('modalBulkActionsBar');
        var countEl = document.getElementById('modalSelectedCount');
        if (!bar || !countEl) return;
        if (checked.length === 0) {
            bar.classList.add('d-none');
        } else {
            bar.classList.remove('d-none');
            countEl.textContent = checked.length + ' selected';
        }
    }

    function clearChoicesSelection(el) {
        if (!el) return;
        var $s2 = window.jQuery;
        if ($s2 && $s2.fn && $s2.fn.select2 && $s2(el).data('select2')) {
            el.value = '';
            $s2(el).trigger('change.select2');
            return;
        }
        if (el.choicesInstance) {
            var inst = el.choicesInstance;
            if (el.multiple && typeof inst.removeActiveItems === 'function') {
                inst.removeActiveItems();
                return;
            }
            if (typeof inst.setChoiceByValue === 'function') {
                try {
                    inst.setChoiceByValue(el.multiple ? [] : '');
                } catch (e) {
                    if (typeof inst.removeActiveItems === 'function') {
                        inst.removeActiveItems();
                    }
                }
            }
            return;
        }
        if (el.multiple) {
            Array.from(el.options || []).forEach(function (opt) {
                opt.selected = false;
            });
        } else {
            el.value = '';
        }
    }

    function clearModalFilters() {
        // Reset all filter inputs to defaults, then reload bills so table shows unfiltered (default) data
        var defaultDateFrom = PMBE_CFG.defaultDateFrom;
        var defaultDateTo = PMBE_CFG.defaultDateTo;
        var defaultInvoiceDate = PMBE_CFG.defaultInvoiceDate;

        function setDateInput(id, value) {
            var el = document.getElementById(id);
            if (!el) return;
            el.value = value;
            if (el._flatpickr) el._flatpickr.setDate(value, false);
        }
        setDateInput('modal_date_from', defaultDateFrom);
        setDateInput('modal_date_to', defaultDateTo);
        setDateInput('modal_invoice_date', defaultInvoiceDate);

        var ct = document.getElementById('modal_client_type');
        clearChoicesSelection(ct);
        if (ct) {
            ct.dispatchEvent(new Event('change', { bubbles: true }));
        }
        var bn = document.getElementById('modal_buyer_name');
        clearChoicesSelection(bn);
        if (bn && !bn.choicesInstance) {
            bn.innerHTML = '<option value="">Buyer</option>';
            var $bn = window.jQuery;
            if ($bn && $bn(bn).data('select2')) $bn(bn).trigger('change.select2');
        }

        // Client Category and the two design-added pills reset too, or "Remove
        // Filter" leaves the grid narrowed by a filter the user cannot see.
        ['modal_client_type_pk', 'modal_status', 'modal_invoice_sent'].forEach(function (id) {
            clearChoicesSelection(document.getElementById(id));
        });
        var mp = document.getElementById('modal_mode_of_payment');
        if (mp) {
            mp.value = 'deduct_from_salary';
            if (mp.choicesInstance) {
                mp.choicesInstance.setChoiceByValue('deduct_from_salary');
            }
        }
        var ms = document.getElementById('modalSearch');
        if (ms) ms.value = '';

        modalBillsSortCol = 'buyer_name';
        modalBillsSortDir = 'asc';
        updateModalBillsSortHeaderIcons();

        loadModalBills();
    }

    if (addProcessMessBillsModalEl) {
        addProcessMessBillsModalEl.addEventListener('show.bs.modal', function() {
            updateModalBillsSortHeaderIcons();
            loadModalBills();
        });
    }
    updateModalBillsSortHeaderIcons();
    var payNowModalForAddRedirect = document.getElementById('payNowModal');
    if (payNowModalForAddRedirect) {
        payNowModalForAddRedirect.addEventListener('hidden.bs.modal', function () {
            focusAddProcessMessBillsModal();
        });
    }
    pmbeOn('modalClearFiltersBtn', 'click', clearModalFilters);

    // No Load Bills button in the design: any filter change reloads the list.
    // Debounced so a cascade (client type -> category -> buyer) fires once.
    var pmbeModalFilterTimer = null;
    pmbeOn('addModalFilterForm', 'change', function (e) {
        var t = e.target;
        if (!t || !t.classList || !t.classList.contains('pmbe-modal-auto-filter')) return;
        pmbeModalClearDependents(t);
        if (pmbeModalFilterTimer) clearTimeout(pmbeModalFilterTimer);
        pmbeModalFilterTimer = setTimeout(function () { loadModalBills(1); }, 500);
    });

    function pmbeModalClearDependents(el) {
        (el.getAttribute('data-clears') || '').split(',').forEach(function (id) {
            id = id.trim();
            var dep = id && document.getElementById(id);
            if (!dep) return;
            dep.value = '';
            var $ = window.jQuery;
            if ($ && $(dep).data('select2')) $(dep).trigger('change.select2');
        });
    }
    pmbeOn('modalSearch', 'input', function() {
        loadModalBills(1);
    });
    document.querySelectorAll('#modalBillsTable .mess-sort-th[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = th.getAttribute('data-sort');
            if (!col) return;
            if (modalBillsSortCol === col) {
                modalBillsSortDir = modalBillsSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                modalBillsSortCol = col;
                modalBillsSortDir = (col === 'total' || col === 'sno') ? 'desc' : 'asc';
            }
            updateModalBillsSortHeaderIcons();
            loadModalBills(1);
        });
    });
    pmbeOn('modalPaginationList', 'click', function (e) {
        var link = e.target.closest('.page-link[data-page]');
        if (!link || link.closest('.page-item').classList.contains('disabled')) return;
        var page = parseInt(link.getAttribute('data-page'), 10);
        var perPage = modalBillsPerPage
            || parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        if (!page || page < 1 || (totalPages && page > totalPages)) return;
        loadModalBills(page);
    });

    pmbeOn('modalPerPage', 'change', function () { loadModalBills(1); });

    // --- Client Type / Buyer dependent dropdowns in modal (similar to Sale Voucher Report) ---
    (function initModalClientTypeFilters() {
        var modalClientType = document.getElementById('modal_client_type');
        var modalClientTypePk = document.getElementById('modal_client_type_pk');
        var modalBuyerName = document.getElementById('modal_buyer_name');
        var studentsByCourseUrl = PMBE_CFG.studentsByCourseUrl;
        var buyersForReportUrl = PMBE_CFG.buyersForReportUrl;
        var courseBuyersByCourseUrl = PMBE_CFG.courseBuyersByCourseUrl;

        if (!modalClientType || !modalClientTypePk || !modalBuyerName) {
            return;
        }

        var clientTypeOptions = PMBE_CFG.clientTypeOptions || {};

        var otCourseOptions = PMBE_CFG.otCourseOptions || [];

        var employeeNames = PMBE_CFG.employeeNamesByStaffType || {};

        var otBuyerNames = PMBE_CFG.otBuyerNames || [];
        var courseBuyerNames = PMBE_CFG.courseBuyerNames || [];
        var otherBuyerNames = PMBE_CFG.otherBuyerNames || [];
        var sectionBuyerNames = PMBE_CFG.sectionBuyerNames || [];
        var allBuyerNames = PMBE_CFG.allBuyerNames || [];

        // NOTE: Choices.js may recreate <option> nodes and drop custom dataset attributes.
        // Keep an explicit mapping from client_type_pk -> client group key (academy staff/faculty/mess staff)
        // so Buyer Name filtering stays correct inside the modal.
        var modalPkToClientGroupKey = {};

        function fillModalClientTypePk() {
            var selectedSlugs = getChoicesMultiValues(modalClientType);
            
            modalClientTypePk.innerHTML = '';

            var choicesPk = modalClientTypePk.choicesInstance || null;
            if (choicesPk) {
                choicesPk.clearStore();
                choicesPk.setChoices([], 'value', 'label', true);
            }

            modalPkToClientGroupKey = {};

            // Collect options from all selected slugs
            var allOptions = [];
            selectedSlugs.forEach(function(slug) {
                if ((slug === 'ot' || slug === 'course') && otCourseOptions.length) {
                    allOptions = allOptions.concat(otCourseOptions);
                } else if (slug && clientTypeOptions[slug]) {
                    clientTypeOptions[slug].forEach(function (o) {
                        allOptions.push(o);
                        if (o.dataClientName) {
                            modalPkToClientGroupKey[String(o.value)] = String(o.dataClientName);
                        }
                    });
                }
            });
            
            // Remove duplicates based on value
            var uniqueOptions = [];
            var seenValues = {};
            allOptions.forEach(function(o) {
                if (!seenValues[o.value]) {
                    seenValues[o.value] = true;
                    uniqueOptions.push(o);
                }
            });
            
            uniqueOptions.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                if (o.dataClientName) {
                    opt.dataset.clientName = o.dataClientName;
                }
                modalClientTypePk.appendChild(opt);
            });

            if (choicesPk) {
                var newChoices = Array.from(modalClientTypePk.options).map(function (o) {
                    return { value: o.value, label: o.text, selected: o.selected };
                });
                choicesPk.clearStore();
                choicesPk.setChoices(newChoices, 'value', 'label', true);
            }
            fillModalBuyerNames();
        }

        function fillModalBuyerNames() {
            var selectedSlugs = getChoicesMultiValues(modalClientType);
            var selectedPks = getChoicesMultiValues(modalClientTypePk);
            
            modalBuyerName.innerHTML = '';

            var choicesBuyer = modalBuyerName.choicesInstance || null;
            if (choicesBuyer) {
                choicesBuyer.clearStore();
                choicesBuyer.setChoices([], 'value', 'label', true);
            }

            function syncChoicesBuyer() {
                if (!choicesBuyer) return;
                var newChoices = Array.from(modalBuyerName.options).map(function (o) {
                    return { value: o.value, label: o.text, selected: o.selected };
                });
                choicesBuyer.clearStore();
                choicesBuyer.setChoices(newChoices, 'value', 'label', true);
            }

            function addBuyerOptions(list) {
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    modalBuyerName.appendChild(opt);
                });
            }

            function getModalDateRangeYmd() {
                return {
                    from: getModalDateYmd('modal_date_from'),
                    to: getModalDateYmd('modal_date_to')
                };
            }

            function loadBuyersFromReportEndpoint(slugToLoad) {
                var range = getModalDateRangeYmd();
                var qs = new URLSearchParams();
                qs.set('client_type_slug', slugToLoad);
                if (range.from) qs.set('from_date', range.from);
                if (range.to) qs.set('to_date', range.to);

                // Add PK if selected (course/ot => course_master_pk, others => client_type_pk)
                var selectedPk = selectedPks[0] || '';
                if (selectedPk) {
                    if (slugToLoad === 'course' || slugToLoad === 'ot') {
                        qs.set('course_master_pk', selectedPk);
                    } else {
                        qs.set('client_type_pk', selectedPk);
                    }
                }

                fetch(buyersForReportUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) {
                            var v = String(name || '').trim();
                            return v ? { value: v, text: v } : null;
                        }).filter(Boolean);
                        addBuyerOptions(buyers);
                        syncChoicesBuyer();
                    })
                    .catch(function () {
                        syncChoicesBuyer();
                    });
            }

            // No client type: show full buyer name list (same as legacy single-select "All" path)
            if (selectedSlugs.length === 0) {
                if ((allBuyerNames || []).length) {
                    var listAllEmpty = (allBuyerNames || []).map(function (name) {
                        return { value: name, text: name };
                    });
                    addBuyerOptions(listAllEmpty);
                }
                syncChoicesBuyer();
                return;
            }

            // Multiple client types selected: merge buyer lists from each slug
            if (selectedSlugs.length > 1) {
                var allBuyers = [];

                selectedSlugs.forEach(function (slug) {
                    if (slug === 'employee') {
                        allBuyers = allBuyers.concat(employeeNames['academy staff'] || [])
                            .concat(employeeNames['faculty'] || [])
                            .concat(employeeNames['mess staff'] || []);
                    } else if (slug === 'ot') {
                        allBuyers = allBuyers.concat((otBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'course') {
                        allBuyers = allBuyers.concat((courseBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'other') {
                        allBuyers = allBuyers.concat((otherBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'section') {
                        allBuyers = allBuyers.concat((sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    }
                });

                var mapMulti = new Map();
                allBuyers.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!mapMulti.has(key)) mapMulti.set(key, { value: o.value, text: o.text });
                });
                var uniqueMulti = Array.from(mapMulti.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });

                addBuyerOptions(uniqueMulti);
                syncChoicesBuyer();
                return;
            }
            
            // Single slug selected: use existing logic
            var slug = selectedSlugs[0];
            var selectedPk = selectedPks[0] || '';

            if (slug === 'employee') {
                var selectedOpt = modalClientTypePk.options[modalClientTypePk.selectedIndex];
                var dataClientName = '';
                if (selectedPk && modalPkToClientGroupKey[String(selectedPk)]) {
                    dataClientName = modalPkToClientGroupKey[String(selectedPk)];
                } else if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.clientName) {
                    dataClientName = selectedOpt.dataset.clientName || '';
                }

                if (dataClientName && employeeNames[dataClientName] && employeeNames[dataClientName].length) {
                    addBuyerOptions(employeeNames[dataClientName]);
                } else if (!selectedPk) {
                    // No subgroup selected: show all employee groups
                    Object.keys(employeeNames || {}).forEach(function (key) {
                        addBuyerOptions(employeeNames[key] || []);
                    });
                }
                syncChoicesBuyer();
            } else if (slug === 'ot' && selectedPk) {
                // OT + specific course: students by course
                fetch(studentsByCourseUrl + '/' + selectedPk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var students = (data.students || []).map(function (s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        addBuyerOptions(students);
                        syncChoicesBuyer();
                    })
                    .catch(function () {
                        // ignore error; no buyer options
                        syncChoicesBuyer();
                    });
            } else if (slug === 'ot' && !selectedPk) {
                // OT + All:
                // 1) Prefer voucher-based buyer list (respects modal date range)
                // 2) If empty, fallback to students from ALL OT courses
                var range2 = getModalDateRangeYmd();
                var qsOt = new URLSearchParams();
                qsOt.set('client_type_slug', 'ot');
                if (range2.from) qsOt.set('from_date', range2.from);
                if (range2.to) qsOt.set('to_date', range2.to);

                function loadStudentsAllOtCourses() {
                    var coursePks = (otCourseOptions || []).map(function (o) { return o.value; }).filter(Boolean);
                    if (!coursePks.length) {
                        syncChoicesBuyer();
                        return;
                    }

                    Promise.all(coursePks.map(function (coursePk) {
                        return fetch(studentsByCourseUrl + '/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                return (data.students || []).map(function (s) {
                                    return String(s.display_name || '').trim();
                                }).filter(function (n) { return !!n; });
                            })
                            .catch(function () { return []; });
                    }))
                        .then(function (results) {
                            var seen = new Set();
                            var all = [];
                            (results || []).forEach(function (names) {
                                (names || []).forEach(function (n) {
                                    var key = String(n || '').trim();
                                    if (!key || seen.has(key)) return;
                                    seen.add(key);
                                    all.push({ value: key, text: key });
                                });
                            });
                            all.sort(function (a, b) {
                                return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                            });
                            addBuyerOptions(all);
                            syncChoicesBuyer();
                        })
                        .catch(function () {
                            syncChoicesBuyer();
                        });
                }

                fetch(buyersForReportUrl + '?' + qsOt.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) { return String(name || '').trim(); })
                            .filter(function (v) { return !!v; })
                            .map(function (v) { return { value: v, text: v }; });
                        if (buyers.length) {
                            addBuyerOptions(buyers);
                            syncChoicesBuyer();
                            return;
                        }
                        loadStudentsAllOtCourses();
                    })
                    .catch(function () {
                        loadStudentsAllOtCourses();
                    });
                return; // async
            } else if (slug === 'course') {
                // Course: same as Sale Voucher logic
                // - Specific course => buyer names for that course (date filtered)
                // - All => buyer names across course vouchers (date filtered)
                if (selectedPk) {
                    var range3 = getModalDateRangeYmd();
                    var qsC = new URLSearchParams();
                    if (range3.from) qsC.set('from_date', range3.from);
                    if (range3.to) qsC.set('to_date', range3.to);
                    var url = courseBuyersByCourseUrl + '/' + selectedPk + (qsC.toString() ? ('?' + qsC.toString()) : '');
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var buyers = (data.buyers || []).map(function (name) {
                                var v = String(name || '').trim();
                                return v ? { value: v, text: v } : null;
                            }).filter(Boolean);
                            addBuyerOptions(buyers);
                            syncChoicesBuyer();
                        })
                        .catch(function () {
                            // fallback: buyers endpoint still respects course_master_pk
                            loadBuyersFromReportEndpoint('course');
                        });
                    return; // async
                }

                loadBuyersFromReportEndpoint('course');
                return; // async
            } else if (slug === 'other') {
                loadBuyersFromReportEndpoint('other');
                return; // async
            } else if (slug === 'section') {
                loadBuyersFromReportEndpoint('section');
                return; // async
            } else if (!slug && (allBuyerNames || []).length) {
                // koi client type select nahi – saare distinct buyer names (course/other/section etc.) dikhado
                var listAll = (allBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addBuyerOptions(listAll);
                syncChoicesBuyer();
            } else {
                syncChoicesBuyer();
            }
        }

        function scheduleFillModalClientTypePk() {
            setTimeout(fillModalClientTypePk, 0);
        }

        modalClientType.addEventListener('change', scheduleFillModalClientTypePk);
        modalClientTypePk.addEventListener('change', fillModalBuyerNames);
        ['addItem', 'removeItem'].forEach(function (eventName) {
            modalClientType.addEventListener(eventName, scheduleFillModalClientTypePk);
        });

        window.fillModalClientTypePk = fillModalClientTypePk;

        // Note: Initial fill is now called when modal is shown (after Choices.js init)
    })();

    // --- Main "Process Mess Bills" filters – Employee / OT / Course + Client Type + Buyer Name ---
    (function initMainClientTypeFilters() {
        var clientTypeSlug = document.getElementById('filterClientTypeSlug');
        var clientTypePk = document.getElementById('filterClientTypePk');
        var buyerSelect = document.getElementById('filterBuyerName');
        var studentsByCourseUrl = PMBE_CFG.studentsByCourseUrl;
        var buyersForReportUrl = PMBE_CFG.buyersForReportUrl;
        var courseBuyersByCourseUrl = PMBE_CFG.courseBuyersByCourseUrl;
        var preservedClientTypePk = PMBE_CFG.preservedClientTypePk || [];
        var preservedBuyerName = PMBE_CFG.preservedBuyerName || [];

        if (!clientTypeSlug || !clientTypePk || !buyerSelect) {
            return;
        }

        var clientTypeOptions = PMBE_CFG.clientTypeOptions || {};

        var otCourseOptions = PMBE_CFG.otCourseOptions || [];

        var employeeNames = PMBE_CFG.employeeNamesByStaffType || {};
        var otBuyerNames = PMBE_CFG.otBuyerNames || [];
        var courseBuyerNames = PMBE_CFG.courseBuyerNames || [];
        var otherBuyerNames = PMBE_CFG.otherBuyerNames || [];
        var sectionBuyerNames = PMBE_CFG.sectionBuyerNames || [];

        // Debug: Log initial data
        console.log('=== Main Filter Initialization ===');
        console.log('clientTypeOptions:', clientTypeOptions);
        console.log('employeeNames:', employeeNames);
        console.log('Academy Staff count:', employeeNames['academy staff'] ? employeeNames['academy staff'].length : 0);
        console.log('Faculty count:', employeeNames['faculty'] ? employeeNames['faculty'].length : 0);
        console.log('Mess Staff count:', employeeNames['mess staff'] ? employeeNames['mess staff'].length : 0);
        console.log('otCourses count:', otCourseOptions.length);

        function fillClientTypePk(preserve) {
            var slug = clientTypeSlug.value;
            // The controller always hands these back as arrays; the pill holds one.
            var currentClientPk = preserve
                ? (Array.isArray(preservedClientTypePk) ? (preservedClientTypePk[0] || '') : (preservedClientTypePk || ''))
                : '';
            console.log('=== fillClientTypePk START ===');
            console.log('slug:', slug, 'preserve:', preserve, 'currentClientPk:', currentClientPk);
            
            
            clientTypePk.innerHTML = '<option value=\"\">Client Category</option>';

            if ((slug === 'ot' || slug === 'course') && otCourseOptions.length) {
                otCourseOptions.forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    clientTypePk.appendChild(opt);
                });
            } else if (slug && clientTypeOptions[slug]) {
                clientTypeOptions[slug].forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    if (o.dataClientName) {
                        opt.dataset.clientName = o.dataClientName;
                    }
                    clientTypePk.appendChild(opt);
                });
            }
            
            // Restore selected value if preserving
            if (currentClientPk) {
                clientTypePk.value = currentClientPk;
            }

            console.log('fillClientTypePk - Re-initializing Choices.js for clientTypePk...');
            console.log('fillClientTypePk - Total options:', clientTypePk.options.length);
            
            // Re-initialize Choices.js after options are added
            pmbeSyncFilterWidget(clientTypePk, currentClientPk);
            
            console.log('fillClientTypePk - Calling fillBuyerSelect(true)...');
            fillBuyerSelect(true);
        }

        function fillBuyerSelect(preserve) {
            // Get selected slugs (now multiselect)
            var selectedSlugs = Array.from(clientTypeSlug.selectedOptions)
                .map(function (opt) { return opt.value; })
                .filter(Boolean);
            
            // Get selected pks (now multiselect)
            var selectedPks = Array.from(clientTypePk.selectedOptions)
                .map(function (opt) { return opt.value; })
                .filter(Boolean);
            
            var currentBuyer = preserve ? preservedBuyerName : [];
            console.log('=== fillBuyerSelect START ===');
            console.log('selectedSlugs:', selectedSlugs, 'selectedPks:', selectedPks, 'preserve:', preserve);
            
            
            // Clear existing options. The blank first option IS the placeholder:
            // without it a single-select would silently pick the first buyer.
            buyerSelect.innerHTML = '<option value="">Buyer</option>';

            function addOptions(list) {
                console.log('addOptions called with', list ? list.length : 0, 'items');
                (list || []).forEach(function (o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    buyerSelect.appendChild(opt);
                    console.log('Added option:', o.text);
                });
                if (Array.isArray(currentBuyer) && currentBuyer.length) {
                    Array.from(buyerSelect.options).forEach(function (option) {
                        option.selected = currentBuyer.indexOf(option.value) !== -1;
                    });
                    console.log('Set current buyers to:', currentBuyer);
                }
            }

            function loadBuyersFromReportEndpoint(slugToLoad) {
                // Uses existing report endpoint to return distinct buyers (optionally date-filtered)
                var df = document.getElementById('date_from');
                var dt = document.getElementById('date_to');
                var dateFromYmd = (df && df.value) ? toYmd(df.value) : '';
                var dateToYmd = (dt && dt.value) ? toYmd(dt.value) : '';

                function fallbackFromServerLists() {
                    if (slugToLoad === 'course') {
                        var listCourse = (courseBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listCourse);
                        return;
                    }
                    if (slugToLoad === 'other') {
                        var listOther = (otherBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listOther);
                        return;
                    }
                    if (slugToLoad === 'section') {
                        var listSection = (sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; });
                        addOptions(listSection);
                        return;
                    }
                }

                var qs = new URLSearchParams();
                qs.set('client_type_slug', slugToLoad);
                if (dateFromYmd) qs.set('from_date', dateFromYmd);
                if (dateToYmd) qs.set('to_date', dateToYmd);
                // Add PK if selected (course/ot => course_master_pk, others => client_type_pk)
                if (selectedPk) {
                    if (slugToLoad === 'course' || slugToLoad === 'ot') {
                        qs.set('course_master_pk', selectedPk);
                    } else {
                        qs.set('client_type_pk', selectedPk);
                    }
                }

                fetch(buyersForReportUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) {
                            return { value: name || '', text: name || '—' };
                        }).filter(function (o) { return !!o.value; });
                        if (!buyers.length) {
                            fallbackFromServerLists();
                        } else {
                        addOptions(buyers);
                        }
                        pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                    })
                    .catch(function () {
                        fallbackFromServerLists();
                        // still init Choices
                        pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                    });
            }

            // When multiple slugs are selected, load buyers from all of them
            if (selectedSlugs.length > 1 || selectedSlugs.length === 0) {
                // Multiple client types or none selected: load buyers from report endpoint for all selected types
                var allBuyers = [];
                var promises = [];
                
                selectedSlugs.forEach(function(slug) {
                    if (slug === 'employee') {
                        // Add all employee buyers
                        allBuyers = allBuyers.concat(employeeNames['academy staff'] || [])
                            .concat(employeeNames['faculty'] || [])
                            .concat(employeeNames['mess staff'] || []);
                    } else if (slug === 'ot') {
                        allBuyers = allBuyers.concat((otBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'course') {
                        allBuyers = allBuyers.concat((courseBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'other') {
                        allBuyers = allBuyers.concat((otherBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    } else if (slug === 'section') {
                        allBuyers = allBuyers.concat((sectionBuyerNames || []).map(function (name) { return { value: name, text: name }; }));
                    }
                });
                
                // De-duplicate
                var map = new Map();
                allBuyers.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!map.has(key)) map.set(key, { value: o.value, text: o.text });
                });
                var unique = Array.from(map.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });
                
                addOptions(unique);
                
                pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                return;
            }
            
            // Single slug selected: use existing logic
            var slug = selectedSlugs[0];
            var selectedPk = selectedPks[0] || '';

            if (slug === 'employee' && selectedPk) {
                var selectedOpt = clientTypePk.options[clientTypePk.selectedIndex];
                var dataClientName = selectedOpt && selectedOpt.dataset ? (selectedOpt.dataset.clientName || '') : '';
                console.log('Employee Debug - selectedPk:', selectedPk);
                console.log('Employee Debug - selectedOpt:', selectedOpt);
                console.log('Employee Debug - dataClientName:', dataClientName);
                console.log('Employee Debug - employeeNames keys:', Object.keys(employeeNames));
                console.log('Employee Debug - employeeNames[dataClientName]:', employeeNames[dataClientName]);
                
                if (dataClientName && employeeNames[dataClientName] && employeeNames[dataClientName].length > 0) {
                    console.log('Employee Debug - Adding', employeeNames[dataClientName].length, 'employees');
                    addOptions(employeeNames[dataClientName]);
                } else {
                    console.warn('Employee: No employees found for dataClientName:', dataClientName);
                    console.warn('Available keys:', Object.keys(employeeNames));
                }
            } else if (slug === 'employee' && !selectedPk) {
                // Employee selected but Client Type = All => show all employee groups
                var all = []
                    .concat(employeeNames['academy staff'] || [])
                    .concat(employeeNames['faculty'] || [])
                    .concat(employeeNames['mess staff'] || []);

                // De-duplicate + sort (by name)
                var map = new Map();
                all.forEach(function (o) {
                    var key = String(o.value || '').trim().toLowerCase();
                    if (!key) return;
                    if (!map.has(key)) map.set(key, { value: o.value, text: o.text });
                });
                var unique = Array.from(map.values()).sort(function (a, b) {
                    return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                });
                addOptions(unique);
            } else if (slug === 'ot' && selectedPk) {
                fetch(studentsByCourseUrl + '/' + selectedPk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var students = (data.students || []).map(function (s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        addOptions(students);
                        // Re-initialize Choices.js after async data load
                        pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                    })
                    .catch(function () {
                        // ignore; leave All Buyers only - still need to init Choices
                        pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                    });
                return; // Exit early for async case
            } else if (slug === 'ot' && !selectedPk) {
                // OT selected but Course = All
                // Same behavior as Sale Voucher Report:
                // 1) Prefer buyer list from report endpoint (respects date filters)
                // 2) If empty, fallback to loading students from ALL OT courses

                var df2 = document.getElementById('date_from');
                var dt2 = document.getElementById('date_to');
                var dateFromYmd2 = (df2 && df2.value) ? toYmd(df2.value) : '';
                var dateToYmd2 = (dt2 && dt2.value) ? toYmd(dt2.value) : '';

                function initBuyerChoicesAfterAsync() {
                    pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                }

                function loadStudentsAllOtCourses() {
                    var coursePks = (otCourseOptions || []).map(function (o) { return o.value; }).filter(Boolean);
                    if (!coursePks.length) {
                        initBuyerChoicesAfterAsync();
                        return;
                    }

                    Promise.all(coursePks.map(function (coursePk) {
                        return fetch(studentsByCourseUrl + '/' + coursePk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                return (data.students || []).map(function (s) {
                                    return String(s.display_name || '').trim();
                                }).filter(function (n) { return !!n; });
                            })
                            .catch(function () { return []; });
                    }))
                        .then(function (results) {
                            var seen = new Set();
                            var all = [];
                            (results || []).forEach(function (names) {
                                (names || []).forEach(function (n) {
                                    var key = String(n || '').trim();
                                    if (!key || seen.has(key)) return;
                                    seen.add(key);
                                    all.push({ value: key, text: key });
                                });
                            });
                            all.sort(function (a, b) {
                                return String(a.text || '').localeCompare(String(b.text || ''), undefined, { sensitivity: 'base' });
                            });
                            addOptions(all);
                            initBuyerChoicesAfterAsync();
                        })
                        .catch(function () {
                            initBuyerChoicesAfterAsync();
                        });
                }

                // Try report endpoint first
                var qsOt = new URLSearchParams();
                qsOt.set('client_type_slug', 'ot');
                if (dateFromYmd2) qsOt.set('from_date', dateFromYmd2);
                if (dateToYmd2) qsOt.set('to_date', dateToYmd2);

                fetch(buyersForReportUrl + '?' + qsOt.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var buyers = (data.buyers || []).map(function (name) { return String(name || '').trim(); })
                            .filter(function (v) { return !!v; })
                            .map(function (v) { return { value: v, text: v }; });

                        if (buyers.length) {
                            addOptions(buyers);
                            initBuyerChoicesAfterAsync();
                            return;
                        }

                        loadStudentsAllOtCourses();
                    })
                    .catch(function () {
                        loadStudentsAllOtCourses();
                    });

                return; // async branch
            } else if (slug === 'course') {
                // Same behavior as Sale Voucher filter:
                // - If a specific course is selected => show buyer names for that course
                // - If course = All => show buyer names across all course vouchers (respecting date filters)
                if (selectedPk) {
                    var df = document.getElementById('date_from');
                    var dt = document.getElementById('date_to');
                    var dateFromYmd = (df && df.value) ? toYmd(df.value) : '';
                    var dateToYmd = (dt && dt.value) ? toYmd(dt.value) : '';

                    var qs = new URLSearchParams();
                    if (dateFromYmd) qs.set('from_date', dateFromYmd);
                    if (dateToYmd) qs.set('to_date', dateToYmd);

                    var url = courseBuyersByCourseUrl + '/' + selectedPk + (qs.toString() ? ('?' + qs.toString()) : '');
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var buyers = (data.buyers || []).map(function (name) {
                                return { value: name || '', text: name || '—' };
                            }).filter(function (o) { return !!o.value; });

                            addOptions(buyers);

                            pmbeSyncFilterWidget(buyerSelect, currentBuyer);
                        })
                        .catch(function () {
                            // Fallback to the report-based endpoint (still respects selected course + dates)
                            loadBuyersFromReportEndpoint('course');
                        });

                    return; // async branch
                }

                // Course + "All"
                loadBuyersFromReportEndpoint('course');
                return; // async branch
            } else if (slug === 'section') {
                // Requirement:
                // - If Section selected AND Client Type = All => Buyer Name should list ALL section names
                // - If specific section selected => Buyer Name should be that section's name
                var sectionOptions = clientTypeOptions['section'] || [];
                if (!selectedPk) {
                    var listSectionAll = sectionOptions.map(function (o) {
                        return { value: o.text, text: o.text };
                    });
                    addOptions(listSectionAll);
                } else {
                    var matchSection = sectionOptions.find(function (o) {
                        return String(o.value) === String(selectedPk);
                    });
                    if (matchSection) {
                        addOptions([{ value: matchSection.text, text: matchSection.text }]);
                    }
                }
            } else if (slug === 'other') {
                // For "other" we can still rely on precomputed distinct buyer names
                var listOther = (otherBuyerNames || []).map(function (name) {
                    return { value: name, text: name };
                });
                addOptions(listOther);
            } else if (slug && clientTypeOptions[slug]) {
                var list3 = clientTypeOptions[slug].map(function (o) {
                    return { value: o.text, text: o.text };
                });
                addOptions(list3);
            }
            
            console.log('fillBuyerSelect - Total options in buyerSelect:', buyerSelect.options.length);
            console.log('fillBuyerSelect - Re-initializing Choices.js...');
            
            // Re-initialize Choices.js after options are added
            pmbeSyncFilterWidget(buyerSelect, currentBuyer);
        }

        clientTypeSlug.addEventListener('change', function () {
            preservedClientTypePk = []; // reset when main type changes
            preservedBuyerName = []; // reset when main type changes
            fillClientTypePk(false);
        });
        clientTypePk.addEventListener('change', function () {
            preservedBuyerName = [];
            fillBuyerSelect(false);
        });

        // Initial populate on page load - delay to ensure Choices.js is initialized
        setTimeout(function() {
            fillClientTypePk(true);
        }, 100);
    })();


    if (pmbeBillsPanelRoot) {
        pmbeBillsPanelRoot.addEventListener('click', function(e) {
            var target = e.target.closest('.modal-bill-check');
            if (target && target.classList.contains('modal-bill-check')) {
                updateBulkActionsBar();
            }
        });
    }

    function doGenerateInvoice(billId, buyerName, btnEl) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        var body = {};
        if (String(billId).indexOf('combined-') === 0) {
            var fromYmd = getModalDateYmd('modal_date_from');
            var toYmdVal = getModalDateYmd('modal_date_to');
            if (fromYmd) body.date_from = fromYmd;
            if (toYmdVal) body.date_to = toYmdVal;
        }
        fetch(generateInvoiceBaseUrl + '/' + encodeURIComponent(billId) + '/generate-invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.message || 'Invoice generated.');
                loadModalBills(modalBillsCurrentPage);
            } else {
                showToast(data.message || 'Failed to generate invoice.', 'error');
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
        })
        .finally(function() {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Invoice'; }
        });
    }

    function doGeneratePayment(billId, buyerName, btnEl, paymentPayload) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        var body = paymentPayload && (paymentPayload.amount || paymentPayload.payment_mode || paymentPayload.payment_date)
            ? JSON.stringify(paymentPayload)
            : JSON.stringify({});
        fetch(generateInvoiceBaseUrl + '/' + encodeURIComponent(billId) + '/generate-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment completed for ' + (buyerName || data.client_name) + '.');
                var payNowModalEl = document.getElementById('payNowModal');
                var payNowWasOpen = payNowModalEl && payNowModalEl.classList.contains('show');
                if (payNowModalEl && typeof bootstrap !== 'undefined') {
                    var payInst = bootstrap.Modal.getInstance(payNowModalEl);
                    if (payInst) payInst.hide();
                }
                if (!payNowWasOpen) {
                    focusAddProcessMessBillsModal();
                }
            } else {
                showToast(data.message || 'Failed to process payment.', 'error');
                if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
        });
    }

    // function formatAmountTwoDecimals(value) {
    //     var num = parseFloat(value);
    //     return isNaN(num) ? '0.00' : num.toFixed(2);
    // }

    function formatPayDetailAmount(value) {
        var num = parseFloat(value);
        if (isNaN(num)) return '0.00';
        var parts = num.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function getPayNowTotalDueCap(content) {
        if (!content) return 0;
        var totalDueRaw = content.getAttribute('data-total-due-amount-raw');
        if (totalDueRaw === null || totalDueRaw === '') return 0;
        var totalDue = parseFloat(totalDueRaw);
        return isNaN(totalDue) || totalDue < 0 ? 0 : totalDue;
    }

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var rows = (data.items || []).map(function(item) {
            return '<tr><td>' + (item.store_name || '—') + '</td><td>' + (item.item_name || '—') + '</td><td>' + (item.issue_date || '—') + '</td><td class="text-end">' + (item.price || '0') + '</td><td class="text-end">' + (item.quantity || '0') + '</td><td class="text-end">' + (item.amount || '0') + '</td></tr>';
        }).join('');
        var clientNameCourse = data.client_name_course || (function () {
            if (data.course_name) {
                return (data.client_name || '—') + ' – ' + data.course_name;
            }
            return data.client_name || '—';
        })();
        var hasRefOrOrder = !!(data.reference_number || data.order_by);
        var html = '<div class="receipt-top">' +
            '<div class="receipt-logo"><span class="receipt-logo-icon"></span><span class="receipt-logo-text">Sargam</span></div>' +
            '<span class="receipt-date">Date ' + dateStr + ' ' + timeStr + '</span>' +
            '</div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + (data.date_from || '') + ' To ' + (data.date_to || '') + '</div>' +
            '</div>' +
            '<hr/>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Receipt No</span>: <span class="client-value">' + (data.receipt_no || '—') + '</span></span>' +
            '<span><span class="client-label">Invoice No</span>: <span class="client-value">' + (data.invoice_no || '—') + '</span></span>' +
            '</div>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Client Name</span>: <span class="client-value">' + clientNameCourse + '</span></span>' +
            '<span><span class="client-label">Client Type</span>: <span class="client-value">' + (data.client_type || '—') + '</span></span>' +
            '</div>' +
            (hasRefOrOrder
                ? ('<div class="client-row">' +
                   (data.reference_number ? '<span><span class="client-label">Reference Number</span>: <span class="client-value">' + data.reference_number + '</span></span>' : '') +
                   (data.order_by ? '<span><span class="client-label">Order By</span>: <span class="client-value">' + data.order_by + '</span></span>' : '') +
                   '</div>')
                : '') +
            (data.remarks
                ? ('<div class="client-row"><span><span class="client-label">Remarks</span>: <span class="client-value">' + data.remarks + '</span></span></div>')
                : '') +
            '<hr/>' +
            '<table class="bill-table"><thead><tr><th>Store Name</th><th>Item Name</th><th>Issue Date</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="receipt-bottom">' +
            '<div></div>' +
            '<div class="payment-summary">' +
            '<div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">' + (data.paid_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">' + (data.total_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">' + (data.due_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Due Amount</span><span class="summary-value">' + (data.total_due_amount || data.due_amount || '0.0') + '</span></div>' +
            '</div>' +
            '</div>';
        return html;
    }

    function openPaymentDetailsModal(billId, dateFromYmd, dateToYmd) {
        paymentDetailsBillId = billId;
        paymentDetailsDateFrom = dateFromYmd || null;
        paymentDetailsDateTo = dateToYmd || null;
        var content = document.getElementById('paymentDetailsContent');
        if (content) content.innerHTML = '<div class="text-center py-4 text-muted">Loading...</div>';
        var url = paymentDetailsUrl.replace('__ID__', encodeURIComponent(billId));
        if (String(billId).indexOf('combined-') === 0 && (paymentDetailsDateFrom || paymentDetailsDateTo)) {
            var params = [];
            if (paymentDetailsDateFrom) params.push('date_from=' + encodeURIComponent(paymentDetailsDateFrom));
            if (paymentDetailsDateTo) params.push('date_to=' + encodeURIComponent(paymentDetailsDateTo));
            if (params.length) url += (url.indexOf('?') >= 0 ? '&' : '?') + params.join('&');
        }
        fetch(url).then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    content.innerHTML = '<div class="text-danger py-4 text-center">' + (data.error || 'Failed to load.') + '</div>';
                    showToast(data.error || 'Failed to load payment details.', 'error');
                    return;
                }
                content.innerHTML = renderPaymentDetailsContent(data);
                content.setAttribute('data-due-amount-raw', data.due_amount_raw != null ? data.due_amount_raw : data.due_amount || 0);
                var totalDueRaw = data.total_due_amount_raw != null
                    ? data.total_due_amount_raw
                    : (parseFloat(String(data.total_due_amount || '0').replace(/,/g, '')) || 0);
                content.setAttribute('data-total-due-amount-raw', totalDueRaw);
                if (data.first_receipt_id) content.setAttribute('data-first-receipt-id', data.first_receipt_id);
                else content.removeAttribute('data-first-receipt-id');
                var pdModal = document.getElementById('paymentDetailsModal');
                var addModalEl = document.getElementById('addProcessMessBillsModal');
                var addModalInstance = addModalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getInstance(addModalEl) : null;
                function showPaymentDetailsModal() {
                    if (pdModal && typeof bootstrap !== 'undefined') {
                        var m = bootstrap.Modal.getOrCreateInstance(pdModal);
                        m.show();
                    }
                }
                if (addModalInstance) {
                    addModalEl.addEventListener('hidden.bs.modal', function once() {
                        addModalEl.removeEventListener('hidden.bs.modal', once);
                        showPaymentDetailsModal();
                    });
                    addModalInstance.hide();
                } else {
                    showPaymentDetailsModal();
                }
            })
            .catch(function() {
                if (content) content.innerHTML = '<div class="text-danger py-4 text-center">Failed to load payment details.</div>';
                showToast('Failed to load payment details.', 'error');
            });
    }

    pmbeOn('paymentDetailsPayNowBtn', 'click', function() {
        var content = document.getElementById('paymentDetailsContent');
        var dueRaw = content && content.getAttribute('data-due-amount-raw');
        var due = dueRaw !== null && dueRaw !== '' ? parseFloat(dueRaw) : 0;
        var totalDueRaw = content && content.getAttribute('data-total-due-amount-raw');
        var totalDue = totalDueRaw !== null && totalDueRaw !== '' ? parseFloat(totalDueRaw) : NaN;
        var totalDueEl = document.getElementById('payNowTotalDueAmount');
        if (totalDueEl) {
            totalDueEl.textContent = isNaN(totalDue) ? '—' : formatPayDetailAmount(totalDue);
        }
        var totalDueCap = getPayNowTotalDueCap(content);
        var amountInput = document.getElementById('payNowAmount');
        amountInput.value = isNaN(due) ? '' : due;
        amountInput.setAttribute('max', totalDueCap > 0 ? totalDueCap : ((isNaN(due) || due < 0) ? '' : due));
        var pdModal = document.getElementById('paymentDetailsModal');
        if (pdModal && bootstrap.Modal.getInstance(pdModal)) bootstrap.Modal.getInstance(pdModal).hide();
        var payNowModal = document.getElementById('payNowModal');
        if (payNowModal && typeof bootstrap !== 'undefined') {
            payNowModal.classList.toggle('payment-mode-cheque', document.getElementById('payNowPaymentMode').value === 'cheque');
            var m = bootstrap.Modal.getOrCreateInstance(payNowModal);
            m.show();
        }
    });

    pmbeOn('paymentDetailsPrintBtn', 'click', function() {
        var content = document.getElementById('paymentDetailsContent');
        var receiptId = paymentDetailsBillId;
        if (String(receiptId || '').indexOf('combined-') === 0) {
            receiptId = receiptId;
        } else {
            receiptId = (content && content.getAttribute('data-first-receipt-id')) || receiptId;
        }
        if (receiptId) {
            var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(receiptId));
            if (String(receiptId).indexOf('combined-') === 0 && (paymentDetailsDateFrom || paymentDetailsDateTo)) {
                printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(paymentDetailsDateFrom || '') + '&date_to=' + encodeURIComponent(paymentDetailsDateTo || '');
            }
            window.open(printUrl, '_blank');
        }
    });

    pmbeOn('payNowPaymentMode', 'change', function() {
        var modal = document.getElementById('payNowModal');
        if (modal) modal.classList.toggle('payment-mode-cheque', this.value === 'cheque');
    });

    pmbeOn('payNowSaveBtn', 'click', function() {
        var billId = paymentDetailsBillId;
        if (!billId) { showToast('No bill selected.', 'error'); return; }
        var content = document.getElementById('paymentDetailsContent');
        var totalDueCap = getPayNowTotalDueCap(content);
        var amountEl = document.getElementById('payNowAmount');
        var modeEl = document.getElementById('payNowPaymentMode');
        var dateEl = document.getElementById('payNowPaymentDate');
        var amount = amountEl && amountEl.value ? amountEl.value : '';
        var paymentMode = modeEl && modeEl.value ? modeEl.value : 'cash';
        var paymentDate = dateEl && dateEl.value ? dateEl.value : '';
        if (!amount) { showToast('Please enter amount.', 'error'); return; }
        var amountNum = parseFloat(amount);
        if (isNaN(amountNum) || amountNum <= 0) { showToast('Please enter a valid amount.', 'error'); return; }
        if (totalDueCap <= 0) {
            showToast('This bill has no outstanding due amount.', 'error');
            return;
        }
        if (amountNum > totalDueCap) {
            showToast('Amount cannot exceed total due amount.', 'error');
            return;
        }
        // var payload = { amount: amountNum.toFixed(2), payment_mode: paymentMode, payment_date: paymentDate };
        var payload = { amount: amount, payment_mode: paymentMode, payment_date: paymentDate };
        if (paymentMode === 'cheque') {
            payload.bank_name = (document.getElementById('payNowBankName') || {}).value || '';
            payload.cheque_number = (document.getElementById('payNowChequeNumber') || {}).value || '';
            payload.cheque_date = (document.getElementById('payNowChequeDate') || {}).value || '';
        }
        if (String(billId).indexOf('combined-') === 0 && paymentDetailsDateFrom) payload.date_from = paymentDetailsDateFrom;
        if (String(billId).indexOf('combined-') === 0 && paymentDetailsDateTo) payload.date_to = paymentDetailsDateTo;
        var btn = this;
        btn.disabled = true;
        doGeneratePayment(billId, '', btn, payload);
        btn.disabled = false;
    });

    document.addEventListener('mousedown', function(e) {
        var invoiceBtn = e.target.closest('.generate-invoice-btn');
        if (invoiceBtn) {
            e.preventDefault();
            e.stopPropagation();
            if (invoiceBtn.disabled || invoiceBtn.getAttribute('data-invoice-sent') === '1') {
                showToast('Already sent invoice for all items in this date range.', 'error');
                return;
            }
            var billId = invoiceBtn.getAttribute('data-bill-id');
            var buyerName = invoiceBtn.getAttribute('data-buyer-name') || '';
            if (confirm('Generate invoice and send notification to ' + (buyerName || 'this employee') + '?')) {
                doGenerateInvoice(billId, buyerName, invoiceBtn);
            }
            return;
        }
        var paymentBtn = e.target.closest('.generate-payment-btn');
        if (paymentBtn) {
            e.preventDefault();
            e.stopPropagation();
            var billId = paymentBtn.getAttribute('data-bill-id');
            var dateFromYmd = null;
            var dateToYmd = null;
            if (String(billId).indexOf('combined-') === 0) {
                dateFromYmd = getModalDateYmd('modal_date_from') || null;
                dateToYmd = getModalDateYmd('modal_date_to') || null;
            }
            openPaymentDetailsModal(billId, dateFromYmd, dateToYmd);
            return;
        }
    }, true);

    // Bulk actions

});

/* ===== index form script ===== */
document.addEventListener('DOMContentLoaded', function() {
    var df = document.getElementById('date_from');
    var dt = document.getElementById('date_to');
    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }
    document.querySelectorAll('form[action="' + PMBE_CFG.indexFormAction + '"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var hFrom = form.querySelector('input[name="date_from"]');
            var hTo = form.querySelector('input[name="date_to"]');
            var valFrom = (df && df.value) ? (toYmd(df.value) || df.value) : (hFrom ? hFrom.value : '');
            var valTo = (dt && dt.value) ? (toYmd(dt.value) || dt.value) : (hTo ? hTo.value : '');
            if (hFrom && valFrom) hFrom.value = valFrom;
            if (hTo && valTo) hTo.value = valTo;
        });
    });
});

/* ===== New-design filter toolbar: debounced auto-apply + dependent clearing =====
   Added for the design.md redesign. Submits #mainFilterForm ~600ms after the last
   change to a .pmbe-auto-filter control (debounce lets multi-selects gather picks). */
document.addEventListener('DOMContentLoaded', function () {
    var pmbeForm = document.getElementById('mainFilterForm');
    if (!pmbeForm) return;
    var pmbeTimer = null;

    // This grid is server-rendered (no DataTables ajax feed), so applying a
    // filter re-fetches THIS page and swaps in the parts that change — rows and
    // the stat cards — instead of reloading the browser.
    function pmbeQueryString() {
        var params = new URLSearchParams();
        new FormData(pmbeForm).forEach(function (value, key) {
            if (value === null || String(value).trim() === '') return;
            params.append(key, value);   // append: multi-selects send repeated keys
        });
        return params.toString();
    }

    function pmbeSetBusy(busy) {
        var host = document.getElementById('processMessBillsTable');
        if (host) host.style.opacity = busy ? '0.55' : '';
    }

    function pmbeApplyFilters() {
        var qs = pmbeQueryString();
        var url = window.location.pathname + (qs ? '?' + qs : '');
        // Keep the URL in step so refresh, bookmarking and the Download/Print
        // links (which read the same query) all agree with what is on screen.
        window.history.replaceState({}, '', url);
        pmbeSetBusy(true);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');

                var freshBody = doc.querySelector('#processMessBillsTable tbody');
                var liveBody = document.querySelector('#processMessBillsTable tbody');
                if (freshBody && liveBody) liveBody.replaceWith(freshBody);

                ['total-bills', 'unpaid', 'paid', 'total-amount', 'total-due-amount'].forEach(function (key) {
                    var id = 'process-mess-stats-' + key;
                    var fresh = doc.getElementById(id);
                    var live = document.getElementById(id);
                    if (fresh && live) live.textContent = fresh.textContent;
                });
            })
            .catch(function () {
                // Never strand the user on a stale grid: fall back to a reload.
                window.location.href = url;
            })
            .then(function () { pmbeSetBusy(false); });
    }
    function pmbeClearDependents(el) {
        var ids = (el.getAttribute('data-clears') || '').split(',');
        ids.forEach(function (id) {
            id = id.trim();
            if (!id) return;
            var dep = document.getElementById(id);
            if (!dep) return;
            var $dep = window.jQuery;
            if ($dep && $dep.fn && $dep.fn.select2 && $dep(dep).data('select2')) {
                dep.value = '';
                $dep(dep).trigger('change.select2');
            } else if (dep.choicesInstance && typeof dep.choicesInstance.removeActiveItems === 'function') {
                try { dep.choicesInstance.removeActiveItems(); } catch (e) {}
            } else {
                Array.prototype.forEach.call(dep.options || [], function (o) { o.selected = false; });
                dep.value = '';
            }
        });
    }
    pmbeForm.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.classList || !t.classList.contains('pmbe-auto-filter')) return;
        pmbeClearDependents(t);
        if (pmbeTimer) clearTimeout(pmbeTimer);
        pmbeTimer = setTimeout(pmbeApplyFilters, 600);
    });
});

/* ===== Column-visibility bridge: modal <-> MessColumnManager =====
   Added for the design.md redesign (mirrors the selling-voucher-date-range page).
   Parameterised so both grids on this module can use it: the index's server-side
   table and the Generate Invoice page's hand-rendered bills table. */
(function () {
    function bindColumnVisibility(tableId, modalId, gridId, inputPrefix) {
        var grid = document.getElementById(gridId);
        var modalEl = document.getElementById(modalId);
        if (!grid || !modalEl) return;

        function getMgr() {
            return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function')
                ? window.MessColumnManager.get(tableId) : null;
        }
        function visibleCount(mgr) {
            return mgr.baseColumns.filter(function (c) { return mgr.state.visibility[String(c.index)] !== false; }).length;
        }
        function buildGrid() {
            var mgr = getMgr();
            if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;
            grid.innerHTML = '';
            (mgr.state.order || []).forEach(function (idx) {
                var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
                if (!col) return;
                var isVisible = mgr.state.visibility[String(col.index)] !== false;
                var inputId = inputPrefix + col.index;
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
            // The manager is created after the grid first renders, so poll briefly.
            if (buildGrid()) return;
            var tries = 0;
            var timer = setInterval(function () { if (buildGrid() || ++tries > 20) clearInterval(timer); }, 100);
        });
    }

    bindColumnVisibility('processMessBillsTable', 'pmbeColumnVisibilityModal', 'pmbeColumnToggleGrid', 'pmbecolvis_');
    bindColumnVisibility('modalBillsTable', 'modalBillsColumnVisibilityModal', 'modalBillsColumnToggleGrid', 'mbcolvis_');
})();

/* ===== Responsive "+Filter" overflow — filters that don't fit collapse into a popover =====
   Added for the design.md redesign (mirrors the selling-voucher-date-range toolbar). */
document.addEventListener('DOMContentLoaded', function () {
    var pmbeForm = document.getElementById('mainFilterForm');
    // The design's filter popover has a close control.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pmbe-close-more]');
        if (!btn) return;
        var toggle = document.getElementById('pmbeMoreFilterToggle');
        if (toggle && window.bootstrap && window.bootstrap.Dropdown) {
            var dd = window.bootstrap.Dropdown.getInstance(toggle) || new window.bootstrap.Dropdown(toggle);
            dd.hide();
        }
    });

    var itemsWrap = document.getElementById('pmbeFilterItems');
    var moreWrap = document.getElementById('pmbeMoreFilterWrap');
    var moreMenu = document.getElementById('pmbeMoreFilterItems');
    var moreToggle = document.getElementById('pmbeMoreFilterToggle');
    if (!pmbeForm || !itemsWrap || !moreWrap || !moreMenu || !moreToggle) return;
    var allItems = Array.prototype.slice.call(itemsWrap.querySelectorAll('.pmbe-filter-item'));
    function fits() { return pmbeForm.scrollWidth <= pmbeForm.clientWidth + 1; }
    function layout() {
        allItems.forEach(function (it) { itemsWrap.appendChild(it); });
        moreWrap.classList.add('d-none');
        if (fits()) return;
        moreWrap.classList.remove('d-none');
        var moved = 0;
        for (var i = allItems.length - 1; i >= 0; i--) {
            if (fits()) break;
            moreMenu.insertBefore(allItems[i], moreMenu.firstChild);
            moved++;
        }
        moreToggle.textContent = moved > 0 ? ('+' + moved + ' Filter') : '+ Filter';
        moreToggle.classList.toggle('pmbe-more-filters-active', moved > 0);
        if (moved === 0) moreWrap.classList.add('d-none');
    }
    var raf = null;
    function scheduleLayout() { if (raf) return; raf = window.requestAnimationFrame(function () { raf = null; layout(); }); }
    layout();
    window.addEventListener('resize', scheduleLayout);
    // Pills grow to fit the value they hold, so re-flow when one changes.
    window.addEventListener('pmbe:filters-resized', scheduleLayout);
    window.setTimeout(layout, 200);
    window.setTimeout(layout, 600);
});

/* ===== Collapsible search =====
   The design shows search as an icon that opens a field. The real input is the
   one DataTables injects into .programme-dt-search, so this only reveals it —
   the slot is width-collapsed, never removed, so filtering keeps working. */
document.addEventListener('DOMContentLoaded', function () {
    var wrap = document.getElementById('pmbeSearchWrap');
    var toggle = document.getElementById('pmbeSearchToggle');
    if (!wrap || !toggle) return;

    function input() { return wrap.querySelector('.programme-dt-search input'); }

    function setOpen(open) {
        wrap.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        // Opening steals width from the filter row, so let it re-flow.
        window.dispatchEvent(new Event('pmbe:filters-resized'));
        if (open) {
            var el = input();
            if (el) el.focus();
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(!wrap.classList.contains('is-open'));
    });

    // Collapsing on a stray click would hide an active search term, so only
    // close when the field is empty.
    document.addEventListener('click', function (e) {
        if (!wrap.classList.contains('is-open')) return;
        if (wrap.contains(e.target)) return;
        var el = input();
        if (el && el.value.trim() !== '') return;
        setOpen(false);
    });

    // A term restored from DataTables state must be visible on load.
    window.setTimeout(function () {
        var el = input();
        if (el && el.value.trim() !== '') setOpen(true);
    }, 700);
});

/* ===== Modal filter toolbar: overflow + collapsible search =====
   Same behaviour as the page's toolbar, against the modal's own IDs. The modal
   is narrower, so more filters collapse into "+N Filter" — that is the design's
   "+2 Filter". */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('pmbeModalFilterForm');
    var itemsWrap = document.getElementById('pmbeModalFilterItems');
    var moreWrap = document.getElementById('pmbeModalMoreFilterWrap');
    var moreMenu = document.getElementById('pmbeModalMoreFilterItems');
    var moreToggle = document.getElementById('pmbeModalMoreFilterToggle');
    if (!form || !itemsWrap || !moreWrap || !moreMenu || !moreToggle) return;

    var allItems = Array.prototype.slice.call(itemsWrap.querySelectorAll('.pmbe-filter-item'));
    function fits() { return form.scrollWidth <= form.clientWidth + 1; }

    function layout() {
        // A hidden modal has no width; measuring then would collapse everything.
        if (!form.clientWidth) return;
        allItems.forEach(function (it) { itemsWrap.appendChild(it); });
        moreWrap.classList.add('d-none');
        if (fits()) return;
        moreWrap.classList.remove('d-none');
        var moved = 0;
        for (var i = allItems.length - 1; i >= 0; i--) {
            if (fits()) break;
            moreMenu.insertBefore(allItems[i], moreMenu.firstChild);
            moved++;
        }
        moreToggle.textContent = moved > 0 ? ('+' + moved + ' Filter') : '+ Filter';
        moreToggle.classList.toggle('pmbe-more-filters-active', moved > 0);
        if (moved === 0) moreWrap.classList.add('d-none');
    }

    var raf = null;
    function schedule() {
        if (raf) return;
        raf = window.requestAnimationFrame(function () { raf = null; layout(); });
    }
    window.pmbeModalLayoutFilters = function () { layout(); window.setTimeout(layout, 150); };
    window.addEventListener('resize', schedule);
    // Pills grow to fit the value they hold.
    window.addEventListener('pmbe:filters-resized', schedule);

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-pmbe-modal-close-more]')) {
            var dd = window.bootstrap && window.bootstrap.Dropdown
                ? (window.bootstrap.Dropdown.getInstance(moreToggle) || new window.bootstrap.Dropdown(moreToggle))
                : null;
            if (dd) dd.hide();
        }
    });

    // --- Collapsible search (the input is the modal's own #modalSearch) ---
    var wrap = document.getElementById('pmbeModalSearchWrap');
    var toggle = document.getElementById('pmbeModalSearchToggle');
    if (!wrap || !toggle) return;
    var input = document.getElementById('modalSearch');

    function setOpen(open) {
        wrap.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        // The field slides open over 180ms; re-flow once it has its real width,
        // or the filters never make room and the input overlaps Remove Filter.
        schedule();
        window.setTimeout(layout, 250);
        if (open && input) input.focus();
    }
    toggle.addEventListener('click', function () { setOpen(!wrap.classList.contains('is-open')); });
    document.addEventListener('click', function (e) {
        if (!wrap.classList.contains('is-open') || wrap.contains(e.target)) return;
        if (input && input.value.trim() !== '') return;   // never hide an active term
        setOpen(false);
    });
});

/* ===== Generate Invoice page: Download (Excel) =====
   The grid's filters live in the form, not in the URL, so the link is rebuilt
   from the same query the table was fetched with — the download can never show
   a different set of bills than the screen. */
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('generateInvoiceDownloadBtn');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
        if (typeof window.buildModalBillsDataUrl !== 'function') return;   // plain href fallback
        e.preventDefault();

        // Reuse the grid's own URL builder, then swap the endpoint and drop the
        // paging params — the export always covers every matching bill.
        var qs = window.buildModalBillsDataUrl({ forPrint: true }).split('?')[1] || '';
        var params = new URLSearchParams(qs);
        ['page', 'per_page', 'for_print'].forEach(function (k) { params.delete(k); });

        // Respect hidden columns, the way the index's export does.
        var mgr = window.MessColumnManager && window.MessColumnManager.get
            ? window.MessColumnManager.get('modalBillsTable') : null;
        if (mgr && mgr.baseColumns) {
            var visible = mgr.baseColumns
                .filter(function (c) { return mgr.state.visibility[String(c.index)] !== false; })
                .map(function (c) { return c.index; });
            if (visible.length) params.set('visible_columns', visible.join(','));
        }

        var base = (window.PMBE_CFG && PMBE_CFG.generateInvoiceExportUrl) || btn.getAttribute('href');
        window.location.href = base + '?' + params.toString();
    });
});
