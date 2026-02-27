<div class="breadcrumb-wrapper mb-4">
    <div class="card card-body py-3 px-3 px-md-4 px-lg-5 border-0 mb-0 position-relative overflow-hidden shadow-sm">
        <!-- Decorative Background Elements -->
        <div class="breadcrumb-bg-decoration" aria-hidden="true"></div>
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>
        
        <div class="row align-items-center g-2 g-md-3 position-relative">
            <div class="col-12">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <!-- Back Button & Title Section -->
                    <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap">
                        <button type="button" 
                                onclick="window.history.back()" 
                                class="btn-back btn d-flex align-items-center justify-content-center gap-2 rounded-pill px-3 px-sm-4 py-2 shadow-sm border-0" 
                                title="Go back"
                                aria-label="Go back to previous page">
                            <i class="material-icons material-symbols-rounded back-icon" aria-hidden="true">arrow_back_ios</i>
                            <span class="d-none d-sm-inline fw-semibold">Back</span>
                        </button>
                        
                        <div class="title-wrapper d-flex align-items-center gap-2">
                            <div class="title-indicator" aria-hidden="true"></div>
                            <h1 class="h4 mb-0 card-title fw-bold text-dark lh-sm title-text">
                                {{ $title }}
                            </h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
