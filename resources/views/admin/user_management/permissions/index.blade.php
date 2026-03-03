@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid permissions-index">
<x-breadcrum title="Permissions"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row permissions-header-row">
                        <div class="col-6">
                            <h4>Permissions</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">+ Add
                                    Permissions</a>
                            </div>
                        </div>
                    </div>
                    <!-- Vertically centered modal -->

                    <hr>
                    <div class="table-responsive">
                        <table id="permissions-table" class="table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Permission Name</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if($permissions->count() > 0)
                                @foreach($permissions as $index => $permission)
                                <tr>
                                    <td>{{ $permissions->firstItem() + $index }}</td>
                                    <td>{{ $permission->name }}</td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('admin.permissions.edit', $permission->id) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" class="m-0">
                                                <input type="hidden" name="_token"
                                                    value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                    type="hidden" name="_method" value="DELETE"> <button type="submit"
                                                    class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="news" data-column="status" data-id="21" checked="">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="4" class="text-center">No permissions found</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <p class="text-muted small mb-0">
                                Showing {{ $permissions->firstItem() ?? 0 }} to {{ $permissions->lastItem() ?? 0 }} of {{ $permissions->total() }} entries
                            </p>
                        </div>
                        <div>
                            {{ $permissions->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
    <div class="card card-body my-4">
  <!-- Home -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="home">
    <label class="form-check-label fw-bold" for="home">
      <i class="bi bi-plus-square text-success"></i> Home <em>(My Home)</em>
    </label>
  </div>

  <!-- Setup -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="setup">
    <label class="form-check-label fw-bold" for="setup">
      <i class="bi bi-plus-square text-success"></i> Setup <em>(admin)</em>
    </label>
  </div>

  <!-- OT Management -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="otManagement">
    <label class="form-check-label fw-bold" for="otManagement">
      <i class="bi bi-plus-square text-success"></i> OT Management <em>(OT Management)</em>
    </label>
  </div>

  <!-- Communications -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="communications">
    <label class="form-check-label fw-bold" for="communications">
      <i class="bi bi-plus-square text-success"></i> Communications <em>(Students- Teachers - Admin)</em>
    </label>
  </div>

  <!-- Academics Accordion -->
  <div class="accordion my-3" id="academicsAccordion">
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingAcademics">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAcademics">
          <i class="bi bi-dash-square text-warning me-2"></i> Academics <em>(Academic Management)</em>
        </button>
      </h2>
      <div id="collapseAcademics" class="accordion-collapse collapse show">
        <div class="accordion-body ps-4">

          <!-- OT Group Management -->
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="otGroup">
            <label class="form-check-label fw-semibold" for="otGroup">
              <i class="bi bi-plus-square text-success"></i> OT Group Management <em>(OT Code Generation And Group)</em>
            </label>
          </div>

          <!-- Academic Setup -->
          <div class="accordion" id="academicSetupAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingAcademicSetup">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAcademicSetup">
                  <i class="bi bi-dash-square text-success me-2"></i> Academic <em>(Academic Setup)</em>
                </button>
              </h2>
              <div id="collapseAcademicSetup" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <!-- Repeatable rows -->
                  <div class="row mb-2">
                    <div class="col-md-6">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="defineActivities">
                        <label class="form-check-label" for="defineActivities">
                          Define Academic Activities <em>(Define Academic Activities)</em>
                        </label>
                      </div>
                    </div>
                    <div class="col-md-6 d-flex gap-3">
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="add1"><label class="form-check-label" for="add1">Add</label></div>
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="edit1"><label class="form-check-label" for="edit1">Edit</label></div>
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="delete1"><label class="form-check-label" for="delete1">Delete</label></div>
                    </div>
                  </div>

                  <!-- Copy and paste similar blocks for other permissions -->
                  <!-- Example: Course Academic Planner -->
                  <div class="row mb-2">
                    <div class="col-md-6">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="academicPlanner">
                        <label class="form-check-label" for="academicPlanner">
                          Course Academic Planner <em>(Academic Year Planner)</em>
                        </label>
                      </div>
                    </div>
                    <div class="col-md-6 d-flex gap-3">
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="add2"><label class="form-check-label" for="add2">Add</label></div>
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="edit2"><label class="form-check-label" for="edit2">Edit</label></div>
                      <div class="form-check"><input type="checkbox" class="form-check-input" id="delete2"><label class="form-check-label" for="delete2">Delete</label></div>
                    </div>
                  </div>

                  <!-- Add more rows as per your list in the image -->

                </div>
              </div>
            </div>
          </div> <!-- End Academic Setup Accordion -->

        </div>
      </div>
    </div>
  </div>
</div>
</div>


@endsection