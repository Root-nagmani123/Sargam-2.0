/**
 * COE question-paper grids — shared client-side DataTable wiring.
 *
 * Used by every coe-* listing (My Question Paper, Question Paper, …) so the
 * toolbar, exports and column picker behave identically. Page chrome comes from
 * the global datatable-global-ui.js (search slot + footer); this only adds what
 * those pages have in common on top of it.
 *
 *   var dt = CoeGrid.init({
 *       table: '#qpTable',
 *       title: 'My Question Paper',            // export title + filename
 *       order: [[5, 'asc']],
 *       noSort: [0, 10],                      // S. No. + Action
 *       exclude: [10],                        // columns never exported
 *       filters: { '#qpFilterDrive': 3 },     // select -> column index
 *       rowFilters: { '#sel': 'service' },    // select -> <tr data-*> (no column needed)
 *       snoCol: 0,                            // S. No. column (default 0)
 *       reset: '#qpResetFilters',
 *       download: '#qpDownloadBtn', print: '#qpPrintBtn',
 *       searchToggle: '#qpSearchToggle', searchSlot: '#qpDtSearch',
 *       colvisGrid: '#qpColumnToggleGrid', colvisKey: 'coeMyQuestionPaper:hiddenColumns:v1',
 *       empty: { title: '…', text: '…' }
 *   });
 */
(function (window, $) {
    'use strict';

    function emptyState(icon, title, text) {
        return '<div class="coe-empty"><i class="bi ' + icon + ' d-block mb-2" aria-hidden="true"></i>' +
            '<h6 class="fw-semibold mb-1">' + title + '</h6>' +
            '<p class="mb-0 small">' + text + '</p></div>';
    }

    // Cell text keeps the markup's whitespace, so allow it around the value.
    function exact(value) {
        return value ? '^\\s*' + $.fn.dataTable.util.escapeRegex(value) + '\\s*$' : '';
    }

    function readHidden(key) {
        try {
            var parsed = JSON.parse(localStorage.getItem(key) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function writeHidden(key, cols) {
        try { localStorage.setItem(key, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function headerText(column) {
        return $(column.header()).text().replace(/\s+/g, ' ').trim();
    }

    function init(o) {
        var $table = $(o.table);
        var exclude = o.exclude || [];
        var empty = o.empty || {};

        var dt = $table.DataTable({
            order: o.order || [],
            // Every column stays on the row (Status + Action included); the
            // .table-responsive wrapper scrolls sideways on narrow screens instead.
            responsive: false,
            columnDefs: [{ targets: o.noSort || [], orderable: false, searchable: false }],
            language: {
                emptyTable: emptyState('bi-file-earmark-text', empty.title || 'No Question Papers', empty.text || 'Nothing to show yet.'),
                zeroRecords: emptyState('bi-search', empty.zeroTitle || 'No Records Found', 'Nothing matches the filters applied.')
            }
        });

        // S. No. follows the visible order, not the server order.
        // snoCol: false = the grid has no running-number column.
        var snoCol = o.snoCol === false ? false : (o.snoCol || 0);
        if (snoCol !== false) {
            dt.on('draw.dt', function () {
                var start = dt.page.info().start;
                dt.column(snoCol, { search: 'applied', order: 'applied', page: 'current' }).nodes()
                    .each(function (cell, i) { cell.textContent = start + i + 1; });
            });
            dt.draw(false);
        }

        /* Download (Excel) / Print — the rows and columns on screen. */
        var exportOptions = {
            columns: function (idx) { return exclude.indexOf(idx) === -1 && dt.column(idx).visible(); },
            modifier: { search: 'applied', order: 'applied' },
            format: { body: function (data, row, column, node) { return $(node).text().replace(/\s+/g, ' ').trim(); } }
        };
        var filename = String(o.title || 'export').toLowerCase().replace(/[^a-z0-9]+/g, '-');

        new $.fn.dataTable.Buttons(dt, {
            buttons: [
                { extend: 'excelHtml5', title: o.title, filename: filename, exportOptions: exportOptions },
                { extend: 'print', title: o.title, exportOptions: exportOptions }
            ]
        });
        $(o.download).on('click', function () { dt.button(0).trigger(); });
        $(o.print).on('click', function () { dt.button(1).trigger(); });

        /* Filters. Initialised here rather than through the global data-searchable
           hook: that one runs before admin_assets/js/custom.js, whose unscoped
           $(".select2").select2() then wraps Select2's own container span (it
           carries class "select2") in a second, empty widget that covers the
           placeholder. A page's ready handler runs after custom.js's, so this is
           safe. Select2 fires a jQuery change — hence $().on. */
        var filters = o.filters || {};
        Object.keys(filters).forEach(function (sel) {
            var $sel = $(sel);
            if (!$sel.length) { return; }
            if (window.DropdownSearch) {
                window.DropdownSearch.init($sel[0], { placeholder: $sel.data('placeholder'), allowClear: false });
            }
            $sel.on('change', function () {
                dt.column(filters[sel]).search(exact(this.value), true, false).draw();
            });
        });

        /* Row filters — match a <tr data-*> value, for a filter with no column
           on screen (e.g. Service). Scoped to this table only. */
        var rowFilters = o.rowFilters || {};
        var tableId = $table.attr('id');
        Object.keys(rowFilters).forEach(function (sel) {
            var $sel = $(sel);
            if (!$sel.length) { return; }
            if (window.DropdownSearch) {
                window.DropdownSearch.init($sel[0], { placeholder: $sel.data('placeholder'), allowClear: false });
            }
            $sel.on('change', function () { dt.draw(); });
        });
        if (Object.keys(rowFilters).length) {
            $.fn.dataTable.ext.search.push(function (settings, data, index) {
                if (settings.nTable.id !== tableId) { return true; }
                var node = dt.row(index).node();
                return Object.keys(rowFilters).every(function (sel) {
                    var want = $(sel).val();
                    return !want || (node && node.getAttribute('data-' + rowFilters[sel]) === want);
                });
            });
        }

        $(o.reset).on('click', function () {
            $(Object.keys(filters).concat(Object.keys(rowFilters)).join(',')).val('').trigger('change.select2');
            dt.search('').columns().search('').draw();
            $(o.searchSlot).find('input').val('');
        });

        /* Search toggle — the icon reveals DataTables' own filter in the slot. */
        $(o.searchToggle).on('click', function () {
            var $slot = $(o.searchSlot);
            var open = $slot.hasClass('d-none');
            $slot.toggleClass('d-none', !open);
            $(this).attr('aria-expanded', open ? 'true' : 'false');
            if (open) { $slot.find('input').trigger('focus'); }
        });

        /* Column visibility — stored by LABEL, not index, so a column added later
           can't inherit someone else's hidden flag. */
        var $grid = $(o.colvisGrid);
        var hidden = readHidden(o.colvisKey);

        dt.columns().every(function () {
            var title = headerText(this);
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        dt.columns().every(function () {
            var index = this.index();
            var title = headerText(this);
            if (!title || !$grid.length) { return; }

            var inputId = $grid.attr('id') + '_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1)
                .on('change', function () {
                    var cols = readHidden(o.colvisKey);
                    var pos = cols.indexOf(title);
                    if (this.checked) { if (pos !== -1) { cols.splice(pos, 1); } }
                    else if (pos === -1) { cols.push(title); }
                    writeHidden(o.colvisKey, cols);
                    dt.column(index).visible(this.checked, false);
                    dt.columns.adjust();
                });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });

        return dt;
    }

    /**
     * Upload modal (admin/coe/partials/upload_modal) — .docx dropzone.
     *
     *   CoeGrid.upload({ table: '#t', trigger: '.coe-act--upload',
     *                   meta: function ($row) { return 'FC101 — …'; },
     *                   onSubmit: function ($row, file) { … } });
     */
    function upload(o) {
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('qpUploadModal'));
        var $submit = $('#qpUploadSubmit');
        var chosenFile = null;
        var $row = null;

        var dz = dropzone({
            root: '#qpUpload',
            accept: /\.docx$/i,
            acceptText: 'Only .docx files are allowed.',
            onChange: function (file) {
                chosenFile = file;
                $submit.prop('disabled', !file);
            }
        });

        $(o.table).on('click', o.trigger, function () {
            $row = $(this).closest('tr');
            $('#qpUploadMeta').text(o.meta ? o.meta($row) : '');
            modal.show();
        });

        $('#qpUploadModal').on('hidden.bs.modal', function () { dz.reset(); $row = null; });

        $('#qpUploadForm').on('submit', function (e) {
            e.preventDefault();
            if (!chosenFile || !$row) { return; }
            o.onSubmit($row, chosenFile);
            modal.hide();
        });
    }

    /**
     * Dropzone (admin/coe/partials/dropzone) — drag & drop or pick one file.
     *
     *   var dz = CoeGrid.dropzone({ root: '#id', accept: /\.(xlsx|xls|csv)$/i,
     *                               acceptText: 'Only …', maxBytes: 5 * 1024 * 1024,
     *                               onChange: function (fileOrNull) { … } });
     *   dz.reset();
     */
    function dropzone(o) {
        var $root = $(o.root);
        var $input = $root.find('.coe-dropzone__input');

        function error(msg) {
            $root.find('.coe-dropzone__error').text(msg || '').toggleClass('d-none', !msg);
            $root.toggleClass('is-invalid', !!msg);
        }

        function choose(file) {
            $root.find('.coe-dropzone__file').addClass('d-none');
            error('');

            if (file && o.accept && !o.accept.test(file.name)) {
                error(o.acceptText || 'This file type is not allowed.');
                file = null;
            } else if (file && o.maxBytes && file.size > o.maxBytes) {
                error('File is larger than ' + Math.round(o.maxBytes / 1048576) + ' MB.');
                file = null;
            }

            if (file) {
                $root.find('.coe-dropzone__name').text(file.name);
                $root.find('.coe-dropzone__file').removeClass('d-none');
            } else {
                $input.val('');
            }
            if (o.onChange) { o.onChange(file || null); }
        }

        $input.on('change', function () { choose(this.files && this.files[0]); });
        $root.find('.coe-dropzone__clear').on('click', function () { choose(null); });

        $root
            .on('dragenter dragover', function (e) { e.preventDefault(); $root.addClass('is-dragover'); })
            .on('dragleave dragend drop', function (e) { e.preventDefault(); $root.removeClass('is-dragover'); })
            .on('drop', function (e) {
                var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
                choose(files && files[0]);
            });

        return { reset: function () { choose(null); } };
    }

    /**
     * Freeze modal (admin/coe/partials/freeze_modal) — OTP boxes + resend timer.
     *
     *   CoeGrid.freeze({ table: '#t', trigger: '.coe-act--freeze', resendSeconds: 60,
     *                   onConfirm: function ($row, otp) { … } });
     */
    function freeze(o) {
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('qpFreezeModal'));
        var $boxes = $('#qpFreezeForm .coe-otp__box');
        var length = $boxes.length;
        var resendSeconds = o.resendSeconds || 60;
        var timer = null;
        var $row = null;

        function error(msg) {
            $('#qpOtpError').text(msg || '').toggleClass('d-none', !msg);
            $boxes.toggleClass('is-invalid', !!msg);
        }

        function value() { return $boxes.map(function () { return this.value; }).get().join(''); }

        function stopTimer() {
            if (timer) { clearInterval(timer); timer = null; }
            $('#qpOtpWait').addClass('d-none');
            $('#qpOtpResend').removeClass('d-none');
        }

        function startTimer() {
            var left = resendSeconds;
            var $t = $('#qpOtpTimer');
            var paint = function () {
                $t.text(String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0'));
            };

            stopTimer();
            $('#qpOtpResend').addClass('d-none');
            $('#qpOtpWait').removeClass('d-none');
            paint();

            timer = setInterval(function () {
                left -= 1;
                if (left <= 0) { stopTimer(); return; }
                paint();
            }, 1000);
        }

        $(o.table).on('click', o.trigger, function () {
            $row = $(this).closest('tr');
            modal.show();
        });

        $('#qpFreezeModal')
            .on('shown.bs.modal', function () { $boxes.first().trigger('focus'); })
            .on('hidden.bs.modal', function () { $boxes.val(''); error(''); stopTimer(); $row = null; });

        // One digit per box: advance on input, step back on Backspace, arrows move.
        $boxes.on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(-1);
            error('');
            if (this.value) { $boxes.eq($boxes.index(this) + 1).trigger('focus'); }
        });

        $boxes.on('keydown', function (e) {
            var i = $boxes.index(this);
            if (e.key === 'Backspace' && !this.value && i > 0) {
                $boxes.eq(i - 1).val('').trigger('focus');
                e.preventDefault();
            } else if (e.key === 'ArrowLeft' && i > 0) {
                $boxes.eq(i - 1).trigger('focus');
            } else if (e.key === 'ArrowRight' && i < length - 1) {
                $boxes.eq(i + 1).trigger('focus');
            }
        });

        // Pasting the whole code into any box fills them all.
        $boxes.on('paste', function (e) {
            var text = ((e.originalEvent.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '');
            if (!text) { return; }
            e.preventDefault();
            $boxes.each(function (i) { this.value = text.charAt(i) || ''; });
            $boxes.eq(Math.min(text.length, length) - 1).trigger('focus');
            error('');
        });

        $('#qpOtpResend').on('click', function () {
            $boxes.val('');
            error('');
            $boxes.first().trigger('focus');
            startTimer();
        });

        $('#qpFreezeForm').on('submit', function (e) {
            e.preventDefault();
            if (value().length !== length) {
                error('Enter the ' + length + '-digit OTP sent to your phone.');
                $boxes.filter(function () { return !this.value; }).first().trigger('focus');
                return;
            }
            if (!$row) { return; }
            o.onConfirm($row, value());
            modal.hide();
        });
    }

    /**
     * Confirm Delete (admin/coe/partials/confirm_delete_modal).
     *
     *   CoeGrid.confirmDelete({ table: '#t', trigger: '.coe-act--del',
     *                          modal: '#qpDeleteModal', confirm: '#qpDeleteConfirm',
     *                          bulkTrigger: '#btn',   // optional: opens it with no row
     *                          onConfirm: function ($rowOrNull) { … } });
     */
    function confirmDelete(o) {
        var modalEl = document.querySelector(o.modal || '#qpDeleteModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var $row = null;

        $(o.table).on('click', o.trigger || '.coe-act--del', function () {
            $row = $(this).closest('tr');
            bulk = false;
            modal.show();
        });
        var bulk = false;
        if (o.bulkTrigger) {
            $(o.bulkTrigger).on('click', function () { $row = null; bulk = true; modal.show(); });
        }
        $(modalEl).on('hidden.bs.modal', function () { $row = null; bulk = false; });
        $(o.confirm || '#qpDeleteConfirm').on('click', function () {
            if (!$row && !bulk) { return; }
            o.onConfirm($row);
            modal.hide();
        });
    }

    /**
     * Activate / Deactivate confirm (admin/coe/partials/confirm_status_modal).
     * The row carries data-coe-active="1|0"; the dialog shows the variant for
     * the change the click would make.
     *
     *   CoeGrid.confirmStatus({ table: '#t', trigger: '.coe-act--toggle',
     *                           onConfirm: function ($row, makeActive) { … } });
     */
    function confirmStatus(o) {
        var el = document.getElementById('coeStatusModal');
        var modal = bootstrap.Modal.getOrCreateInstance(el);
        var $root = $(el).find('.coe-status-confirm');
        var $row = null;
        var makeActive = false;

        var COPY = {
            on: { title: 'Activate this record?', text: 'Are you sure you want to activate this record?',
                  cancel: 'Cancel, Keep it Inactive', ok: 'Yes, Activate', icon: 'bi-toggle-on' },
            off: { title: 'Deactivate this record?', text: 'Are you sure you want to deactivate this record?',
                   cancel: 'Cancel, Keep it active', ok: 'Yes, Deactivate', icon: 'bi-toggle-off' }
        };

        $(o.table).on('click', o.trigger || '.coe-act--toggle', function () {
            $row = $(this).closest('tr');
            makeActive = $row.attr('data-coe-active') !== '1';
            var c = makeActive ? COPY.on : COPY.off;

            $root.toggleClass('is-activate', makeActive).toggleClass('is-deactivate', !makeActive);
            $root.find('.coe-status-confirm__icon i').attr('class', 'bi ' + c.icon);
            $('#coeStatusModalLabel').text(c.title);
            $('#coeStatusModalText').text(c.text);
            $root.find('.coe-status-confirm__cancel').text(c.cancel);
            $root.find('.coe-status-confirm__ok').text(c.ok);
            modal.show();
        });

        $(el).on('hidden.bs.modal', function () { $row = null; });
        $('#coeStatusConfirm').on('click', function () {
            if (!$row) { return; }
            o.onConfirm($row, makeActive);
            modal.hide();
        });
    }

    /**
     * "Name + status" master page (resources/views/admin/coe/simple_master).
     * Everything is addressed through the page's id prefix.
     *
     *   CoeGrid.simpleMaster({ title, entity, prefix, colvisKey, labels, sno, filters, fields });
     *   (the keys are documented at the top of simple_master/index.blade.php)
     *
     * DESIGN PREVIEW: no backend on this branch, so every change is made in the
     * browser only. The real endpoints (feature/coe_module Admin\Master\*MasterController)
     * take <field> + active_inactive; POST first and redraw from the response.
     */
    function simpleMaster(cfg) {
        var P = '#' + cfg.prefix;
        var fields = cfg.fields;
        var L = cfg.labels;
        var noun = cfg.entity.toLowerCase();
        var off = cfg.sno.show ? 1 : 0;                   // columns before the first field
        var COL = { status: off + fields.length, action: off + fields.length + 1 };
        var byKey = {};
        fields.forEach(function (f, i) { f.col = off + i; byKey[f.key] = f; });
        var formFields = (cfg.formOrder || []).map(function (k) { return byKey[k]; });
        var $table = $(P + 'Table');

        /* Status badge + actions — the ONE place each state is drawn. */
        function paintState($row) {
            var active = $row.attr('data-coe-active') === '1';
            $row.find('.coe-cell-status')
                .attr('data-order', active ? 1 : 0)
                .attr('data-search', active ? 'Active' : 'Inactive')
                .html($('<span class="coe-state"></span>')
                    .addClass(active ? 'coe-state--active' : 'coe-state--inactive')
                    .text(active ? 'Active' : 'Inactive'));

            // The toggle caption names the ACTION, not the state (docs §3b).
            $row.find('.coe-cell-actions').html(
                '<div class="coe-act-group" role="group" aria-label="Row actions">' +
                '<button type="button" class="coe-act coe-act--edit">' +
                    '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>' +
                    '<span class="coe-act__label">Edit</span></button>' +
                '<button type="button" class="coe-act coe-act--toggle ' + (active ? 'is-on' : 'is-off') + '">' +
                    '<span class="coe-act__icon"><i class="bi ' + (active ? 'bi-toggle-off' : 'bi-toggle-on') + '" aria-hidden="true"></i></span>' +
                    '<span class="coe-act__label">' + (active ? 'Deactivate' : 'Activate') + '</span></button>' +
                '<button type="button" class="coe-act coe-act--del">' +
                    '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>' +
                    '<span class="coe-act__label">Delete</span></button>' +
                '</div>'
            );
        }

        $table.find('tbody tr').each(function () { paintState($(this)); });

        // Toolbar filters: "status" or any field key → that column.
        var filters = {};
        (cfg.filters || []).forEach(function (flt) {
            filters[P + 'Filter_' + flt.key] = flt.key === 'status' ? COL.status : byKey[flt.key].col;
        });

        var dt = init({
            table: P + 'Table',
            title: cfg.title,
            snoCol: cfg.sno.show ? 0 : false,
            order: [],                                    // keep the server's order
            noSort: cfg.sno.show ? [0, COL.action] : [COL.action],
            exclude: [COL.action],
            filters: filters,
            reset: P + 'ResetFilters',
            download: P + 'DownloadBtn',
            print: P + 'PrintBtn',
            searchToggle: P + 'SearchToggle',
            searchSlot: P + 'DtSearch',
            colvisGrid: P + 'ColumnToggleGrid',
            colvisKey: cfg.colvisKey,
            empty: { title: 'No ' + cfg.entity + ' Records', zeroTitle: 'No ' + cfg.entity + ' Records Found',
                     text: 'Create the first ' + noun + ' to get started.' }
        });

        function redraw($row) { dt.row($row).invalidate('dom').draw(false); }

        confirmStatus({
            table: P + 'Table',
            onConfirm: function ($row, makeActive) {
                $row.attr('data-coe-active', makeActive ? '1' : '0');
                paintState($row);
                redraw($row);
                Swal.fire({ icon: 'success', title: 'Success',
                    text: cfg.entity + ' ' + (makeActive ? 'activated.' : 'deactivated.') });
            }
        });

        confirmDelete({
            table: P + 'Table',
            modal: P + 'DeleteModal',
            confirm: P + 'DeleteConfirm',
            onConfirm: function ($row) {
                dt.row($row).remove().draw(false);
                Swal.fire({ icon: 'success', title: 'Success', text: cfg.entity + ' deleted.' });
            }
        });

        /* Create / Edit */
        var formModal = bootstrap.Modal.getOrCreateInstance(document.querySelector(P + 'FormModal'));
        var $modalBody = $(P + 'FormModal .modal-body');
        var $status = $(P + 'Status');
        var $editing = null;

        function input(f) { return $(P + 'Field_' + f.key); }
        function cell($row, f) { return $row.find('.coe-cell-' + f.key); }
        function cellText($row, f) { return cell($row, f).text().trim(); }

        $status.select2({ width: '100%', dropdownParent: $modalBody });

        /* Select fields. A dependent list (dependsOn) is rebuilt from optionsBy
           whenever its parent changes, and emptied while no parent is chosen. */
        function optionsFor(f) {
            if (!f.dependsOn) { return f.options || []; }
            return (f.optionsBy || {})[input(byKey[f.dependsOn]).val()] || [];
        }

        function fillSelect(f, value) {
            var $sel = input(f).empty().append(new Option(f.placeholder || '', '', false, false));
            optionsFor(f).forEach(function (opt) { $sel.append(new Option(opt, opt)); });
            $sel.val(value && optionsFor(f).indexOf(value) !== -1 ? value : '').trigger('change.select2');
        }

        fields.forEach(function (f) {
            if (f.type !== 'select') { return; }
            input(f).select2({ width: '100%', dropdownParent: $modalBody, placeholder: f.placeholder || '' });
            fillSelect(f, '');
        });
        fields.forEach(function (f) {
            if (!f.dependsOn) { return; }
            input(byKey[f.dependsOn]).on('change', function () { fillSelect(f, ''); });
        });

        function fieldError(f, msg) {
            var $in = input(f), $wrap = $in.parent();
            $wrap.find('.coe-field-error').remove();
            $in.toggleClass('is-invalid', !!msg);
            $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
            if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
            return !msg;
        }

        function clearErrors() { fields.forEach(function (f) { fieldError(f, ''); }); }

        // Stored display value: numbers without leading zeros, text with runs of spaces collapsed.
        function readValue(f) {
            var raw = $.trim(input(f).val() || '');
            if (f.type === 'number') { return raw === '' ? '' : String(parseInt(raw, 10)); }
            return raw.replace(/\s+/g, ' ');
        }

        function openForm($row) {
            $editing = $row;
            var isEdit = !!$row;
            $(P + 'FormModalLabel').text(isEdit ? L.editTitle : L.createTitle);
            $(P + 'Submit').text(isEdit ? L.editSubmit : L.createSubmit);
            // Dialog order, so a parent is set before the list that depends on it.
            formFields.forEach(function (f) {
                var v = isEdit ? cellText($row, f) : '';
                if (f.type === 'select') { fillSelect(f, v); } else { input(f).val(v); }
            });
            $status.val(isEdit ? $row.attr('data-coe-active') : '1').trigger('change.select2');
            clearErrors();
            formModal.show();
        }

        $(P + 'CreateBtn').on('click', function () { openForm(null); });
        $table.on('click', '.coe-act--edit', function () { openForm($(this).closest('tr')); });
        $(P + 'FormModal').on('shown.bs.modal', function () {
            if (formFields[0].type !== 'select') { input(formFields[0]).trigger('focus'); }
        });
        fields.forEach(function (f) {
            input(f).on('input change', function () { if ($.trim(this.value || '') !== '') { fieldError(f, ''); } });
        });

        // Same rules as the backend: required, length / range, unique (case-insensitive).
        // uniqueWith scopes uniqueness: a floor name only has to be unique within its building.
        function validate(f, value, values) {
            var label = f.label.toLowerCase();
            if (value === '') {
                if (!f.required) { return ''; }
                if (f.requiredMsg) { return f.requiredMsg; }
                return (f.type === 'select' ? 'Select the ' : f.type === 'number' ? 'Enter the number of ' : 'Enter the ') + label + '.';
            }
            if (f.type === 'number') {
                var raw = $.trim(input(f).val());
                if (!/^\d+$/.test(raw)) { return 'Enter a whole number.'; }
                var n = parseInt(raw, 10);
                if (f.min != null && n < f.min) { return 'Must be at least ' + f.min + '.'; }
                if (f.max != null && n > f.max) { return 'Must be at most ' + f.max + '.'; }
                return '';
            }
            if (f.type !== 'select' && f.max && value.length > f.max) { return 'Use at most ' + f.max + ' characters.'; }
            if (f.unique) {
                var scope = f.uniqueWith || [];
                var taken = dt.rows().nodes().to$().filter(function () {
                    var $r = $(this);
                    return (!$editing || this !== $editing[0]) &&
                        cellText($r, f).toLowerCase() === value.toLowerCase() &&
                        scope.every(function (k) { return cellText($r, byKey[k]) === values[k]; });
                }).length;
                if (taken) {
                    return 'This ' + label + ' already exists' +
                        (scope.length ? ' in this ' + scope.map(function (k) { return byKey[k].label.toLowerCase().replace(/ name$/, ''); }).join(' / ') : '') + '.';
                }
            }
            return '';
        }

        $(P + 'Form').on('submit', function (e) {
            e.preventDefault();
            var values = {}, firstBad = null;
            fields.forEach(function (f) { values[f.key] = readValue(f); });
            formFields.forEach(function (f) {
                if (!fieldError(f, validate(f, values[f.key], values)) && !firstBad) { firstBad = f; }
            });
            if (firstBad) {
                if (firstBad.type === 'select') { input(firstBad).select2('open'); } else { input(firstBad).trigger('focus'); }
                return;
            }

            var active = $status.val() === '1';
            var $row = $editing;
            if (!$row) {
                var id = 1 + Math.max(0, Math.max.apply(null, dt.rows().nodes().to$().map(function () {
                    return parseInt(this.getAttribute('data-coe-id'), 10) || 0;
                }).get()));
                var blank = [];
                for (var i = 0; i <= COL.action; i++) { blank.push(''); }
                $row = $(dt.row.add(blank).draw(false).node()).attr('data-coe-id', id);
                var $cells = $row.children('td');
                fields.forEach(function (f) {
                    $cells.eq(f.col).addClass('coe-cell-' + f.key + (f.align === 'center' ? ' coe-col-center' : ''));
                });
                $cells.eq(COL.status).addClass('coe-col-center coe-cell-status');
                $cells.eq(COL.action).addClass('coe-cell-actions');
            }

            fields.forEach(function (f) {
                var $c = cell($row, f).text(values[f.key]);
                if (f.type === 'number') { $c.attr('data-order', values[f.key] || 0); }
            });
            $row.attr('data-coe-active', active ? '1' : '0');
            paintState($row);
            redraw($row);

            var wasEdit = !!$editing;
            formModal.hide();
            Swal.fire({ icon: 'success', title: 'Success',
                text: cfg.entity + (wasEdit ? ' updated.' : ' created.') });
        });

        $(P + 'FormModal').on('hidden.bs.modal', function () { $editing = null; clearErrors(); });

        return dt;
    }

    window.CoeGrid = {
        simpleMaster: simpleMaster,
        confirmStatus: confirmStatus, init: init, upload: upload, dropzone: dropzone, freeze: freeze, confirmDelete: confirmDelete };
})(window, jQuery);
