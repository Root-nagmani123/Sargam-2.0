/**
 * Reusable Dropdown Search Utility
 * Provides a simple way to initialize searchable dropdowns using Select2
 * 
 * Features:
 * - Auto-initializes dropdowns with class "select2" or data-searchable="true"
 * - Simple API for manual initialization
 * - Supports all Select2 options
 * - Handles dynamic content (re-initialization)
 * 
 * Usage Examples:
 * 
 * 1. Auto-initialization (recommended):
 *    Add class "select2" to your select element:
 *    <select class="select2" name="myField">
 * 
 *    Or use data attributes:
 *    <select data-searchable="true" data-placeholder="Search..." data-allow-clear="true">
 * 
 * 2. Manual initialization:
 *    DropdownSearch.init('#mySelect', {
 *        placeholder: 'Search and select...',
 *        allowClear: true,
 *        width: '100%'
 *    });
 * 
 * 3. Initialize multiple dropdowns:
 *    DropdownSearch.initAll('.searchable-dropdown', {
 *        placeholder: 'Search...',
 *        allowClear: true
 *    });
 * 
 * 4. Re-initialize after dynamic content changes:
 *    DropdownSearch.reinit('#mySelect', { placeholder: 'Search...' });
 * 
 * 5. Get/Set values:
 *    DropdownSearch.getValue('#mySelect');
 *    DropdownSearch.setValue('#mySelect', 'value123');
 * 
 * 6. Destroy instance:
 *    DropdownSearch.destroy('#mySelect');
 */

(function(window) {
    'use strict';

    // Check if jQuery and Select2 are available
    function isSelect2Available() {
        return typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined';
    }

    /**
     * Initialize Select2 for a single dropdown element
     * 
     * @param {string|HTMLElement|jQuery} selector - CSS selector, DOM element, or jQuery object
     * @param {Object} options - Select2 configuration options
     * @returns {jQuery|null} - jQuery object of initialized select or null if failed
     * 
     * @example
     * DropdownSearch.init('#mySelect', {
     *     placeholder: 'Search and select...',
     *     allowClear: true,
     *     width: '100%'
     * });
     */
    function init(selector, options) {
        if (!isSelect2Available()) {
            console.warn('Select2 is not available. Make sure jQuery and Select2 are loaded.');
            return null;
        }

        const $select = $(selector);
        
        if ($select.length === 0) {
            console.warn('DropdownSearch.init: Element not found', selector);
            return null;
        }

        // Default options
        const defaultOptions = {
            placeholder: 'Search and select...',
            allowClear: false,
            width: '100%',
            dropdownParent: $select.closest('.card-body, .modal-body, body')
        };

        // Merge user options with defaults
        const config = Object.assign({}, defaultOptions, options);

        // If already initialized, destroy first (and stop option observer)
        if ($select.hasClass('select2-hidden-accessible')) {
            stopObservingSelectOptions($select[0]);
            $select.select2('destroy');
        }

        // Initialize Select2
        $select.select2(config);
        $select.data('dropdownsearch-config', config);
        startObservingSelectOptions($select[0], config);

        return $select;
    }

    var optionObserverTimeouts = {};
    function startObservingSelectOptions(selectEl, config) {
        if (!selectEl || selectEl.tagName !== 'SELECT') return;
        var $el = $(selectEl);
        var timeoutKey = 's2-' + (selectEl.id || selectEl.name || ('n' + Math.random().toString(36).slice(2)));
        var observer = new MutationObserver(function() {
            clearTimeout(optionObserverTimeouts[timeoutKey]);
            optionObserverTimeouts[timeoutKey] = setTimeout(function() {
                if (!selectEl.parentNode) return;
                var $s = $(selectEl);
                if (!$s.hasClass('select2-hidden-accessible')) return;
                stopObservingSelectOptions(selectEl);
                $s.select2('destroy');
                var cfg = $s.data('dropdownsearch-config') || config;
                if (cfg) init(selectEl, cfg);
                else initSelectForGlobal(selectEl);
            }, 80);
        });
        observer.observe(selectEl, { childList: true, subtree: false });
        selectEl._dropdownsearch_option_observer = observer;
    }
    function stopObservingSelectOptions(selectEl) {
        if (!selectEl || !selectEl._dropdownsearch_option_observer) return;
        selectEl._dropdownsearch_option_observer.disconnect();
        selectEl._dropdownsearch_option_observer = null;
    }

    /**
     * Initialize Select2 for multiple dropdown elements
     * 
     * @param {string|NodeList|Array} selector - CSS selector, NodeList, or Array of elements
     * @param {Object} options - Select2 configuration options (applied to all)
     * @returns {Array} - Array of initialized jQuery objects
     * 
     * @example
     * DropdownSearch.initAll('.searchable-dropdown', {
     *     placeholder: 'Search...',
     *     allowClear: true
     * });
     */
    function initAll(selector, options) {
        if (!isSelect2Available()) {
            console.warn('Select2 is not available. Make sure jQuery and Select2 are loaded.');
            return [];
        }

        const $selects = $(selector);
        const initialized = [];

        $selects.each(function() {
            const $select = $(this);
            const result = init($select, options);
            if (result) {
                initialized.push(result);
            }
        });

        return initialized;
    }

    /**
     * Destroy Select2 instance for a dropdown
     * 
     * @param {string|HTMLElement|jQuery} selector - CSS selector, DOM element, or jQuery object
     * @returns {boolean} - True if destroyed, false otherwise
     */
    function destroy(selector) {
        if (!isSelect2Available()) {
            return false;
        }

        const $select = $(selector);
        
        if ($select.length === 0) {
            return false;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            stopObservingSelectOptions($select[0]);
            $select.select2('destroy');
            $select.removeData('dropdownsearch-config');
            return true;
        }

        return false;
    }

    /**
     * Re-initialize a dropdown (useful after dynamic content changes)
     * 
     * @param {string|HTMLElement|jQuery} selector - CSS selector, DOM element, or jQuery object
     * @param {Object} options - Select2 configuration options
     * @returns {jQuery|null} - jQuery object of re-initialized select or null if failed
     */
    function reinit(selector, options) {
        destroy(selector);
        return init(selector, options);
    }

    /**
     * Get the value of a Select2 dropdown
     * 
     * @param {string|HTMLElement|jQuery} selector - CSS selector, DOM element, or jQuery object
     * @returns {string|Array|null} - Selected value(s) or null if not found
     */
    function getValue(selector) {
        if (!isSelect2Available()) {
            const $select = $(selector);
            return $select.length > 0 ? $select.val() : null;
        }

        const $select = $(selector);
        
        if ($select.length === 0) {
            return null;
        }

        return $select.val();
    }

    /**
     * Set the value of a Select2 dropdown
     * 
     * @param {string|HTMLElement|jQuery} selector - CSS selector, DOM element, or jQuery object
     * @param {string|Array} value - Value(s) to set
     * @param {boolean} triggerChange - Whether to trigger change event
     * @returns {boolean} - True if set successfully, false otherwise
     */
    function setValue(selector, value, triggerChange = true) {
        if (!isSelect2Available()) {
            const $select = $(selector);
            if ($select.length > 0) {
                $select.val(value);
                if (triggerChange) {
                    $select.trigger('change');
                }
                return true;
            }
            return false;
        }

        const $select = $(selector);
        
        if ($select.length === 0) {
            return false;
        }

        $select.val(value);
        
        if (triggerChange) {
            $select.trigger('change');
        } else {
            $select.trigger('change.select2');
        }

        return true;
    }

    /**
     * Returns true if the select should be skipped (opt-out from Select2).
     * Opt-out: class "no-select2" or data-no-select2="true" or data-select2="false"
     */
    function shouldSkipSelect2(element) {
        if (!element || element.tagName !== 'SELECT') return true;
        if (element.classList.contains('no-select2')) return true;
        const noSelect2 = element.getAttribute('data-no-select2');
        if (noSelect2 === 'true' || noSelect2 === '') return true;
        if (element.getAttribute('data-select2') === 'false') return true;
        return false;
    }

    /**
     * Initialize a single select for global Select2 (used for "all dropdowns").
     * Uses placeholder/allowClear from data attributes or sensible defaults.
     */
    function initSelectForGlobal(element) {
        if (!element || element.tagName !== 'SELECT') return;
        const $el = $(element);
        if ($el.hasClass('select2-hidden-accessible') || $el.data('select2')) return;
        const placeholder = element.getAttribute('data-placeholder') || element.getAttribute('placeholder') || 'Select...';
        const allowClear = element.getAttribute('data-allow-clear') === 'true';
        const dropdownParent = $el.closest('.modal').length ? $el.closest('.modal') : $('body');
        init(element, {
            placeholder: placeholder,
            allowClear: allowClear,
            width: '100%',
            dropdownParent: dropdownParent
        });
    }

    /**
     * Auto-initialize Select2 on EVERY select in the document (wherever select is used).
     * Opt-out: add class "no-select2" or data-no-select2="true" to keep native dropdown.
     */
    function autoInit() {
        if (!isSelect2Available()) return;

        var allSelects = document.querySelectorAll('select');
        for (var i = 0; i < allSelects.length; i++) {
            var el = allSelects[i];
            if (shouldSkipSelect2(el)) continue;
            if ($(el).hasClass('select2-hidden-accessible') || $(el).data('select2')) continue;

            var placeholder = el.getAttribute('data-placeholder') || el.getAttribute('placeholder');
            if (placeholder == null) placeholder = (el.classList.contains('select2') || el.getAttribute('data-searchable') === 'true') ? 'Search and select...' : 'Select...';
            var allowClear = el.getAttribute('data-allow-clear') === 'true';
            var $el = $(el);
            var dropdownParent = $el.closest('.modal').length ? $el.closest('.modal') : $('body');

            init(el, {
                placeholder: placeholder,
                allowClear: allowClear,
                width: '100%',
                dropdownParent: dropdownParent
            });
        }
    }

    // Auto-initialize on DOM ready; delay slightly so dropdowns populated by other scripts on load are ready
    function runAutoInit() {
        autoInit();
        startObservingNewSelects();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(runAutoInit, 80);
        });
    } else {
        setTimeout(runAutoInit, 80);
    }

    /**
     * Observe DOM for newly added select elements (e.g. modals, dynamic content) and init Select2 on them.
     */
    function startObservingNewSelects() {
        if (!isSelect2Available() || !document.body) return;
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType !== 1) return;
                    if (node.tagName === 'SELECT' && !shouldSkipSelect2(node)) {
                        if (!$(node).hasClass('select2-hidden-accessible')) {
                            initSelectForGlobal(node);
                        }
                        return;
                    }
                    var selects = node.querySelectorAll && node.querySelectorAll('select');
                    if (selects) {
                        selects.forEach(function(el) {
                            if (shouldSkipSelect2(el) || $(el).hasClass('select2-hidden-accessible')) return;
                            initSelectForGlobal(el);
                        });
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Public API
    window.DropdownSearch = {
        init: init,
        initAll: initAll,
        destroy: destroy,
        reinit: reinit,
        getValue: getValue,
        setValue: setValue,
        isAvailable: isSelect2Available,
        autoInit: autoInit
    };

})(window);

