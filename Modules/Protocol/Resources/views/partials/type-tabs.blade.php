{{-- Type pills above the grid. components/data-table/table reads the active pill via #tabs and redraws on click. --}}
@if (! empty($tabs))
  <ul class="nav nav-pills gap-2 p-1 rounded-1 bg-white mb-0 programme-status-tabs protocol-tabs" id="tabs" role="group" aria-label="Filter by request type">
    @foreach ($tabs as $tab)
      <li class="nav-item" role="presentation">
        <a href="javascript:void(0)"
           class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $tab['active'] ? 'active' : '' }}"
           data-type="{{ $tab['type'] }}">
          {{ $tab['label'] }}<span class="count">{{ $tab['count'] }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
