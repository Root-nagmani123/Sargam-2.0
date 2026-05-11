{{-- @include vars: applications (LengthAwarePaginator) --}}
<div class="datatables">
    <div class="table-responsive">
        <table class="table text-nowrap align-middle mb-0 vehicle-pass-approval-table">
            <thead>
                <tr>
                    <th scope="col">Employee Name</th>
                    <th scope="col">Vehicle Number</th>
                    <th scope="col">Vehicle Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Vehicle Pass No</th>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Applied On</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->employee_name ?? '--' }}</td>
                        <td><strong>{{ $app->vehicle_number ?? '--' }}</strong></td>
                        <td>{{ $app->vehicle_type ?? '--' }}</td>
                        <td>
                            <span class="badge bg-{{ $app->status_class ?? 'secondary' }}">
                                {{ $app->status ?? '--' }}
                            </span>
                        </td>
                        <td>{{ $app->vehicle_pass_no ?? '--' }}</td>
                        <td>{{ $app->employee_id ?? '--' }}</td>
                        <td>{{ $app->created_date ? \Carbon\Carbon::parse($app->created_date)->format('d-m-Y H:i') : '--' }}</td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                @php $encryptId = encrypt($app->id); @endphp
                                <a href="{{ route('admin.security.vehicle_pass_approval.show', $encryptId) }}"
                                   class="btn  btn-info bg-transparent border-0 text-primary p-0" title="View Details">
                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                </a>
                                @if($app->can_approve ?? false)
                                    <button type="button" class="btn  btn-success btn-veh-approve bg-transparent border-0 text-primary p-0"
                                            data-encrypted-id="{{ $encryptId }}" title="Approve">
                                        <i class="material-icons material-symbols-rounded">check_circle</i>
                                    </button>
                                    <button type="button" class="btn  btn-danger btn-veh-reject bg-transparent border-0 text-primary p-0"
                                            data-encrypted-id="{{ $encryptId }}" title="Reject">
                                        <i class="material-icons material-symbols-rounded">cancel</i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No requests in this category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
