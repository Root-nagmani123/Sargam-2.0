{{-- Level 2: the groups of one event, rendered into the parent row's DataTables
     child row. Server-rendered rather than built in JS so the markup lives with
     the rest of the module's blades.

     Each row expands again into that group's columns (level 3). --}}
<div class="pec-level pec-level--groups">
    <table class="table align-middle mb-0 w-100 pec-subtable">
        <thead>
            <tr>
                <th scope="col">S. No.</th>
                <th scope="col">Group Details</th>
                <th scope="col">Event Name</th>
                <th scope="col" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $index => $group)
                <tr data-group-row="{{ $group->id }}">
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <a href="javascript:void(0)" class="pec-link pec-expand-group" data-group-id="{{ $group->id }}">
                            {{ $group->group_name }}
                        </a>
                    </td>
                    <td>{{ $event->event_name ?: '-' }}</td>
                    <td class="text-center">
                        <button type="button" class="pe-act pec-toggle-group"
                                data-group-id="{{ $group->id }}" aria-expanded="false">
                            <span class="pe-act__icon"><i class="bi bi-chevron-down" aria-hidden="true"></i></span>
                            <span class="pe-act__label">View</span>
                        </button>
                    </td>
                </tr>
                {{-- Filled in on demand by the page script; kept in the DOM so the
                     open/closed state survives collapsing the parent. --}}
                <tr class="pec-group-child d-none" data-group-child="{{ $group->id }}">
                    <td colspan="4" class="p-0"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-body-secondary">
                        This event has no groups yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
