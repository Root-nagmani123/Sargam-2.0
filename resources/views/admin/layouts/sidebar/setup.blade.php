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

                                             <li class="mini-nav-item {{ request()->routeIs('training') ? 'selected' : '' }}"
                                                 id="mini-4">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded text-dark"
                                                             style="font-size: 32px;">
                                                             background_dot_large
                                                         </i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-semibold text-dark">Training</span>
                                                     </div>

                                                     <!-- Right Arrow -->
                                                     <i class="material-icons material-symbols-rounded text-dark"
                                                         style="font-size: 20px;">
                                                         chevron_right
                                                     </i>
                                                 </a>
                                             </li>


                                             <li class="mini-nav-item {{ request()->routeIs('time_table') ? 'selected' : '' }}"
                                                 id="mini-5">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded text-dark"
                                                             style="font-size: 32px;">
                                                             background_dot_large
                                                         </i>
                                                         <span class="mini-nav-title fs-4 fw-semibold text-dark">Time
                                                             Table</span>
                                                     </div>

                                                     <!-- Right Arrow -->
                                                     <i class="material-icons material-symbols-rounded text-dark"
                                                         style="font-size: 20px;">
                                                         chevron_right
                                                     </i>
                                                 </a>
                                             </li>
                                             <li class="mini-nav-item {{ request()->routeIs('user_management') ? 'selected' : '' }}"
                                                 id="mini-6">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded text-dark"
                                                             style="font-size: 32px;">
                                                             background_dot_large
                                                         </i>
                                                         <span class="mini-nav-title fs-4 fw-semibold text-dark">User
                                                             Management</span>
                                                     </div>

                                                     <!-- Right Arrow -->
                                                     <i class="material-icons material-symbols-rounded text-dark"
                                                         style="font-size: 20px;">
                                                         chevron_right
                                                     </i>
                                                 </a>
                                             </li>
                                             <li class="mini-nav-item {{ request()->routeIs('master') ? 'selected' : '' }}"
                                                 id="mini-7">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded text-dark"
                                                             style="font-size: 32px;">
                                                             background_dot_large
                                                         </i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-semibold text-dark">Master</span>
                                                     </div>

                                                     <!-- Right Arrow -->
                                                     <i class="material-icons material-symbols-rounded text-dark"
                                                         style="font-size: 20px;">
                                                         chevron_right
                                                     </i>
                                                 </a>
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

                     <!-- ---------------------------------- -->
                     <!-- Academic -->
                     <!-- ---------------------------------- -->
                        <x-menu.setup_general />

                     <!-- ---------------------------------- -->
                     <!-- Academic -->
                     <!-- ---------------------------------- -->
                        <x-menu.setup_activities />


                     <!-- ---------------------------------- -->
                     <!-- Academic -->
                     <!-- ---------------------------------- -->
                        <x-menu.setup_mappings />

                 </div>
             </div>
         </div>
     </div>
 </aside>

 <script>
 document.addEventListener('DOMContentLoaded', function() {
     // Initialize mini-navbar functionality for setup
     const miniNavItems = document.querySelectorAll('.mini-nav .mini-nav-item');
     const sidebarMenus = document.querySelectorAll('.sidebarmenu nav');
     
     miniNavItems.forEach(function(item) {
         item.addEventListener('click', function() {
             const id = this.id;
             
             // Remove selected class from all mini-nav items
             miniNavItems.forEach(function(navItem) {
                 navItem.classList.remove('selected');
             });
             
             // Add selected class to clicked item
             this.classList.add('selected');
             
             // Hide all sidebar menus
             sidebarMenus.forEach(function(nav) {
                 nav.classList.remove('d-block');
             });
             
             // Show the corresponding sidebar menu
             const targetMenu = document.getElementById('menu-right-' + id);
             if(targetMenu) {
                 targetMenu.classList.add('d-block');
                 document.body.setAttribute('data-sidebartype', 'full');
             }
         });
     });
     
     // Show first menu by default if none is selected
     const hasSelected = document.querySelector('.mini-nav .mini-nav-item.selected');
     if(!hasSelected && miniNavItems.length > 0) {
         miniNavItems[0].classList.add('selected');
         const firstMenuId = miniNavItems[0].id;
         const firstMenu = document.getElementById('menu-right-' + firstMenuId);
         if(firstMenu) {
             firstMenu.classList.add('d-block');
         }
     }
 });
 </script>