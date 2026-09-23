@extends('admin.layouts.master')

@section('title', 'Vehicle Categories')
@section('page_title', 'Vehicles Categories')
@section('page_subtitle', 'Manage vehicle Category data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Vehicles Categories"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Vehicles Categories',
        ]"
    />
    <x-session_message />
    <div class="card-clean p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="section-title">Vehicles Categories</div>
          <div class="section-sub">Manage vehicle Category data</div>
        </div>
        <a href="#" class="btn btn-primary d-flex align-items-center"
            onclick="CategoryModal()">
            <i class="material-icons menu-icon material-symbols-rounded"
                style="font-size: 20px; vertical-align: middle;">add</i>
            Add New Category
        </a>
      </div>

        <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>SR NO</th>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
          <tbody>
            @forelse ($categories as $category)
              <tr style="cursor:pointer;">
                <td>{{ $loop->iteration }}</td>
                <td class="fw-semibold">{{ $category->name }}</td>
                <td>
                    <a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="CategoryModal({{$category}})">Edit</a>
                    <form action="{{ route('protocol.vehicle-category.destroy', $category->id) }}"
                        method="POST"
                        style="display:inline;">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure you want to delete this category?')">
                            Delete
                        </button>
                    </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="3"><div class="empty-state"><i class="bi bi-inbox"></i>No categories yet.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
   <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="categoryModalLabel">Add / Edit Vehicle Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="categoryForm" action="{{ route('protocol.vehicle-category.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="category_id" id="category_id">
                        <div class="form-group mb-2">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter category name" value="{{old('name')}}" required>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success d-inline-flex align-items-center"
                                id="SubmitMenuGroupForm"><i
                                    class="material-icons material-symbols-rounded me-2"
                                    style="font-size: 20px;">save</i>Save</button>
                            <button type="button" class="btn btn-secondary d-inline-flex align-items-center"
                                data-bs-dismiss="modal"><i
                                    class="material-icons material-symbols-rounded me-2"
                                    style="font-size: 20px;">cancel</i>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script>
        function CategoryModal(data = null) {
            if (data) {
                $('#category_id').val(data.id);
                $('#name').val(data.name);
                $('#categoryForm').attr('action', '{{ route('protocol.vehicle-category.update',':id') }}'.replace(':id', data.id));
                $('#categoryForm').append('<input type="hidden" name="_method" value="PATCH">');
            } else {    
                $('#categoryForm')[0].reset();
                $('#categoryForm').attr('action', '{{ route('protocol.vehicle-category.store') }}');
            }
            $('#categoryModal').modal('show');
        }

        document.getElementById('categoryForm').addEventListener('submit', function () {
            const btn = document.getElementById('SubmitMenuGroupForm');
            btn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Saving...
            `;
        });
    </script>
@endpush
