@include('components.menu.partials.panel-shell-open', [
    'panelMenuId' => 'menu-right-mini-9',
    'panelMenuTitle' => 'SECURITY',
    'panelMenuClass' => 'sidebar-setup-security-menu',
])
                           
                            {{--<li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('admin.employee_idcard.create') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Request New ID Card</span>
                                </a>
                            </li>--}}
                           
                           
                            
                            
                             @if (hasRole('Security Card') || hasRole('Admin Security'))
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.security.family_idcard_approval.*') ? 'active' : '' }}"
                                        href="{{ route('admin.security.family_idcard_approval.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Requested Family ID</span>
                                    </a>
                                </li>
                            @endif
                          
                            {{-- <li class="sidebar-item">
                                <a class="sidebar-link" href="{{ route('admin.security.duplicate_vehicle_pass.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Duplicate Vehicle Pass Request</span>
                                </a>
                            </li> --}}
                            @if (hasRole('Security Card') || hasRole('Admin Security'))
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2 {{ request()->routeIs('admin.security.vehicle_pass_approval.*') ? 'active' : '' }}"
                                        href="{{ route('admin.security.vehicle_pass_approval.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Requested Vehicle Pass</span>
                                    </a>
                                </li>
                            @endif
                            
                            @if (!hasRole('Security Card') && !hasRole('Admin Security'))
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link {{ request()->routeIs('admin.security.employee_idcard_approval.approval1') ? 'active' : '' }}"
                                   href="{{ route('admin.security.employee_idcard_approval.approval1') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Id Card Approval</span>
                                </a>
                            </li>
                            @endif
                            @if (hasRole('Security Card'))
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.security.employee_idcard_approval.approval2') ? 'active' : '' }}"
                                       href="{{ route('admin.security.employee_idcard_approval.approval2') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Requested ID Card</span>
                                    </a>
                                </li>
                            @endif
                            @if (hasRole('Admin Security'))
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.security.employee_idcard_approval.approval3') ? 'active' : '' }}"
                                       href="{{ route('admin.security.employee_idcard_approval.approval3') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Id Card Approval</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.security.idcard_card_type.*') ? 'active' : '' }}"
                                       href="{{ route('admin.security.idcard_card_type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Card Type Master</span>
                                    </a>
                                </li>
                                <li class="sidebar-item mb-1">
                                    <a class="sidebar-link {{ request()->routeIs('admin.security.idcard_sub_type.*') ? 'active' : '' }}"
                                       href="{{ route('admin.security.idcard_sub_type.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Sub Type Mapping</span>
                                    </a>
                                </li>
                            @endif

@include('components.menu.partials.panel-shell-close')