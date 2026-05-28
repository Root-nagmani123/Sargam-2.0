@extends('admin.layouts.master')

@section('title', 'Word of the Day Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Word of the Day Master" />

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Add New Word</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('master.word.of.day.master.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Hindi Text <span class="text-danger">*</span></label>
                    <input type="text" name="hindi_text" class="form-control" value="{{ old('hindi_text') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">English Meaning</label>
                    <input type="text" name="english_text" class="form-control" value="{{ old('english_text') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort Order</label>
                    <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="active_inactive" class="form-select" required>
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Scheduled Date</label>
                    <input type="date" name="scheduled_date" class="form-control" value="{{ old('scheduled_date') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Word</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Existing Words</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Hindi Text</th>
                            <th>English Meaning</th>
                            <th style="width: 140px;">Order</th>
                            <th style="width: 170px;">Scheduled Date</th>
                            <th style="width: 140px;">Status</th>
                            <th style="width: 240px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($words as $index => $word)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <input form="updateWord{{ $word->id }}" type="text" name="hindi_text" class="form-control" value="{{ $word->hindi_text }}" required>
                                </td>
                                <td>
                                    <input form="updateWord{{ $word->id }}" type="text" name="english_text" class="form-control" value="{{ $word->english_text }}">
                                </td>
                                <td>
                                    <input form="updateWord{{ $word->id }}" type="number" min="0" name="sort_order" class="form-control" value="{{ $word->sort_order ?? 0 }}">
                                </td>
                                <td>
                                    <input form="updateWord{{ $word->id }}" type="date" name="scheduled_date" class="form-control" value="{{ optional($word->scheduled_date)->format('Y-m-d') }}">
                                </td>
                                <td>
                                    <select form="updateWord{{ $word->id }}" name="active_inactive" class="form-select">
                                        <option value="1" {{ (int) $word->active_inactive === 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ (int) $word->active_inactive === 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <form id="updateWord{{ $word->id }}" method="POST" action="{{ route('master.word.of.day.master.update', $word->id) }}" class="d-inline">
                                        @csrf
                                    </form>
                                    <button type="submit" form="updateWord{{ $word->id }}" class="btn btn-sm btn-primary">Update</button>

                                    <form method="POST" action="{{ route('master.word.of.day.master.delete', $word->id) }}" class="d-inline" onsubmit="return confirm('Delete this word?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No words found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

