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
                            <img src="{{asset('images/hamburger.svg')}}" alt="" style="width:32px;">
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

                                             <li class="mini-nav-item {{ request()->routeIs('academic') ? 'selected' : '' }}"
                                                 id="mini-4">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Academic">
                                                     <iconify-icon icon="solar:layers-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Academic</span>
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
                         <a href="javascript:void(0)" class="text-nowrap logo-img">
                             <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Logo">
                         </a>

                     </div>
                     <!-- ---------------------------------- -->
                     <!-- Academic -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_academic />

                 </div>
             </div>
         </div>
     </div>
 </aside>