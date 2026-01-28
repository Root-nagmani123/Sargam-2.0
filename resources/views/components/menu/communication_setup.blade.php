<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-12" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 0px 20px 24px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            @include('components.profile')
                            <!-- ---------------------------------- -->
                            <!-- Home -->
                            <!-- ---------------------------------- -->
                             <li class="sidebar-item" style="background: #4077ad;border-radius: 30px 0px 0px 30px;width: 100%;box-shadow: -2px 3px rgba(251, 248, 248, 0.1);min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                    aria-expanded="false" aria-controls="generalCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Notifications</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Notice</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Campus Tweet</span>
                                    </a></li>
                            </ul>
                            <li class="sidebar-item" style="background: #4077ad;border-radius: 30px 0px 0px 30px;width: 100%;box-shadow: -2px 3px rgba(251, 248, 248, 0.1);min-width: 250px;">
                                <a class="sidebar-link d-flex justify-content-between align-items-center"
                                    data-bs-toggle="collapse" href="#meetingCollapse" role="button"
                                    aria-expanded="false" aria-controls="meetingCollapse">
                                    <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Meeting Management</span>
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                </a>
                            </li>
                            <ul class="collapse list-unstyled ps-3" id="meetingCollapse">
                                <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting Type</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define Meeting</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Define MOM</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">View MOM</span>
                                    </a></li>
                                    <li class="sidebar-item"><a class="sidebar-link" href="#">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Search Agenda</span>
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