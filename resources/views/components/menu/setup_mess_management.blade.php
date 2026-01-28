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
                    <span class="hide-menu small small-sm-normal text-nowrap">Item Category Master</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.itemsubcategories.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Item Subcategory / Item Master</span>
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


        <!-- ======= MATERIAL MANAGEMENT ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseMaterialManagement" role="button" aria-expanded="false"
                aria-controls="collapseMaterialManagement">
                <span class="fw-bold">Material Management</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseMaterialManagement">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.materialrequests.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Material Requests</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.purchaseorders.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Purchase Orders</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.inboundtransactions.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Goods Receipt</span>
                </a>
            </li>
        </ul>

        <!-- ======= KITCHEN ISSUE MANAGEMENT ======= -->
        <li class="sidebar-item mt-2"
            style="background: #4077ad;
            border-radius: 30px 0px 0px 30px;
            width: 100%;
            box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
            min-width: 250px;">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#collapseKitchenIssue" role="button" aria-expanded="false" aria-controls="collapseKitchenIssue">
                <span class="fw-bold">Kitchen Issues</span>
                <i class="material-icons menu-icon material-symbols-rounded toggle-icon"
                    style="font-size: 24px; transition: transform 0.3s ease;">keyboard_arrow_right</i>
            </a>
        </li>
        <ul class="collapse list-unstyled ps-3" id="collapseKitchenIssue">
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.kitchen-issues.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">All Kitchen Issues</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.kitchen-issues.create') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Create New Issue</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.kitchen-issue-approvals.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Pending Approvals</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.kitchen-issues.bill-report') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Bill Reports</span>
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
                <a class="sidebar-link" href="{{ route('admin.mess.monthly-bills.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Monthly Bills</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.finance-bookings.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Finance Bookings</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.invoices.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Invoices</span>
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
                <a class="sidebar-link" href="{{ route('admin.mess.reports.items-list') }}">
                    <span class="hide-menu small text-nowrap">List of Items</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.mess-summary') }}">
                    <span class="hide-menu small text-nowrap">Mess/Store Summary</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.category-material') }}">
                    <span class="hide-menu small text-nowrap">Category Wise Material</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.pending-orders') }}">
                    <span class="hide-menu small text-nowrap">Pending Purchase Orders</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.payment-overdue') }}">
                    <span class="hide-menu small text-nowrap">Payment Over Due</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.approved-inbound') }}">
                    <span class="hide-menu small text-nowrap">Approved Inbound Transactions</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.purchase-orders') }}">
                    <span class="hide-menu small text-nowrap">Purchase Orders Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.sale-counter') }}">
                    <span class="hide-menu small text-nowrap">Sale Counter Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.store-due') }}">
                    <span class="hide-menu small text-nowrap">Store/Mess Due Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.mess-bill') }}">
                    <span class="hide-menu small text-nowrap">Mess Bill Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.mess-invoice') }}">
                    <span class="hide-menu small text-nowrap">Mess Invoice Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.stock-purchase-details') }}">
                    <span class="hide-menu small text-nowrap">Stock Purchase Details</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.client-invoice') }}">
                    <span class="hide-menu small text-nowrap">Client Invoice Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.reports.stock-issue-detail') }}">
                    <span class="hide-menu small text-nowrap">Stock Issue Detail</span>
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
                <a class="sidebar-link" href="{{ route('admin.mess.mealmappings.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Meal Mappings</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.storeallocations.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Store Allocations</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('admin.mess.permissionsettings.index') }}">
                    <span class="hide-menu small small-sm-normal text-nowrap">Permissions</span>
                </a>
            </li>
        </ul>
    </ul>
</nav>