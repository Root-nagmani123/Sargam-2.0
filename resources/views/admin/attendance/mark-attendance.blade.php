@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Attendance" />
        <x-session_message />


        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Attendance</h4>
                    </div>
                </div>
                <hr>

            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Student List</h4>
                    </div>
                </div>
                <hr>
                <table>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            
                        </tr>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>John Doe</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


@endsection