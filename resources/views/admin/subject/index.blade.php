@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')
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


                </div>
            </div>
            <hr>
            <div class="table-responsive">

                <table class="table">
                    <thead>
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Major Subject Name</th>
                            <th class="col">Short Name</th>
                            <th class="col">Status</th>
                            <th class="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $key => $subject)
                        <tr>
                            <td>{{ $subjects->firstItem() + $key }}</td>
                            <td>{{ $subject->subject_name }}</td>
                            <td>{{ $subject->sub_short_name }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="subject_master" data-column="active_inactive"
                                        data-id="{{ $subject->pk }}"
                                        {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="material-icons material-symbols-rounded" style="font-size: 22px;">more_horiz</i>
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

            {{-- Edit --}}
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="{{ route('subject.edit', $subject->pk) }}">
                    <i class="material-icons material-symbols-rounded" style="font-size: 20px;">edit</i>
                    Edit
                </a>
            </li>

            {{-- Delete --}}
            @if($subject->active_inactive == 1)
                <li>
                    <span class="dropdown-item text-muted d-flex align-items-center gap-2"
                          style="cursor: not-allowed;">
                        <i class="material-icons material-symbols-rounded" style="font-size: 20px;">delete</i>
                        Cannot delete (active)
                    </span>
                </li>
            @else
                <li>
                    <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this subject?');">
                        @csrf
                        @method('DELETE')
                        <button class="dropdown-item text-danger d-flex align-items-center gap-2" type="submit">
                            <i class="material-icons material-symbols-rounded" style="font-size: 20px;">delete</i>
                            Delete
                        </button>
                    </form>
                </li>
            @endif

        </ul>
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
@endsection