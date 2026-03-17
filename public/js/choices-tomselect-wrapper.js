// Mess Module - Choices.js Helper Wrapper
// This provides a TomSelect-like API for Choices.js to minimize code changes

(function() {
    'use strict';

    if (typeof Choices === 'undefined') {
        console.warn('Choices.js not loaded');
        return;
    }

    // Store all Choices instances
    window._choicesInstances = new WeakMap();

    // Add tomselect property to select elements
    Object.defineProperty(HTMLSelectElement.prototype, 'tomselect', {
        get: function() {
            return window._choicesInstances.get(this) || null;
        },
        set: function(instance) {
            if (instance) {
                window._choicesInstances.set(this, instance);
            } else {
                window._choicesInstances.delete(this);
            }
        },
        configurable: true
    });

    // TomSelect-compatible wrapper class 
    class ChoicesWrapper {
        constructor(element, config = {}) {
            this.element = element;
            
            const choicesConfig = {
                searchEnabled: config.searchEnabled !== false,
                itemSelectText: '',
                shouldSort: false,
                placeholderValue: config.placeholder || '',
                removeItemButton: false,
                allowHTML: true
            };

            try {
                this.choices = new Choices(element, choicesConfig);
                this.element.tomselect = this;
                console.log('Choices instance created for:', element.id || element.name);
            } catch (e) {
                console.error('Failed to create Choices instance:', e);
                throw e;
            }
        }

        destroy() {
            if (this.choices) {
                try {
                    this.choices.destroy();
                    console.log('Choices instance destroyed for:', this.element.id || this.element.name);
                } catch (e) {
                    console.error('Error destroying Choices instance:', e);
                }
            }
            this.element.tomselect = null;
        }

        getValue() {
            return this.element.value || '';
        }

        setValue(value, silent = false) {
            if (this.choices) {
                try {
                    this.choices.setChoiceByValue(value);
                } catch (e) {
                    console.warn('setValue error:', e);
                    this.element.value = value;
                }
            } else {
                this.element.value = value;
            }
        }

        clear(silent = false) {
            if (this.choices) {
                try {
                    this.choices.removeActiveItems();
                } catch (e) {
                    console.warn('clear error:', e);
                    this.element.value = '';
                }
            } else {
                this.element.value = '';
            }
        }

        clearOptions() {
            if (this.choices) {
                try {
                    this.choices.clearChoices();
                } catch (e) {
                    console.warn('clearOptions error:', e);
                }
            }
        }

        addOption(option) {
            if (this.choices && option) {
                try {
                    this.choices.setChoices([{
                        value: option.value || '',
                        label: option.text || option.label || '',
                        selected: false,
                        disabled: false
                    }], 'value', 'label', false);
                } catch (e) {
                    console.warn('addOption error:', e);
                }
            }
        }

        get wrapper() {
            return this.element.parentElement;
        }
    }

    // Make TomSelect constructor use Choices 
    window.TomSelect = ChoicesWrapper;

    console.log('Choices.js TomSelect wrapper loaded');
})();
