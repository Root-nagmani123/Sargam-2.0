@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Exemption categories" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Exemption categories</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.exemption.category.master.create')}}" class="btn btn-primary">+
                                    Add Exemption categories</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Name</th>
                                    <th class="col">Short Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Actions</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($categories) && count($categories) > 0)
                                @foreach ($categories as $cat)
                                <tr class="odd">
                                    <td>{{ $categories->firstItem() + $loop->index }}</td>
                                    <td>{{ $cat->exemp_category_name }}</td>
                                    <td>{{ $cat->exemp_cat_short_name }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="exemption_category_master" data-column="active_inactive"
                                                data-id="{{ $cat->pk }}"
                                                {{ $cat->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                       <div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="Category actions">

    <!-- Edit -->
    <a href="{{ route('master.exemption.category.master.edit', ['id' => encrypt($cat->pk)]) }}"
       class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
       aria-label="Edit category">
        <i class="material-icons material-symbols-rounded"
           style="font-size:18px;"
           aria-hidden="true">edit</i>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    @if($cat->active_inactive == 1)
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                disabled
                aria-disabled="true"
                title="Cannot delete active record">
            <i class="material-icons material-symbols-rounded"
               style="font-size:18px;"
               aria-hidden="true">delete</i>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    @else
        <form action="{{ route('master.exemption.category.master.delete', ['id' => encrypt($cat->pk)]) }}"
              method="POST"
              class="d-inline">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                    aria-label="Delete category"
                    onclick="return confirm('Are you sure you want to delete this record?');">
                <i class="material-icons material-symbols-rounded"
                   style="font-size:18px;"
                   aria-hidden="true">delete</i>
                <span class="d-none d-md-inline">Delete</span>
            </button>
        </form>
    @endif

</div>

                                    </td>

                                </tr>
                                @endforeach
                                @else

                                @endif

                            </tbody>
                        </table>

                        <!-- Bootstrap 5 Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of
                                {{ $categories->total() }} entries
                            </div>
                            <div>
                                {{ $categories->links('pagination::bootstrap-5') }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection