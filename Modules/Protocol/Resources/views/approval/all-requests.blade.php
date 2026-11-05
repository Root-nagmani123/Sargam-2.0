@extends('admin.layouts.master')
@section('title', 'All Requests')
@section('page_title', 'All Requests')
@section('page_subtitle', 'Complete list of protocol requests across the institute')

@section('content')
@include('protocol::partials.style')
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
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
          <div class="section-sub">
            {{ request('status') === 'recommended' ? 'Requests recommended for a final decision' : 'Complete list of protocol requests across the institute' }}
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <div class="programme-dt-search" data-dt-search-for="all-requests-table"></div>
          </div>
        </div>

        {{-- Fetches from the route that rendered the page: recommended-booking holders cannot reach requests.all --}}
        <div class="programme-dt-panel">
          <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route(request()->routeIs('protocol.recommended-booking') ? 'protocol.recommended-booking' : 'protocol.requests.all', array_filter(['status' => request('status')])) }}"
                id="all-requests-table"
            />
          </div>
        </div>

        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="all-requests-table"></div>
      </div>
    </div>
  </div>
@endsection
