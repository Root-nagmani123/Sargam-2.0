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
        var original = $th.attr('data-mess-col-original');
        if (original) {
            return String(original).trim();
        }
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

        var mountEl = document.getElementById('messColManagerMount-' + this.tableId);
        if (mountEl) {
            mountEl.innerHTML = '';
            mountEl.appendChild($dropdown[0]);
            return;
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

        /** Normalize a DataTables row (array or numeric-key object) to a cell array. */
        dataTableRowToCells: function (row) {
            if (row == null) {
                return [];
            }
            if (Array.isArray(row)) {
                return row;
            }
            if (typeof row === 'object') {
                if (typeof row.length === 'number' && row.length > 0) {
                    try {
                        return Array.from(row);
                    } catch (e) {}
                }
                var keys = Object.keys(row)
                    .filter(function (k) { return /^\d+$/.test(String(k)); })
                    .sort(function (a, b) { return Number(a) - Number(b); });
                if (keys.length) {
                    return keys.map(function (k) { return row[k]; });
                }
            }
            return [];
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
        },

        /** Visible column indexes, or all non-skipped columns if manager not initialized. */
        resolveExportIndexes: function (tableId) {
            var visible = this.getVisibleIndexes(tableId);
            if (visible && visible.length) {
                return visible;
            }
            var table = document.getElementById(tableId);
            if (!table) return [];
            var mgr = this.get(tableId);
            var skipSet = {};
            if (mgr) {
                mgr.skipColumns.forEach(function (i) { skipSet[i] = true; });
            }
            var headers = table.querySelectorAll('thead tr').length
                ? table.querySelector('thead tr').querySelectorAll('th, td')
                : [];
            var indexes = [];
            for (var i = 0; i < headers.length; i++) {
                if (skipSet[i]) continue;
                var h = (headers[i].textContent || '').trim().toLowerCase();
                if (h === 'actions' || h === 'action') continue;
                indexes.push(i);
            }
            return indexes;
        },

        appendVisibleColumnsToUrl: function (url, tableId) {
            var indexes = this.resolveExportIndexes(tableId);
            if (!indexes.length) return url;
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            return url + sep + 'visible_columns=' + encodeURIComponent(indexes.join(','));
        },

        _stripHtml: function (html) {
            if (typeof window.messDataTableStripHtmlForSearch === 'function') {
                return window.messDataTableStripHtmlForSearch(html);
            }
            var d = document.createElement('div');
            d.innerHTML = String(html || '');
            return (d.textContent || d.innerText || '').trim();
        },

        extractTableData: function (tableId, options) {
            options = options || {};
            var table = document.getElementById(tableId);
            if (!table) return null;

            var mgr = this.get(tableId);
            var visibleIndexes = this.resolveExportIndexes(tableId);
            if (!visibleIndexes.length) return null;

            var headerCells = [];
            var theadRow = table.querySelector('thead tr');
            if (theadRow) {
                headerCells = Array.from(theadRow.querySelectorAll('th, td'));
            }

            var headers = visibleIndexes.map(function (i) {
                var th = headerCells[i];
                var label = '';
                if (th) {
                    label = (th.getAttribute('data-mess-col-original') || th.textContent || '').trim();
                }
                if (mgr && mgr.state && mgr.state.labels && mgr.state.labels[String(i)]) {
                    label = mgr.state.labels[String(i)];
                }
                return label;
            });

            var rowsData = [];
            if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
                var dt = $('#' + tableId).DataTable();
                var scope = options.allPages === false ? 'current' : { search: 'applied' };
                rowsData = dt.rows(scope).data().toArray();
            } else {
                rowsData = Array.from(table.querySelectorAll('tbody tr'))
                    .filter(function (tr) {
                        var cells = tr.querySelectorAll('th, td');
                        return cells.length && !tr.querySelector('td[colspan], th[colspan]');
                    })
                    .map(function (tr) {
                        return Array.from(tr.children).map(function (td) { return td.innerHTML; });
                    });
            }

            var self = this;
            var preserveHtml = !!options.preserveHtml;
            var rows = rowsData.map(function (row) {
                var cells = Array.isArray(row) ? row : Array.from(row);
                return visibleIndexes.map(function (i) {
                    var val = cells[i] != null ? cells[i] : '';
                    return preserveHtml ? String(val) : self._stripHtml(val);
                });
            });

            return {
                headers: headers,
                rows: rows,
                visibleIndexes: visibleIndexes
            };
        },

        exportCsv: function (tableId, options) {
            options = options || {};
            var data = this.extractTableData(tableId, options);
            if (!data || !data.headers.length) {
                if (window.alert) window.alert('No data to export.');
                return;
            }

            function csvEscape(val) {
                var s = String(val == null ? '' : val);
                if (/[",\n\r]/.test(s)) {
                    return '"' + s.replace(/"/g, '""') + '"';
                }
                return s;
            }

            var lines = [];
            lines.push(data.headers.map(csvEscape).join(','));
            data.rows.forEach(function (row) {
                lines.push(row.map(csvEscape).join(','));
            });

            var blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var filename = options.filename || (tableId + '-export.csv');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        },

        _isServerSideDataTable: function (dt) {
            if (!dt) return false;
            var settings = dt.settings()[0];
            if (!settings) return false;
            if (settings.oInit && settings.oInit.serverSide) return true;
            if (settings.oFeatures && settings.oFeatures.bServerSide) return true;
            return false;
        },

        _buildPrintAjaxParams: function (dt) {
            var info = dt.page.info();
            var params = {};
            var settings = dt.settings()[0];
            try {
                if (dt.ajax && typeof dt.ajax.params === 'function') {
                    params = $.extend(true, {}, dt.ajax.params());
                }
            } catch (e) {}
            if ((!params || !Object.keys(params).length) && settings && settings.oAjaxData) {
                params = $.extend(true, {}, settings.oAjaxData);
            }
            if (!params || typeof params !== 'object') {
                params = {};
            }
            params.draw = params.draw || ((settings && settings.iDraw) ? settings.iDraw + 1 : 1);
            params.start = 0;
            params.length = Math.max(info.recordsFiltered || info.recordsDisplay || 0, 1);
            params.for_print = 1;
            if (!params.search) {
                params.search = { value: dt.search(), regex: false };
            }
            if (!params.order || !params.order.length) {
                var ord = dt.order();
                if (ord && ord.length) {
                    params.order = ord.map(function (item) {
                        return { column: item[0], dir: item[1] };
                    });
                }
            }
            return params;
        },

        _resolveDataTableAjaxUrl: function (tableId) {
            var registry = window.messMasterDataTableAjaxUrlByTable || {};
            if (registry[tableId] && typeof registry[tableId] === 'function') {
                return registry[tableId]();
            }
            if (typeof window.messMasterDataTableAjaxUrl === 'function') {
                return window.messMasterDataTableAjaxUrl();
            }
            return window.location.pathname + (window.location.search || '');
        },

        /** Fetch all filtered rows for print (handles server-side DataTables). */
        fetchDataTableRowsForPrint: function (tableId, done) {
            var table = document.getElementById(tableId);
            if (!table) {
                done([]);
                return;
            }
            var dt = null;
            if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
                dt = $('#' + tableId).DataTable();
            }
            if (!dt) {
                var domRows = Array.from(table.querySelectorAll('tbody tr'))
                    .filter(function (tr) {
                        return tr.querySelectorAll('th, td').length && !tr.querySelector('td[colspan], th[colspan]');
                    })
                    .map(function (tr) {
                        return Array.from(tr.children).map(function (td) { return td.innerHTML; });
                    });
                done(domRows);
                return;
            }
            if (!this._isServerSideDataTable(dt)) {
                done(dt.rows({ search: 'applied' }).data().toArray());
                return;
            }
            var self = this;
            $.ajax({
                url: self._resolveDataTableAjaxUrl(tableId),
                type: 'GET',
                data: self._buildPrintAjaxParams(dt),
                dataType: 'json',
                cache: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (json) {
                done(json && Array.isArray(json.data) ? json.data : []);
            }).fail(function () {
                done(dt.rows({ search: 'applied' }).data().toArray());
            });
        },

        _fetchServerSidePrintRows: function (dt, done) {
            var tableId = dt.table().node().id;
            this.fetchDataTableRowsForPrint(tableId || '', done);
        },

        _buildDataTablePrintHtml: function (tableId, rowsData, options) {
            options = options || {};
            var table = document.getElementById(tableId);
            var dt = null;
            if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
                dt = $('#' + tableId).DataTable();
            }

            var printColIndexes = this.resolveExportIndexes(tableId);
            if (!printColIndexes.length && table) {
                var headerCount = table.querySelectorAll('thead tr').length
                    ? table.querySelector('thead tr').querySelectorAll('th, td').length
                    : 0;
                for (var i = 0; i < headerCount; i++) {
                    printColIndexes.push(i);
                }
            }

            var headerHtml = '<tr>' + printColIndexes.map(function (idx) {
                if (dt) {
                    return '<th>' + $(dt.column(idx).header()).html() + '</th>';
                }
                var th = table && table.querySelectorAll('thead tr th, thead tr td')[idx];
                return '<th>' + (th ? th.innerHTML : '') + '</th>';
            }).join('') + '</tr>';

            var self = this;
            var bodyRowsHtml = (rowsData || []).map(function (row) {
                var cells = self.dataTableRowToCells(row);
                return '<tr>' + printColIndexes.map(function (idx) {
                    return '<td>' + (cells[idx] != null ? cells[idx] : '') + '</td>';
                }).join('') + '</tr>';
            }).join('');

            return {
                headerHtml: headerHtml,
                bodyRowsHtml: bodyRowsHtml,
                columnsCount: printColIndexes.length || 1
            };
        },

        _openRichPrintWindow: function (htmlParts, options) {
            options = options || {};
            var columnsCount = htmlParts.columnsCount || 1;
            var title = options.title || 'Report';
            var periodText = options.periodText || '';
            var emblemUrl = options.emblemUrl || 'https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg';
            var logoUrl = options.logoUrl || 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
            var printedOn = new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString();

            var printableTable =
                '<table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead>' +
                '<tr><th colspan="' + columnsCount + '">' +
                '<div class="d-flex justify-content-between align-items-center mb-2 lbsnaa-header">' +
                '<div class="d-flex align-items-center gap-2">' +
                '<img src="' + emblemUrl + '" alt="India Emblem" height="40">' +
                '<div>' +
                '<div class="brand-line-1">Government of India</div>' +
                '<div class="brand-line-2">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
                '<div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>' +
                '</div>' +
                '</div>' +
                '<div><img src="' + logoUrl + '" alt="LBSNAA Logo" height="40"></div>' +
                '</div>' +
                '<div class="d-flex flex-wrap justify-content-between align-items-center report-meta">' +
                '<span><strong>' + title + '</strong></span>' +
                (periodText ? '<span>' + periodText + '</span>' : '') +
                '<span><strong>Printed on:</strong> ' + printedOn + '</span>' +
                '</div></th></tr>' +
                htmlParts.headerHtml +
                '</thead><tbody>' + htmlParts.bodyRowsHtml + '</tbody></table>';

            var printWindow = window.open('', '_blank');
            if (!printWindow) {
                window.print();
                return;
            }

            printWindow.document.open();
            printWindow.document.write(
                '<!doctype html><html lang="en"><head><meta charset="utf-8">' +
                '<meta name="viewport" content="width=device-width, initial-scale=1">' +
                '<title>' + String(title).replace(/</g, '&lt;') + ' - OFFICER\'S MESS LBSNAA MUSSOORIE</title>' +
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">' +
                '<style>' +
                'body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:10px;margin:0;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                '.lbsnaa-header{border-bottom:2px solid #004a93;padding-bottom:.75rem;margin-bottom:1rem;}' +
                '.brand-line-1{font-size:.85rem;text-transform:uppercase;letter-spacing:.06em;color:#004a93;}' +
                '.brand-line-2{font-size:1.1rem;font-weight:700;text-transform:uppercase;color:#222;}' +
                '.brand-line-3{font-size:.8rem;color:#555;}' +
                '.report-meta{font-size:.8rem;margin-bottom:.75rem;}' +
                '.report-meta span{display:inline-block;margin-right:1.5rem;}' +
                '.container-fluid{padding:0!important;margin:0!important;max-width:100%!important;}' +
                'table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:9px;}' +
                'th,td{padding:4px 6px;border:1px solid #dee2e6;white-space:normal!important;word-break:break-word;overflow-wrap:anywhere;vertical-align:top;}' +
                'thead th{background:#f8f9fa;font-weight:600;}' +
                '.table,.table *{white-space:normal!important;}' +
                '.table-responsive{overflow:visible!important;}' +
                'thead{display:table-header-group;}' +
                '.badge{-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                '@page{size:A4 landscape;margin:8mm;}' +
                '@media print{body{margin:0;}}' +
                (options.extraCss || '') +
                '</style></head><body>' +
                '<div class="container-fluid"><div class="table-responsive">' + printableTable + '</div></div>' +
                '<script>window.addEventListener("load",function(){window.print();});<\/script></body></html>'
            );
            printWindow.document.close();
        },

        /** Rich print: visible columns only, HTML preserved, all filtered rows (server-side). */
        printDataTable: function (tableId, options) {
            options = options || {};
            var self = this;
            var table = document.getElementById(tableId);
            if (!table) {
                window.print();
                return;
            }

            var dt = null;
            if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
                dt = $('#' + tableId).DataTable();
            }

            function finish(rowsData) {
                var parts = self._buildDataTablePrintHtml(tableId, rowsData, options);
                if (options.template === 'simple') {
                    self.printTable(tableId, $.extend({}, options, { rowsData: rowsData }));
                    return;
                }
                self._openRichPrintWindow(parts, options);
            }

            if (dt && self._isServerSideDataTable(dt)) {
                self.fetchDataTableRowsForPrint(tableId, finish);
                return;
            }

            var rowsData = [];
            if (dt) {
                rowsData = dt.rows({ search: 'applied' }).data().toArray();
            } else {
                rowsData = Array.from(table.querySelectorAll('tbody tr'))
                    .filter(function (tr) {
                        return tr.querySelectorAll('th, td').length && !tr.querySelector('td[colspan], th[colspan]');
                    })
                    .map(function (tr) {
                        return Array.from(tr.children).map(function (td) { return td.innerHTML; });
                    });
            }
            finish(rowsData);
        },

        printTable: function (tableId, options) {
            options = options || {};
            var table = document.getElementById(tableId);
            if (!table) {
                window.print();
                return;
            }

            if (options.template === 'lbsnaa' || options.rich === true) {
                this.printDataTable(tableId, options);
                return;
            }

            var rowsData = options.rowsData;
            var data;
            if (rowsData) {
                data = this._buildDataTablePrintHtml(tableId, rowsData, options);
                data = {
                    headers: [],
                    rows: [],
                    visibleIndexes: [],
                    headerHtml: data.headerHtml,
                    bodyRowsHtml: data.bodyRowsHtml,
                    columnsCount: data.columnsCount
                };
            } else {
                data = this.extractTableData(tableId, { allPages: true, preserveHtml: !!options.preserveHtml });
            }

            if (!data || (!data.headers && !data.headerHtml)) {
                window.print();
                return;
            }

            var headerHtml = data.headerHtml || (
                '<tr>' + data.headers.map(function (h) {
                    return '<th>' + String(h).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</th>';
                }).join('') + '</tr>'
            );

            var bodyRowsHtml = data.bodyRowsHtml || data.rows.map(function (row) {
                return '<tr>' + row.map(function (cell) {
                    return '<td>' + String(cell).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
                }).join('') + '</tr>';
            }).join('');

            var columnsCount = data.columnsCount || data.headers.length;
            var title = options.title || 'Report';
            var metaHtml = options.metaHtml || '';
            var printableTable =
                '<table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead>' +
                (options.brandHeaderHtml || '') +
                (metaHtml ? '<tr><th colspan="' + columnsCount + '">' + metaHtml + '</th></tr>' : '') +
                headerHtml +
                '</thead><tbody>' + bodyRowsHtml + '</tbody></table>';

            var printWindow = window.open('', '_blank');
            if (!printWindow) {
                window.print();
                return;
            }

            var extraCss = options.extraCss || '';
            printWindow.document.open();
            printWindow.document.write(
                '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>' +
                String(title).replace(/</g, '&lt;') +
                '</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">' +
                '<style>body{font-family:system-ui,sans-serif;font-size:10px;margin:12px;}' +
                'table{width:100%;border-collapse:collapse;} th,td{border:1px solid #dee2e6;padding:4px 6px;}' +
                'thead th{background:#f8f9fa;font-weight:600;}' + extraCss + '</style></head><body>' +
                printableTable +
                '<script>window.addEventListener("load",function(){window.print();});<\/script></body></html>'
            );
            printWindow.document.close();
        },

        wireExportControls: function () {
            if (this._exportControlsWired) return;
            this._exportControlsWired = true;
            var self = this;

            document.addEventListener('click', function (e) {
                var excelBtn = e.target.closest('[data-mess-excel-export]');
                if (excelBtn) {
                    var tableId = excelBtn.getAttribute('data-mess-excel-export') ||
                        excelBtn.getAttribute('data-mess-table-id');
                    if (!tableId) return;
                    var href = excelBtn.getAttribute('href');
                    if (href && href !== '#' && href.indexOf('javascript') !== 0) {
                        e.preventDefault();
                        window.location.href = self.appendVisibleColumnsToUrl(href, tableId);
                        return;
                    }
                    e.preventDefault();
                    self.exportCsv(tableId, {
                        filename: excelBtn.getAttribute('data-filename') || (tableId + '.csv')
                    });
                    return;
                }

                var printBtn = e.target.closest('[data-mess-print-table]');
                if (printBtn) {
                    var printTableId = printBtn.getAttribute('data-mess-print-table');
                    if (!printTableId) return;
                    e.preventDefault();
                    var opts = {
                        title: printBtn.getAttribute('data-print-title') || 'Report',
                        metaHtml: printBtn.getAttribute('data-print-meta') || '',
                        periodText: printBtn.getAttribute('data-print-period') || '',
                        template: printBtn.getAttribute('data-mess-print-template') || ''
                    };
                    if (opts.template === 'lbsnaa' || printBtn.getAttribute('data-print-brand') === '1') {
                        opts.template = 'lbsnaa';
                        self.printDataTable(printTableId, opts);
                    } else {
                        self.printTable(printTableId, opts);
                    }
                }
            });
        }
    };

    window.MessColumnManager.wireExportControls();

    $(document).on('mess:columns:saved', function () {
        /* Column prefs updated — print/export helpers read state on each action. */
    });
})(window, window.jQuery);
