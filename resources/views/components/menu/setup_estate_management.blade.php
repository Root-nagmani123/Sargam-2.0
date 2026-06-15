@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-mini-11',
    'panelMenuTitle' => 'ESTATE',
    'panelMenuClass' => 'sidebar-setup-estate-menu',
])
                            {{-- ESTATE MANAGEMENT (same visibility rules as main Setup menu) --}}
                            @php
                                $showUserManagement = hasRole('Super Admin') || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST');
                                // Staff/self-service: Request For Estate + My Estate Bill.
                                // Training roles should behave like normal staff (self-service), not like estate authorities.
                                $estateSelfServiceRoles = hasRole('Staff')
                                    || hasRole('Officer Trainee')
                                    || hasRole('Doctor')
                                    || hasRole('Guest Faculty')
                                    || hasRole('Internal Faculty')
                                    || hasRole('Training Induction Admin')
                                    || hasRole('Training MCTP Admin')
                                    || hasRole('Training IST');
                                // Check permanent LBSNAA employee (payroll = 0)
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
                                // Estate block visible for:
                                // - Admin / Super Admin / Training / IST (user management; self-service estate items only)
                                // - Estate / HAC Person
                                // - All self-service estate roles (Staff, Student-OT, Doctor, Guest Faculty, Internal Faculty)
                                //   They will still be restricted inside the menu to only their own-data items.
                                $showEstateSection = $showUserManagement || hasRole('Estate') || hasRole('Super Admin') || hasRole('HAC Person') || $estateSelfServiceRoles;
                                $isEstateAdmin = hasRole('Estate');
                                $isHACPerson = hasRole('HAC Person');
                                // Estate authority menus (Put In HAC, Possession Details, etc.): Estate role only.
                                // Admin / Super Admin use self-service items (Request + My Estate Bill) like Staff.
                                // Training roles must NOT get "all estate" access.
                                $canSeeAllEstate = hasRole('Estate');
                                $estateManagementOpen = request()->routeIs('admin.estate.*');
                                // HAC menus (Put In HAC / HAC Approved) visible ONLY to HAC Person + Estate/Admin.
                                $canSeeHAC = $isHACPerson || $canSeeAllEstate;
                                // Staff/self-service: Request For Estate + Generate Estate Bill only. HAC Person (without Staff) sees only Put In HAC + HAC Approved.
                                $canSeeRequestAndBill = $canSeeAllEstate || $estateSelfServiceRoles || hasRole('Super Admin') || hasRole('Super Admin');
                                $canSeeSelfOnly = $canSeeAllEstate || $isHACPerson || $estateSelfServiceRoles || hasRole('Super Admin') || hasRole('Super Admin');
                                // Meter menus: Estate role only (Admin / Super Admin follow staff estate menu).
                                $canSeeUpdateMeterNo = hasRole('Estate');
                                $canSeeListMeterReading = hasRole('Estate');
                                // "Other" estate operations: Estate role only.
                                $canManageOthersEstate = $isEstateAdmin;
                                // For client requirement: Return House menu should NOT appear for self-service users.
                                $canSeeReturnHouse = $canManageOthersEstate;
                                // Estate role sees full bill view label on authority screen; everyone else (incl. Admin / Super Admin) sees "My Estate Bill".
                                $estateBillMenuLabel = hasRole('Estate') ? 'View Estate Bill' : 'My Estate Bill';
                            @endphp

                            @if($showEstateSection)
                                {{-- ESTATE MANAGEMENT (mini sidebar) --}}
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                        data-bs-toggle="collapse" href="#estateManagementMiniCollapse" role="button"
                                        aria-expanded="false" aria-controls="estateManagementMiniCollapse">
                                        <span class="d-flex align-items-center gap-2 min-w-0">
                                            <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">home_work</i>
                                            <span class="hide-menu small small-sm-normal text-nowrap">Estate Management</span>
                                        </span>
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon"
                                            aria-hidden="true">chevron_right</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled mb-2" id="estateManagementMiniCollapse">
                                    <li class="sidebar-panel-submenu-tree">
                                    <ul class="list-unstyled mb-0">
                                    {{-- Staff/self-service: Request For Estate + Generate Estate Bill. HAC Person: only Put In HAC + HAC Approved. --}}
                                    @if($canSeeRequestAndBill)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-estate') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.request-for-estate') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Request For Estate</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeHAC)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.put-in-hac') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.put-in-hac') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Put In HAC</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.change-request-hac-approved') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.change-request-hac-approved') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">HAC Approval</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeAllEstate)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.possession-details') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.possession-details') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Possession Details</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeUpdateMeterNo)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.update-meter-no') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.update-meter-no') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Update Meter Details</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canManageOthersEstate)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-others') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.request-for-others') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Estate Request for Others</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.possession-for-others') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.possession-for-others') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Estate Possession for Other</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.update-meter-reading-of-other') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.update-meter-reading-of-other') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Update Meter Details of Other</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeListMeterReading)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.list-meter-reading*') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.list-meter-reading') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">List Meter Reading</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeRequestAndBill)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.generate-estate-bill') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.generate-estate-bill') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">{{ $estateBillMenuLabel }}</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canManageOthersEstate)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.generate-estate-bill-for-other') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.generate-estate-bill-for-other') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">View Estate Bill for Other</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.define-house') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.define-house') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Define House</span>
                                        </a>
                                    </li>
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.define-electric-slab.*') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.define-electric-slab.index') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Define Electric Slab</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeReturnHouse)
                                    <li class="sidebar-item mb-1">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.return-house') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.return-house') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Return House</span>
                                        </a>
                                    </li>
                                    @endif

                                    @if($canSeeAllEstate)
                                    <li class="sidebar-item">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-house') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.request-for-house') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Change House Request</span>
                                        </a>
                                    </li>
                                    <!-- <li class="sidebar-item">
                                        <a class="sidebar-link {{ request()->routeIs('admin.estate.change-request-details') ? 'active' : '' }}"
                                            href="{{ route('admin.estate.change-request-details') }}">
                                            <span class="hide-menu small small-sm-normal text-nowrap">Change Request Details</span>
                                        </a>
                                    </li> -->
                                    @endif
                                    </ul>
                                    </li>
                                </ul>
                            @endif

                            {{-- ESTATE MASTER --}}
                            @if(hasRole('Estate'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#estateMasterCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateMasterCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">account_tree</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Master</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon"
                                        aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="estateMasterCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-campus.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-campus.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Estate/Campus</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-unit-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Unit Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-unit-sub-type.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-unit-sub-type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Unit Sub Type</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-block-building.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-block-building.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Block/Building</span>
                                    </a>
                                </li>
                                {{-- Define Pay Scale - commented out
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.define-pay-scale.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.define-pay-scale.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Pay Scale</span>
                                    </a>
                                </li>
                                --}}
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.eligibility-criteria.*') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.eligibility-criteria.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Eligibility - Criteria</span>
                                    </a>
                                </li>
                                {{-- Estate Approval Setting - commented out
                                <li class="sidebar-item border-top mt-2 pt-2">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.estate-approval-setting') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.estate-approval-setting') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Approval Setting</span>
                                    </a>
                                </li>
                                --}}
                                {{-- Add Approved Request House - commented out
                                <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.add-approved-request-house') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.add-approved-request-house') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Add Approved Request House</span>
                                    </a>
                                </li>
                                --}}
                                </ul>
                                </li>
                            </ul>
                            @endif

                            {{-- ESTATE REPORTS --}}
                            @if(hasRole('Estate'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
                                    data-bs-toggle="collapse" href="#estateReportsCollapse" role="button"
                                    aria-expanded="false" aria-controls="estateReportsCollapse">
                                    <span class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">description</i>
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Reports</span>
                                    </span>
                                    <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon"
                                        aria-hidden="true">chevron_right</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled mb-2" id="estateReportsCollapse">
                                <li class="sidebar-panel-submenu-tree">
                                <ul class="list-unstyled mb-0">
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.pending-meter-reading') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.pending-meter-reading') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Pending Meter Reading</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.house-status') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.house-status') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">House Status</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.bill-report-grid') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.bill-report-grid') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Bill Report - Grid View</span>
                                    </a>
                                </li>
                                <!-- <li class="sidebar-item">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.bill-report-print') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.bill-report-print') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Estate Bill Report for Print</span>
                                    </a>
                                </li> -->
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.estate.reports.migration-report') ? 'active' : '' }}"
                                        href="{{ route('admin.estate.reports.migration-report') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Migration Report (1998–2026)</span>
                                    </a>
                                </li>
                                </ul>
                                </li>
                            </ul>
                            @endif


@include('components.menu.partials.panel-shell-close')