{{-- In-place navigation skeleton.

     A silhouette of the sidebar + page content, raised while an in-app
     navigation is in flight. This app is server-rendered, so after a link click
     the browser keeps painting the OLD page until the server responds — a slow
     screen looks frozen and users click again. This acknowledges the click.

     It deliberately stops below the real topbar (which stays live), so the app
     never looks like it hard-reloaded. Offset and side-column width are measured
     off the real chrome by js/sargam-nav-skeleton.js.

     Distinct from #sargamLoader (layouts/partials/service-loader), which is the
     full-page service notice shown only on a fresh load or refresh. --}}
<div class="ds-nav-skel" id="sargamNavSkeleton" aria-hidden="true">
    <div class="ds-nav-skel-side">
        <div class="ds-skel-rail">
            @for ($i = 0; $i < 6; $i++)
                <span class="ds-skel-rail-item">
                    <span class="ds-skeleton"></span>
                    <span class="ds-skeleton"></span>
                </span>
            @endfor
        </div>
        <div class="ds-skel-menu">
            <span class="ds-skeleton ds-skel-menu-title"></span>
            @for ($i = 0; $i < 9; $i++)
                <span class="ds-skel-menu-item">
                    <span class="ds-skeleton"></span>
                    <span class="ds-skeleton"></span>
                </span>
            @endfor
        </div>
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
                    @for ($c = 0; $c < 6; $c++)<span class="ds-skeleton"></span>@endfor
                </div>
                @for ($r = 0; $r < 7; $r++)
                    <div class="ds-skel-tr">
                        @for ($c = 0; $c < 6; $c++)<span class="ds-skeleton"></span>@endfor
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
