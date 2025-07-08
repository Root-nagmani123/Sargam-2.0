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

                                             <li class="mini-nav-item {{ request()->is('admin/*') ? 'selected' : '' }}"
                                                 id="mini-5">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="General Setup">
                                                     <iconify-icon icon="solar:notes-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">General
                                                     Setup</span>
                                             </li>

                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-6">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Mappings">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Mappings</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-7">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Setup Activities">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Setup
                                                     Activities</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-8">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Role Management">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Role
                                                     Management</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-9">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Infrastructure and Facilities">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Infrastructure
                                                     and Facilities</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-10">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="Reports">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span
                                                     class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">Reports</span>
                                             </li>
                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-11">
                                                 <a href="javascript:void(0)" data-bs-toggle="tooltip"
                                                     data-bs-custom-class="custom-tooltip" data-bs-placement="right"
                                                     data-bs-title="OT Reports">
                                                     <iconify-icon icon="solar:cloud-file-line-duotone" class="fs-7">
                                                     </iconify-icon>
                                                 </a>
                                                 <span class="mini-nav-title fs-3 fw-bold text-center d-block mb-2">OT
                                                     Reports</span>
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
                     <!-- Academic -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_academic />

                     <!-- ---------------------------------- -->
                     <!-- General Setup -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_general />
                      <!-- ---------------------------------- -->
                     <!-- Mappings -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_mappings />

                      <!-- ---------------------------------- -->
                     <!-- Setup Activities -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_activities />

                     <!-- ---------------------------------- -->
                     <!-- Role Management -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_management />


                     <!-- ---------------------------------- -->
                     <!-- Infrastructure and Facilities -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_infrastructure />


                     <!-- ---------------------------------- -->
                     <!-- Reports -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_reports />

                      <!-- ---------------------------------- -->
                     <!-- OT Reports -->
                     <!-- ---------------------------------- -->
                     <x-menu.setup_ot_reports />

                 </div>
             </div>
         </div>
     </div>
 </aside>