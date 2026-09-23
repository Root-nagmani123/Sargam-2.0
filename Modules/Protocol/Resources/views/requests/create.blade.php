@extends('admin.layouts.master')

@section('title', 'New Request')
@section('page_title', 'New Request')
@section('page_subtitle', 'Select one or more request types, fill in the relevant sections, and submit')

@section('content')
@include('protocol::partials.style')
<div class="container-fluid profile-page">
  <x-breadcrum
      title="New Request"
      :items="[
          'Home',
          ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
          'New Request',
      ]"
  />
  <div class="card-clean p-4">
    <div class="mb-3 pb-3 border-bottom">
      <span class="text-muted small">Requester's Name</span>
      <div class="fw-bold text-danger">{{ auth()->user()?->first_name . ' ' . auth()->user()?->last_name ?? '' }}</div>
    </div>

    <!-- ================= TOP-OF-FORM ALERT (server / unexpected errors only) ================= -->
    <div id="formTopAlert" class="alert alert-danger d-none" role="alert"></div>

    <form method="POST" action="{{ route('protocol.requests.store') }}" id="combinedForm" novalidate>
      @csrf

      <!-- ================= TYPE CHECKBOXES ================= -->
     <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="type-check-card" for="type_guesthouse">
            <input type="checkbox" name="types[]" value="guesthouse" id="type_guesthouse" class="type-check-input" onchange="toggleSection('guesthouse')">
            <div class="icon-circle" style="background:#EFEBFF;color:#5B3FD9;"><i class="bi bi-building"></i></div>
            <div class="fw-bold text-dark">Guest House</div>
            <div class="text-muted small mt-1">Book accommodation for self or visiting guest</div>
          </label>
        </div>
        <div class="col-md-4">
          <label class="type-check-card" for="type_vehicle">
            <input type="checkbox" name="types[]" value="vehicle" id="type_vehicle" class="type-check-input" onchange="toggleSection('vehicle')">
            <div class="icon-circle" style="background:#FFF0E0;color:#E6802A;"><i class="bi bi-truck-front"></i></div>
            <div class="fw-bold text-dark">Vehicle Pass</div>
            <div class="text-muted small mt-1">Request an official vehicle for local/outstation travel</div>
          </label>
        </div>
        <div class="col-md-4">
          <label class="type-check-card" for="type_ticket">
            <input type="checkbox" name="types[]" value="ticket" id="type_ticket" class="type-check-input" onchange="toggleSection('ticket')">
            <div class="icon-circle" style="background:#E0F5F0;color:#0E8F73;"><i class="bi bi-ticket-perforated"></i></div>
            <div class="fw-bold text-dark">Ticket</div>
            <div class="text-muted small mt-1">Request rail / air / bus ticket booking</div>
          </label>
        </div>
      </div>

      <div class="invalid-feedback d-block mb-2" id="err_types" style="display:none !important;"></div>
      <div class="alert-hint mb-4" id="noTypeHint"><i class="bi bi-info-circle me-1"></i> Check one or more types above to open the relevant form section.</div>

      <!-- ================= SHARED TOP FIELDS ================= -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">Course Team To Notify</label>
          <select name="course_team_to_notify" class="form-select">
            <option value="">-- select --</option>
            @if($courses->isNotEmpty())
            @foreach($courses as $key => $value)
            <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
            @endif
          </select>
          <div class="invalid-feedback" id="err_course_team_to_notify"></div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Purpose <span class="required-star">*</span></label>
          <select name="purpose" class="form-select" required>
            <option value="">-- select --</option>
            <option value="Official Visit">Official Visit</option>
            <option value="Training Programme">Training Programme</option>
            <option value="Field Visit">Field Visit</option>
            <option value="Guest Lecture">Guest Lecture</option>
            <option value="Family Visit">Family Visit</option>
            <option value="Other">Other</option>
          </select>
          <div class="invalid-feedback" id="err_purpose"></div>
        </div>
        <div class="col-md-4">
          <label class="form-label d-block">Escort Required <span class="required-star">*</span></label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="escort_required" id="escort_yes" value="yes" required>
            <label class="form-check-label" for="escort_yes">Yes</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="escort_required" id="escort_no" value="no" checked>
            <label class="form-check-label" for="escort_no">No</label>
          </div>
          <div class="invalid-feedback d-block" id="err_escort_required"></div>
        </div>
      </div>

      <!-- ================= GUEST DETAILS (SHARED) ================= -->
      <div class="form-section">
        <div class="form-section-header"><i class="bi bi-people"></i> Provide Guest Details</div>

        <div class="invalid-feedback d-block mb-2" id="err_guests" style="display:none !important;"></div>

        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0" id="guestsTable">
            <thead class="table-primary">
              <tr>
                <th>Guest Name <span class="required-star">*</span></th>
                <th style="width:80px;">Age</th>
                <th style="width:110px;">Sex <span class="required-star">*</span></th>
                <th>Designation</th>
                <th>Mobile No <span class="required-star">*</span></th>
                <th>Email ID</th>
                <th>Guest ID Proof</th>
                <th style="width:90px;">Main Guest</th>
                <th style="width:70px;"></th>
              </tr>
            </thead>
            <tbody id="guestsTableBody"></tbody>
          </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addGuestRow()"><i class="bi bi-plus-lg"></i> Add Guest</button>
      </div>

      <!-- ================= GUEST HOUSE SECTION ================= -->
      <div class="form-section" id="section_guesthouse" style="display:none;">
        <div class="form-section-header"><i class="bi bi-building"></i> For Guest House</div>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Date From <span class="required-star">*</span></label>
            <input type="date" name="guest_house[date_from]" class="form-control section-field" data-section="guesthouse">
            <div class="invalid-feedback" id="err_guest_house-date_from"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date To <span class="required-star">*</span></label>
            <input type="date" name="guest_house[date_to]" class="form-control section-field" data-section="guesthouse">
            <div class="invalid-feedback" id="err_guest_house-date_to"></div>
          </div>
          <div class="col-md-2">
            <label class="form-label">No. of Guests</label>
            <input type="number" name="guest_house[no_of_guests]" class="form-control" value="1" min="1">
            <div class="invalid-feedback" id="err_guest_house-no_of_guests"></div>
          </div>
          <div class="col-md-2">
            <label class="form-label">No. of Rooms <span class="required-star">*</span></label>
            <input type="number" name="guest_house[no_of_rooms]" class="form-control section-field" data-section="guesthouse" value="1" min="1">
            <div class="invalid-feedback" id="err_guest_house-no_of_rooms"></div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Payment Done By</label>
            <select name="guest_house[payment_done_by]" class="form-select">
              <option>Myself</option>
              <option>Institute</option>
              <option>Guest</option>
            </select>
            <div class="invalid-feedback" id="err_guest_house-payment_done_by"></div>
          </div>
          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea name="guest_house[remarks]" class="form-control" rows="2"></textarea>
            <div class="invalid-feedback" id="err_guest_house-remarks"></div>
          </div>
        </div>
      </div>

      <!-- ================= VEHICLE SECTION ================= -->
      <div class="form-section" id="section_vehicle" style="display:none;">
        <div class="form-section-header"><i class="bi bi-truck-front"></i> For Vehicle</div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label">Vehicle Type <span class="required-star">*</span></label>
            <select name="vehicle[vehicle_type]" class="form-select section-field" data-section="vehicle">
              <option value="">--- Select ---</option>
                @foreach ($vehicles as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" id="err_vehicle-vehicle_type"></div>
          </div>
          <div class="col-md-8 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="vehicle[one_way_booking]" value="1" id="one_way">
              <label class="form-check-label fw-semibold text-success" for="one_way">One Way Booking</label>
            </div>
          </div>
        </div>

        <div class="invalid-feedback d-block mb-2" id="err_vehicle-legs" style="display:none !important;"></div>

        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0" id="vehicleLegsTable">
            <thead class="table-primary">
              <tr>
                <th>Date/Time From <span class="required-star">*</span></th>
                <th>Date/Time To</th>
                <th>Pickup Point <span class="required-star">*</span></th>
                <th>Departure Time</th>
                <th>Drop Point <span class="required-star">*</span></th>
                <th>Arrival Time</th>
                <th style="width:90px;">No. of Persons</th>
                <th>Remarks</th>
                <th style="width:60px;"></th>
              </tr>
            </thead>
            <tbody id="vehicleLegsTableBody"></tbody>
          </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addVehicleLeg()"><i class="bi bi-plus-lg"></i> Add Leg</button>

        <div class="row g-3 mt-3">
          <div class="col-md-4">
            <label class="form-label">Payment Will Be Done By</label>
            <select name="vehicle[payment_will_be_done_by]" class="form-select">
              <option>Myself</option>
              <option>Institute</option>
              <option>Guest</option>
            </select>
            <div class="invalid-feedback" id="err_vehicle-payment_will_be_done_by"></div>
          </div>
        </div>
      </div>

      <!-- ================= TICKET SECTION ================= -->
      <div class="form-section" id="section_ticket" style="display:none;">
        <div class="form-section-header"><i class="bi bi-ticket-perforated"></i> For Ticket Booking</div>

        <div class="invalid-feedback d-block mb-2" id="err_ticket-journeys" style="display:none !important;"></div>

        <div id="ticketJourneysWrap"></div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addTicketJourney()"><i class="bi bi-plus-lg"></i> Add Journey</button>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('protocol.requests.my') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
          <i class="bi bi-send-check me-1"></i> <span id="submitBtnLabel">Submit Request</span>
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('styles')
<style>
  .type-check-card{
    display:block; position:relative; border:1.5px solid var(--border); border-radius:14px;
    padding:22px; text-align:center; cursor:pointer; transition:.15s; height:100%;
  }
  .type-check-card:hover{border-color:var(--saffron); box-shadow:0 4px 16px rgba(255,153,51,.12);}
  .type-check-input{position:absolute; top:14px; right:14px; width:18px; height:18px; cursor:pointer;}
  .type-check-card:has(.type-check-input:checked){
    border-color:var(--saffron); background:#FFF9F0; box-shadow:0 4px 16px rgba(255,153,51,.15);
  }
  .form-section{
    border:1.5px solid var(--border); border-radius:12px; padding:20px; margin-bottom:20px; background:#FAFAF8;
  }
  .form-section-header{
    font-weight:700; font-size:14.5px; margin-bottom:16px; display:flex; align-items:center; gap:8px; color:var(--navy);
  }
   #guestsTable th, #vehicleLegsTable th{font-size:10.5px; text-transform:uppercase; letter-spacing:.03em;}
  #guestsTable td, #vehicleLegsTable td{padding:6px;}
  #guestsTable input, #guestsTable select, #vehicleLegsTable input, #vehicleLegsTable select{font-size:12.5px; padding:4px 6px;}
  .remove-row-btn{color:#C13030; cursor:pointer; border:none; background:none; font-size:16px;}
  .journey-block{border:1px dashed var(--border); border-radius:10px; padding:16px; margin-bottom:14px; background:#fff; position:relative;}
  .journey-block .remove-journey-btn{position:absolute; top:10px; right:10px;}

  /* Inline field errors */
  .invalid-feedback{display:none; color:#C13030; font-size:12px; margin-top:4px;}
  .is-invalid{border-color:#C13030 !important;}
  .is-invalid ~ .invalid-feedback,
  .invalid-feedback.show{display:block;}
  #guestsTable .invalid-feedback, #vehicleLegsTable .invalid-feedback{font-size:11px; margin-top:2px;}
</style>
@endpush

@push('scripts')
<script>
let guestRowIndex = 0;
let vehicleLegIndex = 0;
let ticketJourneyIndex = 0;

function toggleSection(type) {
  const checkbox = document.getElementById('type_' + type);
  const section = document.getElementById('section_' + type);
  section.style.display = checkbox.checked ? 'block' : 'none';

  section.querySelectorAll('.section-field').forEach(f => f.required = checkbox.checked);

  // Ensure at least one row exists when a section is opened for the first time
  if (checkbox.checked) {
    if (type === 'vehicle' && document.querySelectorAll('#vehicleLegsTableBody tr').length === 0) addVehicleLeg();
    if (type === 'ticket' && document.querySelectorAll('.journey-block').length === 0) addTicketJourney();
  }

  updateSubmitState();
}

function updateSubmitState() {
  const anyChecked = document.querySelectorAll('input[name="types[]"]:checked').length > 0;
  document.getElementById('submitBtn').disabled = !anyChecked;
  document.getElementById('noTypeHint').style.display = anyChecked ? 'none' : 'block';
}

/* ---------- Guest Details rows ---------- */
function addGuestRow() {
  const i = guestRowIndex++;
  const tbody = document.getElementById('guestsTableBody');
  const isFirst = tbody.children.length === 0;
  const row = document.createElement('tr');
  row.dataset.rowIndex = i;
  row.innerHTML = `
    <td>
      <input type="text" name="guests[${i}][guest_name]" class="form-control" required>
      <div class="invalid-feedback" data-err="guests.${i}.guest_name"></div>
    </td>
    <td>
      <input type="number" name="guests[${i}][age]" class="form-control" min="0" max="120">
      <div class="invalid-feedback" data-err="guests.${i}.age"></div>
    </td>
    <td>
      <select name="guests[${i}][sex]" class="form-select" required>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
      <div class="invalid-feedback" data-err="guests.${i}.sex"></div>
    </td>
    <td><input type="text" name="guests[${i}][designation]" class="form-control"></td>
    <td>
      <input type="text" name="guests[${i}][mobile_no]" class="form-control" required>
      <div class="invalid-feedback" data-err="guests.${i}.mobile_no"></div>
    </td>
    <td>
      <input type="email" name="guests[${i}][email_id]" class="form-control">
      <div class="invalid-feedback" data-err="guests.${i}.email_id"></div>
    </td>
    <td><input type="text" name="guests[${i}][guest_id_proof]" class="form-control"></td>
    <td class="text-center"><input type="radio" name="main_guest_selector" value="${i}" onchange="setMainGuest(${i})" ${isFirst ? 'checked' : ''}>
      <input type="hidden" name="guests[${i}][is_main_guest]" id="is_main_guest_${i}" value="${isFirst ? '1' : '0'}">
    </td>
    <td class="text-center"><button type="button" class="remove-row-btn" onclick="this.closest('tr').remove()"><i class="bi bi-dash-circle"></i></button></td>
  `;
  tbody.appendChild(row);
}

function setMainGuest(selectedIndex) {
  document.querySelectorAll('#guestsTableBody input[type=hidden][id^="is_main_guest_"]').forEach(el => {
    el.value = '0';
  });
  document.getElementById('is_main_guest_' + selectedIndex).value = '1';
}

/* ---------- Vehicle legs ---------- */
function addVehicleLeg() {
  const i = vehicleLegIndex++;
  const tbody = document.getElementById('vehicleLegsTableBody');
  const row = document.createElement('tr');
  row.dataset.rowIndex = i;
  row.innerHTML = `
    <td>
      <input type="datetime-local" name="vehicle[legs][${i}][date_time_from]" class="form-control section-field" data-section="vehicle">
      <div class="invalid-feedback" data-err="vehicle.legs.${i}.date_time_from"></div>
    </td>
    <td><input type="datetime-local" name="vehicle[legs][${i}][date_time_to]" class="form-control"></td>
    <td>
      <select name="vehicle[legs][${i}][pickup_type]" class="form-select mb-1">
        <option value="location">Location</option>
        <option value="reference">Train/Flight/Guest House</option>
      </select>
      <input type="text" name="vehicle[legs][${i}][pickup_value]" class="form-control section-field" data-section="vehicle" placeholder="e.g. Campus / 12345">
      <div class="invalid-feedback" data-err="vehicle.legs.${i}.pickup_value"></div>
    </td>
    <td><input type="time" name="vehicle[legs][${i}][pickup_time]" class="form-control"></td>
    <td>
      <select name="vehicle[legs][${i}][drop_type]" class="form-select mb-1">
        <option value="location">Location</option>
        <option value="reference">Train/Flight/Guest House</option>
      </select>
      <input type="text" name="vehicle[legs][${i}][drop_value]" class="form-control section-field" data-section="vehicle" placeholder="e.g. Railway Station">
      <div class="invalid-feedback" data-err="vehicle.legs.${i}.drop_value"></div>
    </td>
    <td><input type="time" name="vehicle[legs][${i}][drop_time]" class="form-control"></td>
    <td><input type="number" name="vehicle[legs][${i}][no_of_persons]" class="form-control" value="1" min="1"></td>
    <td><textarea name="vehicle[legs][${i}][remarks]" class="form-control" rows="1"></textarea></td>
    <td class="text-center"><button type="button" class="remove-row-btn" onclick="this.closest('tr').remove()"><i class="bi bi-dash-circle"></i></button></td>
  `;
  tbody.appendChild(row);
}

/* ---------- Ticket journeys ---------- */
function addTicketJourney() {
  const i = ticketJourneyIndex++;
  const wrap = document.getElementById('ticketJourneysWrap');
  const block = document.createElement('div');
  block.className = 'journey-block';
  block.dataset.rowIndex = i;
  block.innerHTML = `
    <button type="button" class="remove-journey-btn remove-row-btn" onclick="this.closest('.journey-block').remove()"><i class="bi bi-dash-circle"></i></button>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Origin <span class="required-star">*</span></label>
        <input type="text" name="ticket[journeys][${i}][origin]" class="form-control section-field" data-section="ticket">
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.origin"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Destination <span class="required-star">*</span></label>
        <input type="text" name="ticket[journeys][${i}][destination]" class="form-control section-field" data-section="ticket">
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.destination"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Ticket Type <span class="required-star">*</span></label>
        <select name="ticket[journeys][${i}][ticket_type]" class="form-select section-field" data-section="ticket">
          <option value="Air">Air</option>
          <option value="Rail">Rail</option>
          <option value="Bus">Bus</option>
        </select>
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.ticket_type"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Class</label>
        <input type="text" name="ticket[journeys][${i}][class]" class="form-control" placeholder="e.g. AC 2-Tier, Economy">
      </div>
      <div class="col-md-3">
        <label class="form-label">Date of Journey <span class="required-star">*</span></label>
        <input type="date" name="ticket[journeys][${i}][date_of_journey]" class="form-control section-field" data-section="ticket">
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.date_of_journey"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Train/Flight/Bus Name <span class="required-star">*</span></label>
        <input type="text" name="ticket[journeys][${i}][train_flight_bus_name]" class="form-control section-field" data-section="ticket">
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.train_flight_bus_name"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Train/Flight/Bus No <span class="required-star">*</span></label>
        <input type="text" name="ticket[journeys][${i}][train_flight_bus_no]" class="form-control section-field" data-section="ticket">
        <div class="invalid-feedback" data-err="ticket.journeys.${i}.train_flight_bus_no"></div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Quota</label>
        <select name="ticket[journeys][${i}][quota]" class="form-select">
          <option>General</option>
          <option>Tatkal</option>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="ticket[journeys][${i}][book_if_waiting]" value="1" id="waiting_${i}">
          <label class="form-check-label" for="waiting_${i}">Book if in waiting</label>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Payment Will Be Done By</label>
        <select name="ticket[journeys][${i}][payment_will_be_done_by]" class="form-select">
          <option>Myself</option>
          <option>Institute</option>
          <option>Guest</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Remarks</label>
        <textarea name="ticket[journeys][${i}][remarks]" class="form-control" rows="1"></textarea>
      </div>
    </div>
  `;
  wrap.appendChild(block);
}

// Seed one guest row by default so the table isn't empty on load
addGuestRow();

/* =========================================================
   VALIDATION ERROR HANDLING
   - Field-level errors (422 response) render under the exact
     input, by converting Laravel's dot-notation error key
     (e.g. "guest_house.date_from", "guests.0.mobile_no")
     into the matching bracket-notation `name` attribute
     ("guest_house[date_from]", "guests[0][mobile_no]").
   - Anything that isn't a field-level 422 (500s, network
     failures, unexpected exceptions) shows once in the
     top-of-form banner instead.
========================================================= */

function dotToBracketName(key) {
  const parts = key.split('.');
  return parts[0] + parts.slice(1).map(p => `[${p}]`).join('');
}

function clearAllErrors() {
  document.querySelectorAll('#combinedForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('#combinedForm .invalid-feedback').forEach(el => {
    el.textContent = '';
    el.classList.remove('show');
  });
  const topAlert = document.getElementById('formTopAlert');
  topAlert.classList.add('d-none');
  topAlert.textContent = '';
}

// Group-level errors that aren't attached to one specific input
const GROUP_ERROR_TARGETS = {
  'types': 'err_types',
  'guests': 'err_guests',
  'vehicle.legs': 'err_vehicle-legs',
  'ticket.journeys': 'err_ticket-journeys',
};

function showGroupError(elementId, message) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = message;
  el.classList.add('show');
  el.style.removeProperty('display');
}

function renderFieldErrors(errors) {
  Object.keys(errors).forEach(key => {
    const message = Array.isArray(errors[key]) ? errors[key][0] : String(errors[key]);

    // Group-level (no specific row/field to attach to)
    if (GROUP_ERROR_TARGETS[key]) {
      showGroupError(GROUP_ERROR_TARGETS[key], message);
      return;
    }

    // Dynamically-added rows: guests.N.field / vehicle.legs.N.field / ticket.journeys.N.field
    const dynamicMatch = key.match(/^(guests|vehicle\.legs|ticket\.journeys)\.(\d+)\.(.+)$/);
    if (dynamicMatch) {
      const errBox = document.querySelector(`[data-err="${key}"]`);
      const bracketName = dotToBracketName(key);
      const input = document.querySelector(`[name="${bracketName}"]`);
      if (input) input.classList.add('is-invalid');
      if (errBox) {
        errBox.textContent = message;
        errBox.classList.add('show');
      }
      return;
    }

    // Simple top-level or nested-once fields (purpose, escort_required,
    // guest_house.date_from, vehicle.vehicle_type, etc.)
    const bracketName = dotToBracketName(key);
    const input = document.querySelector(`[name="${bracketName}"]`);
    const errBoxId = 'err_' + key.replace(/\./g, '-');
    const errBox = document.getElementById(errBoxId);

    if (input) input.classList.add('is-invalid');
    if (errBox) {
      errBox.textContent = message;
      errBox.classList.add('show');
    } else if (input) {
      // Fallback: no dedicated error box was pre-placed for this field —
      // insert one right after the input so the message still appears
      // below the field rather than getting lost.
      const fallback = document.createElement('div');
      fallback.className = 'invalid-feedback show';
      fallback.textContent = message;
      input.insertAdjacentElement('afterend', fallback);
    }
  });
}

function showTopAlert(message) {
  const topAlert = document.getElementById('formTopAlert');
  topAlert.textContent = message;
  topAlert.classList.remove('d-none');
  topAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function setSubmitting(isSubmitting) {
  const btn = document.getElementById('submitBtn');
  const label = document.getElementById('submitBtnLabel');
  btn.disabled = isSubmitting;
  label.textContent = isSubmitting ? 'Submitting…' : 'Submit Request';
}

document.getElementById('combinedForm').addEventListener('submit', function (e) {
  e.preventDefault();
  
  clearAllErrors();

  // Client-side pre-checks first, rendered the same way as server errors
  const anyChecked = document.querySelectorAll('input[name="types[]"]:checked').length > 0;
  if (!anyChecked) {
    showGroupError('err_types', 'Please select at least one request type.');
    return;
  }
  if (document.querySelectorAll('#guestsTableBody tr').length === 0) {
    showGroupError('err_guests', 'Please add at least one guest.');
    return;
  }

  const form = document.getElementById('combinedForm');
  const formData = new FormData(form);

  setSubmitting(true);

  fetch(form.action, {
    method: 'POST',
    body: formData,
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })
    .then(async (response) => {
      const data = await response.json().catch(() => null);

      if (response.status === 422 && data?.errors) {
        renderFieldErrors(data.errors);
        return;
      }

      if (!response.ok) {
        showTopAlert((data && data.message) || 'Something went wrong while submitting your request. Please try again, or contact IT support if the problem continues.');
        return;
      }

      // Success
      if (data?.redirect) {
        window.location = data.redirect;
      } else {
        window.location.reload();
      }
    })
    .catch(() => {
      showTopAlert('Could not reach the server. Please check your connection and try again.');
    })
    .finally(() => {
      setSubmitting(false);
    });
});
</script>
@endpush
