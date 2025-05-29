@extends('admin.layouts.master')

@section('title', isset($conclusion) ? 'Edit Memo Conclusion' : 'Add Memo Conclusion')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Memo Conclusion Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ isset($conclusion) ? 'Edit' : 'Add' }} Memo Conclusion
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.memo.conclusion.master.store') }}">
                @csrf
                @if(isset($conclusion))
                    <input type="hidden" name="id" value="{{ encrypt($conclusion->pk) }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="discussion_name" class="form-label">Discussion Name <span>*</span></label>
                            <input type="text" name="discussion_name" class="form-control"
                                   value="{{ old('discussion_name', $conclusion->discussion_name ?? '') }}" required>
                            @error('discussion_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="pt_discusion" class="form-label">PT Discussion</label>
                            <input type="number" name="pt_discusion" class="form-control"
                                   value="{{ old('pt_discusion', $conclusion->pt_discusion ?? '') }}">
                            @error('pt_discusion')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span>*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ (old('active_inactive', $conclusion->active_inactive ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ (old('active_inactive', $conclusion->active_inactive ?? 1) == 2) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">{{ isset($conclusion) ? 'Update' : 'Submit' }}</button>
                    <a href="{{ route('master.memo.conclusion.master.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
