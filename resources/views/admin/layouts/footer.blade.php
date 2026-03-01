
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
  <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js" crossorigin="anonymous"></script>
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
    // Global DataTables defaults + Bootstrap 5 presentation for controls
    (function() {
      try {
        if (window.jQuery && $.fn && $.fn.dataTable) {
          $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            responsive: true,
            pagingType: 'simple_numbers',
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
              search: '',
              searchPlaceholder: 'Search records...',
              lengthMenu: '_MENU_ per page',
              info: 'Showing _START_ to _END_ of _TOTAL_ entries',
              infoEmpty: 'No entries to show',
              infoFiltered: '(filtered from _MAX_ total entries)',
              paginate: {
                previous: '<span aria-hidden="true">&lsaquo;</span>',
                next: '<span aria-hidden="true">&rsaquo;</span>'
              }
            }
          });

          var styleDataTableUi = function(settings) {
            var wrapper = settings && settings.nTableWrapper ? settings.nTableWrapper : null;
            if (!wrapper) return;
            var $wrapper = $(wrapper);

            // DataTables v1 + v2 selectors
            $wrapper.find('.dataTables_length select, .dt-length select')
              .addClass('form-select form-select-sm')
              .attr('aria-label', 'Rows per page');

            $wrapper.find('.dataTables_filter input, .dt-search input')
              .addClass('form-control form-control-sm')
              .attr('placeholder', 'Search records...')
              .attr('aria-label', 'Search records');

            $wrapper.find('.dataTables_info, .dt-info').addClass('text-muted small');
            $wrapper.find('.dataTables_paginate .pagination, .dt-paging .pagination')
              .addClass('pagination-sm mb-0');
          };

          $(document).on('init.dt', function(e, settings) {
            styleDataTableUi(settings);
          });

          $(document).on('draw.dt', function(e, settings) {
            styleDataTableUi(settings);
          });
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


  @yield('scripts')
