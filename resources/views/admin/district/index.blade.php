@extends('admin.layouts.master')

@section('title', 'District - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="District" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>District</h4>
                    </div>
                    <div class="col-6">
                        <div class="float-end gap-2">
                            <a href="{{ route('master.district.create') }}" class="btn btn-primary">+ Add
                                District</a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="dataTables_wrapper">
                    <div class="row mb-3">
                        <div class="col-1">
                            <select name="search" id="" class="form-control">
                                <option value="">10</option>
                                <option value="">20</option>
                                <option value="">50</option>
                                <option value="">100</option>
                            </select>
                        </div>
                        <div class="col-11">
                            <div class="float-end">
                                <input type="search" class="form-control" id="search"
                                    placeholder="Search by district name">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered text-nowrap align-middle dataTable">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">District</th>
                                    <th class="col">Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($districts as $key => $district)
                                <tr class="odd">
                                    <td>{{ $key + 1 }}</td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">{{ $district->district_name }}</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('master.district.edit', $district->pk) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('master.district.delete', $district->pk) }}"
                                                method="POST" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $districts->links('pagination::bootstrap-5') }}

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection