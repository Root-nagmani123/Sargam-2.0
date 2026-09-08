{{-- Shared Select2 wiring for the Club/ Society module.

     Include once per page. Every <select class="cs-select2"> gets a searchable
     Select2 control. select2.full.min.js is already loaded globally by
     admin/layouts/footer.blade.php, so only the stylesheet is added here. --}}

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    /* select2-theme.css sizes the control at 44px; the programme-dt toolbar
       around it is 40px, so bring Select2 back in line inside the toolbar. */
    .programme-dt-filter-select .select2-container--default .select2-selection--single {
        height: 40px;
    }

    .programme-dt-filter-select .select2-container {
        width: 100% !important;
    }

    /* Inside a modal the control should fill its grid column. */
    .modal .select2-container {
        width: 100% !important;
    }

    /* Select2 renders its dropdown at the end of <body> by default; when it is
       parented to a modal it must sit above the modal (Bootstrap uses 1055). */
    .select2-container--open {
        z-index: 1060;
    }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        if (!$.fn.select2) {
            return; // Select2 missing — leave the native selects working.
        }

        $('select.cs-select2').each(function () {
            var $sel = $(this);

            if ($sel.data('select2')) {
                return; // already initialised
            }

            // Use the existing blank option as the placeholder so the control
            // reads the same as it did before ("Course Name", "Status", ...).
            var placeholder = ($sel.find('option[value=""]').first().text() || 'Select').trim();

            var options = {
                width: '100%',
                placeholder: placeholder,
                allowClear: false
            };

            // A Select2 inside a modal must render its dropdown within that
            // modal, or the panel is clipped and its search box cannot be typed
            // into (Bootstrap traps focus inside the modal).
            var $modal = $sel.closest('.modal');
            if ($modal.length) {
                options.dropdownParent = $modal;
            }

            $sel.select2(options);
        });

        /* Programmatic changes (Remove Filter, the Active/Archived pills
           repopulating the course list) must be pushed into the widget.
           `change.select2` redraws Select2 WITHOUT firing the page's own
           change handlers, which would otherwise re-enter and reload twice. */
        window.csSelect2Refresh = function (selector) {
            var $el = $(selector);
            if ($el.length && $el.data('select2')) {
                $el.trigger('change.select2');
            }
            return $el;
        };
    });
</script>
@endpush
