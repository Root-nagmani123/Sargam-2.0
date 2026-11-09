@extends('admin.layouts.master')

@section('title', 'Vehicle Categories')
@section('page_title', 'Vehicles Categories')
@section('page_subtitle', 'Manage vehicle Category data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Vehicles Categories"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Vehicles Categories',
        ]"
    >
      <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" onclick="CategoryModal()">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add New Category
      </button>
    </x-breadcrum>
    <x-session_message />

    <div class="card">
      <div class="card-body">
        <div class="programme-dt-toolbar mb-4">
          <div class="section-sub">Manage vehicle category data</div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
              <thead>
                <tr>
                  <th>S. No.</th>
                  <th>Category Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($categories as $category)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $category->name }}</td>
                    <td>
                      <div class="pr-act-group" role="group" aria-label="Row actions">
                        <button type="button" class="pr-act pr-act--edit" onclick="CategoryModal({{ json_encode($category->only('id', 'name')) }})">
                          <span class="pr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                          <span class="pr-act__label">Edit</span>
                        </button>
                        <form action="{{ route('protocol.vehicle-category.destroy', $category->id) }}" method="POST" class="pr-delete-form"
                              onsubmit="return confirm('Are you sure you want to delete this category?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="pr-act pr-act--del">
                            <span class="pr-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                            <span class="pr-act__label">Delete</span>
                          </button>
                        </form>
                      </div>
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
    </div>
  </div>

  <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content protocol-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalLabel">Add Vehicle Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="categoryForm" action="{{ route('protocol.vehicle-category.store') }}" method="POST">
          @csrf
          <input type="hidden" name="category_id" id="category_id">
          <div class="modal-body">
            <div class="pr-field-card">
              <label class="form-label" for="name">Name <span class="required-star">*</span></label>
              <input type="text" class="form-control" name="name" id="name" placeholder="Enter category name" value="{{ old('name') }}" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn pr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn pr-btn-submit" id="SubmitMenuGroupForm">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection


@push('scripts')
    <script>
        function CategoryModal(data = null) {
            // An earlier Edit leaves _method=PATCH behind; drop it so Add posts to store.
            $('#categoryForm input[name="_method"]').remove();
            if (data) {
                $('#category_id').val(data.id);
                $('#name').val(data.name);
                $('#categoryForm').attr('action', '{{ route('protocol.vehicle-category.update',':id') }}'.replace(':id', data.id));
                $('#categoryForm').append('<input type="hidden" name="_method" value="PATCH">');
                $('#categoryModalLabel').text('Edit Vehicle Category');
            } else {
                $('#categoryForm')[0].reset();
                $('#category_id').val('');
                $('#categoryForm').attr('action', '{{ route('protocol.vehicle-category.store') }}');
                $('#categoryModalLabel').text('Add Vehicle Category');
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
