{{--
    Status column: display-only UX4G Tag (ux4g-tag-tonal-*), text + icon so the
    state is never conveyed by colour alone (WCAG 1.4.1).
    The control (switch) lives in the Action column — see grid-actions.

    @include('admin.master.partials.grid-status', ['active' => $row->active_inactive == 1])

    Optional: 'label' to override the text, 'tone' one of
    success | danger | warning | info | primary | secondary for workflow states;
    'order' to emit data-order (sort key) on the tag.
--}}
@php
    $isActive = (bool) ($active ?? false);
    $tone = $tone ?? ($isActive ? 'success' : 'danger');
    $text = $label ?? ($isActive ? 'Active' : 'Inactive');
    [$uxTone, $uxIcon] = [
        'success' => ['success', 'check_circle'],
        'danger' => ['error', 'cancel'],
        'warning' => ['warning', 'schedule'],
        'info' => ['info', 'info'],
        'primary' => ['primary', 'radio_button_checked'],
        'secondary' => ['neutral', 'remove_circle_outline'],
    ][$tone] ?? ['neutral', 'radio_button_unchecked'];
@endphp
<span class="ux4g-tag-tonal-{{ $uxTone }} sargam-tag"@isset($order) data-order="{{ $order }}"@endisset><i class="material-icons material-symbols-rounded" aria-hidden="true">{{ $uxIcon }}</i>{{ $text }}</span>
