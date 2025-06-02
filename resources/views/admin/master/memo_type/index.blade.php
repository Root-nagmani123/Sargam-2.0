@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Memo Type Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('master.memo.type.master.create') }}" class="btn btn-primary">+ Add Memo Type</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="zero_config" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Memo Type Name</th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($memoTypes as $index => $memo)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $memo->memo_type_name }}</td>
                                        <td>
                                            @if($memo->memo_doc_upload)
                                                <a href="{{ asset('storage/' . $memo->memo_doc_upload) }}" target="_blank">View</a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                         <td>
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch"
                                                    data-table="memo_type_master"
                                                    data-column="active_inactive"
                                                    data-id="{{ $memo->pk }}"
                                                    {{ $memo->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('master.memo.type.master.edit', ['id' => encrypt($memo->pk)]) }}"
                                                class="btn btn-primary btn-sm">Edit</a>
                                            <form action="{{ route('master.memo.type.master.delete', ['id' => encrypt($memo->pk)]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this memo type?')) {
                                                        this.closest('form').submit();
                                                    }" {{ $memo->active_inactive == 1 ? 'disabled' : '' }}>
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
@endsection
