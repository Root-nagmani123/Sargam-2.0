{{-- Home sidebar: personal estate shortcuts --}}
<nav class="sidebar-nav sargam-menu-flyout simplebar-scrollable-y" id="menu-right-home-mini-estate" data-mini-nav-target="home-mini-estate" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content">

                        <div class="sidebar-section-header text-uppercase fw-bold mb-1"
                            style="font-size: 11px; letter-spacing: 2px; color: var(--sidebar-text-muted, #9aa0a6);">
                            Estate
                        </div>

                        <ul class="sidebar-menu list-unstyled" id="sidebarnav">
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.estate.request-for-estate') && request('scope') === 'self' ? 'active' : '' }}"
                                    href="{{ route('admin.estate.request-for-estate', ['scope' => 'self']) }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">real_estate_agent</i>
                                    <span class="hide-menu">Request For Estate</span>
                                </a>
                            </li>
                            <li class="sidebar-item mb-1">
                                <a class="sidebar-link d-flex align-items-center gap-1 {{ request()->routeIs('admin.estate.generate-estate-bill*') && request('scope') === 'self' ? 'active' : '' }}"
                                    href="{{ route('admin.estate.generate-estate-bill', ['scope' => 'self']) }}">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">receipt_long</i>
                                    <span class="hide-menu">My Estate Bill</span>
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
