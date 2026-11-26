@extends('admin.layouts.master')
@section('title', 'All Requests')
@section('page_title', 'All Requests')
@section('page_subtitle', 'Complete list of protocol requests across the institute')

@section('content')
@include('protocol::partials.style')
@php
  $requestsAjaxRouteName = request()->routeIs('protocol.recommended-booking')
      ? 'protocol.recommended-booking'
      : 'protocol.requests.all';
@endphp
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="{{ request('status') === 'recommended' ? 'Recommended Requests' : 'All Requests' }}"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Requests History',
        ]"
    />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
      @include('protocol::partials.type-tabs', ['tabs' => $pageData['tabs']])
      @if (request('status') === 'recommended')
        <a href="{{ route('protocol.requests.all') }}" class="btn programme-dt-btn-columns border-0 text-primary">
          <i class="bi bi-collection" aria-hidden="true"></i><span>View all requests</span>
        </a>
      @endif
    </div>

    <div class="card">
      <div class="card-body">
        @if (session('success'))
          <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        <div>
          <div class="section-sub">
            {{ request('status') === 'recommended' ? 'Requests recommended for a final decision' : 'Complete list of protocol requests across the institute' }}
          </div>
        </div>
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
          
          <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <select id="all-requests-status-filter" class="form-select w-auto" aria-label="Filter by status">
              <option value="" {{ in_array(request('status'), array_keys(config('protocol.statuses', [])), true) ? '' : 'selected' }}>All statuses</option>
              @foreach (config('protocol.statuses', []) as $status => $label)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <div class="programme-dt-search" data-dt-search-for="all-requests-table"></div>
          </div>
        </div>

        {{-- Fetches from the route that rendered the page: recommended-booking holders cannot reach requests.all --}}
        <div class="programme-dt-panel">
          <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route($requestsAjaxRouteName, array_filter(['status' => request('status')])) }}"
                id="all-requests-table"
            />
          </div>
        </div>

        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="all-requests-table"></div>
      </div>
    </div>

    <div class="modal fade" id="ticketUploadModal" tabindex="-1" aria-labelledby="ticketUploadModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content protocol-modal">
          <form id="ticketUploadForm" method="POST" enctype="multipart/form-data"
                data-url-template="{{ route('protocol.requests.ticket-files.store', ['protocolRequest' => 'REQUEST_ID']) }}">
            @csrf
            <input type="hidden" name="protocol_request_id" id="ticketUploadRequestId" value="{{ old('protocol_request_id') }}">
            <input type="hidden" name="request_number" id="ticketUploadRequestNumberInput" value="{{ old('request_number') }}">

            <div class="modal-header">
              <h5 class="modal-title" id="ticketUploadModalLabel">
                Upload tickets for <span id="ticketUploadRequestNumber">{{ old('request_number') }}</span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="text-muted small">Upload PDF or image files. Up to 2 MB in size.</p>
              @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
              @endif
              <div id="ticketUploadFileInputs">
                <div class="ticket-upload-file-row mb-2">
                  <input type="file" name="ticket_files[]" class="form-control" aria-label="Ticket file"
                         accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/jpeg,image/png,image/gif,image/webp">
                </div>
              </div>
              <div id="ticketUploadFileError" class="text-danger small d-none" role="alert"></div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addTicketUploadInput">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add another file
              </button>
              <template id="ticketUploadFileTemplate">
                <div class="ticket-upload-file-row d-flex align-items-center gap-2 mb-2">
                  <input type="file" name="ticket_files[]" class="form-control" aria-label="Ticket file"
                         accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/jpeg,image/png,image/gif,image/webp">
                  <button type="button" class="btn btn-sm btn-outline-danger remove-ticket-upload-input">Remove</button>
                </div>
              </template>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn pr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload Files
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    $(function () {
      var table = $('#all-requests-table').DataTable();
      var baseAjaxRoute = @json(route($requestsAjaxRouteName));

      table.on('preXhr.dt', function (event, settings, data) {
        data.status = $('#all-requests-status-filter').val();
      });

      $('#all-requests-status-filter').on('change', function () {
        table.ajax.url(baseAjaxRoute).load();
      });

      var uploadForm = document.getElementById('ticketUploadForm');
      var uploadModalElement = document.getElementById('ticketUploadModal');
      var uploadModal = bootstrap.Modal.getOrCreateInstance(uploadModalElement);
      var fileInputs = document.getElementById('ticketUploadFileInputs');
      var fileTemplate = document.getElementById('ticketUploadFileTemplate');
      var fileError = document.getElementById('ticketUploadFileError');
      var requestIdInput = document.getElementById('ticketUploadRequestId');
      var requestNumberInput = document.getElementById('ticketUploadRequestNumberInput');
      var requestNumberLabel = document.getElementById('ticketUploadRequestNumber');
      var addInputButton = document.getElementById('addTicketUploadInput');
      var maxFiles = 20;

      $('#all-requests-table').on('click', '.js-ticket-upload', function () {
        var requestId = this.getAttribute('data-request-id');
        var requestNumber = this.getAttribute('data-request-number');
        uploadForm.action = this.getAttribute('data-upload-url');
        requestIdInput.value = requestId;
        requestNumberInput.value = requestNumber;
        requestNumberLabel.textContent = requestNumber;
        uploadModal.show();
      });

      function updateAddInputButton() {
        addInputButton.disabled = fileInputs.querySelectorAll('input[type="file"]').length >= maxFiles;
      }

      addInputButton.addEventListener('click', function () {
        if (fileInputs.querySelectorAll('input[type="file"]').length < maxFiles) {
          fileInputs.appendChild(fileTemplate.content.cloneNode(true));
          updateAddInputButton();
        }
      });

      fileInputs.addEventListener('click', function (event) {
        var removeButton = event.target.closest('.remove-ticket-upload-input');
        if (removeButton) {
          removeButton.closest('.ticket-upload-file-row').remove();
          updateAddInputButton();
        }
      });

      fileInputs.addEventListener('change', function () {
        fileError.classList.add('d-none');
      });

      uploadForm.addEventListener('submit', function (event) {
        var hasFile = Array.prototype.some.call(fileInputs.querySelectorAll('input[type="file"]'), function (input) {
          return input.files && input.files.length > 0;
        });

        if (!hasFile) {
          event.preventDefault();
          fileError.textContent = 'Select at least one PDF or image file.';
          fileError.classList.remove('d-none');
          return;
        }

        Array.prototype.forEach.call(fileInputs.querySelectorAll('input[type="file"]'), function (input) {
          if (!input.files || input.files.length === 0) {
            input.closest('.ticket-upload-file-row').remove();
          }
        });
      });

      uploadModalElement.addEventListener('hidden.bs.modal', function () {
        uploadForm.reset();
        fileInputs.innerHTML = '<div class="ticket-upload-file-row mb-2"><input type="file" name="ticket_files[]" class="form-control" aria-label="Ticket file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/jpeg,image/png,image/gif,image/webp"></div>';
        fileError.classList.add('d-none');
        updateAddInputButton();
      });

      @if ($errors->any() && old('protocol_request_id'))
        uploadForm.action = uploadForm.getAttribute('data-url-template').replace('REQUEST_ID', encodeURIComponent(requestIdInput.value));
        requestNumberLabel.textContent = requestNumberInput.value;
        uploadModal.show();
      @endif
    });
  </script>
@endpush
