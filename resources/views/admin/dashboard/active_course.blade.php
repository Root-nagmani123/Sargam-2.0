@extends('admin.layouts.master')

@section('title', 'Active Course - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Active Course"></x-breadcrum>

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4>Active Course</h4>
            <hr class="my-2">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Course Name</th>
                            <th scope="col">Instructor</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">End Date</th>
                        </tr>
                    </thead>
                    <tbody>



                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection