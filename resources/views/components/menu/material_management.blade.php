{{--
    Anonymous Blade component: <x-menu.material_management />

    Baseline parity restore (H-5):
    In the baseline (ec86a7c) the material sidebar partial
    (resources/views/admin/layouts/sidebar/material.blade.php) was an EMPTY file,
    so the faculty layout's Material / Purchase-Order pane rendered no menu and
    produced no error.

    The theme migration rewrote that partial to reference this component
    (<x-menu.material_management />) which did not exist, causing a fatal
    "Unable to locate a class or view for component [menu.material_management]"
    error on every page that extends faculty.layouts.master.

    This component intentionally renders nothing, exactly matching baseline
    behavior (empty material menu) while preserving the new themed sidebar shell.
    Populate it with the real material / purchase-order menu only when that
    feature is deliberately introduced and product-reviewed.
--}}
