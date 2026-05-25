/**
 * Global DataTables UI enhancements (presentation only).
 * - Pill search with icon, right-aligned
 * - Footer: pagination left, "Showing [length] of N items" right
 */
(function (global, $) {
    'use strict';

    if (typeof $ === 'undefined' || !$.fn || !$.fn.dataTable) {
        return;
    }

    $.extend(true, $.fn.dataTable.defaults, {
        pagingType: 'simple_numbers',
        language: {
            search: '',
            searchPlaceholder: 'Search',
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: '(filtered from _MAX_ total items)',
            paginate: {
                previous: '‹',
                next: '›'
            }
        }
    });

    function hasCustomFilterToolbar($filter) {
        return $filter.find('.hac-approved-filter-toolbar, .dt-custom-filter-toolbar').length > 0;
    }

    function enhanceSearch($wrap) {
        var $filter = $wrap.find('.dataTables_filter').first();
        if (!$filter.length || $filter.data('dt-search-enhanced')) {
            return;
        }
        if ($filter.css('display') === 'none' || hasCustomFilterToolbar($filter)) {
            return;
        }

        var $input = $filter.find('input[type="search"], input[type="text"]').first();
        if (!$input.length) {
            return;
        }

        $filter.data('dt-search-enhanced', true);

        var $label = $input.closest('label');
        if (!$label.length) {
            $label = $('<label class="dt-search-label"></label>');
            $input.wrap($label);
            $label = $input.parent();
        } else {
            $label.addClass('dt-search-label');
            $label.contents().filter(function () {
                return this.nodeType === 3;
            }).remove();
        }

        if (!$filter.find('.dt-search-input-group').length) {
            $input.detach();
            var $group = $('<div class="input-group input-group-sm dt-search-input-group"></div>');
            $group.append(
                '<span class="input-group-text">' +
                '<span class="material-symbols-rounded dt-search-icon" aria-hidden="true">search</span>' +
                '</span>'
            );
            $group.append($input);
            $label.append($group);
        }

        $input
            .attr('placeholder', $input.attr('placeholder') || 'Search')
            .addClass('form-control form-control-sm')
            .attr('aria-label', 'Search table');
    }

    function enhanceFooter($wrap) {
        var $length = $wrap.find('.dataTables_length').first();
        var $info = $wrap.find('.dataTables_info').first();
        var $paginate = $wrap.find('.dataTables_paginate').first();

        if (!$paginate.length && !$info.length && !$length.length) {
            return;
        }

        var $footer = $wrap.children('.dt-footer-toolbar').first();
        if (!$footer.length) {
            $footer = $('<div class="dt-footer-toolbar"></div>');
            $wrap.append($footer);
        }

        if ($paginate.length) {
            $paginate.appendTo($footer);
        }

        var $meta = $footer.children('.dt-footer-meta').first();
        if (!$meta.length) {
            $meta = $('<div class="dt-footer-meta"></div>');
            $footer.append($meta);
        }

        if ($length.length) {
            $length.addClass('dt-length-moved').appendTo($meta);
            var $lengthLabel = $length.find('label');
            $lengthLabel.contents().filter(function () {
                return this.nodeType === 3 && !/\S/.test(this.nodeValue);
            }).remove();
        }

        if ($info.length) {
            $info.appendTo($meta);
        }

        $wrap.data('dt-footer-enhanced', true);
    }

    function enhanceWrapper(settings) {
        try {
            var api = new $.fn.dataTable.Api(settings);
            var $wrap = $(api.table().container());
            if (!$wrap.length || $wrap.data('dt-ui-enhanced')) {
                return;
            }
            $wrap.data('dt-ui-enhanced', true);
            $wrap.addClass('dt-ui-enhanced');

            enhanceSearch($wrap);
            enhanceFooter($wrap);

            $wrap.find('.dataTables_paginate ul.pagination').addClass('mb-0');
            $wrap.find('.dataTables_length select').addClass('form-select form-select-sm');
            $wrap.find('.dataTables_info').addClass('small text-muted');
        } catch (err) {
            if (global.console && console.warn) {
                console.warn('DataTables UI enhance failed:', err);
            }
        }
    }

    $(document).on('init.dt', function (e, settings) {
        enhanceWrapper(settings);
    });

    $(document).on('draw.dt', function (e, settings) {
        var $wrap = $(new $.fn.dataTable.Api(settings).table().container());
        if ($wrap.data('dt-ui-enhanced') && !$wrap.data('dt-footer-enhanced')) {
            enhanceFooter($wrap);
        }
    });
})(typeof window !== 'undefined' ? window : this, typeof jQuery !== 'undefined' ? jQuery : null);
