@extends('admin.layouts.master')

@section('title', 'Edit Meal Rate - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Meal Rate Master" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-header">
            <h5 class="mb-0">
                <iconify-icon icon="solar:clipboard-list-bold" class="me-2"></iconify-icon>
                Edit Meal Rate
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.mess.meal-rate-master.update', $rate->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="meal_type" class="form-label">Meal Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('meal_type') is-invalid @enderror" id="meal_type" name="meal_type" required>
                                @foreach(\App\Models\Mess\MealRateMaster::mealTypes() as $value => $label)
                                    <option value="{{ $value }}" {{ old('meal_type', $rate->meal_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('meal_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_type" class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_type') is-invalid @enderror" id="category_type" name="category_type" required>
                                @foreach(\App\Models\Mess\MealRateMaster::categoryTypes() as $value => $label)
                                    <option value="{{ $value }}" {{ old('category_type', $rate->category_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Govrt, OT, Faculty, Alumni</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('rate') is-invalid @enderror"
                                   id="rate" name="rate" value="{{ old('rate', $rate->rate) }}"
                                   step="0.01" min="0" placeholder="0.00" required>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3 pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $rate->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                            <small class="text-muted">Inactive rates will not be used in billing</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.mess.meal-rate-master.index') }}" class="btn btn-secondary">
                        <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                        Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                        Update Meal Rate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
