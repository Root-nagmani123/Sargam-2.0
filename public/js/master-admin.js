/*
 * Master module — shared listing behaviour (`mst-*`).
 *
 * Loaded per page with @push('scripts') by the master / mapping screens that
 * use public/css/master-admin.css, so each of them ships the same delete
 * confirmation, Columns modal and status-toggle refresh instead of a copy.
 * Page chrome itself (search slot, pager, "Showing N of M") is relocated by
 * public/js/datatable-global-ui.js — nothing here touches it.
 *
 * Public API: window.MstAdmin = { whenTableReady, columnVisibility,
 *                                 reloadPageOnStatusToggle, searchable,
 *                                 repeatable, runFormInit, openFormModal }
 */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    var MstAdmin = window.MstAdmin = window.MstAdmin || {};

    if (MstAdmin.__loaded) {
        return;
    }
    MstAdmin.__loaded = true;

    /* ---------- Delete confirmation ----------
     * Any <form class="mst-del-form" data-name="…"> asks before it submits.
     * The form still posts normally (DELETE via @method), so the controller,
     * route and redirect are exactly what they were. */
    $(document).on('submit', 'form.mst-del-form', function (e) {
        var form = this;
        if (form.dataset.confirmed === '1') {
            return;
        }

        e.preventDefault();
        var name = form.dataset.name ? '"' + form.dataset.name + '"' : 'this record';
        var message = form.dataset.confirm || ('Delete ' + name + '? This cannot be undone.');

        function go() {
            form.dataset.confirmed = '1';
            form.submit();
        }

        if (typeof window.Swal === 'undefined' || typeof window.Swal.fire !== 'function') {
            if (window.confirm(message)) {
                go();
            }
            return;
        }

        window.Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                go();
            }
        });
    });

    /* ---------- Run once a DataTable exists ---------- */
    MstAdmin.whenTableReady = function (selector, callback) {
        var tries = 0;
        (function poll() {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                callback($(selector).DataTable());
                return;
            }
            if (++tries > 80) {
                return;
            }
            setTimeout(poll, 50);
        })();
    };

    /* ---------- Columns modal ----------
     * opts.table      '#table-id'
     * opts.grid       '#xColumnToggleGrid' (give it class .mst-colvis-grid)
     * opts.storageKey per-user localStorage key
     * opts.onChange   optional callback(dt) after a column is shown/hidden
     *
     * Stores header LABELS, not indices, so adding a column later does not
     * shift what a user had hidden (docs/column-visibility.md). */
    MstAdmin.columnVisibility = function (opts) {
        function readHidden() {
            try {
                var raw = window.localStorage.getItem(opts.storageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function saveHidden(hidden) {
            try {
                window.localStorage.setItem(opts.storageKey, JSON.stringify(hidden));
            } catch (e) { /* storage unavailable — the choice just won't persist */ }
        }

        MstAdmin.whenTableReady(opts.table, function (dt) {
            var $grid = $(opts.grid);
            if (!$grid.length) {
                return;
            }
            $grid.empty();

            var hidden = readHidden();
            var gridId = ($grid.attr('id') || 'mstColvis');

            dt.columns().every(function () {
                var idx = this.index();
                var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!label) {
                    return;
                }

                var visible = hidden.indexOf(label) === -1;
                this.visible(visible, false);

                var inputId = gridId + '_' + idx;
                var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr({ id: inputId, 'data-col': idx })
                    .prop('checked', visible);

                $checkbox.on('change', function () {
                    // Never let the user hide the last visible column.
                    if (!this.checked && dt.columns(':visible').count() <= 1) {
                        this.checked = true;
                        return;
                    }

                    var current = readHidden();
                    var pos = current.indexOf(label);
                    if (this.checked) {
                        if (pos !== -1) {
                            current.splice(pos, 1);
                        }
                    } else if (pos === -1) {
                        current.push(label);
                    }
                    saveHidden(current);

                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    if (typeof opts.onChange === 'function') {
                        opts.onChange(dt);
                    }
                });

                $grid.append(
                    $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                        $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                            .attr({ 'for': inputId, title: label })
                            .append($checkbox)
                            .append($('<span></span>').text(label))
                    )
                );
            });

            dt.columns.adjust();
            if (typeof opts.onChange === 'function') {
                opts.onChange(dt);
            }
        });
    };

    /* ---------- Status switch on a server-rendered grid ----------
     * custom.js owns the .status-toggle confirm + AJAX and then reloads
     * `.dataTable` via ajax.reload(), which does nothing for a Blade-rendered
     * table. The badge and the switch live in different columns, so reload
     * the page instead of hand-mirroring them (§3b). */
    MstAdmin.reloadPageOnStatusToggle = function () {
        $(document).ajaxSuccess(function (e, xhr, settings) {
            var url = (settings && settings.url) || '';
            var toggleUrl = (window.routes && window.routes.toggleStatus) || '';
            if (url.indexOf('toggle-status') === -1
                && url.indexOf('toggleStatus') === -1
                && (toggleUrl === '' || url.indexOf(toggleUrl) === -1)) {
                return;
            }
            setTimeout(function () {
                window.location.reload();
            }, 600);
        });
    };

    /* ---------- Searchable dropdowns ----------
     * Every <select> a user picks from gets a search box (new-design §2).
     * Mark it `select.mst-searchable` (optional data-placeholder) rather than
     * `.select2` / `data-searchable`: custom.js later runs an unscoped
     * `$(".select2").select2()` that also matches Select2's own container span
     * and paints a second, empty widget. This handler is registered after
     * custom.js's, so it runs last and inits each select exactly once.
     *
     * Select2 signals a pick with a jQuery `change` — bind with $(el).on(),
     * or bridge it, never addEventListener alone. After a JS refill call
     * MstAdmin.searchable(el) again; after setting a value call
     * $(el).trigger('change.select2'). */
    MstAdmin.searchable = function (el) {
        var $els = $(el);
        if (!$.fn.select2) {
            return $els;
        }
        $els.each(function () {
            var $select = $(this);
            // Inside a modal the panel must live in the modal (or its search box
            // can't take focus); anywhere else attach it to <body>, so a
            // `.card.overflow-hidden` around a short grid can't clip it.
            var $modalBody = $select.closest('.modal-body');
            var options = {
                width: '100%',
                placeholder: $select.data('placeholder') || 'Search and select…',
                allowClear: false,
                dropdownParent: $modalBody.length ? $modalBody : $(document.body)
            };
            if (window.DropdownSearch && typeof window.DropdownSearch.init === 'function') {
                window.DropdownSearch.init($select, options);
            } else {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2(options);
            }
        });
        return $els;
    };

    $(function () {
        MstAdmin.searchable('select.mst-searchable');
    });

    // Escape on an open dropdown inside a modal should close the dropdown, not
    // the whole modal (Bootstrap's dismiss listener sits on the modal element,
    // which the key reaches before Select2 has finished closing). Capture it
    // first, close the open widget ourselves, and stop it there.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !$.fn.select2) {
            return;
        }
        var $open = $('.modal.show .select2-container--open').prev('select.select2-hidden-accessible');
        if (!$open.length) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        $open.select2('close');
        $open.next('.select2-container').find('.select2-selection').trigger('focus');
    }, true);

    /* ---------- Repeatable field cards (Add many at once) ----------
     * opts.container  '#fields'      wrapper of the cards
     * opts.item       '.mst-repeat'  one card
     * opts.add        '.mst-field-btn--add'
     * opts.remove     '.mst-field-btn--remove'
     *
     * The whole state is re-derived from the DOM after every change (§3c):
     * "−" once there is more than one card, "+" on the last card only. The
     * first card is the template; its values are blanked on the clone. */
    MstAdmin.repeatable = function (opts) {
        var $container = $(opts.container);
        if (!$container.length) {
            return;
        }
        var repeatSeq = 0;
        var itemSel = opts.item || '.mst-repeat';
        var addSel = opts.add || '.mst-field-btn--add';
        var removeSel = opts.remove || '.mst-field-btn--remove';

        function sync() {
            var $items = $container.find(itemSel);
            var last = $items.length - 1;
            $items.each(function (index) {
                $(this).find(removeSel).toggle($items.length > 1);
                $(this).find(addSel).toggle(index === last);
            });
        }

        $container.on('click', addSel, function () {
            var $clone = $container.find(itemSel).first().clone();
            $clone.find('input:not([type=hidden]), textarea').val('');
            $clone.find('select').prop('selectedIndex', 0);
            $clone.find('.is-invalid').removeClass('is-invalid');
            $clone.find('.mst-field-error, .invalid-feedback').remove();
            // A cloned Select2 carries the template's widget markup — drop it
            // and build a fresh one on the copy.
            $clone.find('.select2-container').remove();
            $clone.find('select.select2-hidden-accessible')
                .removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id aria-hidden tabindex');
            $clone.find('option').removeAttr('data-select2-id');
            // Ids must stay unique, and each <label for> must follow its field.
            var suffix = '_r' + (++repeatSeq);
            $clone.find('[id]').each(function () {
                var oldId = this.id;
                var newId = oldId.replace(/_r\d+$/, '') + suffix;
                $clone.find('label[for="' + oldId + '"]').attr('for', newId);
                this.id = newId;
            });
            $container.append($clone);
            MstAdmin.searchable($clone.find('select.mst-searchable'));
            sync();
            $clone.find('input, textarea, select').filter(':visible').first().trigger('focus');
        });

        $container.on('click', removeSel, function () {
            if ($container.find(itemSel).length > 1) {
                $(this).closest(itemSel).remove();
                sync();
            }
        });

        sync();
    };

    /* ---------- Form init (full page AND modal) ----------
     * A create/edit view wraps its <form> in
     *   <div data-mst-form-root data-mst-form-title="Add …" data-mst-modal-size="lg">
     * and keeps any form-specific JS inside it as
     *   <script type="text/x-mst-form-init"> … </script>
     * — a function body run with (root, $). It must bind only to elements
     * inside `root` (never $(document)), because the modal runs it again on
     * every open. The type keeps the browser from executing it on its own; this
     * runner executes it once per render, on the full page and in the modal. */
    MstAdmin.runFormInit = function (root) {
        if (!root || root.__mstInit) {
            return;
        }
        root.__mstInit = true;
        MstAdmin.searchable($(root).find('select.mst-searchable'));
        $(root).find('script[type="text/x-mst-form-init"]').each(function () {
            try {
                (new Function('root', '$', 'jQuery', this.textContent))(root, $, $);
            } catch (err) {
                if (window.console) { window.console.error('mst form init failed', err); }
            }
        });
    };

    $(function () {
        $('[data-mst-form-root]').each(function () { MstAdmin.runFormInit(this); });

        // Message carried over a reload after a modal save (see openFormModal).
        var flash = null;
        try {
            flash = window.sessionStorage.getItem('mstFlash');
            window.sessionStorage.removeItem('mstFlash');
        } catch (e) { /* storage unavailable */ }
        if (flash && window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({ icon: 'success', title: 'Success', text: flash });
        }
    });

    /* ---------- Add / Edit in a modal ----------
     * Any link with `data-mst-modal-form` opens its href's form in a shared
     * modal instead of navigating. The href stays the real create/edit page, so
     * Ctrl/middle-click, no-JS and a failed load all fall back to the page.
     *
     * The form posts to its own route exactly as the page does. Laravel then
     * redirects (validation failure: back with errors + old input; success:
     * index with a flash), and the modal re-reads the form URL once to see
     * which. Field errors or an error flash re-render in the modal with the
     * user's input; otherwise it was a save, so the page reloads and the
     * success message is shown. No controller needs to know about the modal. */
    var formModal = { el: null, url: null, busy: false };

    function formModalEl() {
        if (formModal.el) {
            return formModal.el;
        }
        var html = ''
            + '<div class="modal fade mst-modal mst-form-modal" id="mstFormModal" tabindex="-1"'
            + ' aria-labelledby="mstFormModalLabel" aria-hidden="true" data-bs-backdrop="static">'
            + '<div class="modal-dialog modal-lg modal-dialog-centered">'
            + '<div class="modal-content border-0 shadow">'
            + '<div class="modal-header border-bottom">'
            + '<h5 class="modal-title fw-bold" id="mstFormModalLabel"></h5>'
            + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            + '</div>'
            + '<div class="modal-body"></div>'
            + '</div></div></div>';
        formModal.el = $(html).appendTo(document.body)[0];

        var body = formModal.el.querySelector('.modal-body');

        // Cancel inside the form closes the modal instead of leaving the page.
        $(body).on('click', '.mst-btn-cancel', function (e) {
            e.preventDefault();
            window.bootstrap.Modal.getOrCreateInstance(formModal.el).hide();
        });

        // Delegated on the body so form-level handlers (client validation)
        // run first and can still veto the submit.
        body.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || !form || form.tagName !== 'FORM') {
                return;
            }
            e.preventDefault();
            submitModalForm(form, e.submitter);
        });

        formModal.el.addEventListener('shown.bs.modal', function () {
            focusFirstField();
        });
        formModal.el.addEventListener('hidden.bs.modal', function () {
            // Drop the form so its ids can't collide with the next one.
            body.innerHTML = '';
            formModal.url = null;
            formModal.busy = false;
        });
        return formModal.el;
    }

    function focusFirstField() {
        var el = formModal.el && formModal.el.querySelector(
            '.modal-body input:not([type=hidden]):not([disabled]):not([readonly]), '
            + '.modal-body select:not([disabled]), .modal-body textarea:not([disabled])'
        );
        if (!el) {
            return;
        }
        if ($(el).hasClass('select2-hidden-accessible')) {
            $(el).next('.select2-container').find('.select2-selection').trigger('focus');
        } else {
            el.focus();
        }
    }

    function focusFirstError() {
        var $bad = $(formModal.el).find('.modal-body .is-invalid').first();
        if (!$bad.length) {
            focusFirstField();
            return;
        }
        if ($bad.hasClass('select2-hidden-accessible')) {
            $bad.next('.select2-container').find('.select2-selection').trigger('focus');
        } else {
            $bad.trigger('focus');
        }
    }

    function modalAlert(message) {
        var body = formModal.el.querySelector('.modal-body');
        $(body).find('.mst-form-modal-alert').remove();
        $('<div class="alert alert-danger rounded-1 mst-form-modal-alert" role="alert"></div>')
            .text(message)
            .prependTo(body);
    }

    function setModalSize(size) {
        var dialog = formModal.el.querySelector('.modal-dialog');
        dialog.classList.remove('modal-lg', 'modal-xl');
        dialog.classList.add(size === 'xl' ? 'modal-xl' : 'modal-lg');
    }

    // Text of the flash alerts <x-session_message /> rendered on a page.
    function flashText(doc, selector) {
        return $(doc).find(selector).map(function () {
            var clone = this.cloneNode(true);
            $(clone).find('button, strong').remove();
            return $.trim($(clone).text());
        }).get().filter(Boolean);
    }

    // Errors the server rendered into a form. Client-side message slots that
    // ship hidden (.d-none) until JS needs them are not server errors.
    function serverErrorCount(root) {
        return $(root).find('.is-invalid, .mst-field-error').filter(function () {
            return !$(this).closest('.d-none').length;
        }).length;
    }

    // Put a fetched page's form into the modal. Returns false when the page
    // has no form root (so the caller can fall back to navigating there).
    function renderForm(doc) {
        var src = doc.querySelector('[data-mst-form-root]');
        if (!src) {
            return false;
        }
        var body = formModal.el.querySelector('.modal-body');
        var root = document.importNode(src, true);
        body.innerHTML = '';
        body.appendChild(root);

        $('#mstFormModalLabel').text(src.getAttribute('data-mst-form-title') || $('#mstFormModalLabel').text());
        setModalSize(src.getAttribute('data-mst-modal-size'));

        // An `error` flash or a business-rule message the page would have shown
        // above the form. Plain validation errors already sit under their fields.
        var hasFieldErrors = serverErrorCount(root) > 0;
        var errors = flashText(doc, '.alert-danger');
        if (errors.length && !hasFieldErrors) {
            modalAlert(errors.join(' '));
        }

        MstAdmin.runFormInit(root);
        return true;
    }

    function fetchPage(url) {
        return window.fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return res.text();
        }).then(function (html) {
            return new window.DOMParser().parseFromString(html, 'text/html');
        });
    }

    MstAdmin.openFormModal = function (url, title) {
        if (!window.fetch || !window.bootstrap || !window.DOMParser) {
            window.location.href = url;
            return;
        }
        var el = formModalEl();
        var body = el.querySelector('.modal-body');
        formModal.url = url;
        $('#mstFormModalLabel').text(title || '');
        body.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5" role="status">'
            + '<span class="spinner-border text-primary" aria-hidden="true"></span>'
            + '<span class="visually-hidden">Loading…</span></div>';
        window.bootstrap.Modal.getOrCreateInstance(el).show();

        fetchPage(url).then(function (doc) {
            if (formModal.url !== url) {
                return; // closed or replaced meanwhile
            }
            if (!renderForm(doc)) {
                window.location.href = url;
                return;
            }
            if (el.classList.contains('show')) {
                focusFirstField();
            }
        }).catch(function () {
            window.location.href = url;
        });
    };

    function setBusy(form, busy) {
        formModal.busy = busy;
        $(form).find('button[type=submit], input[type=submit]').each(function () {
            var $btn = $(this);
            if (busy) {
                $btn.data('mstLabel', $btn.html()).prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Saving…');
            } else {
                $btn.prop('disabled', false);
                if ($btn.data('mstLabel')) { $btn.html($btn.data('mstLabel')); }
            }
        });
    }

    function submitModalForm(form, submitter) {
        if (formModal.busy) {
            return;
        }
        var url = formModal.url;
        var data = new window.FormData(form);
        if (submitter && submitter.name) {
            data.append(submitter.name, submitter.value);
        }
        setBusy(form, true);

        // No X-Requested-With: the controllers must answer exactly as they do
        // for the page (redirects), not with their AJAX / JSON variants.
        window.fetch(form.action, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            redirect: 'manual',
            headers: { 'Accept': 'text/html,application/xhtml+xml' }
        }).then(function (res) {
            if (res.type === 'opaqueredirect' || (res.status >= 300 && res.status < 400)) {
                // Saved, or bounced back with errors: the form URL tells which.
                return fetchPage(url).then(function (doc) {
                    var root = doc.querySelector('[data-mst-form-root]');
                    var failed = (root && serverErrorCount(root) > 0)
                        || flashText(doc, '.alert-danger').length > 0;
                    if (failed) {
                        renderForm(doc);
                        focusFirstError();
                        return;
                    }
                    var done = flashText(doc, '.alert-success');
                    try {
                        window.sessionStorage.setItem('mstFlash', done[0] || 'Saved successfully.');
                    } catch (e) { /* storage unavailable */ }
                    window.location.reload();
                }, function () {
                    // The save went through but the form could not be re-read;
                    // the reload shows whatever the server flashed.
                    window.location.reload();
                });
            }
            if (res.status === 419) {
                setBusy(form, false);
                modalAlert('Your session has expired. Please reload the page and try again.');
                return;
            }
            if (res.ok) {
                // A controller that answers the POST with a page of its own.
                return res.text().then(function (html) {
                    var doc = new window.DOMParser().parseFromString(html, 'text/html');
                    if (!renderForm(doc)) {
                        window.location.reload();
                    }
                });
            }
            setBusy(form, false);
            modalAlert(res.status === 403
                ? 'You are not authorised to perform this action.'
                : 'Something went wrong (error ' + res.status + '). Please try again.');
        }).catch(function () {
            setBusy(form, false);
            modalAlert('Could not reach the server. Please check your connection and try again.');
        });
    }

    $(document).on('click', 'a[data-mst-modal-form]', function (e) {
        // Let the browser handle new-tab / new-window clicks.
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button === 1) {
            return;
        }
        e.preventDefault();
        MstAdmin.openFormModal(this.href, $(this).attr('data-mst-modal-title') || $.trim($(this).text()));
    });
})(window, document, window.jQuery);
