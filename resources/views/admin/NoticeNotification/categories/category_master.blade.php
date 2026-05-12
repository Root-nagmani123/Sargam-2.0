@extends('admin.layouts.master')

@section('title', 'Notice category master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Notice category master" />
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase">Master</span>
                <h4 class="card-title mb-0">Notice category master</h4>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.notice.subcategory-master.index') }}" class="btn btn-outline-primary btn-sm">Subcategory master</a>
                <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary btn-sm">Notices</a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
                    <span class="material-symbols-rounded align-middle me-1">add</span>
                    Add category
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="bg-light rounded-3 p-3 mb-4">
                <form method="GET" action="{{ route('admin.notice.category-master.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                            <a href="{{ route('admin.notice.category-master.index') }}" class="btn btn-outline-secondary btn-sm">Reset filter</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">S.N.</th>
                            <th scope="col">Name</th>
                            <th scope="col" style="width: 90px;">Sort</th>
                            <th scope="col" style="width: 120px;">Subcategories</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                        @php
                            $canDelete = !$cat->notices_exists && ($cat->sub_categories_count ?? 0) === 0;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $cat->name }}</td>
                            <td>{{ $cat->sort_order }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">{{ $cat->sub_categories_count }}</span>
                                <a href="{{ route('admin.notice.subcategory-master.index', ['notice_category_master_pk' => $cat->pk]) }}" class="small ms-1">List</a>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-flex align-items-center justify-content-center">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notice_category_master" data-column="active_inactive"
                                        data-id="{{ $cat->pk }}" {{ $cat->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-transparent border-0 p-0" title="Edit" aria-label="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEditCategory"
                                        data-cat="{{ json_encode(['pk' => $cat->pk, 'name' => $cat->name, 'sort_order' => (int) $cat->sort_order, 'active_inactive' => (int) $cat->active_inactive]) }}">
                                        <span class="material-symbols-rounded fs-5">edit</span>
                                    </button>
                                    @if($cat->active_inactive == 0 && $canDelete)
                                    <form action="{{ route('admin.notice.category-master.destroy', $cat->pk) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-transparent border-0 p-0" title="Delete" aria-label="Delete">
                                            <span class="material-symbols-rounded fs-5">delete</span>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-transparent border-0 p-0" disabled
                                        title="{{ $cat->active_inactive == 1 ? 'Deactivate before delete' : 'Cannot delete (in use or has subcategories)' }}">
                                        <span class="material-symbols-rounded fs-5">block</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">No categories found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} items
                </div>
                <div>{{ $categories->links('vendor.pagination.custom') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-labelledby="modalAddCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.notice.category-master.store') }}">
                @csrf
                <input type="hidden" name="_form" value="add_category">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddCategoryLabel">Add category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'add_category')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('_form') === 'add_category' ? old('name') : '' }}" required maxlength="255" placeholder="e.g. Office Orders">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('_form') === 'add_category' ? old('sort_order', 0) : 0 }}" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-labelledby="modalEditCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditCategory" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_category">
                <input type="hidden" name="edit_pk" id="edit_category_edit_pk" value="{{ old('_form') === 'edit_category' ? old('edit_pk') : '' }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditCategoryLabel">Edit category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'edit_category')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_category_name" class="form-control" required maxlength="255"
                            value="{{ old('_form') === 'edit_category' ? old('name') : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="edit_category_sort" class="form-control" min="0"
                            value="{{ old('_form') === 'edit_category' ? old('sort_order', 0) : '' }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Status</label>
                        <select name="active_inactive" id="edit_category_active" class="form-select">
                            <option value="1" @if(old('_form') !== 'edit_category' || (string) old('active_inactive', '1') === '1') selected @endif>Active</option>
                            <option value="0" @if(old('_form') === 'edit_category' && (string) old('active_inactive') === '0') selected @endif>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var baseUpdate = @json(rtrim(url('admin/notice/category-master'), '/'));

    var modalEdit = document.getElementById('modalEditCategory');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn || !btn.getAttribute('data-cat')) return;
            try {
                var d = JSON.parse(btn.getAttribute('data-cat'));
                document.getElementById('formEditCategory').action = baseUpdate + '/' + encodeURIComponent(d.pk);
                document.getElementById('edit_category_edit_pk').value = d.pk;
                document.getElementById('edit_category_name').value = d.name || '';
                document.getElementById('edit_category_sort').value = d.sort_order != null ? d.sort_order : 0;
                document.getElementById('edit_category_active').value = String(d.active_inactive != null ? d.active_inactive : 1);
            } catch (e) {}
        });
    }

    @if($errors->any() && old('_form') === 'add_category')
    document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('modalAddCategory');
        if (m && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(m).show();
        }
    });
    @endif

    @if($errors->any() && old('_form') === 'edit_category' && old('edit_pk'))
    document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('modalEditCategory');
        if (!m || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
        document.getElementById('formEditCategory').action = baseUpdate + '/' + encodeURIComponent(@json(old('edit_pk')));
        document.getElementById('edit_category_edit_pk').value = @json(old('edit_pk'));
        document.getElementById('edit_category_name').value = @json(old('name', ''));
        document.getElementById('edit_category_sort').value = @json(old('sort_order', 0));
        document.getElementById('edit_category_active').value = String(@json((int) old('active_inactive', 1)));
        bootstrap.Modal.getOrCreateInstance(m).show();
    });
    @endif
})();
</script>
@endsection
