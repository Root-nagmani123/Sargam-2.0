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
 *                                 repeatable }
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
})(window, document, window.jQuery);
