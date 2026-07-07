@php
    use App\Services\FC\FcRegistrationStatusService;
    $theme = $tabMeta['theme'] ?? 'not-responded';
@endphp

<div id="fcStatusResults" class="fc-status-results" data-theme="{{ $theme }}">
    @if($participants && $participants->total() > 0)
        <div class="fc-status-results__toolbar" role="status">
            <span>
                Page <strong>{{ $participants->currentPage() }}</strong> of <strong>{{ $participants->lastPage() }}</strong>
            </span>
            <span>
                Showing <strong>{{ number_format($participants->firstItem()) }}</strong>–<strong>{{ number_format($participants->lastItem()) }}</strong>
                of <strong>{{ number_format($participants->total()) }}</strong>
            </span>
        </div>
    @endif

    <div class="fc-status-table-panel">
        <div class="fc-status-table-wrap" tabindex="0" aria-label="Scrollable participant list">
            @if($activeTab === FcRegistrationStatusService::TAB_SERVICE)
                <table class="table table-bordered table-sm fc-status-table fc-status-table--service mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width:5rem;">S.No</th>
                            <th scope="col">Name of the Service</th>
                            <th scope="col" style="width:8rem;">Total No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceList as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">{{ $row->service_label ?? ($row->service?->service_name ? strtoupper($row->service->service_name) : 'NOT APPLICABLE') }}</td>
                                <td>{{ number_format($row->count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted py-4">No service data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table table-bordered table-sm fc-status-table fc-status-table--{{ $theme }} mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width:4.5rem;">S.No</th>
                            <th scope="col">Name</th>
                            <th scope="col">Service</th>
                            <th scope="col" style="width:6rem;">Rank</th>
                            @if($activeTab === FcRegistrationStatusService::TAB_EXEMPTION)
                                <th scope="col">Reason</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $index => $participant)
                            <tr>
                                <td>{{ $participants->firstItem() + $index }}</td>
                                <td class="text-start">{{ $participant->full_name }}</td>
                                <td class="text-start">{{ $participant->service_label }}</td>
                                <td>{{ $participant->rank !== null && $participant->rank !== '' ? $participant->rank : '—' }}</td>
                                @if($activeTab === FcRegistrationStatusService::TAB_EXEMPTION)
                                    <td class="text-start">{{ $participant->exemption_reason }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $activeTab === FcRegistrationStatusService::TAB_EXEMPTION ? 5 : 4 }}" class="text-muted py-4">
                                    No records found for this category.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if($participants && $participants->hasPages())
        <div class="fc-status-pagination">
            {{ $participants->appends(['tab' => $activeTab])->links('vendor.pagination.fc-status') }}
        </div>
    @elseif($participants && $participants->total() > 0)
        <div class="fc-status-pagination">
            <p class="fc-status-pagination-summary text-center mb-0">
                Showing all <strong>{{ number_format($participants->total()) }}</strong> records
            </p>
        </div>
    @endif
</div>
