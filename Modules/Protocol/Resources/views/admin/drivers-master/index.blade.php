@extends('admin.layouts.master')

@section('title', 'Drivers Master')
@section('page_title', 'Drivers Master')
@section('page_subtitle', 'Manage driver master data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
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

    <div class="card-clean p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="section-title">Drivers Master</div>
          <div class="section-sub">Manage driver master data</div>
        </div>
      </div>

      <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <x-data-table.table 
                        :columns="$pageData['columns']"
                        :filters="[]" 
                        ajax-route="{{route('protocol.driver-master.index')}}" 
                        id="driver_master_table" 
                    />
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .protocol-tabs{
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f4f5f7;
      border-radius: 10px;
      padding: 6px;
      border: 1px solid #e5e7eb;
  }

  .protocol-tabs .nav-item{
      margin: 0;
  }

  .protocol-tabs .nav-link{
      display: flex;
      align-items: center;
      gap: 6px;
      color: #6b7280;
      font-size: 14px;
      font-weight: 600;
      border: 0;
      border-radius: 8px;
      padding: 6px 15px;
      background: transparent;
      transition: .2s ease;
  }

  .protocol-tabs .nav-link:hover{
      color: #0d6efd;
      background: rgba(13,110,253,.08);
  }

  .protocol-tabs .nav-link.active{
      background: #0d4f9c;
      color: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,.12);
  }

  .protocol-tabs .count{
      background: rgba(255,255,255,.2);
      color: inherit;
      border-radius: 999px;
      padding: 2px 7px;
      font-size: 12px;
      font-weight: 700;
  }

  .protocol-tabs .nav-link:not(.active) .count{
      background: #e5e7eb;
      color: #6b7280;
  }
  </style>
@endpush


@push('scripts')
@endpush
