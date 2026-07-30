{{-- @include vars: groups (Collection), familyMembersQueryString, tableId, emptyIcon, emptyText --}}
<div class="programme-dt-panel">
    <div class="table-responsive">
        <table class="table table-hover text-nowrap align-middle programme-dt-table" id="{{ $tableId }}">
            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Submitted By</th>
                    <th>Employee Type</th>
                    <th>Employee ID</th>
                    <th>Member Count</th>
                    <th>Status</th>
                    <th>Applied On</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                    @php $ts = $group->created_date ? \Carbon\Carbon::parse($group->created_date)->timestamp : 0; @endphp
                    <tr data-ts="{{ $ts }}">
                        <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                        <td><strong>{{ $group->submitted_by ?? '--' }}</strong></td>
                        <td>
                            @if(isset($group->employee_type) && $group->employee_type === 'Contractual Employee')
                                <span class="badge rounded-1 bg-warning text-dark">Contractual</span>
                            @else
                                <span class="badge rounded-1 bg-info">Permanent</span>
                            @endif
                        </td>
                        <td><code>{{ $group->emp_id_apply ?? '--' }}</code></td>
                        <td><span class="badge rounded-1 bg-primary">{{ $group->member_count }}</span></td>
                        <td>
                            <span class="badge rounded-1 bg-{{ $group->phase_class ?? 'secondary' }}"
                                  title="{{ $group->phase_label ?? 'Unknown' }}">
                                {{ $group->phase_label ?? 'Unknown' }}
                            </span>
                            @if(($group->status_int ?? 1) === 2 && isset($group->id_card_physical_print_done) && $group->id_card_physical_print_done)
                                <div class="small text-muted mt-1">Card printed</div>
                            @endif
                        </td>
                        <td data-order="{{ $ts }}">{{ $group->created_date ? \Carbon\Carbon::parse($group->created_date)->format('d-m-Y H:i') : '--' }}</td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                <a href="{{ route('admin.family_idcard.members', $group->first_id) }}{{ $familyMembersQueryString }}"
                                   class="programme-action-btn" title="View Members">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                @if($group->can_approve ?? false)
                                    <form action="{{ route('admin.security.family_idcard_approval.approve_group', encrypt($group->first_id)) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="programme-action-btn" title="Approve"
                                                onclick="return confirm('Are you sure you want to approve?')">
                                            <i class="bi bi-check-circle" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="programme-action-btn programme-action-btn--danger" title="Reject"
                                            data-encrypted-id="{{ encrypt($group->first_id) }}"
                                            data-member-count="{{ $group->member_count }}"
                                            onclick="openRejectModal(this)">
                                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 table-empty-state">
                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">{{ $emptyIcon ?? 'inbox' }}</i>
                                <p class="mb-1 fw-semibold text-body-emphasis">{{ $emptyText ?? 'No requests in this category.' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="{{ $tableId }}"></div>
</div>
