
  <script src="{{asset('admin_assets/js/vendor.min.js')}}"></script>
  <!-- Import Js Files -->
  <script src="{{asset('admin_assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
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
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
  <script src="{{asset('admin_assets/libs/datatables.net/js/jquery.dataTables.min.js')}}"></script>
  <script src="{{asset('admin_assets/js/datatable/datatable-basic.init.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/repeater-init.js')}}"></script>
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <!-- <script src="{{asset('admin_assets/js/pages/calendar.init.js')}}"></script> -->
  <script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
  <!-- <script src="{{asset('admin_assets/js/apps/contact.js')}}"></script> -->
  <script src="{{asset('admin_assets/js/plugins/toastr-init.js')}}"></script>
  <script src="{{asset('admin_assets/js/routes.js')}}"></script>
  <script src="{{asset('admin_assets/js/custom.js')}}"></script>
  <script src="{{asset('admin_assets/js/forms/form-wizard.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-steps/build/jquery.steps.min.js')}}"></script>
  <script src="{{asset('admin_assets/libs/jquery-validation/dist/jquery.validate.min.js')}}"></script>
  <script src="{{ asset('admin_assets/js/prism.min.js') }}"></script>
  <script src="{{ asset('admin_assets/js/dual-listbox.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{ asset('admin_assets/js/validations.js') }}"></script>
  @yield('scripts')
