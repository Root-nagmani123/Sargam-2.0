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

        // If already initialized, destroy first
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        // Initialize Select2
        $select.select2(config);

        return $select;
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
            $select.select2('destroy');
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
     * Auto-initialize all dropdowns with data-searchable="true" attribute or .select2 class
     * This runs automatically on DOMContentLoaded
     */
    function autoInit() {
        if (!isSelect2Available()) {
            return;
        }

        // Initialize dropdowns with data-searchable attribute
        const searchableDropdowns = document.querySelectorAll('[data-searchable="true"]');
        searchableDropdowns.forEach(function(element) {
            const placeholder = element.getAttribute('data-placeholder') || 'Search and select...';
            const allowClear = element.getAttribute('data-allow-clear') === 'true';
            
            init(element, {
                placeholder: placeholder,
                allowClear: allowClear
            });
        });

        // Initialize dropdowns with .select2 class (if not already initialized)
        const select2Dropdowns = document.querySelectorAll('select.select2:not(.select2-hidden-accessible)');
        select2Dropdowns.forEach(function(element) {
            const placeholder = element.getAttribute('data-placeholder') || element.getAttribute('placeholder') || 'Search and select...';
            const allowClear = element.getAttribute('data-allow-clear') === 'true';
            
            init(element, {
                placeholder: placeholder,
                allowClear: allowClear
            });
        });
    }

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
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

