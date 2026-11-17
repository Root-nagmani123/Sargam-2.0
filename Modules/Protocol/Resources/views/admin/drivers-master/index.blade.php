@extends('admin.layouts.master')

@section('title', 'Drivers Master')
@section('page_title', 'Drivers Master')
@section('page_subtitle', 'Manage driver master data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Drivers Master"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Drivers Master',
        ]"
        buttonText="Add New Driver"
        :buttonUrl="route('protocol.driver-master.create')"
        buttonIcon="add"
    />

    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
          <div class="section-sub">Manage driver master data</div>
          <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <div class="programme-dt-search" data-dt-search-for="driver_master_table"></div>
          </div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.driver-master.index') }}"
                id="driver_master_table"
            />
          </div>
        </div>

        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="driver_master_table"></div>
      </div>
    </div>
  </div>
@endsection
