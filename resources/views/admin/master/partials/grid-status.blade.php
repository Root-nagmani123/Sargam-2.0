{{--
    Status column: display-only soft badge (docs/new-design-index-page.md §3b).
    The control (switch) lives in the Action column — see grid-actions.

    @include('admin.master.partials.grid-status', ['active' => $row->active_inactive == 1])

    Optional: 'label' to override the text, 'tone' one of
    success | danger | warning | info | primary | secondary for workflow states.
    Styled by .mst-page .status-pill in public/css/master-admin.css.
--}}
@php
    $isActive = (bool) ($active ?? false);
    $tone = $tone ?? ($isActive ? 'success' : 'danger');
    $text = $label ?? ($isActive ? 'Active' : 'Inactive');
@endphp
<span class="status-pill badge rounded-1 bg-{{ $tone }}-subtle">{{ $text }}</span>
