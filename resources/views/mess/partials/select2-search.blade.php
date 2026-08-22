{{--
    Searchable dropdowns for the Mess module.

    Select2 is the app's own convention — 45 views use it and its JS is already
    loaded for every admin page in admin/layouts/footer.blade.php, so this adds
    behaviour, not a dependency. The shared css/select2-theme.css already paints
    the control in the design system's vocabulary (44px, 8px radius, --ds-line,
    chevron caret, z-index above a modal); only its width/height block is scoped
    to the Medical Exemption pages, so the Mess sizing is added below.

    Include once per page that has native <select> controls:
        @include('mess.partials.select2-search')

    On a page where Choices owns the modals but the toolbar is still native,
    pass a selector so only that part is enhanced — otherwise both libraries
    race for the same control on `shown.bs.modal`:
        @include('mess.partials.select2-search', ['only' => '#poFilterForm'])

    What it deliberately leaves alone:

      • Anything Choices.js owns. Selling Voucher, Selling Voucher with Date
        Range, Purchase Order and Store Allocation run a bespoke Choices
        integration — exact-word token search, fixed-position dropdowns that
        survive a scrolling modal, tab-to-select. Those are already searchable
        and re-implementing them on Select2 would lose behaviour, so they are
        skipped by `.choices`/`[data-choice]` detection rather than fought with.

      • DataTables' own "Show _N_ entries" select, which the global grid enhancer
        relocates into the footer chrome.

      • Any select marked `data-no-select2`.
--}}
@once
@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ @filemtime(public_path('css/select2-theme.css')) ?: time() }}">
<style>
    /* ── Sizing, per context ─────────────────────────────────────────────
       styles.css carries a global `body .select2-container--default
       .select2-selection--single { height: 40px }` that outranks
       select2-theme.css's 44px, so every Select2 in the app is 40px. That is
       correct in a filter pill row and wrong in a Mess form, where the text
       inputs next to it are 44px — a dropdown 4px short of its neighbours is
       exactly the mismatch that made the Purchase Order toolbar look broken.
       These selectors carry three classes, so they beat the global `body` rule
       without !important. */
    #main-content .select2-container { width: 100% !important; }

    /* Form fields: match the 44px control the Mess modals and pages use. */
    #main-content .select2-container--default .select2-selection--single,
    #main-content .select2-container--default .select2-selection--single .select2-selection__rendered,
    #main-content .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }

    /* Filter pills: match the 40px row instead. */
    #main-content .programme-dt-filter-select .select2-container--default .select2-selection--single,
    #main-content .programme-dt-filter-select .select2-container--default .select2-selection--single .select2-selection__rendered,
    #main-content .programme-dt-filter-select .select2-container--default .select2-selection--single .select2-selection__arrow,
    #main-content .sv-filter-item .select2-container--default .select2-selection--single,
    #main-content .sv-filter-item .select2-container--default .select2-selection--single .select2-selection__rendered,
    #main-content .sv-filter-item .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: var(--ds-control-h, 40px);
    }

    /* The native <select> stays in the DOM for form submission. Mess pages give
       .form-select a min-height, which would otherwise stretch the hidden
       element and drag the whole row taller. */
    #main-content select.select2-hidden-accessible {
        min-height: 0 !important;
        height: 1px !important;
        padding: 0 !important;
    }

    /* Inside a modal the panel is appended to the modal itself (see
       dropdownParent below), so it inherits the modal's stacking context. */
    .modal .select2-container--open { z-index: 1065; }

    /* A disabled control should read as disabled, not just refuse clicks. */
    #main-content .select2-container--disabled .select2-selection--single {
        background-color: var(--bs-secondary-bg, #e9ecef);
        color: #667085;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Optional scope: when set, only selects inside this selector are enhanced.
    var ONLY = @json($only ?? null);

    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
        return;
    }

    var $ = window.jQuery;

    // Fewer options than this and a search box is clutter rather than help —
    // a two-option Status filter does not need one. Long lists (the Item
    // Category filter spans four figures of subcategories) always get it.
    var SEARCH_THRESHOLD = 8;

    function skip(el) {
        return el.classList.contains('select2-hidden-accessible')   // already done
            || el.hasAttribute('data-no-select2')
            || el.closest('.choices') !== null                      // Choices owns it
            || el.hasAttribute('data-choice')
            || el.classList.contains('choices__input')
            || el.closest('.dataTables_length') !== null            // grid chrome
            || el.closest('.mess-col-manager-dropdown') !== null;
    }

    // `.mess-select2` on the page container is the opt-in marker; #main-content is
    // the boundary we scan. It has to be the wider of the two, because these views
    // close their .container-fluid and then render the Add / Edit modals after it
    // as siblings — scoping to the container skipped those modals' dropdowns.
    // #main-content still leaves the header and sidebar alone, which is the point:
    // the layout's language switcher is a <select> too, and enhancing global chrome
    // on Mess pages only would make the header change shape as you navigate.
    var SCOPE = ONLY || (document.querySelector('.mess-select2') ? '#main-content' : null);

    function roots(root) {
        if (root) return [root];
        if (!SCOPE) return [];
        return Array.prototype.slice.call(document.querySelectorAll(SCOPE));
    }

    function enhance(root) {
        roots(root).forEach(function (scope) {
        $(scope).find('select').each(function () {
            if (skip(this)) return;

            var $sel = $(this);
            var modal = this.closest('.modal');
            var optionCount = this.options ? this.options.length : 0;

            // A placeholder only makes sense when the first option is the empty
            // "Select …" the Mess forms all use.
            var first = this.options && this.options[0];
            var placeholder = (first && first.value === '') ? first.text : null;

            $sel.select2({
                width: '100%',
                // Append into the modal, or the panel is clipped by the modal's
                // own overflow and the search box cannot take focus.
                dropdownParent: modal ? $(modal) : $(document.body),
                minimumResultsForSearch: optionCount >= SEARCH_THRESHOLD ? 0 : Infinity,
                placeholder: placeholder || undefined,
                allowClear: false,
                theme: 'default',
            });

            // Select2 raises a jQuery event. Inline onchange="" attributes are
            // reached by that, but listeners bound with addEventListener are not
            // — and that is how the Mess filters are wired, so without this the
            // auto-apply toolbar silently stops applying.
            $sel.on('select2:select select2:unselect select2:clear', function () {
                this.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhance();

        // Modal fields are in the DOM from the start, but a modal that has never
        // been opened has no layout, and Select2 measures on init. Re-running on
        // show is cheap (already-enhanced controls are skipped) and gets the
        // width right the first time the user sees it.
        if (!ONLY) {
            document.querySelectorAll('#main-content .modal').forEach(function (modalEl) {
                modalEl.addEventListener('shown.bs.modal', function () { enhance(modalEl); });
            });
        }
    });

    // Rows added after load (line items) need enhancing too.
    window.messSelect2Enhance = enhance;
})();
</script>
@endpush
@endonce
