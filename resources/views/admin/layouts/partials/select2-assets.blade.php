{{-- Select2 stylesheets, in the one order that works.

     Select2's JS is already global (footer.blade.php:66) and
     public/js/dropdown-search.js (footer.blade.php:73) turns any
     `select.select2` / `[data-searchable="true"]` into a searchable dropdown on
     DOMContentLoaded — so a page only ever needs the CSS.

     Order is load-bearing:
       1. select2.min.css   — the widget's own layout
       2. select2-theme.css — repaints it as a Bootstrap .form-select
       3. select2-sargam.css — resizes it 44px -> 40px for the admin grid chrome

     @include this at the TOP of a page's @push('styles'), before the module
     stylesheet, so the module keeps the last word.

     See docs/new-design-index-page.md §2 "Every dropdown is searchable". --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}?v={{ @filemtime(public_path('css/select2-theme.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/select2-sargam.css') }}?v={{ @filemtime(public_path('css/select2-sargam.css')) ?: time() }}">
