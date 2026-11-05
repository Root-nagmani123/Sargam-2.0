@extends('admin.layouts.master')

@section('title', 'My Requests')
@section('page_title', 'My Requests')
@section('page_subtitle', 'Track the status of requests you have raised')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
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

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
      @include('protocol::partials.type-tabs', ['tabs' => $pageData['tabs']])
    </div>

    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
          <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <div class="programme-dt-search" data-dt-search-for="my-requests-table"></div>
          </div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.requests.my') }}"
                id="my-requests-table"
            />
          </div>
        </div>

        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="my-requests-table"></div>
      </div>
    </div>
  </div>
@endsection
