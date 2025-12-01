 <aside class="side-mini-panel with-vertical">
     <div style="height: 100vh; display: flex; flex-direction: column;">
         <!-- ---------------------------------- -->
         <!-- Start Vertical Layout Sidebar -->
         <!-- ---------------------------------- -->
         <div class="iconbar" style="flex: 1 1 auto; display: flex; flex-direction: column;">
             <div style="flex: 1 1 auto; display: flex; flex-direction: column;">
                 <div class="mini-nav" style="flex: 1 1 auto; display: flex; flex-direction: column;">
                     <div class="brand-logo d-flex align-items-start justify-content-start py-2 px-2">
                         <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                             <img src="{{asset('images/hamburger.svg')}}" alt="" style="width:32px;">
                         </a>
                     </div>
                     <ul class="mini-nav-ul simplebar-scrollable-y" data-simplebar="init" style="flex: 1 1 auto;">
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
                                                 id="mini-1">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right" data-bs-title="General">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded"
                                                             style="font-size: 32px;">apps</i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-medium text-dark">General</span>
                                                     </div>

                                                     <i class="material-icons material-symbols-rounded"
                                                         style="font-size: 20px;">chevron_right</i>
                                                 </a>
                                             </li>

                                             <li class="mini-nav-item {{ request()->is('admin/*') ? 'selected' : '' }}"
                                                 id="mini-2">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right" data-bs-title="Master">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded"
                                                             style="font-size: 32px;">menu_open</i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-bold text-dark">Master</span>
                                                     </div>

                                                     <i class="material-icons material-symbols-rounded"
                                                         style="font-size: 20px;">chevron_right</i>
                                                 </a>
                                             </li>

                                             <li class="mini-nav-item {{ request()->is('forms*') ? 'selected' : '' }}"
                                                 id="mini-3">
                                                 <a href="javascript:void(0)"
                                                     class="mini-nav-link d-flex align-items-center justify-content-between w-100"
                                                     data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                                                     data-bs-placement="right" data-bs-title="FC Forms">

                                                     <div class="d-flex align-items-center gap-2">
                                                         <i class="material-icons menu-icon material-symbols-rounded"
                                                             style="font-size: 32px;">note_add</i>
                                                         <span
                                                             class="mini-nav-title fs-4 fw-bold text-dark text-wrap">FC
                                                             Registration</span>
                                                     </div>

                                                     <i class="material-icons material-symbols-rounded"
                                                         style="font-size: 20px;">chevron_right</i>
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
                     <!-- Bottom User Section -->
                     <div class="mini-bottom px-2 pb-3" style="margin-top: auto;">
                         <!-- Settings Icon -->
                         <div class="mini-settings text-center mb-3">
                             <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="right"
                                 data-bs-custom-class="custom-tooltip" data-bs-title="Settings">
                                 <i class="material-icons material-symbols-rounded" style="font-size:32px;">settings</i>
                             </a>
                         </div>
                         <!-- Profile Dropdown -->
                         <div class="dropdown mini-profile text-center">
                             <a href="#" class="d-block" data-bs-toggle="dropdown" aria-expanded="false">
                                 <img src="{{ Auth::user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=User' }}"
                                     class="rounded-circle" width="45" height="45" alt="profile">
                             </a>
                             <ul class="dropdown-menu shadow border-0 mt-2">
                                 <li>
                                     <a class="dropdown-item" href="#">
                                         <i class="material-icons material-symbols-rounded me-2 fs-5">account_circle</i>
                                         View Profile
                                     </a>
                                 </li>
                                 <li>
                                     <form action="{{ route('logout') }}" method="POST">
                                         @csrf
                                         <button class="dropdown-item text-danger" type="submit">
                                             <i class="material-icons material-symbols-rounded me-2 fs-5">logout</i>
                                             Logout
                                         </button>
                                     </form>
                                 </li>
                             </ul>
                         </div>
                     </div>
                 </div>
                 <div class="sidebarmenu">
                     <div class="brand-logo d-flex align-items-center nav-logo">
                         <a href="#" class="text-nowrap logo-img">
                             <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Logo">
                         </a>
                     </div>
                     <!-- ---------------------------------- -->
                     <!-- Dashboard -->
                     <!-- ---------------------------------- -->
                     <x-menu.general />
                     <!-- Master -->
                     <!-- ---------------------------------- -->
                     <x-menu.master />
                     <!-- Forms -->
                     <!-- ---------------------------------- -->
                     <x-menu.fc-sidebar />
                 </div>
             </div>
         </div>
     </div>
 </aside>