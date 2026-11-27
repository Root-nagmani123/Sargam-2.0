@extends('admin.layouts.master')
@section('title', $config['title'])

@push('styles')
<style>
    .adr-page .ds-stat-card { border-left: 1px solid var(--ds-line); color: inherit; text-decoration: none; }
    .adr-page .ds-stat-card.is-active { border-color: var(--ds-primary); box-shadow: inset 0 0 0 1px var(--ds-primary); }
    .adr-page .ds-stat-card .ds-stat-label { margin: 0; font-size: .875rem; color: var(--ds-ink); }
    .adr-page .status-pill.bg-warning-subtle { color: #8a6100; }
    .adr-page .status-pill.bg-info-subtle { color: #055160; }
    .adr-page .status-pill.bg-success-subtle { color: #146c43; }
    .adr-page .status-pill.bg-danger-subtle { color: #b02a37; }
    .adr-page #adrTable td.adr-wrap { white-space: normal; min-width: 12rem; }
    .adr-page .adr-remark { font-size: .75rem; color: var(--ds-ink-muted); white-space: normal; max-width: 16rem; margin-top: var(--ds-space-1); }
</style>
@endpush

@section('setup_content')
@php
    $tone = ['pending' => 'bg-warning-subtle', 'verified' => 'bg-info-subtle', 'accepted' => 'bg-success-subtle', 'rejected' => 'bg-danger-subtle'];
    $cards = ['' => ['All requests', $counts->sum()]];
    foreach ($statuses as $key => $label) {
        $cards[$key] = [$label, $counts[$key] ?? 0];
    }
    $isReview = $stage === 'review';
@endphp
<div class="container-fluid adr-page">
    <x-breadcrum :title="ucfirst($config['title'])" :items="[
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Setup'],
        ['label' => ucfirst($config['title'])],
    ]" />

    <p class="text-body-secondary small mb-3">
        @if ($isReview)
            Stage 1 of 2 — check each request against the user's status and official records, then verify and forward it for final approval, or reject it.
        @else
            Stage 2 of 2 — give the final decision on requests the Computer Section has verified. Approving closes the account: the user can no longer sign in.
        @endif
    </p>

    <nav class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-3" aria-label="Filter by status">
        @foreach ($cards as $key => [$label, $total])
            <div class="col">
                <a href="{{ route($config['route'].'.index', array_filter(['status' => $key])) }}"
                   class="ds-stat-card h-100 {{ (string) $status === (string) $key ? 'is-active' : '' }}"
                   @if ((string) $status === (string) $key) aria-current="page" @endif>
                    <span class="ds-stat-label">{{ $label }}</span>
                    <span class="ds-stat-value">{{ sprintf('%02d', $total) }}</span>
                </a>
            </div>
        @endforeach
    </nav>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <h2 class="h6 fw-medium mb-0">Account Deletion Requests</h2>
                <div id="adrDtSearch" class="programme-dt-search" data-dt-search-for="adrTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="adrTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table datatable" data-export="false">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Request ID</th>
                                <th>Date</th>
                                <th>Full Name</th>
                                <th>User ID</th>
                                <th>Registered Email</th>
                                <th>Deletion Reason</th>
                                <th>Status</th>
                                <th data-orderable="false">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->code }}</td>
                                    <td data-order="{{ $row->created_at->timestamp }}">{{ $row->created_at->format('d/m/Y') }}</td>
                                    <td>{{ $row->full_name }}</td>
                                    <td>{{ $row->user_name }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td class="adr-wrap">
                                        {{ $row->reason }}
                                        @if ($row->comments)
                                            <div class="small text-body-secondary">{{ $row->comments }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-pill badge rounded-1 {{ $tone[$row->status] ?? 'bg-secondary-subtle' }}">
                                            {{ $statuses[$row->status] ?? ucfirst($row->status) }}
                                        </span>
                                        @if ($row->review_remarks)
                                            <div class="adr-remark"><strong>Review</strong> ({{ $names[$row->reviewed_by] ?? '—' }}, {{ $row->reviewed_at?->format('d/m/Y') }}): {{ $row->review_remarks }}</div>
                                        @endif
                                        @if ($row->admin_remarks)
                                            <div class="adr-remark"><strong>Decision</strong> ({{ $names[$row->decided_by] ?? '—' }}, {{ $row->decided_at?->format('d/m/Y') }}): {{ $row->admin_remarks }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->status === $config['from'])
                                            <div class="d-flex gap-1" role="group" aria-label="Decide {{ $row->code }}">
                                                @foreach (['accept' => ['text-success', 'bi-check-lg', $config['accept_label']], 'reject' => ['text-danger', 'bi-x-lg', $config['reject_label']]] as $decision => [$color, $icon, $label])
                                                    <button type="button" class="btn btn-sm btn-link {{ $color }} adr-decide" title="{{ $label }}"
                                                            data-url="{{ route($config['route'].'.decide', $row->id) }}"
                                                            data-decision="{{ $decision }}" data-label="{{ $label }}" data-code="{{ $row->code }}">
                                                        <i class="bi {{ $icon }} fs-5" aria-hidden="true"></i><span class="visually-hidden">{{ $label }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                 data-dt-footer-for="adrTable"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const isReview = @json($isReview);
    const notes = {
        accept: isReview
            ? 'The request goes to the admin for final approval.'
            : 'The user will no longer be able to sign in, and is emailed the decision with your remarks. This cannot be undone from this screen.',
        reject: 'The account stays active. The user is emailed the decision with your remarks.',
    };

    $(document).on('click', '.adr-decide', function () {
        const btn = this;
        const accept = btn.dataset.decision === 'accept';

        Swal.fire({
            icon: accept && !isReview ? 'warning' : 'question',
            title: btn.dataset.label + ' ' + btn.dataset.code + '?',
            text: notes[btn.dataset.decision],
            input: 'textarea',
            inputLabel: 'Remarks',
            inputAttributes: { maxlength: 500 },
            inputValidator: v => (!v || !v.trim()) ? 'Remarks are required.' : undefined,
            showCancelButton: true,
            confirmButtonText: btn.dataset.label,
            confirmButtonColor: accept ? '#198754' : '#dc3545',
            showLoaderOnConfirm: true,
            preConfirm: remarks => $.ajax({
                url: btn.dataset.url,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { decision: btn.dataset.decision, remarks: remarks },
            }).catch(xhr => {
                Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save the decision.');
            }),
            allowOutsideClick: () => !Swal.isLoading(),
        }).then(result => {
            if (!result.isConfirmed || !result.value) return;
            Swal.fire({ icon: 'success', title: 'Saved', text: result.value.message })
                .then(() => window.location.reload());
        });
    });
})();
</script>
@endpush
