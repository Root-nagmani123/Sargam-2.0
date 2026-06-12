<nav class="sidebar-nav simplebar-scrollable-y" id="menu-right-mini-12" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 20px 24px">
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
                            <!-- ---------------------------------- -->
                            <!-- Course Repository -->
                            <!-- ---------------------------------- -->
                            <li class="sidebar-item mb-2">
                                <a class="sidebar-link d-flex align-items-center rounded-pill px-3 py-2 text-decoration-none {{ request()->is('course-repository*') ? 'active' : '' }}"
                                    href="{{ route('course-repository.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Course Repository</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 100px;"></div>
    </div>
    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
    </div>
    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
        <div class="simplebar-scrollbar" style="display: none;"></div>
    </div>
</nav>
