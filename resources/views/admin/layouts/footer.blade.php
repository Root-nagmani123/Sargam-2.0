
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
  <!-- Block dark-layout clicks - always force light, never allow dark -->
  <script>
    (function() {
      'use strict';
      function forceLight() {
        document.documentElement.setAttribute('data-bs-theme', 'light');
        document.documentElement.style.colorScheme = 'light';
        localStorage.setItem('bsTheme', 'light');
      }
      document.addEventListener('click', function(e) {
        if (e.target.closest && e.target.closest('.dark-layout')) {
          e.preventDefault();
          e.stopImmediatePropagation();
          forceLight();
          return false;
        }
      }, true);
    })();
  </script>
  <!-- Ensure light mode persists after theme scripts -->
  <script>
    // Force light mode after theme scripts initialize
    (function() {
      'use strict';
      
      function forceLightMode() {
        document.documentElement.setAttribute('data-bs-theme', 'light');
        document.documentElement.style.colorScheme = 'light';
        if (document.body) {
          document.body.style.colorScheme = 'light';
          if (document.body.getAttribute('data-bs-theme') && 
              document.body.getAttribute('data-bs-theme') !== 'light') {
            document.body.setAttribute('data-bs-theme', 'light');
          }
        }
        
        // Force all Bootstrap CSS variables to light mode
        const root = document.documentElement;
        root.style.setProperty('--bs-body-bg', '#fff', 'important');
        root.style.setProperty('--bs-body-color', '#212529', 'important');
        root.style.setProperty('color-scheme', 'light', 'important');
      }
      
      // Run immediately
      forceLightMode();
      
      // Run on DOMContentLoaded
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceLightMode);
      } else {
        forceLightMode();
      }
      
      // Override any theme changes with aggressive observer
      const observer = new MutationObserver(function(mutations) {
        let needsFix = false;
        mutations.forEach(function(mutation) {
          if (mutation.attributeName === 'data-bs-theme') {
            const htmlTheme = document.documentElement.getAttribute('data-bs-theme');
            const bodyTheme = document.body ? document.body.getAttribute('data-bs-theme') : null;
            if (htmlTheme !== 'light' || (bodyTheme && bodyTheme !== 'light')) {
              needsFix = true;
            }
          }
        });
        if (needsFix) {
          forceLightMode();
        }
      });
      
      observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme']
      });
      
      if (document.body) {
        observer.observe(document.body, {
          attributes: true,
          attributeFilter: ['data-bs-theme']
        });
      }
      
      // Periodic check as ultimate fallback (2s)
      setInterval(forceLightMode, 2000);
    })();
  </script>
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
  <script src="https://bootstrapdemos.adminmart.com/matdash/dist/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
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
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script srx="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://bootstrapdemos.adminmart.com/matdash/dist/assets/js/datatable/datatable-advanced.init.js"></script>


  @yield('scripts')
