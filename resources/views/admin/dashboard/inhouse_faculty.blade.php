@extends('admin.layouts.master')

@section('title', 'Inhouse Faculty - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid inhouse-faculty-index">
    <x-breadcrum title="Inhouse Faculty"></x-breadcrum>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h1 class="h4 mb-0">Inhouse Faculty</h1>
            <hr class="my-2">
            <div class="datatables">
                <div class="table-responsive">
               <table class="table table-striped table-hover align-middle" id="inhouse">
                    <caption class="visually-hidden">Inhouse faculty list</caption>
                    <thead>
                        <tr>
                            <th scope="col">Sl. No.</th>
                            <th scope="col">Faculty Type</th>
                            <th scope="col">Faculty Name</th>
                            <th scope="col">Mobile Number</th>
                            <th scope="col">Current Sector</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inhouse_faculty as $index => $faculty)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="badge bg-success-subtle text-success">Inhouse</span></td>
                            <td>{{ $faculty->full_name }}</td>
                            <td>{{ $faculty->mobile_no }}</td>
                            <td><span class="badge bg-success-subtle text-success">@if($faculty->faculty_sector == 1){{ 'Government' }}@elseif($faculty->faculty_sector == 2){{ 'Private' }}@else{{ 'Other' }}@endif</span></td>
                             </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

<script>
$(document).ready(function (){
    $('#inhouse').DataTable();
});
</script>
@endpush



