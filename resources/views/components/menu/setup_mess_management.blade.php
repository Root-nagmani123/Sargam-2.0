@if(canSeeLowStockAlert())
<nav class="sidebar-nav sidebar-panel-menu d-block simplebar-scrollable-y" id="menu-right-setup-mini-9" data-simplebar="init"
    data-mess-module="{{ request()->is('admin/mess*') ? '1' : '0' }}">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper"><div class="simplebar-height-auto-observer"></div></div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content sidebar-panel-menu__content">
                        <p class="sidebar-panel-menu__title text-uppercase text-secondary small fw-semibold mb-3 px-1">MESS</p>
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapseMasterData" role="button" aria-expanded="false" aria-controls="collapseMasterData"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">inventory_2</i><span class="hide-menu small small-sm-normal text-nowrap">Master Data</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapseMasterData"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.stores.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Store Master</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.vendors.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Vendor Master</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.itemcategories.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Category Item Master</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.itemsubcategories.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Subcategory Item Master</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.client-types.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Client Master</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.sub-stores.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Sub Store Master</span></a></li>
                            </ul></li></ul>
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapsePurchaseOrder" role="button" aria-expanded="false" aria-controls="collapsePurchaseOrder"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">shopping_cart</i><span class="hide-menu small small-sm-normal text-nowrap">Purchase Order</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapsePurchaseOrder"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0"><li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.purchaseorders.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Purchase Orders</span></a></li></ul></li></ul>
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapseMaterialManagement" role="button" aria-expanded="false" aria-controls="collapseMaterialManagement"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">storefront</i><span class="hide-menu small small-sm-normal text-nowrap">Material Management</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapseMaterialManagement"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.material-management.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Selling Voucher</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.selling-voucher-date-range.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Selling Voucher With Date Range</span></a></li>
                            </ul></li></ul>
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapseBillingFinance" role="button" aria-expanded="false" aria-controls="collapseBillingFinance"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">payments</i><span class="hide-menu small small-sm-normal text-nowrap">Billing & Finance</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapseBillingFinance"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.process-mess-bills-employee.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Process Mess Bills</span></a></li>
                                @if(canSeeMessSelfServiceSetup())<li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.mess.my-bills.index') ? 'active' : '' }}" href="{{ route('admin.mess.my-bills.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">My Mess Bills</span></a></li>@endif
                            </ul></li></ul>
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapseReports" role="button" aria-expanded="false" aria-controls="collapseReports"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">bar_chart</i><span class="hide-menu small small-sm-normal text-nowrap">Reports</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapseReports"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.reports.stock-purchase-details') }}"><span class="hide-menu small text-nowrap">Stock Purchase Details Report</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.reports.stock-summary') }}"><span class="hide-menu small text-nowrap">Stock Summary Report</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.reports.category-wise-print-slip') }}"><span class="hide-menu small text-nowrap">Sale Voucher Report</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.reports.stock-balance-till-date') }}"><span class="hide-menu small text-nowrap">Stock Balance as of Till Date</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.mess.reports.low-stock') ? 'active' : '' }}" href="{{ route('admin.mess.reports.low-stock') }}"><span class="hide-menu small text-nowrap">Low Stock Report</span></a></li>
                                <li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.mess.reports.purchase-sale-quantity') ? 'active' : '' }}" href="{{ route('admin.mess.reports.purchase-sale-quantity') }}"><span class="hide-menu small text-nowrap">Item Report</span></a></li>
                            </ul></li></ul>
                            <li class="sidebar-item mb-1"><a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2" data-bs-toggle="collapse" href="#collapseOtherModules" role="button" aria-expanded="false" aria-controls="collapseOtherModules"><span class="d-flex align-items-center gap-2 min-w-0"><i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">widgets</i><span class="hide-menu small small-sm-normal text-nowrap">Other Modules</span></span><i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon" aria-hidden="true">chevron_right</i></a></li>
                            <ul class="collapse list-unstyled mb-2" id="collapseOtherModules"><li class="sidebar-panel-submenu-tree"><ul class="list-unstyled mb-0"><li class="sidebar-item mb-1"><a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('admin.mess.storeallocations.index') }}"><span class="hide-menu small small-sm-normal text-nowrap">Mess Store Allocation</span></a></li></ul></li></ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>
@elseif(canSeeMessSelfServiceSetup())
@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-setup-mini-9',
    'panelMenuTitle' => 'MESS',
    'panelMenuClass' => 'sidebar-setup-mess-menu',
])
<li class="sidebar-item mb-1">
    <a class="sidebar-link d-flex align-items-center gap-2 rounded-2 px-3 py-2 {{ request()->routeIs('admin.mess.my-bills.index') ? 'active' : '' }}" href="{{ route('admin.mess.my-bills.index') }}">
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">receipt_long</i>
        <span class="hide-menu small small-sm-normal text-nowrap">My Mess Bills</span>
    </a>
</li>
@include('components.menu.partials.panel-shell-close')
@endif

@push('scripts')
<script>
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || e.shiftKey) return;

    var nav = document.getElementById('menu-right-setup-mini-9');
    if (!nav || nav.dataset.messModule !== '1') return;

    var active = document.activeElement;
    if (!active || !nav.contains(active)) return;

    // Only treat Tab as "Enter" for actionable sidebar items (not inputs)
    if (active.matches('a, button, [role="button"], [data-bs-toggle="collapse"]')) {
        e.preventDefault();
        try { active.click(); } catch (err) {}
    }
}, true);
</script>
@endpush