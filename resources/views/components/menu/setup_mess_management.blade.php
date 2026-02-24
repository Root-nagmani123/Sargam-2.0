<nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-8" data-simplebar="">
    <ul class="sidebar-menu" id="sidebarnav">
        <!-- ======= MASTER DATA ======= -->
        <li class="sidebar-item"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseMasterData" role="button" aria-expanded="false" aria-controls="collapseMasterData">
                <span class="fw-bold">Master Data</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseMasterData">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.stores.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Store Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.vendors.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Vendor Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.itemcategories.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Category Item Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.itemsubcategories.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Subcategory Item Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.vendor-item-mappings.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Vendor Mapping</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.client-types.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Client Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.sub-stores.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Sub Store Master</span>
                </a>
            </li>
        </ul>


        <!-- ======= PURCHASE ORDER ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapsePurchaseOrder" role="button" aria-expanded="false"
                aria-controls="collapsePurchaseOrder">
                <span class="fw-bold">Purchase Order</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapsePurchaseOrder">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.purchaseorders.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Purchase Orders</span>
                </a>
            </li>
        </ul>

        <!-- ======= MATERIAL MANAGEMENT ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseMaterialManagement" role="button" aria-expanded="false" aria-controls="collapseMaterialManagement">
                <span class="fw-bold">Material Management</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseMaterialManagement">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.material-management.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Selling Voucher</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.selling-voucher-date-range.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Selling Voucher With Date Range</span>
                </a>
            </li>
        </ul>

        <!-- ======= BILLING & FINANCE ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseBillingFinance" role="button" aria-expanded="false" aria-controls="collapseBillingFinance">
                <span class="fw-bold">Billing & Finance</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseBillingFinance">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.process-mess-bills-employee.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Process Mess Bills</span>
                </a>
            </li>
        </ul>

        <!-- ======= REPORTS ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseReports" role="button" aria-expanded="false" aria-controls="collapseReports">
                <span class="fw-bold">Reports</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseReports">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.stock-purchase-details') }}">
                    <span class="hide-menu small text-nowrap">Stock Purchase Details Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.stock-summary') }}">
                    <span class="hide-menu small text-nowrap">Stock Summary Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.category-wise-print-slip') }}">
                    <span class="hide-menu small text-nowrap">Category-wise Print Slip</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.stock-balance-till-date') }}">
                    <span class="hide-menu small text-nowrap">Stock Balance as of Till Date</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('admin.mess.reports.purchase-sale-quantity') ? 'active' : '' }}" href="{{ route('admin.mess.reports.purchase-sale-quantity') }}">
                    <span class="hide-menu small text-nowrap">Item Report</span>
                </a>
            </li>
        </ul>

        <!-- ======= OTHER MODULES ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseOtherModules" role="button" aria-expanded="false" aria-controls="collapseOtherModules">
                <span class="fw-bold">Other Modules</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseOtherModules">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.storeallocations.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Mess Store Allocation</span>
                </a>
            </li>
        </ul>
    </ul>
</nav>