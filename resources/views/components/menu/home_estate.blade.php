{{-- Home sidebar: personal estate shortcuts (same routes as staff self-service; Admin/Estate/Super Admin labels match Setup). --}}
<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-home-mini-estate" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                <span class="sidebar-link d-flex align-items-center text-white py-2 px-3">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Estate Management</span>
                                </span>
                            </li>
                            <li class="sidebar-item mt-2">
                                <a class="sidebar-link {{ request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self' ? 'active' : '' }}"
                                    href="{{ route('admin.estate.request-for-estate', ['scope' => 'self']) }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Request For Estate</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self' ? 'active' : '' }}"
                                    href="{{ route('admin.estate.generate-estate-bill', ['scope' => 'self']) }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">My Estate Bill</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>
