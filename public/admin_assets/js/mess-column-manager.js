/**
 * Mess module — show/hide table columns via a Columns dropdown (checkbox list).
 * Supports DataTables API and plain HTML tables (DOM mode).
 */
(function (window, $) {
    'use strict';

    if (!$) return;

    var STORAGE_PREFIX = 'mess:columns:v2:';

    function safeParse(json, fallback) {
        try {
            return JSON.parse(json);
        } catch (e) {
            return fallback;
        }
    }

    function MessColumnManager(options) {
        this.tableId = options.tableId;
        this.mode = options.mode || 'datatable';
        this.dt = options.dtApi || null;
        this.$table = options.$table || $('#' + this.tableId);
        this.storageKey = options.storageKey || (STORAGE_PREFIX + this.tableId);
        this.lockedColumns = (options.lockedColumns || []).map(Number);
        this.skipColumns = (options.skipColumns || []).map(Number);
        this.colReorderEnabled = !!options.colReorder;
        this.state = null;
        this.baseColumns = [];
    }

    MessColumnManager.prototype._headerText = function (index) {
        if (this.mode === 'datatable' && this.dt) {
            return ($(this.dt.column(index).header()).text() || '').trim();
        }
        var $th = this.$table.find('thead tr').first().children('th,td').eq(index);
        return ($th.text() || '').trim();
    };

    MessColumnManager.prototype._isLocked = function (index) {
        return this.lockedColumns.indexOf(index) !== -1;
    };

    MessColumnManager.prototype._isSkipped = function (index) {
        if (this.skipColumns.indexOf(index) !== -1) return true;
        var h = this._headerText(index).toLowerCase();
        return h === 'actions' || h === 'action';
    };

    MessColumnManager.prototype._scanBaseColumns = function () {
        var self = this;
        var cols = [];
        var count = 0;

        if (this.mode === 'datatable' && this.dt) {
            count = this.dt.columns().count();
        } else {
            count = this.$table.find('thead tr').first().children('th,td').length;
        }

        for (var i = 0; i < count; i++) {
            if (self._isSkipped(i)) continue;
            cols.push({
                index: i,
                label: self._headerText(i) || ('Column ' + (i + 1)),
                locked: self._isLocked(i)
            });
        }
        this.baseColumns = cols;
    };

    MessColumnManager.prototype._defaultState = function () {
        var order = this.baseColumns.map(function (c) { return c.index; });
        var visibility = {};
        this.baseColumns.forEach(function (c) {
            visibility[String(c.index)] = true;
        });
        return {
            version: 2,
            order: order,
            visibility: visibility,
            labels: {},
            aliases: []
        };
    };

    MessColumnManager.prototype.loadState = function () {
        this._scanBaseColumns();
        var raw = null;
        try {
            raw = window.localStorage.getItem(this.storageKey);
        } catch (e) {
            raw = null;
        }
        var saved = raw ? safeParse(raw, null) : null;
        var base = this._defaultState();
        if (!saved || saved.version !== 2) {
            this.state = base;
            return;
        }

        var validIndexes = {};
        this.baseColumns.forEach(function (c) {
            validIndexes[c.index] = true;
        });

        var order = (saved.order || []).filter(function (idx) {
            return validIndexes[idx];
        });
        this.baseColumns.forEach(function (c) {
            if (order.indexOf(c.index) === -1) order.push(c.index);
        });

        var visibility = {};
        this.baseColumns.forEach(function (c) {
            var key = String(c.index);
            visibility[key] = saved.visibility && Object.prototype.hasOwnProperty.call(saved.visibility, key)
                ? !!saved.visibility[key]
                : true;
            if (c.locked) visibility[key] = true;
        });

        this.state = {
            version: 2,
            order: order,
            visibility: visibility,
            labels: {},
            aliases: []
        };
    };

    MessColumnManager.prototype.saveState = function () {
        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(this.state));
        } catch (e) {}
        $(document).trigger('mess:columns:saved', [this.tableId, this.state]);
    };

    MessColumnManager.prototype._visibleCount = function () {
        var self = this;
        var n = 0;
        this.baseColumns.forEach(function (c) {
            if (self.state.visibility[String(c.index)] !== false) n++;
        });
        return n;
    };

    MessColumnManager.prototype._applyVisibilityDataTable = function () {
        var self = this;
        this.baseColumns.forEach(function (c) {
            var show = self.state.visibility[String(c.index)] !== false;
            if (c.locked) show = true;
            self.dt.column(c.index).visible(show, false);
        });
        this.dt.columns.adjust();
    };

    MessColumnManager.prototype._applyVisibilityDom = function () {
        var self = this;
        var $table = this.$table;
        this.baseColumns.forEach(function (c) {
            var show = self.state.visibility[String(c.index)] !== false;
            if (c.locked) show = true;
            $table.find('tr').each(function () {
                $(this).children('th,td').eq(c.index).toggleClass('mess-col-hidden', !show);
            });
        });
        this.skipColumns.forEach(function (idx) {
            $table.find('tr').each(function () {
                $(this).children('th,td').eq(idx).removeClass('mess-col-hidden');
            });
        });
    };

    MessColumnManager.prototype._applyOrderDataTable = function () {
        if (!this.colReorderEnabled || !this.dt || !this.dt.colReorder) return;
        var order = (this.state.order || []).slice();
        try {
            this.dt.colReorder.order(order, true);
        } catch (e) {}
    };

    MessColumnManager.prototype.apply = function () {
        if (this.mode === 'datatable' && this.dt) {
            this._applyOrderDataTable();
            this._applyVisibilityDataTable();
            this.dt.draw(false);
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
        } else {
            this._applyVisibilityDom();
        }
        this._syncFilterFields();
        this._syncDropdownCheckboxes();
    };

    MessColumnManager.prototype._syncFilterFields = function () {
        var self = this;
        this.$table.find('thead [data-mess-filter]').each(function () {
            var $th = $(this);
            var field = $th.data('mess-filter');
            if (!field) return;
            var idx = $th.index();
            var visible = self.state.visibility[String(idx)] !== false;
            var $input = $('[name="' + field + '"], #' + field + ', [data-mess-filter-field="' + field + '"]');
            $input.closest('.col-md-3, .col-md-4, .col-md-6, .col-md-2, .col-12, .mb-3').first().toggleClass('d-none', !visible);
        });
    };

    MessColumnManager.prototype._dropdownMenuEl = function () {
        return document.getElementById('messColManagerMenu-' + this.tableId);
    };

    MessColumnManager.prototype._syncDropdownCheckboxes = function () {
        var self = this;
        var menu = this._dropdownMenuEl();
        if (!menu) return;
        $(menu).find('.mess-col-toggle').each(function () {
            var idx = parseInt($(this).data('col-index'), 10);
            if (isNaN(idx)) return;
            $(this).prop('checked', self.state.visibility[String(idx)] !== false);
        });
    };

    MessColumnManager.prototype.renderDropdownMenu = function () {
        var self = this;
        var menu = this._dropdownMenuEl();
        if (!menu) return;

        menu.innerHTML = '';
        (this.state.order || []).forEach(function (idx) {
            var col = self.baseColumns.filter(function (c) { return c.index === idx; })[0];
            if (!col) return;

            var visible = self.state.visibility[String(col.index)] !== false;
            var li = document.createElement('li');
            li.innerHTML =
                '<div class="dropdown-item px-3 py-1">' +
                    '<div class="form-check d-flex align-items-center mb-0">' +
                        '<input type="checkbox" class="form-check-input me-2 mess-col-toggle" data-col-index="' + col.index + '" ' +
                            (visible ? 'checked' : '') + (col.locked ? ' disabled' : '') + '>' +
                        '<label class="form-check-label cursor-pointer mb-0">' + self._escapeHtml(col.label) + '</label>' +
                    '</div>' +
                '</div>';

            var $li = $(li);
            $li.find('input.mess-col-toggle').on('change', function (e) {
                e.stopPropagation();
                if (col.locked) return;
                var checked = $(this).prop('checked');
                if (!checked && self._visibleCount() <= 1) {
                    $(this).prop('checked', true);
                    if (window.alert) {
                        window.alert('At least one column must remain visible.');
                    }
                    return;
                }
                self.state.visibility[String(col.index)] = checked;
                self.saveState();
                self.apply();
            });
            $li.find('label').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $cb = $(this).closest('.form-check').find('input.mess-col-toggle');
                if ($cb.prop('disabled')) return;
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $li.find('.dropdown-item').on('click', function (e) {
                e.stopPropagation();
            });

            menu.appendChild(li);
        });
    };

    MessColumnManager.prototype.injectColumnsDropdown = function () {
        var dropdownId = 'messColManagerDropdown-' + this.tableId;
        if (document.getElementById(dropdownId)) return;

        var menuId = 'messColManagerMenu-' + this.tableId;
        var tag = 'div';
        var $dropdown = $(
            '<' + tag + ' class="dropdown d-inline-block mess-col-manager-dropdown no-print" data-bs-auto-close="outside">' +
                '<button class="btn btn-outline-secondary btn-sm rounded-1 dropdown-toggle d-inline-flex align-items-center" type="button" ' +
                    'id="' + dropdownId + '" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">' +
                    '<i class="material-icons material-symbols-rounded me-1" style="font-size:18px;line-height:1">view_column</i>' +
                    'Columns' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end py-2 mess-col-manager-menu" id="' + menuId + '" aria-labelledby="' + dropdownId + '"></ul>' +
            '</' + tag + '>'
        );

        if (this.mode === 'datatable' && this.dt) {
            var $wrapper = this.$table.closest('.dataTables_wrapper');
            var $filter = $wrapper.find('.dataTables_filter');
            if ($filter.length) {
                $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');
                $filter.append($dropdown);
                return;
            }
        }

        var toolbarId = 'messColManagerToolbar-' + this.tableId;
        var $host = this.$table.closest('.table-responsive');
        if (!$host.length) $host = this.$table.parent();
        $host.before(
            '<div id="' + toolbarId + '" class="mess-col-manager-toolbar d-flex flex-wrap justify-content-end align-items-center gap-2 mb-2 no-print"></div>'
        );
        $('#' + toolbarId).html($dropdown);
    };

    MessColumnManager.prototype._escapeHtml = function (s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    MessColumnManager.prototype.init = function () {
        this.loadState();
        this.injectColumnsDropdown();
        this.apply();
        this.renderDropdownMenu();

        var self = this;
        if (this.mode === 'datatable' && this.dt) {
            this.dt.on('column-reorder.messColMgr draw.messColMgr', function () {
                self._scanBaseColumns();
                self.renderDropdownMenu();
            });
        }
    };

    window.MessColumnManager = window.MessColumnManager || {
        instances: {},
        init: function (options) {
            var tableId = options.tableId;
            if (!tableId) return null;
            if (this.instances[tableId]) {
                return this.instances[tableId];
            }
            var mgr = new MessColumnManager(options);
            mgr.init();
            this.instances[tableId] = mgr;
            return mgr;
        },
        get: function (tableId) {
            return this.instances[tableId] || null;
        },
        getVisibleIndexes: function (tableId) {
            var mgr = this.get(tableId);
            if (!mgr) return null;
            var indexes = [];
            (mgr.state.order || []).forEach(function (idx) {
                if (mgr.state.visibility[String(idx)] !== false && !mgr._isSkipped(idx)) {
                    indexes.push(idx);
                }
            });
            return indexes;
        }
    };
})(window, window.jQuery);
