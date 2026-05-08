<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-11" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">

                        @php
                            $showUserManagement = hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST');
                            $estateSelfServiceRoles = hasRole('Staff')
                                || hasRole('Student-OT')
                                || hasRole('Doctor')
                                || hasRole('Guest Faculty')
                                || hasRole('Internal Faculty')
                                || hasRole('Training-Induction')
                                || hasRole('Training-MCTP')
                                || hasRole('IST');
                            $isPermanentEstateEmployee = false;
                            $user = Auth::user();
                            if ($user && $estateSelfServiceRoles && \Illuminate\Support\Facades\Schema::hasTable('employee_master')) {
                                $empIdCandidates = array_values(array_filter([
                                    $user->user_id ?? null,
                                    $user->pk ?? null,
                                ], fn ($v) => $v !== null && $v !== ''));
                                if (!empty($empIdCandidates)) {
                                    $empQuery = \Illuminate\Support\Facades\DB::table('employee_master');
                                    $empQuery->whereIn('pk', $empIdCandidates);
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                                        $empQuery->orWhereIn('pk_old', $empIdCandidates);
                                    }
                                    $empRow = $empQuery->select('payroll')->first();
                                    if ($empRow && (int) ($empRow->payroll ?? 0) === 0) {
                                        $isPermanentEstateEmployee = true;
                                    }
                                }
                            }
                            $showEstateSection = $showUserManagement || hasRole('Estate') || hasRole('Super Admin') || hasRole('HAC Person') || $estateSelfServiceRoles;
                            $isEstateAdmin = hasRole('Estate') || hasRole('Super Admin');
                            $isHACPerson = hasRole('HAC Person');
                            $canSeeAllEstate = $isEstateAdmin || hasRole('Admin');
                            $estateManagementOpen = request()->routeIs('admin.estate.*');
                            $canSeeHAC = $isHACPerson || $canSeeAllEstate;
                            $canSeeRequestAndBill = $canSeeAllEstate || $estateSelfServiceRoles;
                            $canSeeSelfOnly = $canSeeAllEstate || $isHACPerson || $estateSelfServiceRoles;
                            $canSeeUpdateMeterNo = hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin');
                            $canSeeListMeterReading = hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin');
                            $canManageOthersEstate = $isEstateAdmin || hasRole('Admin') || hasRole('Super Admin');
                            $canSeeReturnHouse = $canManageOthersEstate;
                            $estateBillMenuLabel = (hasRole('Admin') || hasRole('Super Admin') || hasRole('Estate')) ? 'View Estate Bill' : 'My Estate Bill';
                        @endphp

                        @if($showEstateSection)

                        <div class="sidebar-section-header text-uppercase fw-bold mb-3"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Estate
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">

                            {{-- Estate Management (collapsible) --}}
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#estateManagementMiniCollapse" role="button"
                                    aria-expanded="{{ $estateManagementOpen ? 'true' : 'false' }}" aria-controls="estateManagementMiniCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">home_work</i>
                                        <span class="hide-menu">Estate Management</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled {{ $estateManagementOpen ? 'show' : '' }}" id="estateManagementMiniCollapse">

                                @if($canSeeRequestAndBill)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.request-for-estate') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-estate') }}">
                                        <span class="hide-menu">Request For Estate</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeHAC)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.put-in-hac') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.put-in-hac') }}">
                                        <span class="hide-menu">Put In HAC</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.change-request-hac-approved') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.change-request-hac-approved') }}">
                                        <span class="hide-menu">HAC Approval</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeAllEstate)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.possession-details') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.possession-details') }}">
                                        <span class="hide-menu">Possession Details</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeUpdateMeterNo)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.update-meter-no') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.update-meter-no') }}">
                                        <span class="hide-menu">Update Meter Details</span>
                                    </a>
                                </li>
                                @endif

                                @if($canManageOthersEstate)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.request-for-others') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-others') }}">
                                        <span class="hide-menu">Estate Request for Others</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.possession-for-others') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.possession-for-others') }}">
                                        <span class="hide-menu">Estate Possession for Other</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.update-meter-reading-of-other') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.update-meter-reading-of-other') }}">
                                        <span class="hide-menu">Update Meter Details of Other</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeListMeterReading)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.list-meter-reading*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.list-meter-reading') }}">
                                        <span class="hide-menu">List Meter Reading</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeRequestAndBill)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.generate-estate-bill') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill') }}">
                                        <span class="hide-menu">{{ $estateBillMenuLabel }}</span>
                                    </a>
                                </li>
                                @endif

                                @if($canManageOthersEstate)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.generate-estate-bill-for-other') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.generate-estate-bill-for-other') }}">
                                        <span class="hide-menu">View Estate Bill for Other</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-house') }}">
                                        <span class="hide-menu">Define House</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-electric-slab.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-electric-slab.index') }}">
                                        <span class="hide-menu">Define Electric Slab</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeReturnHouse)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.return-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.return-house') }}">
                                        <span class="hide-menu">Return House</span>
                                    </a>
                                </li>
                                @endif

                                @if($canSeeAllEstate)
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.request-for-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.request-for-house') }}">
                                        <span class="hide-menu">Change House Request</span>
                                    </a>
                                </li>
                                @endif

                            </ul>
                            @endif

                            {{-- Estate Master (collapsible) --}}
                            @if(hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#estateMasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateMasterCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">domain</i>
                                        <span class="hide-menu">Estate Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="estateMasterCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-campus.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-campus.index') }}">
                                        <span class="hide-menu">Define Estate/Campus</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-unit-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-type.index') }}">
                                        <span class="hide-menu">Define Unit Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-unit-sub-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-sub-type.index') }}">
                                        <span class="hide-menu">Define Unit Sub Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.define-block-building.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-block-building.index') }}">
                                        <span class="hide-menu">Define Block/Building</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.eligibility-criteria.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.eligibility-criteria.index') }}">
                                        <span class="hide-menu">Eligibility - Criteria</span>
                                    </a>
                                </li>
                            </ul>
                            @endif

                            {{-- Estate Reports (collapsible) --}}
                            @if(hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center justify-content-between gap-2"
                                    data-bs-toggle="collapse" href="#estateReportsCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateReportsCollapse">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">assessment</i>
                                        <span class="hide-menu">Estate Reports</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded menu-icon" style="font-size:20px;">keyboard_arrow_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled" id="estateReportsCollapse">
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.reports.pending-meter-reading') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.pending-meter-reading') }}">
                                        <span class="hide-menu">Pending Meter Reading</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.reports.house-status') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.house-status') }}">
                                        <span class="hide-menu">House Status</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.reports.bill-report-grid') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.bill-report-grid') }}">
                                        <span class="hide-menu">Estate Bill Report - Grid View</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.estate.reports.migration-report') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.migration-report') }}">
                                        <span class="hide-menu">Migration Report (1998–2026)</span>
                                    </a>
                                </li>
                            </ul>
                            @endif

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
        <div class="simplebar-scrollbar" style="height: 45px; display: block; transform: translate3d(0px, 0px, 0px);"></div>
    </div>
</nav>
