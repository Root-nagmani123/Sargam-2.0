@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<div class="container-fluid">
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
                        <table
                            class="table table-bordered text-nowrap align-middle">
                            <thead style="background-color: #af2910;">
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
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('master.exemption.category.master.edit', 
                                                        ['id' => encrypt(value: $cat->pk)]) }}"><i
                                                    class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">edit</i></a>
                                            <div class="delete-icon-container" data-item-id="{{ $cat->pk }}" data-delete-url="{{ route('master.exemption.category.master.delete', ['id' => encrypt($cat->pk)]) }}">
                                                @if($cat->active_inactive == 1)
                                                    <span class="delete-icon-disabled" title="Cannot delete active record">
                                                        <i class="material-icons menu-icon material-symbols-rounded"
                                                            style="font-size: 24px; color: #ccc; cursor: not-allowed;">delete</i>
                                                    </span>
                                                @else
                                                    <form
                                                        title="Delete"
                                                        action="{{ route('master.exemption.category.master.delete', 
                                                                ['id' => encrypt($cat->pk)]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="javascript:void(0)" onclick="event.preventDefault(); 
                                                                if(confirm('Are you sure you want to delete this record?')) {
                                                                    this.closest('form').submit();
                                                                }">
                                                            <i class="material-icons menu-icon material-symbols-rounded"
                                                                style="font-size: 24px;">delete</i>
                                                        </a>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="exemption_category_master" data-column="active_inactive"
                                                data-id="{{ $cat->pk }}"
                                                {{ $cat->active_inactive == 1 ? 'checked' : '' }}>
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
                                Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} entries
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