<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
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
                            @include('components.profile')
                            {{-- GENERAL --}}
                            <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px 0px 0px 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                    aria-expanded="false" aria-controls="generalCollapse">
                                    <span class="hide-menu fw-bold">Quick Links</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                <li class="sidebar-item"><a class="sidebar-link"
                                        href="#">
                                        <span class="hide-menu"></span>
                                    </a></li>
                            </ul>

                            {{-- COURSE --}}
                            <li class="sidebar-item" style="background: #4077ad;
    border-radius: 30px 0px 0px 30px;
    width: 100%;
    box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
    min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#courseCollapse" role="button" aria-expanded="false"
                                    aria-controls="courseCollapse">
                                    <span class="hide-menu fw-bold">Usefull Links</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="courseCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu"></span>
                                    </a></li>
                            </ul>

                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>