@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            Notice notification List
            <a href="{{ route('admin.notice.create') }}" class="btn btn-success btn-sm float-end">Add Notice</a>
        </div>
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
         
        @if(session('success'))
            <div class="alert alert-success">
               {{ session('success') }}
            </div>
            @endif

        <div id="status-msg"></div>


        <div class="card-body">

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.N.</th>
                        <th>Notice Title</th>
                        <th>Notice Type</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Display Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($notices as $index => $n)
                    @php $encId = Crypt::encrypt($n->pk); @endphp

                    <tr>
                        <td>{{ $index + $notices->firstItem() }}</td>
                        <td>{{ $n->notice_title }}</td>
                        <td>{{ $n->notice_type }}</td>
                        <td>{{ $n->user->first_name }} {{ $n->user->last_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}</td>

                        <td>{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}</td>

                        <td>
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                    data-table="notices_notification" data-column="active_inactive" data-id="{{ $n->pk }}"
                                    {{ $n->active_inactive == 1 ? 'checked' : '' }}>
                            </div>

                        </td>

                        <td>
                            <a href="{{ route('admin.notice.edit',$encId) }}" class="btn btn-primary btn-sm">Edit</a>
                            @if( $n->active_inactive == 0)
                           <form id="deleteForm{{ $encId }}" action="{{ route('admin.notice.destroy',$encId) }}" 
                                    method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="deleteConfirm('{{ $encId }}')">Delete</button>
                                </form>

                            @else
                            <button class="btn btn-danger btn-sm" disabled>Delete</button>

                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                <div class="text-muted small mb-2">
                    Showing {{ $notices->firstItem() ?? 0 }}
                    to {{ $notices->lastItem() }}
                    of {{ $notices->total() }} items
                </div>

                <div>
                    {{ $notices->links('vendor.pagination.custom') }}
                </div>

            </div>

        </div>
    </div>
</div>

@endsection