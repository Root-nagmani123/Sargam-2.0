{{-- @include vars: applications (Collection), tableId, emptyIcon, emptyText --}}
<div class="programme-dt-panel">
    <div class="table-responsive">
        <table class="table table-hover text-nowrap align-middle programme-dt-table vehicle-pass-approval-table" id="{{ $tableId }}">
            <thead>
                <tr>
                    <th scope="col">S. No.</th>
                    <th scope="col">Employee Name</th>
                    <th scope="col">Vehicle Number</th>
                    <th scope="col">Vehicle Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Vehicle Pass No</th>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Applied On</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    @php $ts = $app->created_date ? \Carbon\Carbon::parse($app->created_date)->timestamp : 0; @endphp
                    <tr data-ts="{{ $ts }}">
                        <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                        <td>{{ $app->employee_name ?? '--' }}</td>
                        <td><strong>{{ $app->vehicle_number ?? '--' }}</strong></td>
                        <td>{{ $app->vehicle_type ?? '--' }}</td>
                        <td>
                            <span class="badge rounded-1 bg-{{ $app->status_class ?? 'secondary' }}">
                                {{ $app->status ?? '--' }}
                            </span>
                        </td>
                        <td>{{ $app->vehicle_pass_no ?? '--' }}</td>
                        <td>{{ $app->employee_id ?? '--' }}</td>
                        <td data-order="{{ $ts }}">{{ $app->created_date ? \Carbon\Carbon::parse($app->created_date)->format('d-m-Y H:i') : '--' }}</td>
                        <td class="text-center">
                            @php $encryptId = encrypt($app->id); @endphp
                            <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                <a href="{{ route('admin.security.vehicle_pass_approval.show', $encryptId) }}"
                                   class="programme-action-btn" title="View Details">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                @if($app->can_approve ?? false)
                                    <button type="button" class="programme-action-btn btn-veh-approve"
                                            data-encrypted-id="{{ $encryptId }}" title="Approve">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="programme-action-btn programme-action-btn--danger btn-veh-reject"
                                            data-encrypted-id="{{ $encryptId }}" title="Reject">
                                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 table-empty-state">
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
