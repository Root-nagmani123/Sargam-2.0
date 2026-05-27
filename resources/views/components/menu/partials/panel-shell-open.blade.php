@php
    $panelMenuId = $panelMenuId ?? 'menu-right-1';
    $panelMenuTitle = $panelMenuTitle ?? 'MENU';
    $panelMenuClass = trim('sidebar-nav sidebar-panel-menu d-block simplebar-scrollable-y ' . ($panelMenuClass ?? ''));
@endphp
<nav class="{{ $panelMenuClass }}" id="{{ $panelMenuId }}" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content sidebar-panel-menu__content">
                        <p class="sidebar-panel-menu__title text-uppercase text-secondary small fw-semibold mb-3 px-1">
                            {{ $panelMenuTitle }}
                        </p>
                        <ul class="sidebar-menu list-unstyled mb-0" id="sidebarnav">
