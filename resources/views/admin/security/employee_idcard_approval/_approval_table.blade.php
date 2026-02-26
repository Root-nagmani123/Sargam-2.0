{{-- Shared table for Approval I and Approval II - same columns and layout --}}
@props(['requests', 'approvalStage' => 1])

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0" id="approvalTable">
        <thead class="table-primary">
            <tr>
                <th style="width:50px;" class="text-center"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
                <th style="width:50px;" class="text-center">S.No.</th>
                <th>EMPLOYEE NAME</th>
                <th>DESIGNATION</th>
                <th>FATHER NAME</th>
                <th>ID CARD NO</th>
                <th>ID TYPE</th>
                <th>REQUEST TYPE</th>
                <th>DATE OF BIRTH</th>
                <th>BLOOD GROUP</th>
                <th>CONTACT NO</th>
                <th>PHOTO DOWNLOAD</th>
                <th>VALID FROM</th>
                <th>VALID TO</th>
                <th>APPROVED/REJECT</th>
                <th>REQUEST DATE</th>
                <th>REQUESTED BY</th>
                <th>REQUESTED SECTION</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $index => $req)
                <tr>
                    <td class="text-center"><input type="checkbox" class="row-check" value="{{ $req->id }}" aria-label="Select row"></td>
                    <td class="text-center fw-medium">{{ $requests->firstItem() + $index }}</td>
                    <td>{{ $req->name ?? '--' }}</td>
                    <td>{{ $req->designation ?? '--' }}</td>
                    <td>{{ $req->father_name ?? '--' }}</td>
                    <td>{{ $req->id_card_number ?? '--' }}</td>
                    <td>{{ $req->card_type ?? '--' }}</td>
                    <td>
                        @if(isset($req->request_type) && $req->request_type === 'duplicate')
                            <span class="badge bg-info">Duplicate</span>
                        @else
                            <span class="badge bg-secondary">Regular</span>
                        @endif
                    </td>
                    <td>{{ $req->date_of_birth ? (\Carbon\Carbon::parse($req->date_of_birth)->format('d-m-Y')) : '--' }}</td>
                    <td>{{ $req->blood_group ?? '--' }}</td>
                    <td>{{ $req->mobile_number ?? $req->telephone_number ?? '--' }}</td>
                    <td>
                        @if($req->photo)
                            @php
                                // Construct the correct storage path
                                $photoPath = str_starts_with($req->photo, 'idcard/')
                                    ? $req->photo
                                    : 'idcard/photos/' . $req->photo;
                                $photoExists = \Storage::disk('public')->exists($photoPath);
                                $photoUrl = $photoExists ? asset('storage/' . $photoPath) : asset('images/dummypic.jpeg');
                            @endphp
                            <a href="{{ $photoUrl }}" target="_blank" class="text-primary" title="Download Photo">Download</a>
                        @else
                            <span class="text-muted">--</span>
                        @endif
                    </td>
                    <td>{{ $req->id_card_valid_from ?? '--' }}</td>
                    <td>{{ $req->id_card_valid_upto ?? '--' }}</td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            @php
                                // Determine the encryption key based on request type
                                $encryptKey = $req->id;
                                if (isset($req->request_type) && $req->request_type === 'duplicate') {
                                    // For duplicate contractual: c-dup-pk
                                    // For duplicate permanent: p-dup-pk
                                    if (is_string($encryptKey) && str_starts_with($encryptKey, 'c-')) {
                                        $encryptKey = 'c-dup-' . substr($encryptKey, 2);
                                    } else {
                                        $encryptKey = 'p-dup-' . $encryptKey;
                                    }
                                }
                            @endphp
                            <form action="{{ $approvalStage === 1 ? route('admin.security.employee_idcard_approval.approve1', encrypt($encryptKey)) : route('admin.security.employee_idcard_approval.approve2', encrypt($encryptKey)) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-success text-decoration-none" title="Approve">Approve</button>
                            </form>
                            <span class="text-muted">|</span>
                            <button type="button" class="btn btn-link p-0 text-danger text-decoration-none reject-btn" title="Reject"
                                data-name="{{ $req->name }}"
                                data-url="{{ $approvalStage === 1 ? route('admin.security.employee_idcard_approval.reject1', encrypt($encryptKey)) : route('admin.security.employee_idcard_approval.reject2', encrypt($encryptKey)) }}">Reject</button>
                        </div>
                    </td>
                    <td>{{ $req->created_at ? $req->created_at->format('d-m-Y') : '--' }}</td>
                    <td>{{ $req->requested_by ?? '--' }}</td>
                    <td>{{ $req->requested_section ?? '--' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="text-center text-muted py-4">
                        No pending requests for {{ $approvalStage === 1 ? 'Approval I' : 'Approval II' }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
