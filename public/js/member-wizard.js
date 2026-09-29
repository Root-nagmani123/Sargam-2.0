/*
 * Member add / edit wizard — presentation layer on top of jQuery Steps.
 *
 * The plugin owns step changing, per-step validation and submit (that logic
 * stays in admin/member/create.blade.php and edit.blade.php). This file only
 * supplies the chrome the design asks for and drives the plugin through its
 * public API:
 *
 *   - numbers the rail circles (the plugin renders "1." as text; the CSS clips
 *     it and re-draws the digit, or a tick once the step is done)
 *   - replaces the plugin's Previous/Next/Finish list with the Cancel + Next
 *     pair, where the primary button becomes the finish label on the last step
 *   - greys an unpicked <select> so it reads like an input placeholder
 *
 * Config comes off the wizard element:
 *   data-finish-label  caption for the primary button on the last step
 *   data-cancel-url    where Cancel goes (a plain link, so the page's own
 *                      unsaved-changes guard intercepts it like any other)
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    function stampStepNumbers($wizard) {
        $wizard.children('.steps').find('> ul > li').each(function (index) {
            $(this).find('.number').attr('data-step', index + 1);
        });
    }

    function markPlaceholderSelects($wizard) {
        $wizard.find('select.form-select').each(function () {
            $(this).toggleClass('is-placeholder', this.value === '');
        });
    }

    function stepCount($wizard) {
        return $wizard.children('.steps').find('> ul > li').length;
    }

    function currentIndex($wizard) {
        try {
            return $wizard.steps('getCurrentIndex');
        } catch (e) {
            // Fall back to the rail's own state if the plugin isn't ready yet.
            return $wizard.children('.steps').find('> ul > li').index($wizard.find('li.current'));
        }
    }

    window.MemberWizardUI = {
        /**
         * Lock or release the wizard's primary button around an in-flight
         * submit. Called by the page that owns the AJAX, because only it knows
         * when the request has finished.
         */
        setBusy: function (selector, busy) {
            var $wizard = $(selector);
            var $primary = $wizard.data('mbrwPrimary');
            if (!$primary || !$primary.length) {
                return;
            }
            $primary.prop('disabled', !!busy).toggleClass('mbrw-btn--busy', !!busy);
            if (busy) {
                $primary.data('mbrwLabel', $primary.text()).text('Saving\u2026');
            } else if ($primary.data('mbrwLabel')) {
                $primary.text($primary.data('mbrwLabel'));
            }
        },

        attach: function (wizard) {
            var $wizard = $(wizard);
            if (!$wizard.length || $wizard.data('mbrwAttached')) {
                return;
            }
            $wizard.data('mbrwAttached', true);

            var finishLabel = $wizard.data('finish-label') || 'Submit';
            var nextLabel = $wizard.data('next-label') || 'Next';
            var cancelUrl = $wizard.data('cancel-url') || '';

            var $bar = $('<div class="mbrw-actions"></div>');
            var $cancel = $('<a class="btn mbrw-btn mbrw-btn-cancel"></a>')
                .attr('href', cancelUrl || 'javascript:void(0)')
                .text('Cancel');
            var $primary = $('<button type="button" class="btn mbrw-btn mbrw-btn-primary"></button>')
                .text(nextLabel);

            $bar.append($cancel).append($primary);
            $wizard.children('.actions').append($bar);

            // The submit is an INSERT of an identity record, and employee_master
            // has an index on emp_id but no unique constraint on it, so a second
            // click while the first POST is in flight creates a duplicate member
            // that nothing downstream will reject. The guard lives here, in the
            // shared chrome, so create and edit both inherit it.
            $wizard.data('mbrwPrimary', $primary);

            $primary.on('click', function () {
                if ($primary.prop('disabled')) {
                    return;
                }

                if (currentIndex($wizard) >= stepCount($wizard) - 1) {
                    $wizard.steps('finish');
                } else {
                    $wizard.steps('next');
                }
            });

            var sync = function () {
                stampStepNumbers($wizard);
                markPlaceholderSelects($wizard);
                $primary.text(
                    currentIndex($wizard) >= stepCount($wizard) - 1 ? finishLabel : nextLabel
                );
            };

            // The plugin exposes callbacks, not events, and the step bodies also
            // arrive later over AJAX — watching the DOM keeps the chrome correct
            // without the page having to call back into here.
            if (window.MutationObserver) {
                var pending = null;
                var observer = new MutationObserver(function () {
                    if (pending) {
                        return;
                    }
                    pending = window.setTimeout(function () {
                        pending = null;
                        sync();
                    }, 0);
                });
                observer.observe($wizard[0], {
                    subtree: true,
                    childList: true,
                    attributes: true,
                    attributeFilter: ['class'],
                });
            }

            $wizard.on('change', 'select.form-select', function () {
                $(this).toggleClass('is-placeholder', this.value === '');
            });

            sync();
        },
    };
})(window.jQuery);
