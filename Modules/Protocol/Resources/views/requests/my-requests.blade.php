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

      {{-- Table --}}
        <div class="datatables">
        <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.requests.my') }}"
                id="my-requests-table"
                :tabs="$pageData['tabs']"
            />
        </div>
      </div>
    </div>
  </div>
@endsection