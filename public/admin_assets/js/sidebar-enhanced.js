/**
 * Enhanced Sidebar Menu Functionality
 * Fixes double-click requirement and improves menu opening behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Initialize Bootstrap collapse instances for proper menu handling
    const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
    
    collapseElements.forEach(function(element) {
        // Ensure each collapse link has proper Bootstrap collapse initialization
        if (element.hasAttribute('href') || element.hasAttribute('data-bs-target')) {
            // Prevent default behavior for collapse links
            element.addEventListener('click', function(e) {
                // Only prevent default if it's a link
                if (this.tagName === 'A') {
                    e.preventDefault();
                }
                
                // Get the target element ID
                const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
                if (!targetId) return;

                const targetElement = document.querySelector(targetId.replace('#', '#'));
                if (!targetElement) return;

                // Toggle the collapse manually using Bootstrap's collapse API
                const bsCollapse = new bootstrap.Collapse(targetElement, {
                    toggle: true
                });

                // Update aria-expanded attribute
                const isExpanded = targetElement.classList.contains('show');
                this.setAttribute('aria-expanded', isExpanded);
            });
        }
    });

    // Ensure single-click functionality works properly
    // Fix for menu items with nested collapse items
    const menuItems = document.querySelectorAll('.sidebar-link[data-bs-toggle="collapse"]');
    
    menuItems.forEach(function(item) {
        // Add single-click handler
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
            if (!targetId) return;

            const target = document.querySelector(targetId);
            if (!target) return;

            // Use Bootstrap collapse API for proper toggling
            const collapse = new bootstrap.Collapse(target, { toggle: true });
            
            // Update the aria-expanded state
            const isShowing = target.classList.contains('show');
            this.setAttribute('aria-expanded', isShowing);
        });

        // Prevent double-click default behavior
        item.addEventListener('dblclick', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    // Additional handling for nested collapse menus
    const nestedCollapses = document.querySelectorAll('.collapse .sidebar-link[data-bs-toggle="collapse"]');
    
    nestedCollapses.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
            if (!targetId) return;

            const target = document.querySelector(targetId);
            if (!target) return;

            // Toggle nested collapse
            const collapse = new bootstrap.Collapse(target, { toggle: true });
            this.setAttribute('aria-expanded', target.classList.contains('show'));
        });
    });

    // Ensure arrow icons rotate properly on collapse toggle
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(toggler) {
        const targetId = toggler.getAttribute('href') || toggler.getAttribute('data-bs-target');
        if (!targetId) return;

        const target = document.querySelector(targetId);
        if (!target) return;

        // Listen for bootstrap collapse events
        target.addEventListener('show.bs.collapse', function() {
            const arrow = toggler.querySelector('.keyboard_arrow_down, .chevron_right, [class*="arrow"]');
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
            }
        });

        target.addEventListener('hide.bs.collapse', function() {
            const arrow = toggler.querySelector('.keyboard_arrow_down, .chevron_right, [class*="arrow"]');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        });
    });
});
