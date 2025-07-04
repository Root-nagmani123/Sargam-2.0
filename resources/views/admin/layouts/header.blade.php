<!--  Header Start -->
<header class="topbar">
    <div class="with-vertical">

        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Header -->
        <!-- ---------------------------------- -->
        <nav class="navbar navbar-expand-lg p-0">
            <ul class="navbar-nav w-100 justify-content-center text-center">
                <!-- Hamburger (mobile only) -->
                <li class="nav-item d-flex d-xl-none justify-content-center align-items-center">
                    <a class="nav-link nav-icon-hover-bg rounded-circle sidebartoggler" id="headerCollapse"
                        href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                </li>

                <!-- Home -->
                <li class="nav-item d-none d-xl-flex flex-column align-items-center justify-content-center mx-3">
                    <a href="../main/app-chat.html" class="text-decoration-none text-dark text-center">
                        <iconify-icon icon="solar:home-angle-bold-duotone" class="mb-1" style="color:#af2910;font-size: 1.5rem;">
                        </iconify-icon>
                        <h6 class="mb-0">Home</h6>
                    </a>
                </li>

                <!-- Setup -->
                <li class="nav-item d-none d-xl-flex flex-column align-items-center justify-content-center mx-3">
                    <a href="../main/app-chat.html" class="text-decoration-none text-dark text-center">
                        <iconify-icon icon="solar:settings-minimalistic-bold-duotone" class="mb-1"
                            style="color:#af2910; font-size: 1.5rem;"></iconify-icon>
                        <h6 class="mb-0">Setup</h6>
                    </a>
                </li>

                <!-- Communication -->
                <li class="nav-item d-none d-xl-flex flex-column align-items-center justify-content-center mx-3">
                    <a href="../main/app-chat.html" class="text-decoration-none text-dark text-center">
                        <iconify-icon icon="solar:circle-bottom-up-bold-duotone" class="mb-1"
                            style="color:#af2910; font-size: 1.5rem;"></iconify-icon>
                        <h6 class="mb-0">Communication</h6>
                    </a>
                </li>

                <!-- Academics -->
                <li class="nav-item d-none d-xl-flex flex-column align-items-center justify-content-center mx-3">
                    <a href="../main/app-chat.html" class="text-decoration-none text-dark text-center">
                        <iconify-icon icon="solar:square-academic-cap-2-bold-duotone" class="mb-1"
                            style="color:#af2910; font-size: 1.5rem;"></iconify-icon>
                        <h6 class="mb-0">Academics</h6>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item d-flex d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle  sidebartoggler " id="headerCollapse"
                        href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                </li>
            </ul>

            <div class="d-block d-lg-none py-9 py-xl-0">
                <img src="{{asset('admin_assets/images/logos/logo.svg')}}" alt="matdash-img">
            </div>
            <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
            </a>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">

                <div class="d-flex align-items-center justify-content-between">

                    <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                        <li class="nav-item dropdown">
                            <a href="javascript:void(0)"
                                class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                                type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                                aria-controls="offcanvasWithBothOptions">
                                <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                            </a>
                        </li>


                        <!-- ------------------------------- -->
                        <!-- start profile Dropdown -->
                        <!-- ------------------------------- -->
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2 lh-base">
                                    <img src="{{asset('admin_assets/images/profile/user-1.jpg')}}"
                                        class="rounded-circle" width="35" height="35" alt="matdash-img">
                                    <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                                </div>
                            </a>
                            <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                aria-labelledby="drop1">
                                <div class="position-relative px-4 pt-3 pb-2">
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                        <img src="{{asset('admin_assets/images/profile/user-1.jpg')}}"
                                            class="rounded-circle" width="56" height="56" alt="matdash-img">
                                        <div>
                                            <h5 class="mb-0 fs-12">David McMichael <span
                                                    class="text-success fs-11">Pro</span>
                                            </h5>
                                            <p class="mb-0 text-dark">
                                                david@wrappixel.com
                                            </p>
                                        </div>
                                    </div>
                                    <div class="message-body">
                                        <a href="page-user-profile.html" class="p-2 dropdown-item h6 rounded-1">
                                            My Profile
                                        </a>
                                        <a href="page-pricing.html" class="p-2 dropdown-item h6 rounded-1">
                                            My Subscription
                                        </a>
                                        <a href="app-invoice.html" class="p-2 dropdown-item h6 rounded-1">
                                            My Invoice <span
                                                class="badge bg-danger-subtle text-danger rounded ms-8">4</span>
                                        </a>
                                        <a href="page-account-settings.html" class="p-2 dropdown-item h6 rounded-1">
                                            Account Settings
                                        </a>
                                        <form action="{{route('logout')}}" method="POST">
                                            @csrf
                                            <button type="submit" class="p-2 dropdown-item h6 rounded-1">
                                                Sign Out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <!-- ------------------------------- -->
                        <!-- end profile Dropdown -->
                        <!-- ------------------------------- -->
                    </ul>
                </div>
            </div>
        </nav>
        <!-- ---------------------------------- -->
        <!-- End Vertical Layout Header -->
        <!-- ---------------------------------- -->
    </div>
</header>
<!--  Header End -->