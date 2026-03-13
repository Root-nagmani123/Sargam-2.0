<header>
    {{-- Top accessibility bar --}}
    <div class="top-header">
        <div class="container d-flex justify-content-between align-items-center small">
            <div class="d-flex align-items-center">
                {{-- Flag / Govt of India text --}}
                <span class="me-2">
                    {{-- Optional small flag icon – add/replace image if available --}}
                    <img src="https://negd.gov.in/wp-content/themes/negd-update/assets/images/icon/Inidan-Flag.png" alt="Indian Flag" height="14">
                </span>
                <span>Government of India</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="#content" class="text-white text-decoration-none">Skip to Main Content</a>
                <span class="vl d-none d-md-inline"></span>
                <a href="#" class="text-white text-decoration-none">Screen Reader</a>

                <div class="dropdown">
                    <a class="text-white text-decoration-none dropdown-toggle" href="#"
                        id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        English
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                        <li><a class="dropdown-item" href="#">English</a></li>
                        <li><a class="dropdown-item" href="#">हिन्दी</a></li>
                    </ul>
                </div>

                <button class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </div>
        </div>
    </div>

    {{-- Logo bar in LBSNAA theme --}}
    <div class="header border-bottom">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                <div class="d-flex align-items-center text-center text-md-start mb-2 mb-md-0">
                    {{-- Government emblem / generic logo (left) --}}
                    {{-- Replace with actual emblem image if available --}}
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b4/Emblem_of_India_with_transparent_background.png" alt="Government of India Emblem" height="64"
                        class="me-3">

                    <div>
                        <div class="fw-semibold text-uppercase small text-muted">
                            Government of India
                        </div>
                        <div class="fw-bold text-primary">
                            Lal Bahadur Shastri National Academy of Administration
                        </div>
                        <div class="small text-muted">
                            Mussoorie, Uttarakhand
                        </div>
                    </div>
                </div>

                {{-- LBSNAA logo on the right --}}
                <div class="d-flex align-items-center">
                    {{-- IMPORTANT: place the LBSNAA logo file at public/images/lbsnaa-logo.png
                         or update the path below to whatever you use --}}
                    <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" class="img-fluid"
                        style="max-height: 72px;">
                </div>
            </div>
        </div>
    </div>
</header>

