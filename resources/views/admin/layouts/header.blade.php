<!--  Header Start -->
<header class="topbar">
    <div class="with-vertical">

        <!-- ---------------------------------- -->
        <!-- Start Vertical Layout Header -->
        <!-- ---------------------------------- -->
        <nav class="navbar navbar-expand-lg p-0">



            <ul class="navbar-nav">
                <li class="nav-item d-flex d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle  sidebartoggler " id="headerCollapse"
                        href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                </li>
            </ul>

            <div class="d-block d-lg-none py-9 py-xl-0">
                <img src="{{asset('admin_assets/images/logos/logo.svg')}}" alt="sargam-img">
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
                                    {{-- <img src="{{asset('admin_assets/images/profile/user-1.jpg')}}"
                                        class="rounded-circle" width="35" height="35" alt="matdash-img"> --}}

                                    <span class="rounded-circle fw-bolder border p-2 text-white bg-dark" width="35" height="35">
                                        {{ Auth::user()->getInitialsAttribute() }}
                                    </span>
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
                                            <h5 class="mb-0 fs-12">{{ Auth::user()->user_name }}</h5>
                                            <p class="mb-0 text-dark">
                                                {{ Auth::user()->email_id }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="message-body">
                                        <a href="#" class="p-2 dropdown-item h6 rounded-1">
                                            My Profile
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tab-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all tab items
            document.querySelectorAll('.tab-item').forEach(tab => tab.classList.remove(
                'active'));

            // Add active class to clicked tab
            this.classList.add('active');

            // Remove active class and hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
                pane.classList.add('d-none');
            });

            // Show and activate the target content pane
            const target = this.getAttribute('data-tab');
            const targetPane = document.getElementById('tab-' + target);
            if (targetPane) {
                targetPane.classList.remove('d-none');
                targetPane.classList.add('active');
            }
        });
    });
});
</script>