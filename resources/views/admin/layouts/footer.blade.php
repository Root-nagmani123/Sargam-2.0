
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
    // Global DataTables defaults + auto-init + Bootstrap 5 presentation
    (function() {
      function styleDataTableUi(settings) {
        var wrapper = settings && settings.nTableWrapper ? settings.nTableWrapper : null;
        if (!wrapper) return;
        var $wrapper = $(wrapper);

        // DataTables v1 + v2 selectors
        $wrapper.find('.dataTables_length label, .dt-length label')
          .addClass('d-inline-flex align-items-center gap-2 mb-0');

        $wrapper.find('.dataTables_filter label, .dt-search label')
          .addClass('d-inline-flex align-items-center gap-2 mb-0');

        $wrapper.find('.dataTables_length select, .dt-length select')
          .addClass('form-select form-select-sm')
          .attr('aria-label', 'Rows per page');

        $wrapper.find('.dataTables_filter input, .dt-search input')
          .addClass('form-control form-control-sm')
          .attr('placeholder', 'Search records...')
          .attr('aria-label', 'Search records');

        $wrapper.find('.dataTables_info, .dt-info').addClass('text-muted small');
        $wrapper.find('.dataTables_paginate .pagination, .dt-paging .pagination')
          .addClass('pagination-sm mb-0 justify-content-md-end');
      }

      function hasExternalPagination($table) {
        var $container = $table.closest('.card-body, .modal-body, .container-fluid, .tab-pane');
        if (!$container.length) {
          $container = $table.parent();
        }

        return $container.find('.pagination').filter(function() {
          return $(this).closest('.dataTables_wrapper').length === 0;
        }).length > 0;
      }

      function shouldAutoInit($table) {
        if (!$table || !$table.length) return false;
        if (!$table.find('thead th').length) return false;
        if ($table.closest('.dataTables_wrapper').length) return false;
        if ($table.is('.no-datatable, .skip-datatable, [data-no-datatable], [data-datatable="false"]')) return false;
        if ($.fn.DataTable.isDataTable($table[0])) return false;

        var isOptIn =
          $table.closest('.datatables').length > 0 ||
          $table.is('.datatable, .js-datatable, .dataTable, [data-datatable]');

        if (!isOptIn) return false;
        if (hasExternalPagination($table) && !$table.is('[data-force-datatable="true"]')) return false;

        return true;
      }

      function autoInitDataTables(scope) {
        if (!(window.jQuery && $.fn && $.fn.dataTable)) return;

        var $scope = scope ? $(scope) : $(document);
        $scope.find('table').each(function() {
          var $table = $(this);
          if (!shouldAutoInit($table)) return;

          var pageLength = parseInt($table.attr('data-page-length'), 10);
          if (isNaN(pageLength) || pageLength <= 0) {
            pageLength = 10;
          }

          $table.DataTable({
            order: [],
            pageLength: pageLength
          });
        });
      }

      try {
        if (window.jQuery && $.fn && $.fn.dataTable) {
          $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            lengthChange: true,
            pagingType: 'simple_numbers',
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom:
              '<"row g-2 align-items-center mb-2"<"col-12 col-md-6 d-flex align-items-center"l><"col-12 col-md-6 d-flex justify-content-md-end"f>>' +
              'rt' +
              '<"row g-2 align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7 d-flex justify-content-md-end"p>>',
            language: {
              search: '',
              searchPlaceholder: 'Search records...',
              lengthMenu: 'Show _MENU_ entries',
              info: 'Showing _START_ to _END_ of _TOTAL_ entries',
              infoEmpty: 'No entries to show',
              infoFiltered: '(filtered from _MAX_ total entries)',
              paginate: {
                previous: '<span aria-hidden="true">&lsaquo;</span>',
                next: '<span aria-hidden="true">&rsaquo;</span>'
              }
            }
          });

          $(document).on('init.dt', function(e, settings) {
            styleDataTableUi(settings);
          });

          $(document).on('draw.dt', function(e, settings) {
            styleDataTableUi(settings);
          });

          // Run after all per-page scripts have executed (including pushed scripts).
          window.addEventListener('load', function() {
            autoInitDataTables(document);
          });

          // Optional hook for AJAX-rendered HTML chunks.
          window.SargamDataTables = window.SargamDataTables || {};
          window.SargamDataTables.autoInit = autoInitDataTables;
          window.SargamDataTables.style = styleDataTableUi;

          document.addEventListener('sargam:datatable:refresh', function(evt) {
            var scope = evt && evt.detail && evt.detail.scope ? evt.detail.scope : document;
            autoInitDataTables(scope);
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
