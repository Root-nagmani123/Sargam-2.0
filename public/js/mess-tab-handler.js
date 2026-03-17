/**
 * Mess Module Tab Handler
 * Makes Tab key behave exactly like Enter key in forms
 * - Triggers the same events as Enter key
 * - Shift+Tab still works normally for backward navigation
 */

(function() {
    'use strict';

    console.log('Mess Tab Handler: Script loaded');

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('Mess Tab Handler: Initialized');
        // Listen to all keydown events on the document (use capture phase for priority)
        document.addEventListener('keydown', handleTabAsEnter, true);
    }

    function handleTabAsEnter(event) {
        // Only handle Tab key (not Shift+Tab)
        if (event.key !== 'Tab' || event.shiftKey) {
            return;
        }

        const target = event.target;
        
        // Only handle form input elements (not buttons)
        const isFormInput = target.matches('input, select, textarea');
        if (!isFormInput) {
            return;
        }

        // Don't handle if inside a textarea (allow normal tab for indentation)
        if (target.tagName === 'TEXTAREA') {
            return;
        }

        console.log('Mess Tab Handler: Tab pressed on', target);

        // Prevent default tab behavior
        event.preventDefault();
        event.stopPropagation();

        // Create and dispatch a synthetic Enter key event
        const enterEvent = new KeyboardEvent('keydown', {
            key: 'Enter',
            code: 'Enter',
            keyCode: 13,
            which: 13,
            bubbles: true,
            cancelable: true
        });

        console.log('Mess Tab Handler: Dispatching Enter event');

        // Dispatch the Enter event on the same target
        const enterHandled = !target.dispatchEvent(enterEvent);

        // If Enter event was not prevented, handle default behavior
        if (!enterHandled) {
            console.log('Mess Tab Handler: Enter not handled, moving to next field');
            // Check if we're in a form
            const form = target.closest('form');
            if (form) {
                // Get all focusable elements
                const focusableElements = getFocusableElements(form);
                const currentIndex = focusableElements.indexOf(target);

                // Move to next element
                if (currentIndex !== -1 && currentIndex < focusableElements.length - 1) {
                    const nextElement = focusableElements[currentIndex + 1];
                    console.log('Mess Tab Handler: Moving focus to', nextElement);
                    nextElement.focus();
                    
                    // Select text in input fields for easy replacement
                    if (nextElement.tagName === 'INPUT' && 
                        (nextElement.type === 'text' || nextElement.type === 'number' || 
                         nextElement.type === 'tel' || nextElement.type === 'email')) {
                        setTimeout(() => nextElement.select(), 0);
                    }
                } else if (currentIndex === focusableElements.length - 1) {
                    // Last field - focus submit button or blur
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        console.log('Mess Tab Handler: Moving focus to submit button');
                        submitButton.focus();
                    }
                }
            }
        } else {
            console.log('Mess Tab Handler: Enter event was handled by existing handler');
        }
    }

    function getFocusableElements(form) {
        const selector = 'input:not([type="hidden"]):not([disabled]):not([readonly]), ' +
                        'select:not([disabled]), ' +
                        'textarea:not([disabled]):not([readonly]), ' +
                        'button[type="submit"]:not([disabled])';
        return Array.from(form.querySelectorAll(selector)).filter(el => {
            return el.offsetParent !== null; // Filter out hidden elements
        });
    }
})();
