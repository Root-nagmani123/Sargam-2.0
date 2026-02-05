@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    .switch {
  position: relative;
  display: inline-block;
  width: 46px;
  height: 22px;
}

.switch input {display:none;}

.slider {
  position: absolute;
  cursor: pointer;
  background-color: #ccc;
  border-radius: 34px;
  top: 0; left: 0; right: 0; bottom: 0;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 2px;
  bottom: 2px;
  background: white;
  border-radius: 50%;
  transition: .4s;
}

input:checked + .slider {
  background-color: #4caf50;
}

input:checked + .slider:before {
  transform: translateX(24px);
}

</style>
<div class="container-fluid roles-index">
<x-breadcrum title="Roles"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row roles-header-row">
                        <div class="col-6">
                            <h4>Roles</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+ Add Roles</a>
                            </div>
                        </div>
                    </div>

                    <hr>
                    {{ $dataTable->table(['class' => 'table']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
    {{ $dataTable->scripts() }}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
document.addEventListener("DOMContentLoaded", function () {

    $(document).on("submit", "form.delete-role-form", function(e) {
        e.preventDefault(); // stop default form submit

        let form = this;
        let row = $(this).closest("tr");

        // Get status toggle inside the same row
        let toggle = row.find(".status-toggle");
        let isActive = toggle.is(":checked");

        // 🔴 If ROLE is ACTIVE → Stop delete
        if (isActive) {

            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete!',
                text: 'Active role cannot be deleted. Please deactivate the role first.',
                confirmButtonColor: '#d33',
            });

            return false;
        }

        // 🟡 Ask Confirmation Before Delete
        Swal.fire({
            title: "Are you sure?",
            text: "This role will be permanently deleted!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // now submit form
            }
        });

    });

});
</script>

@endpush