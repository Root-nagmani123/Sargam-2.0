{{-- @include vars: groups (LengthAwarePaginator), familyMembersQueryString --}}
<div class="table-responsive">
    <table class="table text-nowrap mb-0">
        <thead>
            <tr>
                <th>Submitted By</th>
                <th>Employee Type</th>
                <th>Employee ID</th>
                <th>Member Count</th>
                <th>Status</th>
                <th>Applied On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
                <tr>
                    <td><strong>{{ $group->submitted_by ?? '--' }}</strong></td>
                    <td>
                        @if(isset($group->employee_type) && $group->employee_type === 'Contractual Employee')
                            <span class="badge bg-warning">Contractual</span>
                        @else
                            <span class="badge bg-info">Permanent</span>
                        @endif
                    </td>
                    <td><code>{{ $group->emp_id_apply ?? '--' }}</code></td>
                    <td>
                        <span class="badge bg-primary">{{ $group->member_count }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $group->phase_class ?? 'secondary' }}"
                              title="{{ $group->phase_label ?? 'Unknown' }}">
                            {{ $group->phase_label ?? 'Unknown' }}
                        </span>
                        @if(($group->status_int ?? 1) === 2 && isset($group->id_card_physical_print_done) && $group->id_card_physical_print_done)
                            <div class="small text-muted mt-1">Card printed</div>
                        @endif
                    </td>
                    <td>{{ $group->created_date ? \Carbon\Carbon::parse($group->created_date)->format('d-m-Y H:i') : '--' }}</td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.family_idcard.members', $group->first_id) }}{{ $familyMembersQueryString }}"
                               class="btn  btn-outline-info bg-transparent border-0 text-primary p-0" title="View Members">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                            </a>
                            @if($group->can_approve ?? false)
                                <form action="{{ route('admin.security.family_idcard_approval.approve_group', encrypt($group->first_id)) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn  btn-outline-success bg-transparent border-0 text-primary p-0" title="Approve"
                                            onclick="return confirm('Are you sure you want to approve?')">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i>
                                    </button>
                                </form>
                                <button type="button" class="btn  btn-outline-danger bg-transparent border-0 text-primary p-0" title="Reject"
                                        data-encrypted-id="{{ encrypt($group->first_id) }}"
                                        data-member-count="{{ $group->member_count }}"
                                        onclick="openRejectModal(this)">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No requests in this category.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
