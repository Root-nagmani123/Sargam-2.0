@extends('admin.layouts.master')

@section('title', 'Category Item Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mess-master-admin.css') }}?v={{ @filemtime(public_path('css/mess-master-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    $categoryTypes = \App\Models\Mess\ItemCategory::categoryTypes();
    $selectedCategoryType = $categoryTypeFilter ?? request('category_type', '');
    $canDeleteItemCategory = hasRole('Admin') || hasRole('Mess-Admin') || hasRole('Mess Admin') || hasRole('mess admin');
    $isItemCategoryActive = static function ($itemcategory) {
        return ($itemcategory->status ?? 'active') === 'active';
    };
    $openCreateModal = request('open') === 'create' || ($errors->any() && old('_method') !== 'PUT');
    $openEditModal = request('open') === 'edit' || ($errors->any() && old('_method') === 'PUT');
@endphp
<div class="container-fluid mess-master-page py-4">
    <x-breadcrum title="Category Item Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal" data-bs-target="#createItemCategoryModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Category Item</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mess-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('admin.mess.itemcategories.index') }}" class="mess-filter-bar row g-2 align-items-end">
                <div class="col-auto">
                    <label for="filter_category_type" class="form-label mb-1 small fw-semibold">Category type</label>
                    <select name="category_type" id="filter_category_type" class="form-select form-select-sm" style="min-width: 180px;">
                        <option value="">All</option>
                        @foreach($categoryTypes as $value => $label)
                            <option value="{{ $value }}" {{ (string) $selectedCategoryType === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if($selectedCategoryType !== '')
                        <a href="{{ route('admin.mess.itemcategories.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>

            <div class="programme-dt-toolbar mess-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 mb-4">
                <div id="icDtSearch" class="programme-dt-search" data-dt-search-for="itemCategoriesTable"></div>
                <div id="messColManagerMount-itemCategoriesTable" class="flex-shrink-0"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive mess-dt-scroll">
                    <table id="itemCategoriesTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Category Name</th>
                                <th scope="col">Category Type</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemcategories as $index => $itemcategory)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $index + 1 }}</td>
                                <td><span class="mess-row-title">{{ $itemcategory->category_name }}</span></td>
                                <td class="text-capitalize">
                                    {{ $categoryTypes[$itemcategory->category_type ?? 'raw_material'] ?? ucfirst(str_replace('_', ' ', $itemcategory->category_type ?? '')) }}
                                </td>
                                <td class="text-start">{{ $itemcategory->description ?? '-' }}</td>
                                <td>
                                    <span class="badge rounded-pill programme-status-badge mess-status-badge programme-status-badge--{{ $isItemCategoryActive($itemcategory) ? 'active' : 'inactive' }}">
                                        {{ $isItemCategoryActive($itemcategory) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @include('components.mess-master-action-cell', [
                                        'entityLabel' => 'category item',
                                        'recordId' => $itemcategory->id,
                                        'isActive' => $isItemCategoryActive($itemcategory),
                                        'canDelete' => $canDeleteItemCategory,
                                        'destroyUrl' => route('admin.mess.itemcategories.destroy', $itemcategory->id),
                                        'toggleTable' => 'mess_item_categories',
                                        'editClass' => 'btn-edit-itemcategory',
                                        'editAttributes' => [
                                            'data-id' => $itemcategory->id,
                                            'data-category-name' => e($itemcategory->category_name),
                                            'data-category-type' => e($itemcategory->category_type ?? 'raw_material'),
                                            'data-description' => e($itemcategory->description ?? ''),
                                            'data-status' => e($itemcategory->status ?? 'active'),
                                        ],
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="mess-empty-state text-center">
                                    <i class="bi bi-tags display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Category Items Found</h5>
                                    <p class="text-secondary mb-0">Add a category item to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="icDtFooter"
                    class="programme-dt-footer mess-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="itemCategoriesTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createItemCategoryModal" tabindex="-1" aria-labelledby="createItemCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form method="POST" action="{{ route('admin.mess.itemcategories.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="createItemCategoryModalLabel">Add Category Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control" required value="{{ old('category_name') }}">
                            @error('category_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('category_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Category Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editItemCategoryModal" tabindex="-1" aria-labelledby="editItemCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mess-master-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4 mess-modal-form">
            <form id="editItemCategoryForm" method="POST" action="{{ $openEditModal && old('item_category_modal_id') ? route('admin.mess.itemcategories.update', old('item_category_modal_id')) : '' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="item_category_modal_id" id="edit_item_category_modal_id" value="{{ old('item_category_modal_id', $editItemCategory?->id ?? '') }}">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold mb-0" id="editItemCategoryModalLabel">Edit Category Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control" required
                                value="{{ old('category_name', $editItemCategory->category_name ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" id="edit_category_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Category Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3">{{ old('description', $editItemCategory->description ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active" {{ old('status', $editItemCategory->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $editItemCategory->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.mess-master-datatables', [
    'tableId' => 'itemCategoriesTable',
    'searchPlaceholder' => 'Search',
    'orderColumn' => 1,
    'actionColumnIndex' => 5,
    'infoLabel' => 'category items',
    'pageLength' => 10,
])

@push('scripts')
<script src="{{ asset('js/mess-master-list.js') }}?v={{ @filemtime(public_path('js/mess-master-list.js')) ?: time() }}"></script>
<script>
(function () {
    var tableSelector = '#itemCategoriesTable';
    var canDelete = @json($canDeleteItemCategory);
    var destroyBaseUrl = @json(url('admin/mess/itemcategories'));

    function initPage() {
        var ML = window.MessMasterList;
        if (!ML) return;

        ML.moveModalsToBody(['createItemCategoryModal', 'editItemCategoryModal']);
        ML.wireModalExclusivity([{ create: 'createItemCategoryModal', edit: 'editItemCategoryModal' }]);
        ML.bindMessStatusToggle(tableSelector, {
            entityLabel: 'category item',
            canDelete: canDelete,
            destroyBaseUrl: destroyBaseUrl
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit-itemcategory');
            if (!btn || !btn.closest('#itemCategoriesTable')) return;
            e.preventDefault();
            e.stopPropagation();
            ML.hideMessModal('createItemCategoryModal');
            var id = btn.getAttribute('data-id');
            document.getElementById('editItemCategoryForm').action = destroyBaseUrl + '/' + id;
            document.getElementById('edit_item_category_modal_id').value = id;
            document.getElementById('edit_category_name').value = btn.getAttribute('data-category-name') || '';
            document.getElementById('edit_category_type').value = btn.getAttribute('data-category-type') || '';
            document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
            document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
            ML.showMessModal('editItemCategoryModal');
        });

        @if($openCreateModal)
        ML.showMessModal('createItemCategoryModal');
        @endif
        @if($openEditModal)
        (function () {
            var editId = document.getElementById('edit_item_category_modal_id');
            if (editId && editId.value) {
                document.getElementById('editItemCategoryForm').action = destroyBaseUrl + '/' + editId.value;
                @if($editItemCategory)
                document.getElementById('edit_category_type').value = @json($editItemCategory->category_type ?? 'raw_material');
                @endif
            }
            ML.showMessModal('editItemCategoryModal');
        })();
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPage);
    } else {
        initPage();
    }
})();
</script>
@endpush
@endsection
