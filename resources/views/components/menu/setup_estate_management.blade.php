<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-10" data-simplebar="init">
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
                            <!-- Estate Management Section -->
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#estateManagementCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateManagementCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Estate Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="estateManagementCollapse">
                                <!-- Campus Master -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.campus.*') ? 'active' : '' }}"
                                        href="{{ route('estate.campus.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Campus Master</span>
                                    </a>
                                </li>
                                
                                <!-- Area Master -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.area.*') ? 'active' : '' }}"
                                        href="{{ route('estate.area.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Area Master</span>
                                    </a>
                                </li>
                                
                                <!-- Block/Building Master -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.block.*') ? 'active' : '' }}"
                                        href="{{ route('estate.block.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Block/Building Master</span>
                                    </a>
                                </li>
                                
                                <!-- Unit Type Master -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.unit-type.*') ? 'active' : '' }}"
                                        href="{{ route('estate.unit-type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Unit Type Master</span>
                                    </a>
                                </li>
                                
                                <!-- Unit Master -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.unit.*') ? 'active' : '' }}"
                                        href="{{ route('estate.unit.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Unit Master</span>
                                    </a>
                                </li>
                                
                                <!-- Electric Slab -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.electric-slab.*') ? 'active' : '' }}"
                                        href="{{ route('estate.electric-slab.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Electric Slab Master</span>
                                    </a>
                                </li>
                                
                                <!-- Possession Management -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.possession.*') ? 'active' : '' }}"
                                        href="{{ route('estate.possession.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Possession Management</span>
                                    </a>
                                </li>
                                
                                <!-- Billing Management -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('estate.billing.*') ? 'active' : '' }}"
                                        href="{{ route('estate.billing.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Billing Management</span>
                                    </a>
                                </li>
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
