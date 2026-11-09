@extends('admin.layouts.master')

@section('title', 'Vehicle Types')
@section('page_title', 'Vehicles Types')
@section('page_subtitle', 'Manage vehicle Type data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Vehicles Types"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Vehicles Types',
        ]"
    >
      <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" onclick="TypeModal()">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add New Type
      </button>
    </x-breadcrum>
    <x-session_message />

    <div class="card">
      <div class="card-body">
        <div class="programme-dt-toolbar mb-4">
          <div class="section-sub">Manage vehicle type data</div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
              <thead>
                <tr>
                  <th>S. No.</th>
                  <th>Type Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($types as $type)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $type->name }}</td>
                    <td>
                      <div class="pr-act-group" role="group" aria-label="Row actions">
                        <button type="button" class="pr-act pr-act--edit" onclick="TypeModal({{ json_encode($type->only('id', 'name')) }})">
                          <span class="pr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                          <span class="pr-act__label">Edit</span>
                        </button>
                        <form action="{{ route('protocol.vehicle-types.destroy', $type->id) }}" method="POST" class="pr-delete-form"
                              onsubmit="return confirm('Are you sure you want to delete this type?')">
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
                  <tr><td colspan="3"><div class="empty-state"><i class="bi bi-inbox"></i>No types yet.</div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content protocol-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="typeModalLabel">Add Vehicle Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="typeForm" action="{{ route('protocol.vehicle-types.store') }}" method="POST">
          @csrf
          <input type="hidden" name="type_id" id="type_id">
          <div class="modal-body">
            <div class="pr-field-card">
              <label class="form-label" for="name">Name <span class="required-star">*</span></label>
              <input type="text" class="form-control" name="name" id="name" placeholder="Enter type name" value="{{ old('name') }}" required>
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
        function TypeModal(data = null) {
            // An earlier Edit leaves _method=PATCH behind; drop it so Add posts to store.
            $('#typeForm input[name="_method"]').remove();
            if (data) {
                $('#type_id').val(data.id);
                $('#name').val(data.name);
                $('#typeForm').attr('action', '{{ route('protocol.vehicle-types.update',':id') }}'.replace(':id', data.id));
                $('#typeForm').append('<input type="hidden" name="_method" value="PATCH">');
                $('#typeModalLabel').text('Edit Vehicle Type');
            } else {
                $('#typeForm')[0].reset();
                $('#type_id').val('');
                $('#typeForm').attr('action', '{{ route('protocol.vehicle-types.store') }}');
                $('#typeModalLabel').text('Add Vehicle Type');
            }
            $('#typeModal').modal('show');
        }

        document.getElementById('typeForm').addEventListener('submit', function () {
            const btn = document.getElementById('SubmitMenuGroupForm');
            btn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Saving...
            `;
        });
    </script>
@endpush
