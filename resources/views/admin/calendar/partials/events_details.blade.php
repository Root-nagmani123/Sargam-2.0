@php
    $canManageEvents = hasRole('Training-Induction') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST');
@endphp

{{-- Hover popover (shown on event card mouseenter) --}}
<div id="eventDetailsHoverPopover" class="event-details-hover-popover d-none" role="tooltip" aria-hidden="true">
    @include('admin.calendar.partials.events_details_card', ['prefix' => 'hover', 'canManageEvents' => $canManageEvents])
</div>

{{-- Modal (click / list view) --}}
<div class="modal fade" id="eventDetails" tabindex="-1" aria-labelledby="eventDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered event-details-dialog">
        <div class="modal-content event-details-modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body p-0">
                @include('admin.calendar.partials.events_details_card', ['prefix' => 'modal', 'canManageEvents' => $canManageEvents])
            </div>
        </div>
    </div>
</div>
