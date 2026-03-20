{{-- Shared table for Approval I and Approval II - same columns and layout --}}
@props(['requests', 'approvalStage' => 1])

<div class="table-responsive">
    <table class="table text-nowrap align-middle mb-0" id="approvalTable">
        <thead class="table-primary">
            <tr>
                <th style="width:50px;" class="text-center">S.No.</th>
                
                <th>EMPLOYEE NAME</th>
                <th>DESIGNATION</th>
                <th>ID CARD NO</th>
                <th>ID TYPE</th>
                <th>REQUEST TYPE</th>
                <th style="width:70px;" class="text-center">PHOTO</th>
                <th>CONTACT NO</th>
                <th class="text-center">APPROVED/REJECT</th>
                <th>REQUEST DATE</th>
                <th>REQUESTED SECTION</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $index => $req)
                <tr>
                    <td class="text-center fw-medium">{{ $requests->firstItem() + $index }}</td>
                    
                    <td>{{ $req->name ?? '--' }}</td>
                    <td>{{ $req->designation ?? '--' }}</td>
                    <td>{{ $req->id_card_number ?? '--' }}</td>
                    <td>{{ $req->card_type ?? '--' }}</td>
                    
                    <td class="text-center">
                        @if(isset($req->request_type) && $req->request_type === 'duplicate')
                            @php
                                $empType = $req->employee_type ?? null;
                                $empTypeShort = match ($empType) {
                                    'Permanent Employee' => 'Permanent',
                                    'Contractual Employee' => 'Contractual',
                                    default => null,
                                };
                            @endphp
                            @if($empTypeShort)
                                <span class="badge bg-info">Duplicate ({{ $empTypeShort }})</span>
                            @else
                                <span class="badge bg-info">Duplicate</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Fresh</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            // PERF: Avoid per-row storage existence checks (slow on Windows/network disks).
                            // Use a deterministic URL and fall back to dummy image on load error.
                            $photoPath = null;
                            if ($req->photo) {
                                $photoPath = str_starts_with($req->photo, 'idcard/')
                                    ? $req->photo
                                    : 'idcard/photos/' . $req->photo;
                            }
                            $photoUrl = $photoPath ? asset('storage/' . $photoPath) : asset('images/dummypic.jpeg');
                            $dummyUrl = asset('images/dummypic.jpeg');
                        @endphp
                        @if($photoPath)
                            <a href="{{ $photoUrl }}" target="_blank" class="d-inline-block">
                                <img src="{{ $photoUrl }}" alt="Photo"
                                     onerror="this.onerror=null;this.src='{{ $dummyUrl }}';"
                                     style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6; cursor:pointer;"
                                     title="Click to view full photo">
                            </a>
                        @else
                            <img src="{{ $dummyUrl }}" alt="No Photo"
                                 style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;"
                                 title="No photo available">
                        @endif
                    </td>
                    <td>{{ $req->mobile_number ?? $req->telephone_number ?? '--' }}</td>
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

                        <div class="d-flex flex-column gap-1 align-items-center">
                            <a href="{{ route('admin.security.employee_idcard_approval.show', ['id' => $encryptedId, 'stage' => $approvalStage]) }}"
                               class="btn btn-link p-0 text-primary text-decoration-none"
                               title="View full request details">
                                View Request
                            </a>

                            @if(($req->status ?? 'Pending') !== 'Pending')
                                {{-- Non-pending requests are always view-only --}}
                                @php
                                    $statusLabel = (string) ($req->status ?? '');
                                    $statusBadgeClass = match ($statusLabel) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                <small class="text-muted">No further actions available</small>
                            @elseif(isset($req->is_view_only) && $req->is_view_only)
                                @if($approvalStage === 2 && in_array(($req->employee_type ?? ''), ['Permanent Employee', 'Contractual Employee']))
                                    <span class="badge bg-warning">Pending from Final Approval</span>
                                    <small class="text-muted">{{ $req->final_status_hint ?? 'Recommended at Level 2' }}</small>
                                @else
                                    {{-- View-only rows (e.g. other non-actionable rows) --}}
                                    <span class="badge bg-info">View Only</span>
                                    <small class="text-muted">Approved at Level 1</small>
                                @endif
                            @else
                                <div class="d-flex gap-1 flex-wrap justify-content-center align-items-center">
                                    @php
                                        $approveRoute = $approvalStage === 1
                                            ? route('admin.security.employee_idcard_approval.approve1', $encryptedId)
                                            : ($approvalStage === 2
                                                ? route('admin.security.employee_idcard_approval.approve2', $encryptedId)
                                                : route('admin.security.employee_idcard_approval.approve3', $encryptedId));
                                        $rejectRoute = $approvalStage === 1
                                            ? route('admin.security.employee_idcard_approval.reject1', $encryptedId)
                                            : ($approvalStage === 2
                                                ? route('admin.security.employee_idcard_approval.reject2', $encryptedId)
                                                : route('admin.security.employee_idcard_approval.reject3', $encryptedId));
                                    @endphp
                                    <form action="{{ $approveRoute }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 text-success text-decoration-none" title="Approve">Approve</button>
                                    </form>
                                    <span class="text-muted">|</span>
                                    <button type="button" class="btn btn-link p-0 text-danger text-decoration-none reject-btn" title="Reject"
                                        data-name="{{ $req->name }}"
                                        data-url="{{ $rejectRoute }}">
                                        Reject
                                    </button>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $req->created_at ? $req->created_at->format('d-m-Y') : '--' }}</td>
                    <td>{{ $req->requested_section ?? '--' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        No requests found for {{ $approvalStage === 1 ? 'Approval I' : ($approvalStage === 2 ? 'Approval II' : 'Approval III') }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
