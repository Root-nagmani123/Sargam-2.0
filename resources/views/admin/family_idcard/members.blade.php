@extends('admin.layouts.master')
@section('title', 'List of Family Members - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-members-page">
    <x-breadcrum title="List of Family Members"></x-breadcrum>

    <h5 class="fw-bold mb-1">List of Family Members</h5>
    <p class="text-muted small mb-4">This page displays all family members for this request (Parent ID: {{ $parentId ?? '--' }}), and provide options to manage records such as edit, delete, excel upload, excel download, print etc.</p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;">arrow_back</i>
            Back to Request List
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="d-flex flex-wrap align-items-center gap-3 px-3 py-2 border-bottom bg-light">
                <label class="mb-0 small text-muted">Show</label>
                <select class="form-select form-select-sm" style="width:auto;" id="membersPerPage">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }} entries</option>
                    @endforeach
                </select>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="showHideColsMembers" data-bs-toggle="dropdown" aria-expanded="false">Show / hide columns</button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="showHideColsMembers">
                            <li class="dropdown-item-text small text-muted">Toggle column visibility</li>
                            @foreach(['sno','request_date','guardians','id_number','name','relation','dob','individual_photo','valid_from','valid_to','family_photo','status','duplicate'] as $col)
                                <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="form-check-input col-toggle-m" data-col="{{ $col }}" checked> <span class="text-capitalize">{{ str_replace('_', ' ', $col) }}</span></label></li>
                            @endforeach
                        </ul>
                    </div>
                    <label class="mb-0 small text-muted">Search with in table:</label>
                    <input type="search" class="form-control form-control-sm" id="membersTableSearch" placeholder="Search..." style="max-width:200px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle family-members-table" id="familyMembersTable">
                    <thead>
                        <tr class="table-primary">
                            <th class="col-sno">S.NO.</th>
                            <th class="col-request_date">REQUEST DATE</th>
                            <th class="col-guardians">GUARDIANS DETAILS</th>
                            <th class="col-id_number">ID NUMBER</th>
                            <th class="col-name">NAME</th>
                            <th class="col-relation">RELATION</th>
                            <th class="col-dob">DATE OF BIRTHDAY</th>
                            <th class="col-individual_photo">INDIVIDUAL PHOTO</th>
                            <th class="col-valid_from">VALID FROM</th>
                            <th class="col-valid_to">VALID TO</th>
                            <th class="col-family_photo">FAMILY PHOTO</th>
                            <th class="col-status">STATUS</th>
                            <th class="col-duplicate">DUPLICATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $index => $member)
                            <tr class="member-row" data-search="{{ strtolower(($member->name ?? '') . ' ' . ($member->relation ?? '') . ' ' . ($member->employee_id ?? '') . ' ' . ($member->family_member_id ?? '') . ' ' . ($member->status_label ?? '')) }}">
                                <td class="fw-medium col-sno">{{ $index + 1 }}</td>
                                <td class="col-request_date">{{ $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('d-m-Y') : '--' }}</td>
                                <td class="col-guardians">--</td>
                                <td class="col-id_number">{{ $parentId ?? '--' }} / {{ $member->id ?? $member->fml_id_apply ?? '--' }}</td>
                                <td class="col-name">{{ $member->name ?? '--' }}</td>
                                <td class="col-relation">{{ $member->relation ?? '--' }}</td>
                                <td class="col-dob">{{ $member->dob ? \Carbon\Carbon::parse($member->dob)->format('d-m-Y') : '--' }}</td>
                                <td class="col-individual_photo">
                                    @if(!empty($member->id_photo_path))
                                        <a href="{{ asset('storage/' . $member->id_photo_path) }}" target="_blank" class="btn btn-link btn-sm p-0">DOWNLOAD</a>
                                    @elseif(!empty($member->family_photo))
                                        <a href="{{ asset('storage/' . $member->family_photo) }}" target="_blank" class="btn btn-link btn-sm p-0">DOWNLOAD</a>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="col-valid_from">{{ $member->valid_from ? \Carbon\Carbon::parse($member->valid_from)->format('d-m-Y') : '--' }}</td>
                                <td class="col-valid_to">{{ $member->valid_to ? \Carbon\Carbon::parse($member->valid_to)->format('d-m-Y') : '--' }}</td>
                                <td class="col-family_photo">
                                    @if(!empty($member->family_photo))
                                        <a href="{{ asset('storage/' . $member->family_photo) }}" target="_blank" class="btn btn-link btn-sm p-0">DOWNLOAD</a>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="col-status">{{ $member->status_label ?? 'Pending' }}</td>
                                <td class="col-duplicate">
                                    <button type="button" class="btn btn-link btn-sm p-0 duplicate-btn" data-member-id="{{ $member->id }}" data-member-name="{{ e($member->name ?? '') }}" title="Request Duplicate ID">DUPLICATE</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-5 text-muted">
                                    <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">group</i>
                                    <p class="mb-1">No family members found for this request.</p>
                                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-primary btn-sm mt-2">Back to List</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->isNotEmpty())
                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing <strong>1</strong> to <strong>{{ $members->count() }}</strong> of <strong>{{ $members->count() }}</strong> entries
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Duplicate ID Card Request Modal -->
    <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold" id="duplicateModalLabel">Duplicate ID Card Request</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="duplicateForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Family Name</label>
                            <input type="text" class="form-control" id="duplicateFamilyName" readonly placeholder="--">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Duplicate Reason <span class="text-danger">*</span></label>
                            <select name="duplicate_reason" class="form-select" required>
                                <option value="">Select reason</option>
                                <option value="Card Lost">Card Lost</option>
                                <option value="Card Damage">Card Damage</option>
                                <option value="Card Extended">Card Extended</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">From date</label>
                            <input type="date" name="from_date" class="form-control" id="duplicateFromDate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">To Date</label>
                            <input type="date" name="to_date" class="form-control" id="duplicateToDate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Upload Doc</label>
                            <input type="file" name="dup_doc" class="form-control" accept=".pdf,.jpeg,.jpg,.png">
                            <small class="text-muted">PDF, JPEG, PNG max 5MB</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">send</i>
                            Send
                        </button>
                        <button type="button" class="btn btn-danger d-flex align-items-center gap-1" data-bs-dismiss="modal">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>
                            Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.family-members-page .card { border-radius: 0.5rem; overflow: hidden; }
.family-members-table thead tr.table-primary { background: #004a93 !important; color: #fff; border: none; }
.family-members-table thead th { font-weight: 700; font-size: 0.75rem; padding: 0.75rem 0.5rem; border: none; text-align: left; }
.family-members-table tbody td { padding: 0.65rem 0.5rem; vertical-align: middle; border-bottom: 1px solid #eee; font-size: 0.875rem; }
.family-members-table tbody tr:hover { background: #f8fafc; }
@media print {
    .btn, .breadcrumb, .family-members-page .d-flex.border-bottom, #membersTableSearch, #showHideColsMembers, .col-duplicate { display: none !important; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('membersTableSearch');
    var table = document.getElementById('familyMembersTable');
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var rows = table.querySelectorAll('tbody tr.member-row');
            rows.forEach(function(row) {
                var text = row.getAttribute('data-search') || '';
                row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
    document.querySelectorAll('.col-toggle-m').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var col = this.getAttribute('data-col');
            var show = this.checked;
            document.querySelectorAll('.col-' + col).forEach(function(el) { el.style.display = show ? '' : 'none'; });
        });
    });

    var duplicateModal = document.getElementById('duplicateModal');
    var duplicateForm = document.getElementById('duplicateForm');
    var duplicateFamilyName = document.getElementById('duplicateFamilyName');
    var duplicateUrlTemplate = '{{ route("admin.family_idcard.duplicate", ["id" => "__ID__"]) }}';
    if (duplicateModal && duplicateForm) {
        document.querySelectorAll('.duplicate-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var memberId = this.getAttribute('data-member-id');
                var memberName = this.getAttribute('data-member-name') || '--';
                duplicateForm.action = duplicateUrlTemplate.replace('__ID__', memberId);
                duplicateFamilyName.value = memberName;
                duplicateForm.querySelector('[name="duplicate_reason"]').value = '';
                document.getElementById('duplicateFromDate').value = '';
                document.getElementById('duplicateToDate').value = '';
                duplicateForm.querySelector('[name="dup_doc"]').value = '';
                var modal = new bootstrap.Modal(duplicateModal);
                modal.show();
            });
        });
    }
});
</script>
@endsection
