@extends('admin.layouts.master')

@section('title', 'Notice category master')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
@endpush

@section('content')
<div class="container-fluid notice-module-page">
    <x-breadcrum title="Notice category master" />
    <x-session_message />

    <div class="card notice-card border-0 shadow-sm rounded-4 border-start border-4 border-primary overflow-hidden">
        <div class="card-header notice-list-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary-subtle text-primary fw-semibold text-uppercase px-3 py-2">Master</span>
                <h4 class="card-title mb-0 fw-bold">Notice category master</h4>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.notice.subcategory-master.index') }}" class="btn btn-outline-primary btn-sm rounded-3">
                    <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>Subcategory master
                </a>
                <a href="{{ route('admin.notice.feed') }}" class="btn btn-outline-secondary btn-sm rounded-3">
                    <i class="bi bi-collection me-1" aria-hidden="true"></i>All notices
                </a>
                <button type="button" class="btn btn-notice-save text-white btn-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add category
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="notice-filter-panel rounded-3 p-3 mb-4">
                <form method="GET" action="{{ route('admin.notice.category-master.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold d-block mb-1">&nbsp;</label>
                            <a href="{{ route('admin.notice.category-master.index') }}" class="btn btn-outline-secondary btn-sm">Reset filter</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 notice-category-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3 py-3 text-secondary fw-semibold" style="width: 72px;">S. No.</th>
                            <th scope="col" class="py-3 text-secondary fw-semibold">Notice Category Name</th>
                            <th scope="col" class="py-3 text-secondary fw-semibold" style="width: 180px;">Notice Sub-Categories</th>
                            <th scope="col" class="py-3 text-secondary fw-semibold text-center" style="width: 120px;">Status</th>
                            <th scope="col" class="pe-3 py-3 text-secondary fw-semibold text-center" style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($categories as $cat)
                        @php
                            $canDelete = !$cat->notices_exists && ($cat->sub_categories_count ?? 0) === 0;
                            $subcategoryUrl = route('admin.notice.subcategory-master.index', ['filter_category_pk' => $cat->pk]);
                        @endphp
                        <tr>
                            <td class="ps-3 py-3 text-body-secondary">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="py-3">
                                <a href="{{ $subcategoryUrl }}" class="link-primary link-underline-opacity-0 link-underline-opacity-100-hover fw-medium">
                                    {{ $cat->name }}
                                </a>
                            </td>
                            <td class="py-3 text-body-secondary">{{ $cat->sub_categories_count }}</td>
                            <td class="py-3 text-center">
                                @if($cat->active_inactive == 1)
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-medium">Active</span>
                                @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-medium">Inactive</span>
                                @endif
                            </td>
                            <td class="pe-3 py-3">
                                <div class="d-inline-flex align-items-center justify-content-center gap-2" role="group" aria-label="Category actions">
                                    <a href="{{ $subcategoryUrl }}" class="btn btn-link btn-sm text-primary p-0 border-0" title="View subcategories" aria-label="View subcategories">
                                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">visibility</span>
                                    </a>
                                    <button type="button" class="btn btn-link btn-sm text-primary p-0 border-0" title="Edit" aria-label="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEditCategory"
                                        data-cat="{{ json_encode(['pk' => $cat->pk, 'name' => $cat->name, 'sort_order' => (int) $cat->sort_order, 'active_inactive' => (int) $cat->active_inactive]) }}">
                                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>
                                    </button>
                                    <div class="form-check form-switch d-inline-flex align-items-center justify-content-center mb-0">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="notice_category_master" data-column="active_inactive"
                                            data-id="{{ $cat->pk }}" {{ $cat->active_inactive == 1 ? 'checked' : '' }}
                                            title="Toggle status" aria-label="Toggle status">
                                    </div>
                                    @if($cat->active_inactive == 0 && $canDelete)
                                    <form action="{{ route('admin.notice.category-master.destroy', $cat->pk) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0 border-0" title="Delete" aria-label="Delete">
                                            <span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-link btn-sm text-body-tertiary p-0 border-0" disabled
                                        title="{{ $cat->active_inactive == 1 ? 'Deactivate before delete' : 'Cannot delete (in use or has subcategories)' }}">
                                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-5">No categories found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
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
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.notice.category-master.store') }}">
                @csrf
                <input type="hidden" name="_form" value="add_category">
                <div class="modal-header border-bottom px-4 pt-4 pb-3">
                    <h5 class="modal-title fw-semibold mb-0" id="modalAddCategoryLabel">Add Notice Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if ($errors->any() && old('_form') === 'add_category')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-medium mb-2">Notice Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('_form') === 'add_category' ? old('name') : '' }}" required maxlength="255" placeholder="eg. Office Order">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium mb-2">Sort order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('_form') === 'add_category' ? old('sort_order', 0) : 0 }}" min="0" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Notice Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-labelledby="modalEditCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formEditCategory" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_category">
                <input type="hidden" name="edit_pk" id="edit_category_edit_pk" value="{{ old('_form') === 'edit_category' ? old('edit_pk') : '' }}">
                <div class="modal-header border-bottom px-4 pt-4 pb-3">
                    <h5 class="modal-title fw-semibold mb-0" id="modalEditCategoryLabel">Edit Notice Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    @if ($errors->any() && old('_form') === 'edit_category')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-medium mb-2">Notice Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_category_name" class="form-control" required maxlength="255"
                            value="{{ old('_form') === 'edit_category' ? old('name') : '' }}" placeholder="eg. Office Order">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium mb-2">Sort order</label>
                        <input type="number" name="sort_order" id="edit_category_sort" class="form-control" min="0"
                            value="{{ old('_form') === 'edit_category' ? old('sort_order', 0) : '' }}" placeholder="0">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium mb-2">Status</label>
                        <select name="active_inactive" id="edit_category_active" class="form-select">
                            <option value="1" @if(old('_form') !== 'edit_category' || (string) old('active_inactive', '1') === '1') selected @endif>Active</option>
                            <option value="0" @if(old('_form') === 'edit_category' && (string) old('active_inactive') === '0') selected @endif>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Notice Category</button>
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
