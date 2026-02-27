<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-11" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->

                            {{-- ESTATE MANAGEMENT --}}
                            @php
                                $estateManagementOpen = request()->routeIs('admin.estate.*');
                            @endphp
                            <li class="sidebar-item mt-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#estateManagementCollapse" role="button"
                                    aria-expanded="{{ $estateManagementOpen ? 'true' : 'false' }}" aria-controls="estateManagementCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Estate Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3 {{ $estateManagementOpen ? 'show' : '' }}" id="estateManagementCollapse">
                                {{-- Main flow: Request → Put in HAC → HAC Forward → HAC Approved → Possession Details --}}
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-estate') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-estate') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request For Estate</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.put-in-hac') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.put-in-hac') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Put In HAC</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.change-request-hac-approved') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.change-request-hac-approved') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">HAC Approved</span>
                                    </a>
                                </li>
                                {{-- Possession Details (LBSNAA) and Estate Possession for Other (Others) - two different pages --}}
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.possession-details') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.possession-details') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Possession Details</span>
                                    </a>
                                </li>      
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.update-meter-no') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.update-meter-no') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Update Meter No.</span>
                                    </a>
                                </li>

                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-others') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-others') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Request for Others</span>
                                    </a>
                                </li>
                                
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.possession-for-others') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.possession-for-others') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Possession for Other</span>
                                    </a>
                                </li>
                                <!-- <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.possession-view') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.possession-view') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Possession View (Add)</span>
                                    </a>
                                </li> -->
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.update-meter-reading-of-other') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.update-meter-reading-of-other') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Update Meter Reading of Other</span>
                                    </a>
                                </li>
                                
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.list-meter-reading*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.list-meter-reading') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">List Meter Reading</span>
                                    </a>
                                </li>
                                <!-- <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.update-meter-reading') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.update-meter-reading') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Update Meter Reading</span>
                                    </a>
                                </li> -->
                                
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.generate-estate-bill') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Generate Estate Bill</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.generate-estate-bill-for-other') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill-for-other') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Generate Estate Bill for Other</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.return-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.return-house') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Return House</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-house') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define House</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-electric-slab.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-electric-slab.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Electric Slab</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-house') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Request For House</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.change-request-details') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.change-request-details') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Change Request Details</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- ESTATE MASTER --}}
                            <li class="sidebar-item mt-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#estateMasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateMasterCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Estate Master</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="estateMasterCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-campus.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-campus.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Estate/Campus</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-unit-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Unit Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-unit-sub-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-sub-type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Unit Sub Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-block-building.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-block-building.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Block/Building</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-pay-scale.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-pay-scale.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Pay Scale</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.eligibility-criteria.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.eligibility-criteria.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Eligibility - Criteria</span>
                                    </a>
                                </li>
                                <li class="sidebar-item border-top mt-2 pt-2">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.estate-approval-setting') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.estate-approval-setting') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Approval Setting</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.add-approved-request-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.add-approved-request-house') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Add Approved Request House</span>
                                    </a>
                                </li>
                            </ul>

                            {{-- ESTATE REPORTS --}}
                            <li class="sidebar-item mt-2" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#estateReportsCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateReportsCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Estate Reports</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="estateReportsCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.pending-meter-reading') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.pending-meter-reading') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Pending Meter Reading</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.house-status') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.house-status') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">House Status</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.bill-report-grid') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.bill-report-grid') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Bill Report - Grid View</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.bill-report-print') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.bill-report-print') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Bill Report for Print</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.migration-report') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.migration-report') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Migration Report (1998–2026)</span>
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
