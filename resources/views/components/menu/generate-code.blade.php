@foreach(config($config) as $section)

    @if(!isset($section['permission']) || auth()->user()->can($section['permission']))
        <li class="sidebar-item">
            <a class="sidebar-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                href="#{{ $section['id'] }}" role="button" aria-expanded="false" aria-controls="{{ $section['id'] }}"
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