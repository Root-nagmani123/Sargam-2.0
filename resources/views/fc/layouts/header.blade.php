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
            background: var(--bs-body-bg, #fff);
            box-shadow: 0 -0.35rem 1.25rem rgba(0, 74, 147, 0.12);
            padding: 0.65rem 1rem calc(0.85rem + env(safe-area-inset-bottom, 0));
            border-top: 1px solid rgba(0, 74, 147, 0.12);
            border-radius: 1rem 1rem 0 0;
        }

        #navbarNav .navbar-nav {
            justify-content: space-between;
            align-items: stretch;
            width: 100%;
            flex-direction: row;
            gap: 0.35rem;
        }

        #navbarNav .nav-item {
            flex: 1 1 0;
            min-width: 0;
            display: flex;
        }

        #navbarNav .nav-link {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 0.8125rem;
            line-height: 1.25;
            padding: 0.5rem 0.35rem !important;
            border-radius: 0.65rem;
        }

        #navbarNav .nav-item .btn {
            font-size: 0.8125rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            white-space: nowrap;
        }

        body {
            padding-bottom: 78px;
        }
    }

    @media (min-width: 992px) {
        #navbarNav {
            position: static !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            border-top: none !important;
            padding: 0 !important;
        }

        #navbarNav .navbar-nav {
            flex-direction: row;
            gap: 0.25rem;
        }

        #navbarNav .nav-link {
            font-size: inherit;
            padding: 0.5rem 1rem !important;
        }

        body {
            padding-bottom: 0;
        }
    }

    /* Top bar + main header polish */
    .fc-header-main {
        box-shadow: 0 0.125rem 1rem rgba(0, 74, 147, 0.06);
    }

    .fc-top-header .nav-link {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .fc-top-header .nav-link:hover {
        transform: translateY(-1px);
    }

    .fc-header-main .nav-link:not(.btn) {
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    @media (min-width: 992px) {
        .fc-header-main .nav-link:not(.btn):hover {
            background-color: rgba(0, 74, 147, 0.06);
        }
    }

    .fc-header-main .navbar-brand img {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .fc-header-main .navbar-brand:hover img {
        box-shadow: 0 0.25rem 0.75rem rgba(0, 74, 147, 0.15) !important;
        transform: translateY(-1px);
    }

    .fc-header-main .navbar-toggler:focus-visible,
    .fc-top-header .nav-link:focus-visible,
    .fc-header-main .nav-link:focus-visible,
    .fc-header-main .btn:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.35);
    }

    .fc-top-header .nav-link:focus-visible {
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.55);
    }
</style>

<!-- Top Blue Bar (Govt of India) - Hidden on mobile -->
<div class="top-header fc-top-header d-none d-md-block border-bottom border-white border-opacity-25">
    <div class="container-lg py-2 px-3 px-lg-4">
        <div class="row align-items-center gy-2">
            <div class="col-md-6 col-lg-5 d-flex align-items-center">
                <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                    alt="Flag of India" height="28" width="42" class="flex-shrink-0 rounded-1 shadow-sm">
                <span class="ms-2 small text-white text-opacity-90 fw-medium lh-sm">Government of India</span>
            </div>
            <div class="col-md-6 col-lg-7">
                <ul class="nav justify-content-md-end align-items-center flex-wrap column-gap-3 row-gap-2 mb-0 small">
                    <li class="nav-item">
                        <a href="#content"
                            class="nav-link link-light link-opacity-75 link-opacity-100-hover py-1 px-2 px-md-0 rounded-2">
                            Skip to Main Content
                        </a>
                    </li>
                    <li class="nav-item d-none d-sm-block" aria-hidden="true">
                        <span class="text-white text-opacity-50 user-select-none">|</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link link-light link-opacity-75 link-opacity-100-hover text-decoration-none d-inline-flex align-items-center gap-2 py-1 px-2 px-md-0 rounded-2"
                            id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;">
                            <img src="{{ asset('images/accessible.png') }}" alt="" width="20" height="20" class="flex-shrink-0 opacity-90">
                            <span class="text-white fw-medium">More</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Header -->
<div class="header fc-header-main sticky-top bg-body rounded-bottom-4 border-bottom border-light border-opacity-75">
    <div class="container-lg py-2 py-md-3 px-3 px-lg-4">
        <nav class="navbar navbar-expand-lg navbar-light py-0 align-items-center" aria-label="Primary">
            <div class="d-flex align-items-center gap-2 gap-sm-3 me-auto flex-shrink-0">
                <a class="navbar-brand p-0 m-0" href="#" aria-label="National emblem">
                    <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="National emblem"
                        height="64"
                        class="rounded-3 bg-white border p-1 shadow-sm object-fit-contain" style="width: auto; max-height: 64px;">
                </a>
                <span class="vr d-none d-sm-inline align-self-stretch my-1 opacity-25" aria-hidden="true"></span>
                <a class="navbar-brand p-0 m-0" href="#" aria-label="LBSNAA">
                    <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA logo" height="64"
                        class="rounded-3 bg-white border p-1 shadow-sm object-fit-contain" style="width: auto; max-height: 64px;">
                </a>
            </div>

            <button class="navbar-toggler border-0 rounded-3 p-2 shadow-sm" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-lg-end flex-grow-1" id="navbarNav">
                <ul class="navbar-nav align-items-lg-center align-items-stretch ms-lg-auto w-100 w-lg-auto pt-3 pt-lg-0 pb-1 pb-lg-0 flex-lg-row flex-lg-nowrap gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-primary-emphasis link-primary link-opacity-75-hover rounded-3 px-3 py-2"
                            href="https://www.lbsnaa.gov.in/menu/about-lbsnaa" target="_blank" rel="noopener noreferrer">
                            About Us
                            <i class="bi bi-box-arrow-up-right ms-1 small opacity-75 d-none d-lg-inline" aria-hidden="true"></i>
                            <span class="visually-hidden">(opens in new tab)</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-primary-emphasis link-primary link-opacity-75-hover rounded-3 px-3 py-2"
                            href="https://www.lbsnaa.gov.in/footer_menu/contact-us" target="_blank"
                            rel="noopener noreferrer">
                            Contact
                            <i class="bi bi-box-arrow-up-right ms-1 small opacity-75 d-none d-lg-inline" aria-hidden="true"></i>
                            <span class="visually-hidden">(opens in new tab)</span>
                        </a>
                    </li>
                    <li class="nav-item mt-2 mt-lg-0 w-100 w-lg-auto flex-shrink-0">
                        <a class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold w-100 w-lg-auto d-inline-flex align-items-center justify-content-center gap-2"
                            href="{{ route('fc.login') }}">
                            <i class="bi bi-box-arrow-in-right d-none d-lg-inline" aria-hidden="true"></i>
                            Login
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
