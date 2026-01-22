<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-8" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <!-- Mess Management -->
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#messmanagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="messmanagementCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Mess Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="messmanagementCollapse">
                                <!-- Master Data -->
                                <li class="sidebar-item"><a class="sidebar-link" href="#" data-bs-toggle="collapse" data-bs-target="#masterDataCollapse">
                                    <span class="hide-menu small small-sm-normal text-nowrap">📋 Master Data</span>
                                </a></li>
                                <ul class="collapse list-unstyled ps-4" id="masterDataCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.events.index') }}">
                                        <span class="hide-menu small text-nowrap">Events</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.inventories.index') }}">
                                        <span class="hide-menu small text-nowrap">Inventory Items</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.vendors.index') }}">
                                        <span class="hide-menu small text-nowrap">Vendors</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.itemcategories.index') }}">
                                        <span class="hide-menu small text-nowrap">Item Categories</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.itemsubcategories.index') }}">
                                        <span class="hide-menu small text-nowrap">Item Subcategories</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.stores.index') }}">
                                        <span class="hide-menu small text-nowrap">Stores</span>
                                    </a></li>
                                </ul>
                                
                                <!-- Material Management -->
                                <li class="sidebar-item"><a class="sidebar-link" href="#" data-bs-toggle="collapse" data-bs-target="#materialMgmtCollapse">
                                    <span class="hide-menu small small-sm-normal text-nowrap">📦 Material Management</span>
                                </a></li>
                                <ul class="collapse list-unstyled ps-4" id="materialMgmtCollapse">
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.materialrequests.index') }}">
                                        <span class="hide-menu small text-nowrap">Material Requests</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.purchaseorders.index') }}">
                                        <span class="hide-menu small text-nowrap">Purchase Orders</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.inboundtransactions.index') }}">
                                        <span class="hide-menu small text-nowrap">Goods Receipt</span>
                                    </a></li>
                                </ul>
                                
                                <!-- Other Modules -->
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.mealmappings.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">🍽️ Meal Mappings</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.invoices.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">💰 Invoices</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.storeallocations.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">🏪 Store Allocations</span>
                                </a></li>
                                <li class="sidebar-item"><a class="sidebar-link" href="{{ route('mess.permissionsettings.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">🔒 Permissions</span>
                                </a></li>
                            </ul>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);">
        </div>
    </div>
</nav>