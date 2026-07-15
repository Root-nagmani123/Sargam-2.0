{{-- App-shell skeleton (preloader).

     A silhouette of the chrome that is about to appear: topbar, sidebar rail +
     menu, page header, and a table card. Styled by LAYER D of
     css/sargam-app.css; driven by js/sargam-skeleton-loader.js, which hides it
     on load and re-shows it while an in-app navigation is in flight.

     Shared by admin/layouts/master and faculty/layouts/master — keep it here
     rather than inline so the two shells can't drift apart.

     The .sargam-loader / #sargamLoader names are load-bearing: several print
     stylesheets hide the overlay by name. --}}
<div class="sargam-loader" id="sargamLoader" role="status" aria-live="polite" aria-label="Loading page">
    <div class="ds-skel-shell" aria-hidden="true">
        <div class="ds-skel-topbar">
            <span class="ds-skeleton ds-skel-brand"></span>
            @for($i = 0; $i < 4; $i++)
                <span class="ds-skeleton ds-skel-tab"></span>
            @endfor
            <span class="ds-skeleton ds-skeleton-chip ds-skel-spacer"></span>
            <span class="ds-skeleton ds-skeleton-avatar"></span>
        </div>
        <div class="ds-skel-body">
            <div class="ds-skel-rail">
                @for($i = 0; $i < 6; $i++)
                    <span class="ds-skel-rail-item">
                        <span class="ds-skeleton"></span>
                        <span class="ds-skeleton"></span>
                    </span>
                @endfor
            </div>
            <div class="ds-skel-menu">
                <span class="ds-skeleton ds-skel-menu-title"></span>
                @for($i = 0; $i < 9; $i++)
                    <span class="ds-skel-menu-item">
                        <span class="ds-skeleton"></span>
                        <span class="ds-skeleton"></span>
                    </span>
                @endfor
            </div>
            <div class="ds-skel-content">
                <div class="ds-skel-page-head">
                    <span class="ds-skeleton ds-skel-page-title"></span>
                    <span class="ds-skeleton ds-skeleton-btn ds-skel-page-btn"></span>
                </div>
                <div class="ds-skeleton-card">
                    <div class="ds-skel-toolbar">
                        <span class="ds-skeleton ds-skeleton-btn-sm"></span>
                        <span class="ds-skeleton ds-skeleton-btn-sm"></span>
                        <span class="ds-skeleton ds-skel-search"></span>
                    </div>
                    <div class="ds-skel-table">
                        <div class="ds-skel-tr ds-skel-thead">
                            @for($c = 0; $c < 6; $c++)<span class="ds-skeleton"></span>@endfor
                        </div>
                        @for($r = 0; $r < 7; $r++)
                            <div class="ds-skel-tr">
                                @for($c = 0; $c < 6; $c++)<span class="ds-skeleton"></span>@endfor
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span class="visually-hidden">Loading, please wait…</span>
</div>
