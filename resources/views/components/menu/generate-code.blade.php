@php
    $currentUser = auth()->user();
    $excludedUserId = '1555555618834'; 
@endphp

@foreach(config($config) as $section)
    @php
        $hasAccess = false;

        // Check access for section
        if (!isset($section['allowedPermissions']) && !isset($section['route'])) {
            $hasAccess = true;
        } elseif ($currentUser->id == $excludedUserId) {
            $hasAccess = true;
        } elseif (isset($section['route']) && $currentUser->can($section['route'])) {
            $hasAccess = true;
        } elseif (isset($section['allowedPermissions']) && is_array($section['allowedPermissions'])) {
            foreach ($section['allowedPermissions'] as $perm) {
                if ($currentUser->can($perm)) {
                    $hasAccess = true;
                    break;
                }
            }
        }
    @endphp

    @if($hasAccess)
        <li class="sidebar-item">
            <a class="sidebar-link d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                href="#{{ $section['id'] }}" role="button" aria-expanded="false"
                aria-controls="{{ $section['id'] }}"
                style="background-color: #af2910 !important; color: #fff; border-radius: 10px;">
                <span class="hide-menu fw-bold">{{ $section['title'] }}</span>
                @if(!empty($section['icon']))
                    <i class="bi {{ $section['icon'] }} ms-2 text-white"></i>
                @endif
            </a>
        </li>

        <ul class="collapse list-unstyled ps-3" id="{{ $section['id'] }}">
            @foreach($section['items'] as $item)
                @php
                    $canSeeItem = true;

                    if (!$currentUser->id == $excludedUserId) {
                        $itemPermission = $item['permission'] ?? $item['route'] ?? null;

                        if (!empty($itemPermission) && !$currentUser->can($itemPermission)) {
                            $canSeeItem = false;
                        }
                    }

                    // 'visible' key can forcibly hide items regardless of permission
                    if (isset($item['visible']) && !$item['visible']) {
                        $canSeeItem = false;
                    }
                @endphp

                @if($canSeeItem)
                    <li class="sidebar-item {{ request()->routeIs($item['route']) ? 'selected' : '' }}">
                        <a class="sidebar-link" href="{{ route($item['route']) }}">
                            @if(!empty($item['icon']))
                                <iconify-icon icon="{{ $item['icon'] }}"></iconify-icon>
                            @endif
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
