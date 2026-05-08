@if(canSeeLowStockAlert())
<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-9" data-simplebar="init"
    data-mess-module="{{ request()->is('admin/mess*') ? '1' : '0' }}">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Mess Management
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- Master Data (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseMasterData" role="button"
                                    aria-expanded="false" aria-controls="collapseMasterData">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">inventory_2</i>
                                        <span class="hide-menu">Master Data</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseMasterData">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.stores.index') }}">
                                        <span class="hide-menu">Store Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.vendors.index') }}">
                                        <span class="hide-menu">Vendor Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.itemcategories.index') }}">
                                        <span class="hide-menu">Category Item Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.itemsubcategories.index') }}">
                                        <span class="hide-menu">Subcategory Item Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.client-types.index') }}">
                                        <span class="hide-menu">Client Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.sub-stores.index') }}">
                                        <span class="hide-menu">Sub Store Master</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Purchase Order (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapsePurchaseOrder" role="button"
                                    aria-expanded="false" aria-controls="collapsePurchaseOrder">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">shopping_cart</i>
                                        <span class="hide-menu">Purchase Order</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapsePurchaseOrder">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.purchaseorders.index') }}">
                                        <span class="hide-menu">Purchase Orders</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Material Management (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseMaterialManagement" role="button"
                                    aria-expanded="false" aria-controls="collapseMaterialManagement">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">local_shipping</i>
                                        <span class="hide-menu">Material Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseMaterialManagement">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.material-management.index') }}">
                                        <span class="hide-menu">Selling Voucher</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.selling-voucher-date-range.index') }}">
                                        <span class="hide-menu">Selling Voucher With Date Range</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Billing & Finance (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseBillingFinance" role="button"
                                    aria-expanded="false" aria-controls="collapseBillingFinance">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">payments</i>
                                        <span class="hide-menu">Billing & Finance</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseBillingFinance">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.process-mess-bills-employee.index') }}">
                                        <span class="hide-menu">Process Mess Bills</span>
                                    </a>
                                </li>
                                @if(canSeeMessSelfServiceSetup())
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.mess.my-bills.index') ? 'active' : '' }}" href="{{ route('admin.mess.my-bills.index') }}">
                                        <span class="hide-menu">My Mess Bills</span>
                                    </a>
                                </li>
                                @endif
                            </ul>

                            {{-- Reports (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseReports" role="button"
                                    aria-expanded="false" aria-controls="collapseReports">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">assessment</i>
                                        <span class="hide-menu">Reports</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseReports">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.reports.stock-purchase-details') }}">
                                        <span class="hide-menu">Stock Purchase Details Report</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.reports.stock-summary') }}">
                                        <span class="hide-menu">Stock Summary Report</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.reports.category-wise-print-slip') }}">
                                        <span class="hide-menu">Sale Voucher Report</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.reports.stock-balance-till-date') }}">
                                        <span class="hide-menu">Stock Balance as of Till Date</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.mess.reports.low-stock') ? 'active' : '' }}"
                                       href="{{ route('admin.mess.reports.low-stock') }}">
                                        <span class="hide-menu">Low Stock Report</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.mess.reports.purchase-sale-quantity') ? 'active' : '' }}" href="{{ route('admin.mess.reports.purchase-sale-quantity') }}">
                                        <span class="hide-menu">Item Report</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- Other Modules (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#collapseOtherModules" role="button"
                                    aria-expanded="false" aria-controls="collapseOtherModules">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">widgets</i>
                                        <span class="hide-menu">Other Modules</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="collapseOtherModules">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2" href="{{ route('admin.mess.storeallocations.index') }}">
                                        <span class="hide-menu">Mess Store Allocation</span>
                                    </a>
                                </li>
                            </ul>

                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: auto; height: 0px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar"
            style="height: 25px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
    </div>
</nav>
@elseif(canSeeMessSelfServiceSetup())
<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-setup-mini-9" data-simplebar="init"
    data-mess-module="{{ request()->is('admin/mess*') ? '1' : '0' }}">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Mess
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.mess.my-bills.index') ? 'active' : '' }}" href="{{ route('admin.mess.my-bills.index') }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">receipt_long</i>
                                    <span class="hide-menu">My Mess Bills</span>
                                </a>
                            </li>

                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: auto; height: 0px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar"
            style="height: 25px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
    </div>
</nav>
@endif

@push('scripts')
<script>
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || e.shiftKey) return;

    var nav = document.getElementById('menu-right-setup-mini-9');
    if (!nav || nav.dataset.messModule !== '1') return;

    var active = document.activeElement;
    if (!active || !nav.contains(active)) return;

    if (active.matches('a, button, [role="button"], [data-bs-toggle="collapse"]')) {
        e.preventDefault();
        try { active.click(); } catch (err) {}
    }
}, true);
</script>
@endpush
