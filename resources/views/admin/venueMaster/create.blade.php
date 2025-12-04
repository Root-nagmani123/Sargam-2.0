@extends('admin.layouts.master')

@section('title', 'Create Venue Master - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Add Venue-Master</h4>
            <hr>
            <form action="{{ route('Venue-Master.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venue_name" class="form-label">Venue Name</label>
                            <input type="text" class="form-control @error('venue_name') is-invalid @enderror"
                                id="venue_name" name="venue_name" value="{{ old('venue_name') }}" required>
                            @error('venue_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="venue_short_name" class="form-label">Short Name</label>
                            <input type="text" class="form-control @error('venue_short_name') is-invalid @enderror"
                                id="venue_short_name" name="venue_short_name" value="{{ old('venue_short_name') }}" required>
                            @error('venue_short_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 text-end gap-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('Venue-Master.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection