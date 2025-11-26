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
        background-color: #B12923;
        color: #fbf8f8 !important;
        font-size: 16px !important;
        line-height: 24px;
        font-weight: 500 !important;
        padding:10px !important;
        border-radius: 26px !important;
        Width:90px !important;
        Height:40px !important;
        text-align: center !important;
        justify-content: center !important;
        transition: all 0.3s ease-in-out;
        box-shadow:3px 0 3px 0 rgba(232,191,189,0.8);
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

    <ul class="navbar-nav shadow-sm px-3 py-1 gap-1 align-items-center"
        style="border-radius: 30px; height: 60px;background-color: #F2F2F2">

        <!-- Home -->
        <li class="nav-item">
            <a href="#" class="nav-link active rounded-pill px-4 py-1 fw-semibold">
                Home
            </a>
        </li>

        <!-- Setup -->
        <li class="nav-item">
            <a href="#tab-setup" class="nav-link rounded-pill px-3 py-1 fw-semibold">
                Setup
            </a>
        </li>

        <!-- Communications -->
        <li class="nav-item">
            <a href="#tab-communications" class="nav-link rounded-pill px-3 py-1 fw-semibold">
                Communications
            </a>
        </li>

        <!-- Academics -->
        <li class="nav-item">
            <a href="#tab-academics" class="nav-link rounded-pill px-3 py-1 fw-semibold">
                Academics
            </a>
        </li>

        <!-- Material Management -->
        <li class="nav-item">
            <a href="#tab-material-management" class="nav-link rounded-pill px-3 py-1 fw-semibold">
                Material Management
            </a>
        </li>

        <!-- CUSTOM DROPDOWN WITH ARROW -->
        <li class="nav-item dropdown">
            <a class="nav-link rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-1 dropdown-toggle-custom"
               href="#" id="financialDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Financial
                <i class="material-icons material-symbols-rounded fs-5 dropdown-arrow">expand_more</i>
            </a>

            <ul class="dropdown-menu shadow-sm border-0" aria-labelledby="financialDropdown">
                <li><a class="dropdown-item" href="#">Budget</a></li>
                <li><a class="dropdown-item" href="#">Accounts</a></li>
            </ul>
        </li>

        <!-- SEARCH ICON (AT THE END) -->
        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link rounded-pill px-2 py-1 ">
                <i class="material-icons material-symbols-rounded text-dark" style="font-size: 24px;">search</i>
            </a>
        </li>

    </ul>

</div>


                <div class="d-flex align-items-center ms-auto gap-3" style="margin-right:56px;">

                    <!-- 🔐 Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 text-danger" aria-label="Sign Out">
                             <i class="material-icons material-symbols-rounded" style="font-size: 24px; color:#333333;">logout</i>
                        </button>
                    </form>

                    <!-- ⏰ Last Login -->
                    <div class="text-end small text-muted lh-sm justify-content-center" >
                        <div class="text-center" style="font-size: 12px;line-height: 16px;">Last login:</div>
                        <time id="myTime" datetime="2025-05-14T13:56:02">2025-05-14 13:56:02</time>

                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<!-- 🧠 Search Toggle Script -->
 <script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll("time").forEach(function (el) {
        const dt = new Date(el.getAttribute("datetime"));

        const day   = String(dt.getDate()).padStart(2, '0');
        const month = String(dt.getMonth() + 1).padStart(2, '0'); // JS months start at 0
        const year  = dt.getFullYear();

        const hours   = String(dt.getHours()).padStart(2, '0');
        const minutes = String(dt.getMinutes()).padStart(2, '0');
        const seconds = String(dt.getSeconds()).padStart(2, '0');

        el.textContent = `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
    });
});
</script>

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
