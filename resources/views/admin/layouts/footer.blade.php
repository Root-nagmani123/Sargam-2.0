
  <script src="{{asset('admin_assets/js/vendor.min.js')}}"></script>
  <!-- Import Js Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <!-- Immediately intercept Bootstrap's theme detection -->
  <script>
    (function() {
      'use strict';
      // Force light mode immediately after Bootstrap loads
      document.documentElement.setAttribute('data-bs-theme', 'light');
      
      // Override Bootstrap's getTheme function if it exists
      if (window.bootstrap) {
        // Bootstrap 5.3+ uses getTheme() method
        const originalGetTheme = window.bootstrap.getTheme || function() {
          const theme = document.documentElement.getAttribute('data-bs-theme');
          if (theme) return theme;
          return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };
        
        window.bootstrap.getTheme = function() {
          return 'light';
        };
      }
    })();
  </script>
  <script src="{{asset('admin_assets/libs/simplebar/dist/simplebar.min.js')}}"></script>
  <!-- Force light mode before theme scripts load -->
  <script>
    // Ensure light mode is set before theme initialization
    // This runs after Bootstrap loads to reinforce light mode
    (function() {
      'use strict';
      document.documentElement.setAttribute('data-bs-theme', 'light');
      
      // Override Bootstrap's theme detection if it hasn't been overridden yet
      if (window.bootstrap && window.bootstrap.getTheme) {
        const originalGetTheme = window.bootstrap.getTheme;
        window.bootstrap.getTheme = function() {
          return 'light';
        };
      }
    })();
  </script>
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
  <!-- jQuery DataTables -->
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <script src="{{asset('js/dropdown-search.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
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
  <script>
    // Auto-initialize DataTables for any table using the `.datatable` class
    (function() {
      function parseBool(value, defaultValue) {
        if (typeof value !== 'string' || value.trim() === '') return defaultValue;
        const normalized = value.trim().toLowerCase();
        if (['true', '1', 'yes', 'on'].includes(normalized)) return true;
        if (['false', '0', 'no', 'off'].includes(normalized)) return false;
        return defaultValue;
      }

      function parseIntSafe(value, defaultValue) {
        const n = parseInt(value, 10);
        return Number.isFinite(n) ? n : defaultValue;
      }

      function parseOrder(value, fallback) {
        if (!value || typeof value !== 'string') return fallback;
        try {
          const parsed = JSON.parse(value);
          if (Array.isArray(parsed) && parsed.length) return parsed;
        } catch (e) {}
        return fallback;
      }

      function initAutoDataTables() {
        try {
          if (!(window.jQuery && $.fn && $.fn.dataTable)) return;

          $('table.datatable').each(function() {
            if ($.fn.dataTable.isDataTable(this)) return;

            const $table = $(this);
            const showExport = parseBool($table.attr('data-export'), true);
            const pageLength = parseIntSafe($table.attr('data-page-length'), 10);
            const enablePaging = parseBool($table.attr('data-paging'), true);
            const enableSearching = parseBool($table.attr('data-searching'), true);
            const enableOrdering = parseBool($table.attr('data-ordering'), true);
            const enableInfo = parseBool($table.attr('data-info'), true);
            const order = parseOrder($table.attr('data-order'), [[0, 'asc']]);
            const rawLengthStyle = ($table.attr('data-length-style') || 'pill').toLowerCase();
            const allowedLengthStyles = ['pill', 'underline', 'minimal', 'boxed'];
            const lengthStyle = allowedLengthStyles.includes(rawLengthStyle) ? rawLengthStyle : 'pill';

            const hasButtons = !!($.fn.dataTable && $.fn.dataTable.Buttons);
            const tableOptions = {
              responsive: true,
              autoWidth: false,
              pageLength: pageLength,
              lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
              order: order,
              paging: enablePaging,
              searching: enableSearching,
              ordering: enableOrdering,
              info: enableInfo,
              language: {
                search: 'Search:',
                searchPlaceholder: 'Type to filter...'
              },
              initComplete: function() {
                try {
                  $(this.api().table().container()).addClass('dt-length-style-' + lengthStyle);
                } catch (e) {}
              }
            };

            if (hasButtons && showExport) {
              tableOptions.dom = "<'row mb-3'<'col-md-8 d-flex align-items-center gap-2 flex-wrap'Bl><'col-md-4'f>>" +
                                 "<'row'<'col-12'tr>>" +
                                 "<'row mt-3'<'col-md-5'i><'col-md-7'p>>";
              tableOptions.buttons = [
                { extend: 'copyHtml5', className: 'btn btn-outline-primary btn-sm' },
                { extend: 'csvHtml5', className: 'btn btn-outline-primary btn-sm' },
                { extend: 'excelHtml5', className: 'btn btn-outline-primary btn-sm' },
                { extend: 'pdfHtml5', className: 'btn btn-outline-primary btn-sm' },
                { extend: 'print', className: 'btn btn-outline-primary btn-sm' }
              ];
            } else {
              tableOptions.dom = "<'row mb-3'<'col-md-6'l><'col-md-6'f>>" +
                                 "<'row'<'col-12'tr>>" +
                                 "<'row mt-3'<'col-md-5'i><'col-md-7'p>>";
            }

            $table.DataTable(tableOptions);
          });
        } catch (e) {
          console.warn('Auto DataTable initialization failed:', e);
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoDataTables);
      } else {
        initAutoDataTables();
      }

      // Re-run when Bootstrap tabs are shown (for lazily rendered content)
      document.addEventListener('shown.bs.tab', function() {
        initAutoDataTables();
      });
    })();
  </script>
  <script src="{{asset('admin_assets/js/datatable/datatable-basic.init.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/repeater-init.js')}}"></script>
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <script src="{{asset('admin_assets/js/plugins/toastr-init.js')}}"></script>
  <script src="{{asset('admin_assets/js/routes.js')}}"></script>
  <!-- SweetAlert2 must be loaded before custom.js (status-toggle confirmation uses Swal.fire) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{asset('admin_assets/js/custom.js')}}"></script>
  <script src="{{asset('admin_assets/js/status-toggle-delete.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-steps/build/jquery.steps.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-validation/dist/jquery.validate.min.js')}}"></script>
  <script src="{{ asset('admin_assets/js/prism.min.js') }}"></script>
  <script src="{{ asset('admin_assets/js/dual-listbox.js') }}"></script>
  <script src="{{ asset('admin_assets/js/validations.js') }}"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://bootstrapdemos.adminmart.com/matdash/dist/assets/js/datatable/datatable-advanced.init.js"></script>


  @yield('scripts')
