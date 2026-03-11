{{-- Shared table for Approval I and Approval II - same columns and layout --}}
@props(['requests', 'approvalStage' => 1])

<div class="table-responsive">
    <table class="table text-nowrap align-middle mb-0" id="approvalTable">
        <thead class="table-primary">
            <tr>
                <th style="width:50px;" class="text-center">S.No.</th>
                
                <th>EMPLOYEE NAME</th>
                <th>DESIGNATION</th>
                <th>FATHER NAME</th>
                <th>ID CARD NO</th>
                <th>ID TYPE</th>
                <th>REQUEST TYPE</th>
                <th style="width:70px;" class="text-center">PHOTO</th>
                <th>DATE OF BIRTH</th>
                <th>BLOOD GROUP</th>
                <th>CONTACT NO</th>
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
                    <td class="text-center">
                        @if($req->photo)
                            @php
                                $photoPath = str_starts_with($req->photo, 'idcard/')
                                    ? $req->photo
                                    : 'idcard/photos/' . $req->photo;
                                $photoExists = \Storage::disk('public')->exists($photoPath);
                                $photoUrl = $photoExists ? asset('storage/' . $photoPath) : asset('images/dummypic.jpeg');
                            @endphp
                            <a href="{{ $photoUrl }}" target="_blank" class="d-inline-block">
                                <img src="{{ $photoUrl }}" alt="Photo" style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6; cursor:pointer;" title="Click to view full photo">
                            </a>
                        @else
                            <img src="{{ asset('images/dummypic.jpeg') }}" alt="No Photo" style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;" title="No photo available">
                        @endif
                    </td>
                    <td>{{ $req->date_of_birth ? (\Carbon\Carbon::parse($req->date_of_birth)->format('d-m-Y')) : '--' }}</td>
                    <td>{{ $req->blood_group ?? '--' }}</td>
                    <td>{{ $req->mobile_number ?? $req->telephone_number ?? '--' }}</td>
                    <td>{{ $req->id_card_valid_from ?? '--' }}</td>
                    <td>{{ $req->id_card_valid_upto ?? '--' }}</td>
                    <td>
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
                            $encryptedId = encrypt($encryptKey);
                        @endphp

                        <div class="d-flex flex-column gap-1">
                            <a href="{{ route('admin.security.employee_idcard_approval.show', $encryptedId) }}"
                               class="btn btn-link p-0 text-primary text-decoration-none"
                               title="View full request details">
                                View Request
                            </a>

                            @if(isset($req->is_view_only) && $req->is_view_only)
                                {{-- Contractual requests at Approval 2 are view-only --}}
                                <span class="badge bg-info align-self-start">View Only</span>
                                <small class="text-muted">Approved at Level 1</small>
                            @else
                                <div class="d-flex gap-1 flex-wrap">
                                    <form action="{{ $approvalStage === 1 ? route('admin.security.employee_idcard_approval.approve1', $encryptedId) : route('admin.security.employee_idcard_approval.approve2', $encryptedId) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 text-success text-decoration-none" title="Approve">Approve</button>
                                    </form>
                                    <span class="text-muted">|</span>
                                    <button type="button" class="btn btn-link p-0 text-danger text-decoration-none reject-btn" title="Reject"
                                        data-name="{{ $req->name }}"
                                        data-url="{{ $approvalStage === 1 ? route('admin.security.employee_idcard_approval.reject1', $encryptedId) : route('admin.security.employee_idcard_approval.reject2', $encryptedId) }}">
                                        Reject
                                    </button>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $req->created_at ? $req->created_at->format('d-m-Y') : '--' }}</td>
                    <td>{{ $req->requested_by ?? '--' }}</td>
                    <td>{{ $req->requested_section ?? '--' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="19" class="text-center text-muted py-4">
                        No pending requests for {{ $approvalStage === 1 ? 'Approval I' : 'Approval II' }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
