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

    if (typeof window.Choices !== 'undefined') {
        document
            .querySelectorAll('.process-mess-bills-employee-report select.choices-select')
            .forEach(function (el) {
                initChoicesElement(el);
            });
    }

    // Ensure modal dropdowns are (re)initialized with Choices when the modal opens
    var addProcessMessBillsModalEl = document.getElementById('addProcessMessBillsModal');
    if (addProcessMessBillsModalEl && typeof bootstrap !== 'undefined') {
        addProcessMessBillsModalEl.addEventListener('shown.bs.modal', function () {
            ['modal_client_type', 'modal_client_type_pk', 'modal_buyer_name', 'modal_mode_of_payment'].forEach(function (id) {
                var el = document.getElementById(id);
                initChoicesElement(el);
            });

            initModalBillsColumnManager();
            updateModalBillsSortHeaderIcons();

            // After Choices.js initialization, populate the modal dropdowns
            setTimeout(function() {
                if (typeof fillModalClientTypePk === 'function') {
                    fillModalClientTypePk();
                }
            }, 50);
        });
    }

    var modalBillsData = [];
    var modalBillsCurrentPage = 1;
    var modalBillsTotal = 0;
    var modalBillsFrom = 0;
    var modalBillsTo = 0;
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
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        var bulkActionsBar = document.getElementById('modalBulkActionsBar');
        var paginationInfo = document.getElementById('modalPaginationInfo');
        var paginationNav = document.getElementById('modalPaginationNav');
        if (!tbody) return;

        var skeletonRow = function (rowIndex) {
            var srText = rowIndex === 0
                ? '<span class="visually-hidden" role="status">Loading bills</span>'
                : '';
            return '<tr class="modal-bills-skeleton-row" aria-hidden="' + (rowIndex === 0 ? 'false' : 'true') + '">' +
                '<td>' + srText + '<span class="modal-bills-skeleton modal-bills-skeleton--check"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--sn"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--buyer"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--invoice"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--payment"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--total"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--status"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--action"></span></td>' +
                '<td><span class="modal-bills-skeleton modal-bills-skeleton--receipt"></span></td>' +
                '</tr>';
        };

        tbody.innerHTML = [0, 1, 2, 3, 4].map(skeletonRow).join('');
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        if (bulkActionsBar) bulkActionsBar.classList.add('d-none');
        if (paginationInfo) paginationInfo.textContent = 'Loading bills...';
        if (paginationNav) paginationNav.classList.add('d-none');
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

                        buyerSelect.innerHTML = '';

                        buyers.forEach(function (name) {
                            var opt = document.createElement('option');
                            opt.value = name;
                            opt.textContent = name;
                            buyerSelect.appendChild(opt);
                        });

                        if (buyerSelect.choicesInstance) {
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
        if (!addModalEl || typeof bootstrap === 'undefined') return;
        var wasVisible = addModalEl.classList.contains('show');
        var addInst = bootstrap.Modal.getOrCreateInstance(addModalEl);
        addInst.show();
        if (wasVisible) loadModalBills();
    }

    function getFilteredModalBills() {
        return modalBillsData || [];
    }

    function updateModalPaginationNav(totalPages, filteredLength) {
        var nav = document.getElementById('modalPaginationNav');
        var prevBtn = document.getElementById('modalPaginationPrev');
        var nextBtn = document.getElementById('modalPaginationNext');
        var prevLi = document.getElementById('modalPaginationPrevLi');
        var nextLi = document.getElementById('modalPaginationNextLi');
        var pageLi = document.getElementById('modalPaginationPageLi');
        var label = document.getElementById('modalPaginationPageLabel');
        if (!nav || !prevBtn || !nextBtn || !label) return;
        if (totalPages <= 1 || !filteredLength) {
            nav.classList.add('d-none');
            return;
        }
        nav.classList.remove('d-none');
        label.textContent = 'Page ' + modalBillsCurrentPage + ' of ' + totalPages;
        var onFirst = modalBillsCurrentPage <= 1;
        var onLast = modalBillsCurrentPage >= totalPages;
        prevBtn.disabled = onFirst;
        nextBtn.disabled = onLast;
        if (prevLi) prevLi.classList.toggle('disabled', onFirst);
        if (nextLi) nextLi.classList.toggle('disabled', onLast);
        if (pageLi) pageLi.classList.add('disabled');
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
                skipColumns: [7, 8]
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

    function renderModalTable() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        setModalBillsLoading(false);
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        var filtered = getFilteredModalBills();
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        modalBillsCurrentPage = Math.max(1, Math.min(modalBillsCurrentPage, totalPages || 1));
        var start = modalBillsFrom ? modalBillsFrom - 1 : 0;
        var pageData = filtered;

        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No unpaid bills found. Adjust date range and click Load Bills.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function(b, i) {
                var sn = b.sno || (start + i + 1);
                var printUrl = printReceiptBaseUrl.replace('__ID__', encodeURIComponent(b.id));
                if (String(b.id || '').indexOf('combined-') === 0) {
                    var receiptDf = b.date_from || getModalDateYmd('modal_date_from') || '';
                    var receiptDt = b.date_to || getModalDateYmd('modal_date_to') || '';
                    printUrl += (printUrl.indexOf('?') >= 0 ? '&' : '?') + 'date_from=' + encodeURIComponent(receiptDf) + '&date_to=' + encodeURIComponent(receiptDt);
                }
                var statusCell = formatInvoiceNotificationStatusCell(b);
                var invoiceFullySent = !!b.invoice_notification_fully_sent;
                var invoiceBtnClass = invoiceFullySent ? 'btn btn-outline-secondary generate-invoice-btn' : 'btn btn-outline-primary generate-invoice-btn';
                var invoiceBtnTitle = invoiceFullySent
                    ? 'Invoice already sent for all items in this range'
                    : (b.invoice_notification_partial ? 'Send invoice for new item(s)' : 'Generate Invoice');
                var invoiceBtnAttrs = 'data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="' + invoiceBtnTitle + '"' + (invoiceFullySent ? ' disabled data-invoice-sent="1"' : '');
                return '<tr class="' + (i % 2 === 0 ? 'table-light' : '') + '">' +
                    '<td><input type="checkbox" class="form-check-input modal-bill-check" data-id="' + b.id + '" data-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '"></td>' +
                    '<td>' + sn + '</td>' +
                    '<td>' + (b.buyer_name || '—') + '</td>' +
                    '<td>' + (b.invoice_no || '—') + '</td>' +
                    '<td>' + (b.payment_type || '—') + '</td>' +
                    '<td class="text-end">' + (b.total || '0') + '</td>' +
                    '<td class="text-end fw-semibold">' + (b.total_due_amount || '0.00') + '</td>' +
                    '<td class="text-center">' + statusCell + '</td>' +
                    '<td class="text-center"><div class="btn-group btn-group-sm">' +
                    '<button type="button" class="' + invoiceBtnClass + '" ' + invoiceBtnAttrs + '>Invoice</button>' +
                    '<button type="button" class="btn btn-outline-success generate-payment-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Mark as Paid">Payment</button>' +
                    '</div></td>' +
                    '<td class="text-center"><a href="' + printUrl + '" target="_blank" class="btn  btn-outline-secondary" title="Print receipt"><i class="material-symbols-rounded" style="font-size:1.1rem;">receipt</i></a></td>' +
                    '</tr>';
            }).join('');
        }

        document.getElementById('modalPaginationInfo').textContent = 'Showing ' + modalBillsFrom + ' to ' + modalBillsTo + ' of ' + modalBillsTotal + ' entries';
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
            bn.innerHTML = '';
        }
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

    document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() {
        updateModalBillsSortHeaderIcons();
        loadModalBills();
    });
    updateModalBillsSortHeaderIcons();
    var payNowModalForAddRedirect = document.getElementById('payNowModal');
    if (payNowModalForAddRedirect) {
        payNowModalForAddRedirect.addEventListener('hidden.bs.modal', function () {
            focusAddProcessMessBillsModal();
        });
    }
    document.getElementById('modalLoadBillsBtn').addEventListener('click', function() { loadModalBills(1); });
    document.getElementById('modalClearFiltersBtn').addEventListener('click', clearModalFilters);
    document.getElementById('modalSearch').addEventListener('input', function() {
        loadModalBills(1);
    });
    document.getElementById('modalPerPage').addEventListener('change', function() {
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
    document.getElementById('modalPaginationPrev').addEventListener('click', function() {
        if (modalBillsCurrentPage > 1) {
            loadModalBills(modalBillsCurrentPage - 1);
        }
    });
    document.getElementById('modalPaginationNext').addEventListener('click', function() {
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var totalPages = modalBillsTotal ? Math.ceil(modalBillsTotal / perPage) : 0;
        if (modalBillsCurrentPage < totalPages) {
            loadModalBills(modalBillsCurrentPage + 1);
        }
    });

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
            var currentClientPk = preserve ? preservedClientTypePk : '';
            console.log('=== fillClientTypePk START ===');
            console.log('slug:', slug, 'preserve:', preserve, 'currentClientPk:', currentClientPk);
            
            // If Choices.js exists, destroy it first to rebuild clean
            if (clientTypePk.choicesInstance) {
                console.log('Destroying existing Choices.js instance for clientTypePk...');
                try {
                    clientTypePk.choicesInstance.destroy();
                    clientTypePk.choicesInstance = null;
                    clientTypePk.dataset.choicesInitialized = 'false';
                } catch (e) {
                    console.error('Error destroying Choices instance:', e);
                }
            }
            
            clientTypePk.innerHTML = '<option value=\"\">All</option>';

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
            if (typeof window.Choices !== 'undefined') {
                initChoicesElement(clientTypePk);
                if (currentClientPk && clientTypePk.choicesInstance) {
                    console.log('fillClientTypePk - Setting choice to:', currentClientPk);
                    try {
                        clientTypePk.choicesInstance.setChoiceByValue(currentClientPk);
                    } catch (e) {
                        console.error('Error setting choice value:', e);
                    }
                }
            }
            
            console.log('fillClientTypePk - Calling fillBuyerSelect(true)...');
            fillBuyerSelect(true);
        }

        function fillBuyerSelect(preserve) {
            // Get selected slugs (now multiselect)
            var selectedSlugs = [];
            if (clientTypeSlug.choicesInstance) {
                selectedSlugs = clientTypeSlug.choicesInstance.getValue(true);
            } else {
                selectedSlugs = Array.from(clientTypeSlug.selectedOptions).map(function(opt) { return opt.value; });
            }
            
            // Get selected pks (now multiselect)
            var selectedPks = [];
            if (clientTypePk.choicesInstance) {
                selectedPks = clientTypePk.choicesInstance.getValue(true);
            } else {
                selectedPks = Array.from(clientTypePk.selectedOptions).map(function(opt) { return opt.value; });
            }
            
            var currentBuyer = preserve ? preservedBuyerName : [];
            console.log('=== fillBuyerSelect START ===');
            console.log('selectedSlugs:', selectedSlugs, 'selectedPks:', selectedPks, 'preserve:', preserve);
            console.log('buyerSelect.choicesInstance exists?', !!buyerSelect.choicesInstance);
            
            // If Choices.js exists, destroy it first to rebuild clean
            if (buyerSelect.choicesInstance) {
                console.log('Destroying existing Choices.js instance...');
                try {
                    buyerSelect.choicesInstance.destroy();
                    buyerSelect.choicesInstance = null;
                    buyerSelect.dataset.choicesInitialized = 'false';
                } catch (e) {
                    console.error('Error destroying Choices instance:', e);
                }
            }
            
            // Clear existing options
            buyerSelect.innerHTML = '';

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
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                            if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
                                buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                            }
                        }
                    })
                    .catch(function () {
                        fallbackFromServerLists();
                        // still init Choices
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                        }
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
                
                if (typeof window.Choices !== 'undefined') {
                    initChoicesElement(buyerSelect);
                    if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
                        try {
                            buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                        } catch (e) {
                            console.error('Error setting buyer values:', e);
                        }
                    }
                }
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
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                            if (Array.isArray(currentBuyer) && currentBuyer.length) {
                                buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                            }
                        }
                    })
                    .catch(function () {
                        // ignore; leave All Buyers only - still need to init Choices
                        if (typeof window.Choices !== 'undefined') {
                            initChoicesElement(buyerSelect);
                        }
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
                    if (typeof window.Choices !== 'undefined') {
                        initChoicesElement(buyerSelect);
                        if (Array.isArray(currentBuyer) && currentBuyer.length && buyerSelect.choicesInstance) {
                            try { buyerSelect.choicesInstance.setChoiceByValue(currentBuyer); } catch (e) {}
                        }
                    }
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

                            if (typeof window.Choices !== 'undefined') {
                                initChoicesElement(buyerSelect);
                                if (currentBuyer && buyerSelect.choicesInstance) {
                                    buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                                }
                            }
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
            if (typeof window.Choices !== 'undefined') {
                initChoicesElement(buyerSelect);
                if (currentBuyer && buyerSelect.choicesInstance) {
                    console.log('fillBuyerSelect - Setting choice to:', currentBuyer);
                    try {
                        buyerSelect.choicesInstance.setChoiceByValue(currentBuyer);
                    } catch (e) {
                        console.error('Error setting choice value:', e);
                    }
                }
            }
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

    document.getElementById('modalSelectAll').addEventListener('change', function() {
        document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check').forEach(function(cb) {
            cb.checked = this.checked;
        }.bind(this));
        updateBulkActionsBar();
    });

    document.getElementById('addProcessMessBillsModal').addEventListener('click', function(e) {
        var target = e.target.closest('.modal-bill-check');
        if (target && target.classList.contains('modal-bill-check')) {
            updateBulkActionsBar();
        }
    });

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

    document.getElementById('paymentDetailsPayNowBtn').addEventListener('click', function() {
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

    document.getElementById('paymentDetailsPrintBtn').addEventListener('click', function() {
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

    document.getElementById('payNowPaymentMode').addEventListener('change', function() {
        var modal = document.getElementById('payNowModal');
        if (modal) modal.classList.toggle('payment-mode-cheque', this.value === 'cheque');
    });

    document.getElementById('payNowSaveBtn').addEventListener('click', function() {
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
    document.getElementById('modalBulkInvoiceBtn').addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked')).map(function(c) { return c.getAttribute('data-id'); });
        if (ids.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        var toSend = ids.filter(function(id) {
            var b = (modalBillsData || []).find(function(x) { return String(x.id) === String(id); });
            return canSendInvoiceNotification(b);
        });
        var skipped = ids.length - toSend.length;
        if (toSend.length === 0) {
            showToast('Already sent invoice for all items in the selected date range.', 'error');
            return;
        }
        if (!confirm('Generate invoice for ' + toSend.length + ' selected bill(s)?')) return;
        if (skipped > 0) {
            showToast('Skipping ' + skipped + ' bill(s): all items already notified.', 'error');
        }
        toSend.forEach(function(id) {
            doGenerateInvoice(id, '', null);
        });
        showToast('Processing ' + toSend.length + ' invoice(s)...');
    });

    document.getElementById('modalBulkPaymentBtn').addEventListener('click', function() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        if (checked.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        if (!confirm('Mark ' + checked.length + ' selected bill(s) as paid?')) return;
        checked.forEach(function(cb) {
            doGeneratePayment(cb.getAttribute('data-id'), cb.getAttribute('data-name'), null);
        });
    });
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
    function pmbeClearDependents(el) {
        var ids = (el.getAttribute('data-clears') || '').split(',');
        ids.forEach(function (id) {
            id = id.trim();
            if (!id) return;
            var dep = document.getElementById(id);
            if (!dep) return;
            if (dep.choicesInstance && typeof dep.choicesInstance.removeActiveItems === 'function') {
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
        pmbeTimer = setTimeout(function () { pmbeForm.submit(); }, 600);
    });
});

/* ===== Column-visibility bridge: pmbeColumnVisibilityModal <-> MessColumnManager =====
   Added for the design.md redesign (mirrors the selling-voucher-date-range page). */
(function () {
    var TABLE_ID = 'processMessBillsTable';
    var grid = document.getElementById('pmbeColumnToggleGrid');
    var modalEl = document.getElementById('pmbeColumnVisibilityModal');
    if (!grid || !modalEl) return;
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
            var inputId = 'pmbecolvis_' + col.index;
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

/* ===== Responsive "+Filter" overflow — filters that don't fit collapse into a popover =====
   Added for the design.md redesign (mirrors the selling-voucher-date-range toolbar). */
document.addEventListener('DOMContentLoaded', function () {
    var pmbeForm = document.getElementById('mainFilterForm');
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
    window.setTimeout(layout, 200);
    window.setTimeout(layout, 600);
});
