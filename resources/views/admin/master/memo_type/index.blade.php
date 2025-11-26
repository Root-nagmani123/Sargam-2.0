@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{ route('master.memo.type.master.create') }}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Memo Type
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
                        <table class="table w-100 nowrap" style="border-radius: 10px; overflow: hidden;">
                            <thead style="background-color: #af2910;">
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Memo Type Name</th>
                                    <th class="col">Document</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($memoTypes as $index => $memo)
                                <tr>
                                    <td>{{ $memoTypes->firstItem() + $index }}</td>
                                    <td>{{ $memo->memo_type_name }}</td>
                                    <td>
                                        @if($memo->memo_doc_upload)
                                        <a href="{{ asset('storage/' . $memo->memo_doc_upload) }}"
                                            target="_blank">View</a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                    <td>
                                        <a
                                            href="{{ route('master.memo.type.master.edit', ['id' => encrypt($memo->pk)]) }}"><i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">edit</i></a>
                                        <form
                                            action="{{ route('master.memo.type.master.delete', ['id' => encrypt($memo->pk)]) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this memo type?')) {
                                                        this.closest('form').submit();
                                                    }" {{ $memo->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">delete</i>
                                            </a>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="memo_type_master" data-column="active_inactive"
                                                data-id="{{ $memo->pk }}"
                                                {{ $memo->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $memoTypes->firstItem() }}
                                to {{ $memoTypes->lastItem() }}
                                of {{ $memoTypes->total() }} items
                            </div>

                            <div>
                                {{ $memoTypes->links('vendor.pagination.custom') }}
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('searchToggle');
    const input = document.getElementById('searchInput');

    toggle.addEventListener('click', () => {
        input.classList.toggle('active');
        if (input.classList.contains('active')) {
            input.focus();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-expand')) {
            input.classList.remove('active');
        }
    });
});
</script>

@endsection