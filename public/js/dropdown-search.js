/**
 * Reusable Dropdown Search Utility
 * Uses Choices.js for searchable dropdowns (replaces Select2)
 *
 * Features:
 * - Auto-initializes dropdowns with class "select2" or data-searchable="true"
 * - Simple API for manual init, destroy, reinit, getValue, setValue
 * - No jQuery dependency for Choices.js
 *
 * Usage:
 * - Add class "select2" to a select: <select class="form-select select2" name="myField">
 * - Or data-searchable="true" with optional data-placeholder="..."
 * - Manual: DropdownSearch.init('#mySelect', { placeholder: 'Search...' });
 * - Reinit after dynamic options: DropdownSearch.reinit('#mySelect', { placeholder: '...' });
 */

(function(window) {
    'use strict';

    var Choices = window.Choices;
    var choicesInstances = new WeakMap();

    function isChoicesAvailable() {
        return typeof Choices === 'function';
    }

    function getElement(selector) {
        if (typeof selector === 'string') {
            return document.querySelector(selector);
        }
        if (selector && selector.nodeType === 1) {
            return selector;
        }
        if (typeof $ !== 'undefined' && selector && selector.jquery) {
            return selector[0];
        }
        return null;
    }

    function getOptions(options) {
        var opts = {
            searchEnabled: true,
            searchPlaceholderValue: 'Type to search...',
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Search and select...',
            shouldSort: false
        };
        if (options && typeof options === 'object') {
            if (options.placeholder !== undefined) opts.placeholderValue = options.placeholder;
            if (options.searchPlaceholderValue !== undefined) opts.searchPlaceholderValue = options.searchPlaceholderValue;
            if (options.allowClear !== undefined && options.allowClear) opts.removeItemButton = true;
            // Pass through any other Choices.js options (e.g. position, classNames)
            ['position', 'classNames', 'callbackOnCreateTemplates'].forEach(function(key) {
                if (options[key] !== undefined) opts[key] = options[key];
            });
        }
        return opts;
    }

    /**
     * Initialize Choices.js for a single select
     * @param {string|HTMLElement|jQuery} selector
     * @param {Object} options - { placeholder: string, allowClear: boolean }
     * @returns {Object|null} - Choices instance or null
     */
    function init(selector, options) {
        if (!isChoicesAvailable()) {
            console.warn('Choices.js is not loaded.');
            return null;
        }
        var el = getElement(selector);
        if (!el || el.tagName !== 'SELECT') {
            if (selector && typeof selector === 'string') console.warn('DropdownSearch.init: Element not found', selector);
            return null;
        }
        var existing = choicesInstances.get(el);
        if (existing && existing.destroy) {
            try { existing.destroy(); } catch (e) {}
            choicesInstances.delete(el);
        }
        var config = getOptions(options);
        var instance = new Choices(el, config);
        choicesInstances.set(el, instance);
        return instance;
    }

    /**
     * Initialize Choices for multiple elements
     */
    function initAll(selector, options) {
        if (!isChoicesAvailable()) return [];
        var nodes = typeof selector === 'string' ? document.querySelectorAll(selector) : selector;
        if (!nodes || !nodes.length) return [];
        var list = Array.isArray(nodes) ? nodes : Array.prototype.slice.call(nodes);
        return list.map(function(el) { return init(el, options); }).filter(Boolean);
    }

    /**
     * Destroy Choices instance
     */
    function destroy(selector) {
        var el = getElement(selector);
        if (!el) return false;
        var instance = choicesInstances.get(el);
        if (instance && instance.destroy) {
            try { instance.destroy(); } catch (e) {}
            choicesInstances.delete(el);
            return true;
        }
        return false;
    }

    /**
     * Re-initialize (destroy then init)
     */
    function reinit(selector, options) {
        destroy(selector);
        return init(selector, options);
    }

    /**
     * Get current value (single or multiple)
     */
    function getValue(selector) {
        var el = getElement(selector);
        if (!el) return null;
        if (el.multiple) {
            return Array.from(el.selectedOptions).map(function(o) { return o.value; });
        }
        return el.value;
    }

    /**
     * Set value and optionally trigger change
     */
    function setValue(selector, value, triggerChange) {
        if (triggerChange === undefined) triggerChange = true;
        var el = getElement(selector);
        if (!el) return false;
        if (Array.isArray(value)) {
            Array.from(el.options).forEach(function(opt) {
                opt.selected = value.indexOf(opt.value) !== -1;
            });
        } else {
            el.value = value;
        }
        var instance = choicesInstances.get(el);
        if (instance && instance.setChoiceByValue) {
            try {
                instance.setChoiceByValue(Array.isArray(value) ? value : [value]);
            } catch (e) {
                el.value = value;
            }
        }
        if (triggerChange) {
            var ev = new Event('change', { bubbles: true });
            el.dispatchEvent(ev);
        }
        return true;
    }

    function autoInit() {
        if (!isChoicesAvailable()) return;
        var searchable = document.querySelectorAll('[data-searchable="true"]');
        searchable.forEach(function(el) {
            var placeholder = el.getAttribute('data-placeholder') || 'Search and select...';
            var allowClear = el.getAttribute('data-allow-clear') === 'true';
            if (!choicesInstances.get(el)) init(el, { placeholder: placeholder, allowClear: allowClear });
        });
        var select2Class = document.querySelectorAll('select.select2');
        select2Class.forEach(function(el) {
            if (choicesInstances.get(el)) return;
            var placeholder = el.getAttribute('data-placeholder') || el.getAttribute('placeholder') || 'Search and select...';
            var allowClear = el.getAttribute('data-allow-clear') === 'true';
            init(el, { placeholder: placeholder, allowClear: allowClear });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    window.DropdownSearch = {
        init: init,
        initAll: initAll,
        destroy: destroy,
        reinit: reinit,
        getValue: getValue,
        setValue: setValue,
        isAvailable: isChoicesAvailable,
        autoInit: autoInit
    };
})(window);
