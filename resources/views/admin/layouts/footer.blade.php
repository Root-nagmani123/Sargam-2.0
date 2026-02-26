
  <script src="{{asset('admin_assets/js/vendor.min.js')}}"></script>
  <!-- Import Js Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="{{asset('admin_assets/libs/simplebar/dist/simplebar.min.js')}}"></script>
  <script src="{{asset('admin_assets/js/theme/app.init.js')}}"></script>
  <script src="{{asset('admin_assets/js/theme/theme.js')}}"></script>
  <script src="{{asset('admin_assets/js/theme/app.min.js')}}"></script>
  <script src="{{asset('admin_assets/js/theme/sidebarmenu.js')}}"></script>

  <!-- solar icons -->
  <script src="{{asset('admin_assets/css/iconify-icon.min.js')}}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

  <!-- highlight.js (code view) -->
  <script src="{{asset('admin_assets/js/highlights/highlight.min.js')}}"></script>
  <script>
  hljs.initHighlightingOnLoad();


  document.querySelectorAll("pre.code-view > code").forEach((codeBlock) => {
    codeBlock.textContent = codeBlock.innerHTML;
  });
</script>
  <script src="{{asset('admin_assets/libs/jquery-steps/build/jquery.steps.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-validation/dist/jquery.validate.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/select2/dist/js/select2.full.min.js')}}"></script>
  <script src="{{asset('js/dropdown-search.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
  <!-- DataTables 1.13.8 + Bootstrap 5 (latest) -->
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
  <!-- DataTables Responsive plugin -->
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <!-- Optional: Responsive CSS (can be moved to <head> if desired) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" />
  <script>
    // Global DataTables defaults: disable auto column width to reduce header jitter
    (function() {
      try {
        if (window.jQuery && $.fn && $.fn.dataTable) {
          $.extend(true, $.fn.dataTable.defaults, { autoWidth: false, responsive: true });
        }
      } catch (e) {
        console.warn('Failed to set DataTables defaults:', e);
      }
    })();
  </script>
  <script src="{{asset('admin_assets/js/datatable/datatable-basic.init.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/repeater-init.js')}}"></script>
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <!-- <script src="{{asset('admin_assets/js/pages/calendar.init.js')}}"></script> -->
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <!-- <script src="{{asset('admin_assets/js/apps/contact.js')}}"></script> -->
  <script src="{{asset('admin_assets/js/plugins/toastr-init.js')}}"></script>
  <script src="{{asset('admin_assets/js/routes.js')}}"></script>
  <script src="{{asset('admin_assets/js/custom.js')}}"></script>
  <script src="{{asset('admin_assets/js/status-toggle-delete.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-steps/build/jquery.steps.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-validation/dist/jquery.validate.min.js')}}"></script>
  <script src="{{ asset('admin_assets/js/prism.min.js') }}"></script>
  <script src="{{ asset('admin_assets/js/dual-listbox.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{ asset('admin_assets/js/validations.js') }}"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://bootstrapdemos.adminmart.com/matdash/dist/assets/js/datatable/datatable-advanced.init.js"></script>

  <!-- Choices.js (global searchable selects) -->
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Apply Choices.js to ALL selects by default.
      // Add class "no-search-select" on a <select> if you want to OPT OUT.
      const elements = document.querySelectorAll('select:not(.no-search-select)');

      elements.forEach(function (el) {
        if (el.dataset.choicesInitialized === 'true') {
          return;
        }

        new Choices(el, {
          searchEnabled: true,
          itemSelectText: '',
          removeItemButton: false,
          shouldSort: false,
          placeholder: true,
          placeholderValue: el.getAttribute('placeholder') || 'Select an option',
        });

        el.dataset.choicesInitialized = 'true';
      });
    });
  </script>


  @yield('scripts')
