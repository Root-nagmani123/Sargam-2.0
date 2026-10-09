{{--
    Row actions: View · Edit · status switch · Delete, as equal-width
    icon-over-label stacks (docs/new-design-index-page.md §3b). One source for
    every master / mapping grid — Yajra closures render it with
    view('admin.master.partials.grid-actions', [...])->render(), Blade tables
    @include it. Styled by public/css/master-admin.css (.mst-page .mst-act*).

    Every key is optional; an action is rendered only when its key is given.

    'name'   => record name, used in titles / aria-labels

    'view'   => ['href' => url]                         link
              | ['class' => 'js-hook', 'attrs' => [..]]  button (page JS opens it)

    'edit'   => same shape as 'view'

    'toggle' => ['active' => bool, 'table' => 'x_master', 'column' => 'active_inactive',
                 'id' => pk, 'id_column' => null, 'class' => '', 'attrs' => [..]]
                The global .status-toggle handler (admin_assets/js/custom.js)
                does the confirm + AJAX; nothing to wire. Pass 'class' =>
                'status-toggle-own-route' + attrs['data-url'] for a guarded route.
                'global' => false leaves off the .status-toggle class, for a page
                that wires its own handler (else custom.js fires a second request).

    'delete' => ['action' => url, 'method' => 'DELETE']   real <form>, confirmed
                                                          by public/js/master-admin.js
              | ['class' => 'js-hook', 'attrs' => [..]]   button for a JS-driven delete
                + 'disabled' => bool, 'reason' => tooltip when disabled
                + 'confirm' => custom confirmation text (form variant)

    'extra'  => [['icon' => 'bi-people', 'label' => 'Assign', 'href' => url
                  | 'class' => 'js-hook', 'attrs' => [..], 'tone' => 'view'], ...]
                appended before Delete, same stack shape.
--}}
@php
    $mstName = (string) ($name ?? '');
    $mstAttrs = function (array $attrs = []) {
        $out = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $out .= ' ' . e($key) . ($value === true ? '' : '="' . e((string) $value) . '"');
        }
        return $out;
    };
    $mstLabelFor = fn (string $verb) => trim($verb . ' ' . $mstName);
@endphp
<div class="mst-act-group" role="group" aria-label="Row actions">
    @isset($view)
        @if (!empty($view['href']))
            <a href="{{ $view['href'] }}" class="mst-act mst-act--view {{ $view['class'] ?? '' }}"
               title="{{ $mstLabelFor('View') }}"{!! $mstAttrs($view['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                <span class="mst-act__label">View</span>
            </a>
        @else
            <button type="button" class="mst-act mst-act--view {{ $view['class'] ?? '' }}"
                    title="{{ $mstLabelFor('View') }}"{!! $mstAttrs($view['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                <span class="mst-act__label">View</span>
            </button>
        @endif
    @endisset

    @isset($edit)
        @if (!empty($edit['href']))
            <a href="{{ $edit['href'] }}" class="mst-act mst-act--edit {{ $edit['class'] ?? '' }}"
               title="{{ $mstLabelFor('Edit') }}"{!! $mstAttrs($edit['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="mst-act__label">Edit</span>
            </a>
        @else
            <button type="button" class="mst-act mst-act--edit {{ $edit['class'] ?? '' }}"
                    title="{{ $mstLabelFor('Edit') }}"{!! $mstAttrs($edit['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="mst-act__label">Edit</span>
            </button>
        @endif
    @endisset

    @isset($toggle)
        @php $mstOn = (bool) ($toggle['active'] ?? false); @endphp
        {{-- No .form-check/.form-switch wrapper: custom.css pulls the input
             -2.375rem left inside one, which breaks the stacked layout (§3b). --}}
        <label class="mst-act mst-act--toggle">
            <span class="mst-act__icon">
                <input class="form-check-input {{ ($toggle['global'] ?? true) ? 'status-toggle' : '' }} {{ $toggle['class'] ?? '' }}" type="checkbox" role="switch"
                       data-table="{{ $toggle['table'] ?? '' }}"
                       data-column="{{ $toggle['column'] ?? 'active_inactive' }}"
                       data-id="{{ $toggle['id'] ?? '' }}"
                       @if (!empty($toggle['id_column'])) data-id_column="{{ $toggle['id_column'] }}" @endif
                       aria-label="{{ $mstLabelFor($mstOn ? 'Deactivate' : 'Activate') }}"
                       {!! $mstAttrs($toggle['attrs'] ?? []) !!} @checked($mstOn)>
            </span>
            {{-- Caption names the ACTION, not the state — the badge shows the state. --}}
            <span class="mst-act__label">{{ $mstOn ? 'Deactivate' : 'Activate' }}</span>
        </label>
    @endisset

    @foreach (($extra ?? []) as $mstExtra)
        @php $mstTone = $mstExtra['tone'] ?? 'view'; @endphp
        @if (!empty($mstExtra['href']))
            <a href="{{ $mstExtra['href'] }}" class="mst-act mst-act--{{ $mstTone }} {{ $mstExtra['class'] ?? '' }}"
               title="{{ $mstExtra['title'] ?? $mstLabelFor($mstExtra['label']) }}"{!! $mstAttrs($mstExtra['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi {{ $mstExtra['icon'] ?? 'bi-three-dots' }}" aria-hidden="true"></i></span>
                <span class="mst-act__label">{{ $mstExtra['label'] }}</span>
            </a>
        @else
            <button type="button" class="mst-act mst-act--{{ $mstTone }} {{ $mstExtra['class'] ?? '' }}"
                    title="{{ $mstExtra['title'] ?? $mstLabelFor($mstExtra['label']) }}"{!! $mstAttrs($mstExtra['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi {{ $mstExtra['icon'] ?? 'bi-three-dots' }}" aria-hidden="true"></i></span>
                <span class="mst-act__label">{{ $mstExtra['label'] }}</span>
            </button>
        @endif
    @endforeach

    @isset($delete)
        @if (!empty($delete['disabled']))
            {{-- The server would refuse this delete — show why instead of a red
                 icon that always fails (§3b). --}}
            <span class="mst-act mst-act--del is-disabled" aria-disabled="true" tabindex="0"
                  title="{{ $delete['reason'] ?? 'This record cannot be deleted.' }}">
                <span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="mst-act__label">Delete</span>
                <span class="visually-hidden">{{ $delete['reason'] ?? 'This record cannot be deleted.' }}</span>
            </span>
        @elseif (!empty($delete['action']))
            <form action="{{ $delete['action'] }}" method="POST" class="mst-del-form {{ $delete['form_class'] ?? '' }}"
                  data-name="{{ $mstName }}"
                  @if (!empty($delete['confirm'])) data-confirm="{{ $delete['confirm'] }}" @endif>
                @csrf
                @if (strtoupper($delete['method'] ?? 'DELETE') !== 'POST')
                    @method($delete['method'] ?? 'DELETE')
                @endif
                @foreach (($delete['fields'] ?? []) as $mstField => $mstValue)
                    <input type="hidden" name="{{ $mstField }}" value="{{ $mstValue }}">
                @endforeach
                <button type="submit" class="mst-act mst-act--del {{ $delete['class'] ?? '' }}"
                        title="{{ $mstLabelFor('Delete') }}"{!! $mstAttrs($delete['attrs'] ?? []) !!}>
                    <span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                    <span class="mst-act__label">Delete</span>
                </button>
            </form>
        @else
            <button type="button" class="mst-act mst-act--del {{ $delete['class'] ?? '' }}"
                    title="{{ $mstLabelFor('Delete') }}"{!! $mstAttrs($delete['attrs'] ?? []) !!}>
                <span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="mst-act__label">Delete</span>
            </button>
        @endif
    @endisset
</div>
