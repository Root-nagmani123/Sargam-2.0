@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Memo Conclusion Master" />
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Conclusion Master</h4>
                        </div>
                        @can('master.memo.conclusion.master.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{ route('master.memo.conclusion.master.create') }}" class="btn btn-primary">+ Add Memo Conclusion</a>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <hr>

                    <table class="table table-bordered table-striped" id="zero_config" style="width: 100%;">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Discussion Name</th>
                                <th class="col">PT Discussion</th>
                                <th class="col">Status</th>
                                <th class="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($conclusions as $index => $conclusion)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $conclusion->discussion_name }}</td>
                                    <td>{{ $conclusion->pt_discusion }}</td>
                                    <td>
                                        @can('master.memo.conclusion.master.active_inactive')
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch"
                                                    data-table="memo_conclusion_master"
                                                    data-column="active_inactive"
                                                    data-id="{{ $conclusion->pk }}"
                                                    {{ $conclusion->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>
                                        @endcan
                                        
                                    </td>
                                    <td>
                                        @can('master.memo.conclusion.master.edit')
                                            <a href="{{ route('master.memo.conclusion.master.edit', encrypt($conclusion->pk)) }}"
                                            class="btn btn-primary btn-sm">Edit</a>
                                        @endcan
                                        @can('master.memo.conclusion.master.delete')
                                            <form action="{{ route('master.memo.conclusion.master.delete', encrypt($conclusion->pk)) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this memo conclusion?')) {
                                                        this.closest('form').submit();
                                                    }"
                                                    {{ $conclusion->active_inactive == 1 ? 'disabled' : '' }}>
                                                    Delete
                                                </button> 
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <!-- <tr><td colspan="6" class="text-center">No records found</td></tr> -->
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
</div>
@endsection
