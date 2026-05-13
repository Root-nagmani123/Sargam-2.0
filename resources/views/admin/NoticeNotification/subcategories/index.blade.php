@extends('admin.layouts.master')

@section('title', 'Notice subcategory master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Notice subcategory master" />
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase">Master</span>
                <h4 class="card-title mb-0">Notice subcategory master</h4>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.notice.category-master.index') }}" class="btn btn-outline-primary btn-sm">Category master</a>
                <a href="{{ route('admin.notice.feed') }}" class="btn btn-outline-secondary btn-sm">All notices</a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddSubcategory">
                    <span class="material-symbols-rounded align-middle me-1">add</span>
                    Add subcategory
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="bg-light rounded-3 p-3 mb-4">
                <form method="GET" action="{{ route('admin.notice.subcategory-master.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="filter_category_pk" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($categoriesActive as $c)
                                <option value="{{ $c->pk }}" @selected((string) ($categoryFilter ?? '') === (string) $c->pk)>{{ $c->name }}</option>
                                @endforeach
                                @if($filterCategoryExtra)
                                <option value="{{ $filterCategoryExtra->pk }}" @selected((string) ($categoryFilter ?? '') === (string) $filterCategoryExtra->pk)>{{ $filterCategoryExtra->name }} (inactive)</option>
                                @endif
                            </select>
                        </div>
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
                            <a href="{{ route('admin.notice.subcategory-master.index') }}" class="btn btn-outline-secondary btn-sm">Reset filters</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">S.N.</th>
                            <th scope="col">Category</th>
                            <th scope="col">Subcategory</th>
                            <th scope="col" style="width: 90px;">Sort</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subcategories as $sub)
                        @php
                            $inUse = $usedSubcategoryIds->contains($sub->pk);
                            $canDelete = !$inUse;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $subcategories->firstItem() + $loop->index }}</td>
                            <td>{{ $sub->category->name ?? '—' }}</td>
                            <td class="fw-semibold">{{ $sub->name }}</td>
                            <td>{{ $sub->sort_order }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-flex align-items-center justify-content-center">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notice_subcategory_master" data-column="active_inactive"
                                        data-id="{{ $sub->pk }}" {{ $sub->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-transparent border-0 p-0" title="Edit" aria-label="Edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEditSubcategory"
                                        data-sub="{{ json_encode(['pk' => $sub->pk, 'notice_category_master_pk' => (int) $sub->notice_category_master_pk, 'name' => $sub->name, 'sort_order' => (int) $sub->sort_order, 'active_inactive' => (int) $sub->active_inactive]) }}">
                                        <span class="material-symbols-rounded fs-5">edit</span>
                                    </button>
                                    @if($sub->active_inactive == 0 && $canDelete)
                                    <form action="{{ route('admin.notice.subcategory-master.destroy', $sub->pk) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this subcategory?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-transparent border-0 p-0" title="Delete" aria-label="Delete">
                                            <span class="material-symbols-rounded fs-5">delete</span>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-transparent border-0 p-0" disabled
                                        title="{{ $sub->active_inactive == 1 ? 'Deactivate before delete' : 'Cannot delete (in use)' }}">
                                        <span class="material-symbols-rounded fs-5">block</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">No subcategories found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $subcategories->firstItem() ?? 0 }} to {{ $subcategories->lastItem() ?? 0 }} of {{ $subcategories->total() }} items
                </div>
                <div>{{ $subcategories->links('vendor.pagination.custom') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddSubcategory" tabindex="-1" aria-labelledby="modalAddSubcategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.notice.subcategory-master.store') }}">
                @csrf
                <input type="hidden" name="_form" value="add_subcategory">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddSubcategoryLabel">Add subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'add_subcategory')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    @php
                        $addSelectedCategory = old('_form') === 'add_subcategory' ? old('notice_category_master_pk') : $categoryFilter;
                        if ($addSelectedCategory !== null && $addSelectedCategory !== '' && !$categoriesActive->contains(fn ($c) => (string) $c->pk === (string) $addSelectedCategory)) {
                            $addSelectedCategory = '';
                        }
                    @endphp
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="notice_category_master_pk" class="form-select" required>
                            <option value="">Select category</option>
                            @foreach($categoriesActive as $c)
                            <option value="{{ $c->pk }}" @selected($addSelectedCategory !== null && $addSelectedCategory !== '' && (string) $addSelectedCategory === (string) $c->pk)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subcategory name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('_form') === 'add_subcategory' ? old('name') : '' }}" required maxlength="255" placeholder="e.g. Circular">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('_form') === 'add_subcategory' ? old('sort_order', 0) : 0 }}" min="0">
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

<div class="modal fade" id="modalEditSubcategory" tabindex="-1" aria-labelledby="modalEditSubcategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditSubcategory" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_subcategory">
                <input type="hidden" name="edit_pk" id="edit_sub_edit_pk" value="{{ old('_form') === 'edit_subcategory' ? old('edit_pk') : '' }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditSubcategoryLabel">Edit subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && old('_form') === 'edit_subcategory')
                    <div class="alert alert-danger small mb-3">
                        <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="notice_category_master_pk" id="edit_sub_category_pk" class="form-select" required>
                            @foreach($categoriesForEdit as $c)
                            <option value="{{ $c->pk }}">{{ $c->name }}@if((int) $c->active_inactive !== 1) (inactive)@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subcategory name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_sub_name" class="form-control" required maxlength="255"
                            value="{{ old('_form') === 'edit_subcategory' ? old('name') : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="edit_sub_sort" class="form-control" min="0"
                            value="{{ old('_form') === 'edit_subcategory' ? old('sort_order', 0) : '' }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Status</label>
                        <select name="active_inactive" id="edit_sub_active" class="form-select">
                            <option value="1" @selected(old('_form') !== 'edit_subcategory' || (string) old('active_inactive', '1') === '1')>Active</option>
                            <option value="0" @selected(old('_form') === 'edit_subcategory' && (string) old('active_inactive') === '0')>Inactive</option>
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
    var baseUpdate = @json(rtrim(url('admin/notice/subcategory-master'), '/'));

    var modalEdit = document.getElementById('modalEditSubcategory');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn || !btn.getAttribute('data-sub')) return;
            try {
                var d = JSON.parse(btn.getAttribute('data-sub'));
                document.getElementById('formEditSubcategory').action = baseUpdate + '/' + encodeURIComponent(d.pk);
                document.getElementById('edit_sub_edit_pk').value = d.pk;
                document.getElementById('edit_sub_category_pk').value = String(d.notice_category_master_pk);
                document.getElementById('edit_sub_name').value = d.name || '';
                document.getElementById('edit_sub_sort').value = d.sort_order != null ? d.sort_order : 0;
                document.getElementById('edit_sub_active').value = String(d.active_inactive != null ? d.active_inactive : 1);
            } catch (e) {}
        });
    }

    @if($errors->any() && old('_form') === 'add_subcategory')
    document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('modalAddSubcategory');
        if (m && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(m).show();
        }
    });
    @endif

    @if($errors->any() && old('_form') === 'edit_subcategory' && old('edit_pk'))
    document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('modalEditSubcategory');
        if (!m || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
        document.getElementById('formEditSubcategory').action = baseUpdate + '/' + encodeURIComponent(@json(old('edit_pk')));
        document.getElementById('edit_sub_edit_pk').value = @json(old('edit_pk'));
        document.getElementById('edit_sub_category_pk').value = String(@json(old('notice_category_master_pk')));
        document.getElementById('edit_sub_name').value = @json(old('name', ''));
        document.getElementById('edit_sub_sort').value = @json(old('sort_order', 0));
        document.getElementById('edit_sub_active').value = String(@json((int) old('active_inactive', 1)));
        bootstrap.Modal.getOrCreateInstance(m).show();
    });
    @endif
})();
</script>
@endsection
