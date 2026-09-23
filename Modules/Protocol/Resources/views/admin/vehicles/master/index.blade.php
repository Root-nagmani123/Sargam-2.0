@extends('admin.layouts.master')
@section('title', 'Vehicles Master')
@section('page_title', 'Vehicles Master')
@section('page_subtitle', 'Manage vehicle master data')
@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Vehicles Master"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Vehicles Master',
        ]"
        buttonText="Add New Vehicle"
        :buttonUrl="route('protocol.vehicle-master.create')"
        buttonIcon="add"
    />
    <div class="card-clean p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="section-title">Vehicles Master</div>
          <div class="section-sub">Manage vehicle master data</div>
        </div>
      </div>
      <div class="datatables">
        <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.vehicle-master.index') }}"
                id="vehicles-master-table"
            />
        </div>
      </div>
    </div>
  </div>
@endsection
@push('scripts')
@endpush