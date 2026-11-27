<aside class="gtc-side">
    <h6>Guest TA Claim</h6>
    <nav class="nav flex-column">
        @foreach ($gtcMenu as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-link {{ request()->routeIs(...explode('|', $item['match'])) ? 'active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}
                @if (! empty($item['count']))
                    <span class="badge rounded-pill text-bg-danger">{{ $item['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</aside>
