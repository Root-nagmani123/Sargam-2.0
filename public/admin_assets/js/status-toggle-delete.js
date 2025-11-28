/**
 * Reusable Status Toggle and Delete Icon Management
 * 
 * This script handles dynamic updates of delete icons based on status toggle changes.
 * It works with any module that uses status-toggle checkboxes and delete-icon-container elements.
 * 
 * Requirements:
 * - Status toggle checkbox must have: data-table, data-column, data-id attributes
 * - Delete icon container must have: data-item-id attribute matching the checkbox data-id
 * - Delete icon container should have: data-delete-route (Laravel route name) and data-item-name (for confirmation message)
 */

(function($) {
    'use strict';

    // Configuration map for different modules
    // Each module can specify its delete route pattern and item name
    const moduleConfig = {
        'subject_master': {
            deleteRoute: 'subject.destroy',
            itemName: 'subject'
        },
        'subject_module_master': {
            deleteRoute: 'subject-module.destroy',
            itemName: 'Subject module'
        },
        'exemption_category_master': {
            deleteRoute: 'master.exemption.category.master.delete',
            itemName: 'record',
            useEncryptedId: true // Some routes use encrypted IDs
        }
    };

    /**
     * Get module configuration
     */
    function getModuleConfig(tableName) {
        return moduleConfig[tableName] || {
            deleteRoute: null,
            itemName: 'item',
            useEncryptedId: false
        };
    }

    /**
     * Update delete icon based on status
     * @param {jQuery} $container - The delete icon container element
     * @param {string} itemId - The item ID
     * @param {number} status - Status (1 = active, 0 = inactive)
     * @param {object} config - Module configuration
     */
    function updateDeleteIcon($container, itemId, status, config) {
        if (status == 1) {
            // Item is active - show disabled icon
            const itemName = config.itemName || 'item';
            $container.html(`
                <span class="delete-icon-disabled" title="Cannot delete active ${itemName}">
                    <i class="material-icons material-symbols-rounded"
                        style="font-size: 22px; color: #ccc; cursor: not-allowed;">delete</i>
                </span>
            `);
        } else {
            // Item is inactive - show enabled delete form
            let deleteUrl = '';
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            
            // Try to get delete URL from data attribute first
            deleteUrl = $container.data('delete-url');
            
            // If not in data attribute, try to find existing form in the same row
            if (!deleteUrl) {
                const $row = $container.closest('tr');
                const existingForm = $row.find('form[action*="destroy"], form[action*="delete"]').first();
                if (existingForm.length) {
                    deleteUrl = existingForm.attr('action');
                }
            }
            
            // If still not found, try to get from container's parent (for cases where form is sibling)
            if (!deleteUrl) {
                const existingForm = $container.siblings('form').first();
                if (existingForm.length) {
                    deleteUrl = existingForm.attr('action');
                }
            }

            const itemName = config.itemName || 'item';
            const confirmMessage = `Are you sure you want to delete this ${itemName}?`;
            
            $container.html(`
                <form action="${deleteUrl}" method="POST"
                    class="m-0 delete-form" data-status="0">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <a href="javascript:void(0)" onclick="event.preventDefault();
                            if(confirm('${confirmMessage}')) {
                                this.closest('form').submit();
                            }">
                        <i class="material-icons material-symbols-rounded"
                            style="font-size: 22px;">delete</i>
                    </a>
                </form>
            `);
        }
    }

    /**
     * Initialize delete icon containers on page load
     */
    function initializeDeleteIcons() {
        $('.status-toggle').each(function() {
            const $checkbox = $(this);
            const tableName = $checkbox.data('table');
            const itemId = $checkbox.data('id');
            const status = $checkbox.is(':checked') ? 1 : 0;
            
            // Find corresponding delete icon container
            const $container = $(`.delete-icon-container[data-item-id="${itemId}"]`);
            
            if ($container.length && tableName) {
                // If container doesn't have delete-url, try to extract from existing form
                if (!$container.data('delete-url')) {
                    const $row = $checkbox.closest('tr');
                    const existingForm = $row.find('form[action*="destroy"], form[action*="delete"]').first();
                    if (existingForm.length) {
                        $container.attr('data-delete-url', existingForm.attr('action'));
                    }
                }
                
                const config = getModuleConfig(tableName);
                updateDeleteIcon($container, itemId, status, config);
            }
        });
    }

    // Track which item's status is being updated
    let currentUpdatingItemId = null;
    let currentUpdatingTable = null;

    /**
     * Monitor status toggle changes
     */
    $(document).on('change', '.status-toggle', function() {
        const $checkbox = $(this);
        currentUpdatingItemId = $checkbox.data('id');
        currentUpdatingTable = $checkbox.data('table');
    });

    /**
     * Hook into AJAX success for status toggle updates
     */
    $(document).ajaxSuccess(function(event, xhr, settings) {
        // Check if this is a status toggle request
        if (settings.url && (settings.url.includes('toggle-status') || settings.url.includes('toggleStatus'))) {
            let tableName = null;
            let itemId = null;

            // Parse the request data to get table name and item ID
            if (typeof settings.data === 'string') {
                // URL-encoded string
                const tableMatch = settings.data.match(/[&?]table=([^&]+)/);
                const idMatch = settings.data.match(/[&?]id=(\d+)/);
                
                if (tableMatch) {
                    tableName = decodeURIComponent(tableMatch[1]);
                }
                if (idMatch) {
                    itemId = idMatch[1];
                }
            } else if (typeof settings.data === 'object' && settings.data.table) {
                tableName = settings.data.table;
                itemId = settings.data.id;
            }

            // Use tracked values if available
            tableName = currentUpdatingTable || tableName;
            itemId = currentUpdatingItemId || itemId;

            if (tableName && itemId) {
                const config = getModuleConfig(tableName);
                const $checkbox = $(`.status-toggle[data-table="${tableName}"][data-id="${itemId}"]`);
                const $container = $(`.delete-icon-container[data-item-id="${itemId}"]`);

                // Small delay to ensure checkbox state is updated
                setTimeout(function() {
                    if ($checkbox.length && $container.length) {
                        const status = $checkbox.is(':checked') ? 1 : 0;
                        updateDeleteIcon($container, itemId, status, config);
                    }
                    currentUpdatingItemId = null;
                    currentUpdatingTable = null;
                }, 150);
            }
        }
    });

    /**
     * Fallback: Watch for success message appearance
     */
    const statusMsgObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length && currentUpdatingItemId && currentUpdatingTable) {
                const itemId = currentUpdatingItemId;
                const tableName = currentUpdatingTable;
                
                setTimeout(function() {
                    const $checkbox = $(`.status-toggle[data-table="${tableName}"][data-id="${itemId}"]`);
                    const $container = $(`.delete-icon-container[data-item-id="${itemId}"]`);
                    
                    if ($checkbox.length && $container.length) {
                        const status = $checkbox.is(':checked') ? 1 : 0;
                        const config = getModuleConfig(tableName);
                        updateDeleteIcon($container, itemId, status, config);
                    }
                    currentUpdatingItemId = null;
                    currentUpdatingTable = null;
                }, 100);
            }
        });
    });

    // Observe the status message container
    $(document).ready(function() {
        const statusMsgContainer = document.getElementById('status-msg');
        if (statusMsgContainer) {
            statusMsgObserver.observe(statusMsgContainer, {
                childList: true,
                subtree: true
            });
        }

        // Initialize delete icons on page load
        initializeDeleteIcons();
    });

})(jQuery);

