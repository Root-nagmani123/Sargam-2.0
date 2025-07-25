
<nav class="sidebar-nav d-block simplebar-scrollable-y" id="{{ $id }}" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 0px 20px 24px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            @foreach(config($config) as $section)

                                @if(!isset($section['permission']) || auth()->user()->can(abilities: $section['permission']))
                                    <li class="sidebar-item">
                                        <a class="sidebar-link d-flex justify-content-between align-items-center"
                                            data-bs-toggle="collapse" href="#{{ $section['id'] }}" role="button"
                                            aria-expanded="false" aria-controls="{{ $section['id'] }}"
                                            style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                                            <span class="hide-menu fw-bold">{{ $section['title'] }}</span>
                                            @if(!empty($section['icon']))
                                                <i class="bi {{ $section['icon'] }} ms-2 text-white"></i>
                                            @endif
                                        </a>
                                    </li>
                                    <ul class="collapse list-unstyled ps-3" id="{{ $section['id'] }}">

                                        @foreach($section['items'] as $item)
                                            @if(!isset($item['permission']) || auth()->user()->can($item['permission']))
                                                <li class="sidebar-item {{ request()->routeIs($item['route']) ? 'selected' : '' }}">
                                                    <a class="sidebar-link" href="{{ route($item['route']) }}">
                                                        <iconify-icon icon="{{ $item['icon'] }}"></iconify-icon>
                                                        @if(!empty($item['title']))
                                                            <span class="hide-menu">{{ $item['title'] }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            @endforeach
                            
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>