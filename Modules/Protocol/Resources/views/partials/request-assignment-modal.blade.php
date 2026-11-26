<div class="modal fade" id="resourceAssignmentModal" tabindex="-1" aria-labelledby="resourceAssignmentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content protocol-modal">
      <form method="POST" action="{{ $action }}">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title" id="resourceAssignmentModalLabel">
            {{ $protocolRequest->request_type === 'vehicle' ? 'Assign Vehicle and Driver' : 'Assign Guest House' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="text-muted small">Save the assignment details for this approved request.</p>
          <div class="row g-3">
            @if ($protocolRequest->request_type === 'vehicle')
              @php($driver = $protocolRequest->vehicleDriver)
              <div class="col-md-6">
                <label class="form-label" for="assignmentVehicleNo">Vehicle Number <span class="required-star">*</span></label>
                <input type="text" name="vehicle_no" id="assignmentVehicleNo" maxlength="100" required
                  value="{{ old('vehicle_no', $driver?->vehicle_no) }}" class="form-control @error('vehicle_no') is-invalid @enderror">
                @error('vehicle_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assignmentVehicleName">Vehicle Name <span class="required-star">*</span></label>
                <input type="text" name="vehicle_name" id="assignmentVehicleName" maxlength="150" required
                  value="{{ old('vehicle_name', $driver?->vehicle_name) }}" class="form-control @error('vehicle_name') is-invalid @enderror">
                @error('vehicle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assignmentDriverName">Driver Name <span class="required-star">*</span></label>
                <input type="text" name="driver_name" id="assignmentDriverName" maxlength="150" required
                  value="{{ old('driver_name', $driver?->driver_name) }}" class="form-control @error('driver_name') is-invalid @enderror">
                @error('driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assignmentDriverNumber">Driver Number <span class="required-star">*</span></label>
                <input type="tel" name="driver_number" id="assignmentDriverNumber" maxlength="30" required
                  value="{{ old('driver_number', $driver?->driver_number) }}" class="form-control @error('driver_number') is-invalid @enderror">
                @error('driver_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            @else
              <div class="col-12">
                <label class="form-label" for="assignedGuestHouse">Guest House <span class="required-star">*</span></label>
                <select name="assigned_guest_house" id="assignedGuestHouse" class="form-select @error('assigned_guest_house') is-invalid @enderror"
                  data-searchable="true" data-placeholder="Select guest house" required>
                  <option value="">-- select --</option>
                  @foreach ($GuestHouse as $key => $value)
                    <option value="{{ $key }}" @selected(old('assigned_guest_house', $protocolRequest->requestable?->assigned_guest_house) == $key)>{{ $value }}</option>
                  @endforeach
                </select>
                @error('assigned_guest_house')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            @endif
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn pr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-green">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Save Assignment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($errors->has('vehicle_no') || $errors->has('vehicle_name') || $errors->has('driver_name') || $errors->has('driver_number') || $errors->has('assigned_guest_house'))
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('resourceAssignmentModal')).show();
    });
  </script>
@endif
