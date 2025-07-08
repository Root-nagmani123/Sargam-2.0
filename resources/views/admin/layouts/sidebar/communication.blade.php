 <aside class="side-mini-panel with-vertical">
     <div>
         <!-- ---------------------------------- -->
         <!-- Start Vertical Layout Sidebar -->
         <!-- ---------------------------------- -->
         <div class="iconbar">
             <div>
                 <div class="mini-nav">
                     <div class="brand-logo d-flex align-items-center justify-content-center">
                         <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                             <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                         </a>
                     </div>
                     <ul class="mini-nav-ul simplebar-scrollable-y" data-simplebar="init">
                         <div class="simplebar-wrapper" style="margin: 0px;">
                             <div class="simplebar-height-auto-observer-wrapper">
                                 <div class="simplebar-height-auto-observer"></div>
                             </div>
                             <div class="simplebar-mask">
                                 <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                     <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                         aria-label="scrollable content" style="height: 100%; overflow: hidden scroll;">
                                         <div class="simplebar-content" style="padding: 0px;">

                                             <li class="mini-nav-item {{ request()->routeIs('dashboard') ? 'selected' : '' }}"
                                                 id="mini-12">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Setup">
                                                     <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Setup</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->routeIs('dashboard') ? 'selected' : '' }}"
                                                 id="mini-13">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Notification">
                                                     <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Notification</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->routeIs('dashboard') ? 'selected' : '' }}"
                                                 id="mini-14">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Meeting Management">
                                                     <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Meeting Management</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->routeIs('dashboard') ? 'selected' : '' }}"
                                                 id="mini-15">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="PA Management">
                                                     <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">PA Management</span>
                                             </li>

                                         </div>
                                     </div>
                                 </div>
                             </div>
                             <div class="simplebar-placeholder" style="width: 80px; height: 537px;"></div>
                         </div>
                         <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                             <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                         </div>
                         <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                             <div class="simplebar-scrollbar"
                                 style="height: 75px; display: block; transform: translate3d(0px, 0px, 0px);">
                             </div>
                         </div>
                     </ul>

                 </div>
                 <div class="sidebarmenu">
                     <div class="brand-logo d-flex align-items-center nav-logo">
                         <a href="#" class="text-nowrap logo-img">
                             <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Logo">
                         </a>

                     </div>
                     <!-- ---------------------------------- -->
                     <!-- Setup -->
                     <!-- ---------------------------------- -->
                     <x-menu.communication_setup />

                     <!-- Notification -->
                     <!-- ---------------------------------- -->


                     <x-menu.communication_notification />

                     <!-- Meeting Management -->
                     <!-- ---------------------------------- -->
                     <x-menu.communication_meeting/>

                     <!-- PA Management -->
                     <!-- ---------------------------------- -->
                     <x-menu.communication_management />

                 </div>
             </div>
         </div>
     </div>
 </aside>