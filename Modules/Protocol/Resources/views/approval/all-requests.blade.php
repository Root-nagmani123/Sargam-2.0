@extends('admin.layouts.master')
@section('title', 'All Requests')
@section('page_title', 'All Requests')
@section('page_subtitle', 'Complete list of protocol requests across the institute')
@section('page_style')
  
@section('content')
@include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="All Requests"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Requests History',
        ]"
    />

    <div class="card-clean p-3">
      <div class="section-title">All Requests</div>
      <div class="section-sub mb-2">Complete list of protocol requests across the institute</div>

      
       {{-- Table --}}
      <div class="datatables">
        <div class="table-responsive">
            <x-data-table.table
                :columns="$pageData['columns']"
                :filters="[]"
                ajax-route="{{ route('protocol.requests.all') }}"
                id="all-requests-table"
                :tabs="$pageData['tabs']"
            />
        </div>
      </div>
    </div>
  </div>
@endsection
