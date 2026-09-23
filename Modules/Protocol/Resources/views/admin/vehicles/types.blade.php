@extends('admin.layouts.master')

@section('title', 'Vehicle Types')
@section('page_title', 'Vehicles Types')
@section('page_subtitle', 'Manage vehicle Type data')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Vehicles Types"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Vehicles Types',
        ]"
    />
    <x-session_message />
    <div class="card-clean p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="section-title">Vehicles Types</div>
          <div class="section-sub">Manage vehicle Type data</div>
        </div>
        <a href="#" class="btn btn-primary d-flex align-items-center"
            onclick="TypeModal()">
            <i class="material-icons menu-icon material-symbols-rounded"
                style="font-size: 20px; vertical-align: middle;">add</i>
            Add New Type
        </a>
      </div>

        <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>SR NO</th>
                    <th>Type Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
          <tbody>
            @forelse ($types as $type)
              <tr style="cursor:pointer;">
                <td>{{ $loop->iteration }}</td>
                <td class="fw-semibold">{{ $type->name }}</td>
                <td>
                    <a href="javascript:void(0);" class="btn btn-sm btn-primary" onclick="TypeModal({{$type}})">Edit</a>
                    <form action="{{ route('protocol.vehicle-types.destroy', $type->id) }}"
                        method="POST"
                        style="display:inline;">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure you want to delete this type?')">
                            Delete
                        </button>
                    </form>
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
   <div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="typeModalLabel">Add / Edit Vehicle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="typeForm" action="{{ route('protocol.vehicle-types.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type_id" id="type_id">
                        <div class="form-group mb-2">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter type name" value="{{old('name')}}" required>
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
        function TypeModal(data = null) {
            if (data) {
                $('#type_id').val(data.id);
                $('#name').val(data.name);
                $('#typeForm').attr('action', '{{ route('protocol.vehicle-types.update',':id') }}'.replace(':id', data.id));
                $('#typeForm').append('<input type="hidden" name="_method" value="PATCH">');
            } else {    
                $('#typeForm')[0].reset();
                $('#typeForm').attr('action', '{{ route('protocol.vehicle-types.store') }}');
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
