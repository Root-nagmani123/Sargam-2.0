/**
 * Mess module — dynamic column management (show/hide, reorder, rename, add alias columns).
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

    function slugify(s) {
        return String(s || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'col';
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
        this.sortableInstance = null;
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
            this.dt.columns().every(function (i) {
                count = Math.max(count, i + 1);
            });
            count = this.dt.columns().count();
        } else {
            count = this.$table.find('thead tr').first().children('th,td').length;
        }

        for (var i = 0; i < count; i++) {
            if (self._isSkipped(i)) continue;
            cols.push({
                index: i,
                key: 'col-' + i,
                label: self._headerText(i) || ('Column ' + (i + 1)),
                locked: self._isLocked(i)
            });
        }
        this.baseColumns = cols;
    };

    MessColumnManager.prototype._defaultState = function () {
        var order = this.baseColumns.map(function (c) { return c.index; });
        var visibility = {};
        var labels = {};
        this.baseColumns.forEach(function (c) {
            visibility[String(c.index)] = true;
            labels[String(c.index)] = c.label;
        });
        return {
            version: 2,
            order: order,
            visibility: visibility,
            labels: labels,
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

        var labels = {};
        this.baseColumns.forEach(function (c) {
            var key = String(c.index);
            labels[key] = (saved.labels && saved.labels[key]) || c.label;
        });

        var aliases = Array.isArray(saved.aliases) ? saved.aliases : [];
        aliases = aliases.filter(function (a) {
            return a && validIndexes[a.sourceIndex] && a.label;
        });

        this.state = {
            version: 2,
            order: order,
            visibility: visibility,
            labels: labels,
            aliases: aliases
        };
    };

    MessColumnManager.prototype.saveState = function () {
        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(this.state));
        } catch (e) {}
        $(document).trigger('mess:columns:saved', [this.tableId, this.state]);
    };

    MessColumnManager.prototype.resetState = function () {
        this.state = this._defaultState();
        this.saveState();
        this.apply();
        this.renderPanel();
    };

    MessColumnManager.prototype.getVisibleColumnIndexes = function () {
        var self = this;
        var visible = [];
        (this.state.order || []).forEach(function (idx) {
            if (self.state.visibility[String(idx)] !== false) {
                visible.push(idx);
            }
        });
        (this.state.aliases || []).forEach(function (a) {
            if (a.visible !== false) {
                visible.push('alias:' + a.id);
            }
        });
        return visible;
    };

    MessColumnManager.prototype._applyLabels = function () {
        var self = this;
        this.baseColumns.forEach(function (c) {
            var label = self.state.labels[String(c.index)] || c.label;
            if (self.mode === 'datatable' && self.dt) {
                var $h = $(self.dt.column(c.index).header());
                if ($h.length) {
                    $h.attr('data-mess-col-original', $h.attr('data-mess-col-original') || c.label);
                    if ($h.children().length === 0) {
                        $h.text(label);
                    } else {
                        $h.find('.mess-col-label-text').remove();
                        $h.prepend($('<span class="mess-col-label-text">').text(label));
                    }
                }
            } else {
                var $th = self.$table.find('thead tr').first().children('th,td').eq(c.index);
                if ($th.length) {
                    $th.attr('data-mess-col-original', $th.attr('data-mess-col-original') || c.label);
                    var $keep = $th.children().not('.mess-col-label-text');
                    $th.empty().append($keep).append(
                        $('<span class="mess-col-label-text">').text(label)
                    );
                }
            }
        });
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
        if (!this.colReorderEnabled || !this.dt.colReorder) return;
        var order = (this.state.order || []).slice();
        try {
            this.dt.colReorder.order(order, true);
        } catch (e) {}
    };

    MessColumnManager.prototype._applyOrderDom = function () {
        var order = this.state.order || [];
        var skipSet = {};
        var self = this;
        this.skipColumns.forEach(function (i) {
            skipSet[i] = true;
        });

        this.$table.find('thead tr, tbody tr').each(function () {
            var $row = $(this);
            var $cells = $row.children('th,td');
            if (!$cells.length || $cells.is('[colspan]')) return;

            var nodes = [];
            order.forEach(function (idx) {
                var node = $cells.get(idx);
                if (node) nodes.push(node);
            });
            $cells.each(function (i) {
                if (skipSet[i]) nodes.push(this);
            });

            $row.append($(nodes));
        });
    };

    MessColumnManager.prototype.apply = function () {
        if (this.mode === 'datatable' && this.dt) {
            this._applyOrderDataTable();
            this._applyVisibilityDataTable();
            this._applyLabels();
            this.dt.draw(false);
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
        } else {
            this._applyOrderDom();
            this._applyVisibilityDom();
            this._applyLabels();
        }
        this._syncFilterFields();
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

    MessColumnManager.prototype._panelListEl = function () {
        return document.getElementById('messColManagerList-' + this.tableId);
    };

    MessColumnManager.prototype.renderPanel = function () {
        var self = this;
        var list = this._panelListEl();
        if (!list) return;

        list.innerHTML = '';
        var order = this.state.order || [];

        order.forEach(function (idx) {
            var col = self.baseColumns.filter(function (c) { return c.index === idx; })[0];
            if (!col) return;
            list.appendChild(self._buildRow(col));
        });

        var aliasWrap = document.getElementById('messColManagerAliases-' + this.tableId);
        if (aliasWrap) {
            aliasWrap.innerHTML = '';
            (self.state.aliases || []).forEach(function (alias) {
                aliasWrap.appendChild(self._buildAliasRow(alias));
            });
        }
    };

    MessColumnManager.prototype._buildRow = function (col) {
        var self = this;
        var li = document.createElement('li');
        li.className = 'list-group-item mess-col-manager-row d-flex align-items-center gap-2 py-2';
        li.setAttribute('data-col-index', String(col.index));

        var visible = this.state.visibility[String(col.index)] !== false;

        li.innerHTML =
            '<span class="mess-col-drag text-muted cursor-grab" title="Drag to reorder" aria-hidden="true">' +
                '<i class="material-symbols-rounded" style="font-size:1.25rem">drag_indicator</i>' +
            '</span>' +
            '<div class="flex-grow-1 min-w-0">' +
                '<input type="text" class="form-control form-control-sm mess-col-label-input" value="' +
                    self._escapeAttr(self.state.labels[String(col.index)] || col.label) + '" ' +
                    (col.locked ? 'readonly' : '') + '>' +
            '</div>' +
            '<div class="form-check form-switch mb-0 flex-shrink-0">' +
                '<input class="form-check-input mess-col-visible-toggle" type="checkbox" role="switch" ' +
                    (visible ? 'checked' : '') + (col.locked ? ' disabled' : '') + ' title="Show column">' +
            '</div>' +
            '<div class="dropdown flex-shrink-0">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Replace data source">Replace</button>' +
                '<ul class="dropdown-menu dropdown-menu-end mess-col-replace-menu"></ul>' +
            '</div>';

        var $li = $(li);
        $li.find('.mess-col-visible-toggle').on('change', function () {
            if (col.locked) return;
            self.state.visibility[String(col.index)] = $(this).prop('checked');
            self.apply();
        });
        $li.find('.mess-col-label-input').on('change blur', function () {
            self.state.labels[String(col.index)] = $(this).val().trim() || col.label;
            self.apply();
        });

        var $menu = $li.find('.mess-col-replace-menu');
        self.baseColumns.forEach(function (src) {
            if (src.index === col.index) return;
            $('<li><button type="button" class="dropdown-item">' + self._escapeHtml(src.label) + '</button></li>')
                .find('button').on('click', function () {
                    self._replaceColumnData(col.index, src.index);
                }).end()
                .appendTo($menu);
        });

        return li;
    };

    MessColumnManager.prototype._buildAliasRow = function (alias) {
        var self = this;
        var li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-2 py-2 bg-light';
        li.innerHTML =
            '<span class="badge text-bg-info">Alias</span>' +
            '<span class="small flex-grow-1 text-truncate">' + self._escapeHtml(alias.label) + '</span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger mess-col-alias-remove" title="Remove">×</button>';

        $(li).find('.mess-col-alias-remove').on('click', function () {
            self.state.aliases = (self.state.aliases || []).filter(function (a) {
                return a.id !== alias.id;
            });
            self.saveState();
            self.renderPanel();
        });
        return li;
    };

    MessColumnManager.prototype._replaceColumnData = function (targetIndex, sourceIndex) {
        if (this.mode === 'datatable' && this.dt && this.colReorderEnabled && this.dt.colReorder) {
            var order = this.dt.colReorder.order();
            var posTarget = order.indexOf(targetIndex);
            var posSource = order.indexOf(sourceIndex);
            if (posTarget !== -1 && posSource !== -1) {
                order[posTarget] = sourceIndex;
                order[posSource] = targetIndex;
                this.state.order = order;
                this.dt.colReorder.order(order, true);
            }
        } else {
            var tLabel = this.state.labels[String(targetIndex)];
            var sLabel = this.state.labels[String(sourceIndex)];
            this.state.labels[String(targetIndex)] = sLabel;
            this.state.labels[String(sourceIndex)] = tLabel;
        }
        this.saveState();
        this.apply();
        this.renderPanel();
    };

    MessColumnManager.prototype.addAliasColumn = function (label, sourceIndex) {
        label = String(label || '').trim();
        sourceIndex = parseInt(sourceIndex, 10);
        if (!label || isNaN(sourceIndex)) return false;

        var id = 'alias-' + Date.now();
        this.state.aliases = this.state.aliases || [];
        this.state.aliases.push({
            id: id,
            label: label,
            sourceIndex: sourceIndex,
            visible: true
        });
        this.saveState();
        this.renderPanel();
        return true;
    };

    MessColumnManager.prototype._initSortable = function () {
        var self = this;
        var list = this._panelListEl();
        if (!list || typeof window.Sortable === 'undefined') return;

        if (this.sortableInstance) {
            try { this.sortableInstance.destroy(); } catch (e) {}
        }

        this.sortableInstance = window.Sortable.create(list, {
            handle: '.mess-col-drag',
            animation: 150,
            onEnd: function () {
                var newOrder = [];
                $(list).children('[data-col-index]').each(function () {
                    newOrder.push(parseInt($(this).attr('data-col-index'), 10));
                });
                self.state.order = newOrder;
                self.saveState();
                self.apply();
            }
        });
    };

    MessColumnManager.prototype._bindPanelActions = function () {
        var self = this;

        $('#messColManagerSave-' + this.tableId).off('click.messColMgr').on('click.messColMgr', function () {
            self.saveState();
            self.apply();
            var offcanvas = document.getElementById('messColManagerOffcanvas-' + self.tableId);
            if (offcanvas && window.bootstrap) {
                var inst = bootstrap.Offcanvas.getInstance(offcanvas);
                if (inst) inst.hide();
            }
        });

        $('#messColManagerReset-' + this.tableId).off('click.messColMgr').on('click.messColMgr', function () {
            if (!window.confirm('Reset column layout to defaults?')) return;
            self.resetState();
        });

        $('#messColManagerAdd-' + this.tableId).off('click.messColMgr').on('click.messColMgr', function () {
            var label = $('#messColManagerAddLabel-' + self.tableId).val();
            var src = $('#messColManagerAddSource-' + self.tableId).val();
            if (self.addAliasColumn(label, src)) {
                $('#messColManagerAddLabel-' + self.tableId).val('');
            }
        });

        $('#messColManagerMoveUp-' + this.tableId).off('click.messColMgr').on('click.messColMgr', function () {
            self._moveSelectedRow(-1);
        });
        $('#messColManagerMoveDown-' + this.tableId).off('click.messColMgr').on('click.messColMgr', function () {
            self._moveSelectedRow(1);
        });
    };

    MessColumnManager.prototype._moveSelectedRow = function (dir) {
        var list = this._panelListEl();
        if (!list) return;
        var $selected = $(list).children('.mess-col-manager-row.active');
        if (!$selected.length) return;
        var $sibling = dir < 0 ? $selected.prev() : $selected.next();
        if (!$sibling.length) return;
        if (dir < 0) $selected.insertBefore($sibling);
        else $selected.insertAfter($sibling);

        var newOrder = [];
        $(list).children('[data-col-index]').each(function () {
            newOrder.push(parseInt($(this).attr('data-col-index'), 10));
        });
        this.state.order = newOrder;
        this.saveState();
        this.apply();
    };

    MessColumnManager.prototype._bindRowSelection = function () {
        var self = this;
        var list = this._panelListEl();
        if (!list) return;
        $(list).off('click.messColMgr', '.mess-col-manager-row').on('click.messColMgr', '.mess-col-manager-row', function (e) {
            if ($(e.target).closest('input,button,select,label,.dropdown-menu').length) return;
            $(list).children('.mess-col-manager-row').removeClass('active');
            $(this).addClass('active');
        });
    };

    MessColumnManager.prototype.injectToolbar = function () {
        var $table = this.$table;
        if (!$table.length) return;

        var toolbarId = 'messColManagerToolbar-' + this.tableId;
        if (document.getElementById(toolbarId)) return;

        var $host = $table.closest('.table-responsive');
        if (!$host.length) $host = $table.parent();

        var html =
            '<div id="' + toolbarId + '" class="mess-col-manager-toolbar d-flex flex-wrap justify-content-end align-items-center gap-2 mb-2 no-print" data-table-id="' + this.tableId + '">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" ' +
                    'data-bs-toggle="offcanvas" data-bs-target="#messColManagerOffcanvas-' + this.tableId + '" aria-controls="messColManagerOffcanvas-' + this.tableId + '">' +
                    '<i class="material-symbols-rounded" style="font-size:1.1rem">view_column</i>' +
                    '<span>Manage Columns</span>' +
                '</button>' +
            '</div>';

        $host.before(html);
    };

    MessColumnManager.prototype._escapeHtml = function (s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    MessColumnManager.prototype._escapeAttr = function (s) {
        return this._escapeHtml(s).replace(/'/g, '&#39;');
    };

    MessColumnManager.prototype.init = function () {
        this.loadState();
        this.injectToolbar();
        this.apply();
        this.renderPanel();
        this._initSortable();
        this._bindPanelActions();
        this._bindRowSelection();
        this._populateAddSourceSelect();

        var self = this;
        if (this.mode === 'datatable' && this.dt) {
            this.dt.on('draw.messColMgr', function () {
                self._applyLabels();
            });
        }
    };

    MessColumnManager.prototype._populateAddSourceSelect = function () {
        var $sel = $('#messColManagerAddSource-' + this.tableId);
        if (!$sel.length) return;
        $sel.empty();
        this.baseColumns.forEach(function (c) {
            $sel.append($('<option>', { value: c.index, text: c.label }));
        });
    };

  // —— Public registry ——
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
