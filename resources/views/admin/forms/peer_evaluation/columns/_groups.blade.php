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
                {{-- The master switch over the OT-facing form for this group.
                     Deactivating criteria one by one never closed the form - it
                     left the OT a grid of names with nothing to score - and until
                     this switch landed here the only screen that could close a
                     form was the legacy Manage Groups page. --}}
                <th scope="col" class="text-center">Form Status</th>
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
                        <div class="d-inline-flex align-items-center gap-2">
                            {{-- Its own handler, not the generic .status-toggle one:
                                 opening a form is what tells the group's OTs they can
                                 fill it (PeerEvaluationController@toggleFormStatus ->
                                 PeerEvaluationNotifier), and a straight column write
                                 would skip that. --}}
                            <input class="form-check-input m-0 pec-form-toggle" type="checkbox" role="switch"
                                   data-group-id="{{ $group->id }}"
                                   aria-label="Evaluation form for {{ $group->group_name }}"
                                   @checked($group->is_form_active)>
                            <span class="status-pill badge rounded-1 {{ $group->is_form_active ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                {{ $group->is_form_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </td>
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
                    <td colspan="5" class="p-0"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-body-secondary">
                        This event has no groups yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
