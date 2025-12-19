@extends('admin.layouts.master')

@section('title', 'Guest Faculty - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Guest Faculty"></x-breadcrum>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4>Guest Faculty</h4>
            <hr class="my-2">
            <div class="table-responsive">
                <table class="table">
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
                        @foreach($guest_faculty as $index => $faculty)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="  text-center"><span class="badge bg-success-subtle text-success">Guest</span></td>
                            <td>{{ $faculty->full_name }}</td>
                            <td>{{ $faculty->mobile_no }}</td>
                            <td class="  text-center"><span class="badge bg-success-subtle text-success">@if($faculty->faculty_sector == 1){{ 'Government' }}@elseif($faculty->faculty_sector == 2){{ 'Private' }}@else{{ 'Other' }}@endif</span></td>
                             </tr>
                        @endforeach



                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection