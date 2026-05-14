<style>
    /* Mobile-first: Bottom navigation for mobile devices only */
    @media (max-width: 991.98px) {
        #navbarNav {
            display: flex !important;
            visibility: visible !important;
            position: fixed !important;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        #navbarNav .navbar-nav {
            justify-content: space-around;
            width: 100%;
        }

        /* Add padding to body to prevent content from being hidden behind fixed bottom nav */
        body {
            padding-bottom: 70px;
        }
    }

    /* Desktop view: Keep original positioning */
    @media (min-width: 992px) {
        #navbarNav {
            position: static !important;
            box-shadow: none !important;
        }

        body {
            padding-bottom: 0;
        }
    }
</style>

   <!-- Top Blue Bar (Govt of India) - Hidden on mobile -->
   <div class="top-header d-none d-md-block">
       <div class="container">
           <div class="row align-items-center">
               <div class="col-md-3 d-flex align-items-center">
                   <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                       alt="GoI Logo" height="30">
                   <span class="ms-2" style="font-size: 14px;">Government of India</span>
               </div>
               <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                   <ul class="nav justify-content-end align-items-center">
                       <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                               style=" font-size: 12px;">Skip to Main Content</a></li>
                       <span class="text-muted me-3 ">|</span>
                       <li class="nav-item"><a class="text-white text-decoration-none" id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;"><img
                                   src="{{ asset('images/accessible.png') }}" alt="" width="20">
                               <span class="text-white ms-1" style=" font-size: 12px;">
                                   More
                               </span>
                           </a>
                       </li>
                   </ul>
               </div>
           </div>
       </div>
   </div>
   <!-- Sticky Header -->
   <div class="header sticky-top bg-white shadow-sm">
       <div class="container">
           <nav class="navbar navbar-expand-lg navbar-light">
               <div class="container-fluid px-0">
                   <!-- Logo 1 -->
                   <a class="navbar-brand me-1 me-md-2" href="#">
                       <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="Logo 1"
                           class="img-fluid" height="80">
                   </a>
                   <!-- Divider - Hidden on mobile -->
                   <span class="vr mx-1 mx-md-2 d-none d-sm-block"></span>
                   <!-- Logo 2 -->
                   <a class="navbar-brand me-auto" href="#">
                       <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" class="img-fluid" height="80">
                   </a>

                   <!-- Mobile toggle button -->
                   <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                       aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                       <span class="navbar-toggler-icon"></span>
                   </button>

                   <!-- Navigation Menu -->
                   <div class="collapse navbar-collapse" id="navbarNav">
                       <ul class="navbar-nav ms-auto align-items-lg-center">
                           <li class="nav-item">
                               <a class="nav-link fw-medium" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa" target="_blank">About Us</a>
                           </li>
                           <li class="nav-item">
                               <a class="nav-link fw-medium" href="https://www.lbsnaa.gov.in/footer_menu/contact-us" target="_blank">Contact</a>
                           </li>
                           <li class="nav-item mt-2 mt-lg-0">
                               <a class="btn btn-outline-primary" href="{{ route('fc.login') }}">Login</a>
                           </li>
                       </ul>
                   </div>
               </div>
           </nav>
       </div>
   </div>
