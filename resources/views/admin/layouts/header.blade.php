<!-- 🌟 Header Start -->
<style>
    /* --- Navbar Styling --- */
    .navbar-nav .nav-link {
        color: #333;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link:focus {
        background-color: #f2f2f2;
        color: #000;
        outline: none;
    }

    .navbar-nav .nav-link.active {
        background-color: #c62828;
        color: #fff !important;
    }

    .navbar-nav {
        background-color: #f9f9f9;
        border-radius: 50rem;
    }

    .btn-link {
        text-decoration: none !important;
    }

    .btn-link:hover {
        opacity: 0.8;
    }

    @media (max-width: 991.98px) {
        .navbar-nav {
            border-radius: 0.5rem;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .navbar-nav .nav-link {
            width: 100%;
            border-radius: 0.5rem;
        }
    }

    /* --- Search Animation --- */
    .search-wrapper {
        position: relative;
        display: inline-block;
    }

    .search-box {
        position: absolute;
        top: 50%;
        left: 120%;
        transform: translateY(-50%) scale(0.95);
        opacity: 0;
        display: none;
        min-width: 220px;
        transition: all 0.3s ease;
        z-index: 1050;
    }

    .search-box.show {
        display: block !important;
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }

    .input-group-sm .form-control {
        border-radius: 50rem 0 0 50rem;
    }

    .input-group-sm .btn {
        border-radius: 0 50rem 50rem 0;
    }
</style>

<header class="topbar">
    <div class="with-vertical">
        <nav class="navbar navbar-expand-lg p-0">
            <ul class="navbar-nav">
                <li class="nav-item d-flex d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle sidebartoggler" id="headerCollapse"
                        href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                    </a>
                </li>
            </ul>

            <div class="d-block d-lg-none py-9 py-xl-0">
                <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="logo">
            </div>

            <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                    <ul class="navbar-nav rounded-pill bg-light shadow-sm px-3 py-1 gap-1">
                        <li class="nav-item"><a href="#" class="nav-link active rounded-pill px-4 py-1 fw-semibold">Home</a></li>
                        <li class="nav-item"><a href="#tab-setup" class="nav-link rounded-pill px-3 py-1 fw-semibold">Setup</a></li>
                        <li class="nav-item"><a href="#tab-communications" class="nav-link rounded-pill px-3 py-1 fw-semibold">Communications</a></li>
                        <li class="nav-item"><a href="#tab-academics" class="nav-link rounded-pill px-3 py-1 fw-semibold">Academics</a></li>
                        <li class="nav-item"><a href="#tab-material-management" class="nav-link rounded-pill px-3 py-1 fw-semibold">Material Management</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle rounded-pill px-3 py-1 fw-semibold" href="#"
                                id="financialDropdown" data-bs-toggle="dropdown" aria-expanded="false">Financial</a>
                            <ul class="dropdown-menu" aria-labelledby="financialDropdown">
                                <li><a class="dropdown-item" href="#">Budget</a></li>
                                <li><a class="dropdown-item" href="#">Accounts</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>

                <div class="d-flex align-items-center ms-auto gap-3">
                    <!-- 🔍 Search Button + Expandable Input -->
                    <div class="search-wrapper">
                        <button class="btn btn-link p-0 text-dark" id="searchToggleBtn" aria-label="Search">
                            <i class="bi bi-search fs-5"></i>
                        </button>

                        <div id="searchContainer" class="search-box">
                            <div class="input-group input-group-sm">
                                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search..."
                                    aria-label="Search in table">
                                <button class="btn btn-outline-secondary" id="closeSearchBtn" aria-label="Close Search">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 🔐 Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-danger" aria-label="Sign Out">
                            <iconify-icon icon="solar:login-3-outline" class="fs-7"></iconify-icon>
                        </button>
                    </form>

                    <!-- ⏰ Last Login -->
                    <div class="text-end small text-muted lh-sm">
                        <div>Last login:</div>
                        <time datetime="2025-05-14T13:56:02">2025-05-14 13:56:02</time>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<!-- 🧠 Search Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('searchToggleBtn');
    const searchBox = document.getElementById('searchContainer');
    const closeBtn = document.getElementById('closeSearchBtn');
    const searchInput = document.getElementById('tableSearchInput');

    // Open/close search
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        searchBox.classList.toggle('show');
        if (searchBox.classList.contains('show')) {
            searchInput.focus();
        } else {
            searchInput.value = '';
        }
    });

    // Close via X button
    closeBtn.addEventListener('click', () => {
        searchBox.classList.remove('show');
        searchInput.value = '';
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!searchBox.contains(e.target) && !toggleBtn.contains(e.target)) {
            searchBox.classList.remove('show');
        }
    });

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            searchBox.classList.remove('show');
        }
    });
});
</script>
<!-- 🌟 Header End -->
