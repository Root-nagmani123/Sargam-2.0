{{--
    LBSNAA letterhead for printed sheets.

    Hidden on screen, shown only in print — the page keeps its normal breadcrumb
    heading in the browser, and the printout gets the institute header instead
    (public/css/master-admin.css hides `.modern-breadcrumb-wrapper` in print).

    Used by the faculty detail sheet and the blank form; both mark their root
    `.mst-page.print-area`, which is what the print rules key off.

    Props:
      title — the report title printed under the letterhead (optional)
--}}
@props(['title' => null])

@php
    // Take the first mark that is actually on disk; the project keeps the same
    // logos under a couple of different paths depending on the theme build.
    $pickAsset = function (array $candidates) {
        foreach ($candidates as $relative) {
            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return null;
    };

    $emblemSrc = $pickAsset([
        'admin_assets/images/logos/ashoka.png',
        'images/ashoka.png',
    ]);

    $logoSrc = $pickAsset([
        'images/lbsnaa_logo.jpg',
        'images/lbsnaa_logo.png',
        'admin_assets/images/logos/logo_new.png',
        'admin_assets/images/logos/logo.png',
    ]);
@endphp

<div class="mst-print-header" aria-hidden="true">
    <div class="mst-print-header__row">
        @if($emblemSrc)
            <img src="{{ $emblemSrc }}" alt="" class="mst-print-header__mark">
        @endif

        <div class="mst-print-header__brand">
            <div class="mst-print-header__gov">Government of India</div>
            <div class="mst-print-header__name">LBSNAA Mussoorie</div>
            <div class="mst-print-header__full">Lal Bahadur Shastri National Academy of Administration</div>
        </div>

        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="" class="mst-print-header__mark">
        @endif
    </div>

    @if(filled($title))
        <div class="mst-print-header__title">{{ $title }}</div>
    @endif
</div>
