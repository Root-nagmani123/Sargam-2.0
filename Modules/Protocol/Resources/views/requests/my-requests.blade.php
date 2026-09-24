@extends('admin.layouts.master')

@section('title', 'My Requests')
@section('page_title', 'My Requests')
@section('page_subtitle', 'Track the status of requests you have raised')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="My Requests"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'My Requests',
        ]"
        buttonText="Add New Request"
        :buttonUrl="route('protocol.requests.create')"
        buttonIcon="add"
    />

    <div class="card-clean p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="section-title">My Requests</div>
          <div class="section-sub">Track the status of requests you have raised</div>
        </div>
      </div>

      <!-- ================= FILTER TABS ================= -->
      <ul class="nav protocol-tabs">

          <li class="nav-item">
              <a href="javascript:void(0)"
                class="nav-link active"
                data-type="all">
                  All
                  <span class="count">{{ $counts['all'] }}</span>
              </a>
          </li>

          <li class="nav-item">
              <a href="javascript:void(0)"
                class="nav-link"
                data-type="guesthouse">
                  Guest House
                  <span class="count">{{ $counts['guesthouse'] }}</span>
              </a>
          </li>

          <li class="nav-item">
              <a href="javascript:void(0)"
                class="nav-link"
                data-type="vehicle">
                  Vehicle
                  <span class="count">{{ $counts['vehicle'] }}</span>
              </a>
          </li>

          <li class="nav-item">
              <a href="javascript:void(0)"
                class="nav-link"
                data-type="ticket">
                  Ticket
                  <span class="count">{{ $counts['ticket'] }}</span>
              </a>
          </li>

      </ul>

      {{-- Table --}}
      <div class="table-responsive">
          <table class="table align-middle text-nowrap" id="RequestTable">
              <thead>
                  <tr>
                      <th class="col">S.No.</th>
                      <th class="col">Request ID</th>
                      <th class="col">Type</th>
                      <th class="col text-wrap">Details</th>
                      <th class="col">Raised On</th>
                      <th class="col">Status</th>
                      <th class="col">Action</th>
                  </tr>
              </thead>
          </table>
      </div>
    </div>
  </div>
@endsection


@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  let type = 'all';
  let table;

  $(document).ready(function () {
    table = $('#RequestTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "{{ route('protocol.requests.my') }}",
        type: "GET",
        data: function (d) {
            d.type = type;
        }
      },
      columns: [
          {
              data: 'DT_RowIndex',
              orderable: false,
              searchable: false
          },
          {
              data: 'request_id'
          },
          {
              data: 'type'
          },
          {
              data: 'details'
          },
          {
              data: 'raised_on'
          },
          {
              data: 'status',
              orderable: false,
              searchable: false
          },
          {
              data: 'action',
              orderable: false,
              searchable: false
          }
      ],
      });
      $('.protocol-tabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        $('.protocol-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        type = $(this).data('type');
        table.ajax.reload();
      });
    });
  
</script>
@endpush

