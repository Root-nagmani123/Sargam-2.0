<form action="{{ route('master.caste.category.store') }}" method="POST" id="casteCategoryForm">
    @csrf
    @if(!empty($casteCategory)) 
        <input type="hidden" name="pk" value="{{ encrypt($casteCategory->pk) }}">
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <x-input
                    name="Seat_name"
                    label="Category/Caste name :" 
                    placeholder="Enter category/caste name" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('Seat_name', $casteCategory->Seat_name ?? '') }}"
                    />
            </div>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <x-input
                    name="Seat_name_hindi"
                    label="Category/Caste name (Hindi) :" 
                    placeholder="Enter category/caste name in Hindi" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('Seat_name_hindi', $casteCategory->Seat_name_hindi ?? '') }}"
                    />
            </div>
        </div>
    </div>
</form>
