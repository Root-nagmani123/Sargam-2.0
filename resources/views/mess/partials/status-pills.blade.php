{{--
    Mess Management — shared status pill row.

    The Figma export puts this row above the card on every Master Data screen and
    every Report (see the Design-vs-Code audit, finding M-01). It was only ever
    built on Store Master, so it lives here now: one partial, fourteen call sites.

    Vocabulary (M-02): the design writes the second pill as "Archived", but every
    mess master stores `status` as `active` / `inactive` and the Add/Edit modals
    offer exactly those two words. Labelling an *inactive* row "Archived" would
    describe a state the data does not have, so the pills read Active / Inactive.
    If the wording is ever settled the other way, it is one edit in $options below
    — that is precisely why this is a partial and not fourteen copies of a <ul>.

    Selection is a toggle and nothing is pre-selected, so the default view of each
    grid still shows every row. Clicking the lit pill clears the filter, and the
    "Remove Filter" button clears it too.

    Options:
      $tableId  (string, required) — DataTable id the pills filter.
      $current  (string|null)      — active status; defaults to ?status= on the URL.
      $options  (array)            — value => label map.
      $label    (string)           — aria-label for the group.
--}}
@php
    $tableId = $tableId ?? '';
    $current = strtolower(trim((string) ($current ?? request('status', ''))));
    $options = $options ?? ['active' => 'Active', 'inactive' => 'Inactive'];
    $label = $label ?? 'Filter by status';
@endphp
<ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
    role="group" aria-label="{{ $label }}" data-mess-status-tabs="{{ $tableId }}">
    @foreach($options as $value => $text)
        @php $on = $current === (string) $value; @endphp
        <li class="nav-item" role="presentation">
            <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $on ? 'active' : '' }}"
                    data-mess-status="{{ $value }}"
                    aria-pressed="{{ $on ? 'true' : 'false' }}"
                    @if($on) aria-current="true" @endif>{{ $text }}</button>
        </li>
    @endforeach
</ul>
