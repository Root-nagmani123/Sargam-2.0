/**
 * Shared UI helpers for Mess master list pages (status toggle, delete control, modals).
 */
(function (window) {
    'use strict';

    function updateMessStatusBadge($row, isActive) {
        if (typeof jQuery === 'undefined') return;
        var $badge = jQuery($row).find('.mess-status-badge').first();
        if (!$badge.length) return;
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function buildMessDeleteControl(isActive, recordId, options) {
        if (typeof jQuery === 'undefined') return null;
        var $ = jQuery;
        options = options || {};
        var baseClass = 'mess-delete-btn programme-action-btn programme-action-btn--danger';
        var entityLabel = options.entityLabel || 'record';
        var destroyBaseUrl = options.destroyBaseUrl || '';
        var canDelete = !!options.canDelete;
        var confirmMessage = options.confirmMessage || ('Are you sure you want to delete this ' + entityLabel + '?');

        if (isActive) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'Cannot delete active ' + entityLabel,
                'aria-label': 'Delete ' + entityLabel
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        if (!canDelete) {
            return $('<button>', {
                type: 'button',
                class: baseClass,
                disabled: true,
                'aria-disabled': 'true',
                title: 'You do not have permission to delete',
                'aria-label': 'Delete ' + entityLabel
            }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        }

        var $form = $('<form>', {
            method: 'POST',
            action: destroyBaseUrl + '/' + recordId,
            class: 'd-inline mess-delete-form m-0'
        });
        $form.append('<input type="hidden" name="_token" value="' + ($('meta[name="csrf-token"]').attr('content') || '') + '">');
        $form.append('<input type="hidden" name="_method" value="DELETE">');
        var $btn = $('<button>', {
            type: 'submit',
            class: baseClass,
            title: 'Delete ' + entityLabel,
            'aria-label': 'Delete ' + entityLabel
        }).append('<i class="bi bi-trash" aria-hidden="true"></i>');
        $form.on('submit', function () {
            return confirm(confirmMessage);
        });
        $form.append($btn);
        return $form;
    }

    function updateMessDeleteControl($row, isActive, recordId, options) {
        if (typeof jQuery === 'undefined') return;
        var $group = jQuery($row).find('.mess-row-actions').first();
        if (!$group.length) return;
        $group.find('.mess-delete-form, .mess-delete-btn').remove();
        var $deleteControl = buildMessDeleteControl(isActive, recordId, options);
        if ($deleteControl) {
            $group.append($deleteControl);
        }
    }

    function bindMessStatusToggle(tableSelector, options) {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        $(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = $(this);
            var isActive = $toggle.is(':checked');
            var $row = $toggle.closest('tr');
            var recordId = $toggle.data('id');
            window.setTimeout(function () {
                updateMessStatusBadge($row, isActive);
                updateMessDeleteControl($row, isActive, recordId, options);
                $row.find('[data-status]').attr('data-status', isActive ? 'active' : 'inactive');
            }, 0);
        });
    }

    function moveModalsToBody(modalIds) {
        (modalIds || []).forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }

    function showMessModal(modalId) {
        var el = document.getElementById(modalId);
        if (!el || !window.bootstrap || !bootstrap.Modal) return;
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function hideMessModal(modalId) {
        var el = document.getElementById(modalId);
        if (!el || !window.bootstrap || !bootstrap.Modal) return;
        var instance = bootstrap.Modal.getInstance(el);
        if (instance) instance.hide();
    }

    function wireModalExclusivity(pairs) {
        (pairs || []).forEach(function (pair) {
            var createId = pair.create;
            var editId = pair.edit;
            var createEl = document.getElementById(createId);
            var editEl = document.getElementById(editId);
            if (createEl) {
                createEl.addEventListener('show.bs.modal', function () {
                    hideMessModal(editId);
                });
            }
            if (editEl) {
                editEl.addEventListener('show.bs.modal', function () {
                    hideMessModal(createId);
                });
            }
        });
    }

    window.MessMasterList = {
        updateMessStatusBadge: updateMessStatusBadge,
        buildMessDeleteControl: buildMessDeleteControl,
        updateMessDeleteControl: updateMessDeleteControl,
        bindMessStatusToggle: bindMessStatusToggle,
        moveModalsToBody: moveModalsToBody,
        showMessModal: showMessModal,
        hideMessModal: hideMessModal,
        wireModalExclusivity: wireModalExclusivity
    };
})(window);
