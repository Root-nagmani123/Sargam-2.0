@extends('admin.layouts.master')
@section('title', 'Vehicles Master')
@section('page_title', 'Vehicles Master')
@section('page_subtitle', 'Manage vehicle master data')
@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
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

    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
          <div class="section-sub">Manage vehicle master data</div>
          <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <div class="programme-dt-search" data-dt-search-for="vehicles-master-table"></div>
          </div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.vehicle-master.index') }}"
                id="vehicles-master-table"
            />
          </div>
        </div>

        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="vehicles-master-table"></div>
      </div>
    </div>
  </div>
@endsection
