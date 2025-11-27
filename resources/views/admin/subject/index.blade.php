@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card dataTables_wrapper" id="alt_pagination_wrapper" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-semibold text-dark mb-0">Subject</h4>

                <div class="d-flex align-items-center gap-2">

                    <!-- Add New Button -->
                    <a href="{{ route('subject.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i>
                        Add Subject
                    </a>

                    <!-- Search Box + Icon -->
                    <div class="position-relative">

                        <!-- Hidden Search Input -->
                        <form action="{{ route('subject.index') }}" method="GET" class="search-box d-none"
                            id="searchBox">
                            <input type="text" name="search" class="form-control" placeholder="Search..."
                                style="width: 220px;">
                        </form>

                        <!-- Search Icon Button -->
                        <a href="javascript:void(0)" id="searchToggleBtn" style="padding: 7px 10px;">
                            <i class="material-icons material-symbols-rounded" style="font-size: 22px;">search</i>
                        </a>

                    </div>

                </div>
            </div>
            <hr>
            <div class="table-responsive">

                <table class="table table-bordered text-nowrap">
                    <thead style="background-color: #af2910;">
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Major Subject Name</th>
                            <th class="col">Short Name</th>
                            <th class="col">Action</th>
                            <th class="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $key => $subject)
                        <tr>
                            <td>{{ $subjects->firstItem() + $key }}</td>
                            <td>{{ $subject->subject_name }}</td>
                            <td>{{ $subject->sub_short_name }}</td>
                            <td>
                                <div class="d-flex justify-content-start align-items-start gap-2">
                                    <a href="{{ route('subject.edit', $subject->pk) }}"><i
                                            class="material-icons material-symbols-rounded"
                                            style="font-size: 22px;">edit</i></a>
                                    <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                        class="m-0 delete-form" data-status="{{ $subject->active_inactive }}">
                                        @csrf
                                        @method('DELETE')
                                        <a href="javascript:void(0)" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this subject?')) {
                                                    this.closest('form').submit();
                                                }" {{ $subject->active_inactive == 1 ? 'disabled' : '' }}>
                                            <i class="material-icons material-symbols-rounded"
                                                style="font-size: 22px;">delete</i>
                                        </a>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="subject_master" data-column="active_inactive"
                                        data-id="{{ $subject->pk }}"
                                        {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
            </div>
            
            <!-- Bootstrap 5 Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $subjects->firstItem() ?? 0 }} to {{ $subjects->lastItem() ?? 0 }} of {{ $subjects->total() }} entries
                </div>
                <div>
                    {{ $subjects->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
<script>
document.getElementById("searchToggleBtn").addEventListener("click", function() {
    const box = document.getElementById("searchBox");
    box.classList.toggle("d-none");

    if (!box.classList.contains("d-none")) {
        box.querySelector("input").focus();
    }
});
</script>
@endsection