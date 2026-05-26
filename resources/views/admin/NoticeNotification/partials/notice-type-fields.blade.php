@php
    $noticeModel = $notice ?? null;
    $selectedCategoryPk = old(
        'notice_category_master_pk',
        optional($noticeModel)->notice_category_master_pk
    );
    $selectedSubcategoryPk = old(
        'notice_subcategory_master_pk',
        optional($noticeModel)->notice_subcategory_master_pk
    );
    if (! $selectedCategoryPk && ! empty(optional($noticeModel)->notice_type)) {
        $match = $categories->firstWhere('name', $noticeModel->notice_type);
        $selectedCategoryPk = $match->pk ?? null;
    }
@endphp

<div class="col-6" id="noticeTitleCol">
    <label class="form-label notice-form-label" for="notice_title">Notice Title <span class="text-danger">*</span></label>
    <input type="text" name="notice_title" id="notice_title" class="form-control" placeholder="eg. Notice 01"
        value="{{ old('notice_title', optional($noticeModel)->notice_title) }}" required>
</div>
<div class="col-6" id="noticeTypeCol">
    <label class="form-label notice-form-label" for="noticeCategory">Notice Type <span class="text-danger">*</span></label>
    <select name="notice_category_master_pk" id="noticeCategory" class="form-select" required>
        <option value="">Select the notice type</option>
        @foreach($categories as $category)
        <option value="{{ $category->pk }}" {{ (string) $selectedCategoryPk === (string) $category->pk ? 'selected' : '' }}>
            {{ $category->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="col-4 d-none" id="noticeSubTypeBox">
    <label class="form-label notice-form-label" for="noticeSubcategory">Notice Sub Type <span class="text-danger" id="noticeSubTypeRequired">*</span></label>
    <select name="notice_subcategory_master_pk" id="noticeSubcategory" class="form-select">
        <option value="">Select the sub type</option>
    </select>
</div>
