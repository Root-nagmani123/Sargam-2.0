@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('content')
<div class="container-fluid">

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Memo Conclusion Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">

                        <!-- Add Group Mapping -->
                        <a href="{{ route('master.memo.conclusion.master.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Memo Conclusion
                        </a>

                        <!-- Search Expand -->
                        <div class="search-expand d-flex align-items-center">
                            <a href="javascript:void(0)" id="searchToggle">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 24px;">search</i>
                            </a>

                            <input type="text" class="form-control search-input ms-2" id="searchInput"
                                placeholder="Search…" aria-label="Search">
                        </div>

                    </div>
                </div>
            </div>
            <hr>

            <div class="table-responsive">
                <table class="table" style="border-radius: 10px; overflow: hidden; width: 100%;">
                    <thead style="background-color: #af2910;">
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
                            <td>{{ $conclusions->firstItem() + $index }}</td>
                            <td>{{ $conclusion->discussion_name }}</td>
                            <td>{{ $conclusion->pt_discusion }}</td>
                            <td>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="memo_conclusion_master" data-column="active_inactive"
                                        data-id="{{ $conclusion->pk }}"
                                        {{ $conclusion->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('master.memo.conclusion.master.edit', encrypt($conclusion->pk)) }}"><i
                                        class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">edit</i></a>

                                <form
                                    action="{{ route('master.memo.conclusion.master.delete', encrypt($conclusion->pk)) }}"
                                    method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <a href="javascript:void(0)" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this memo conclusion?')) {
                                                    this.closest('form').submit();
                                                }" {{ $conclusion->active_inactive == 1 ? 'disabled' : '' }}>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">delete</i>
                                    </a>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <!-- <tr><td colspan="6" class="text-center">No records found</td></tr> -->
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                    <div class="text-muted small mb-2">
                        Showing {{ $conclusions->firstItem() }}
                        to {{ $conclusions->lastItem() }}
                        of {{ $conclusions->total() }} items
                    </div>

                    <div>
                        {{ $conclusions->links('vendor.pagination.custom') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection