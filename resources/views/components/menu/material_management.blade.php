{{--
    Anonymous Blade component: <x-menu.material_management />

    Renders nothing, deliberately.

    resources/views/admin/layouts/sidebar/material.blade.php references this
    component, and resources/views/faculty/layouts/sidebar.blade.php includes that
    partial, so every page extending faculty.layouts.master died with
    "Unable to locate a class or view for component [menu.material_management]".
    GET /faculty_dashboard returned HTTP 500 on main because of it.

    Verified rather than assumed, at origin/main:
      - no material_management.blade.php exists anywhere under resources/views
      - it is the ONLY <x-menu.*> reference in the view tree that does not resolve;
        the other twelve all have a file
      - at baseline ec86a7c the material sidebar partial was a 0-byte file, so the
        Material pane rendered no menu and raised no error. The themed rewrite grew
        it to 6,128 bytes and introduced this reference.

    An empty component is therefore the baseline behaviour, not a stub that papers
    over a missing feature: general.blade.php and setup_academic.blade.php are both
    0-byte files beside it for the same reason. Populate this only when a real
    material / purchase-order menu is deliberately introduced and product-reviewed.
--}}
