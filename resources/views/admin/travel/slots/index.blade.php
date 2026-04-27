@extends('admin.layouts.master')
@section('title', 'FC Travel — Arrival slots')

@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-clock-history me-2"></i>Arrival time slots</h4>
        <a href="{{ route('admin.travel.index') }}" class="btn btn-sm btn-outline-secondary">Back to travel plans</a>
    </div>
    <p class="text-muted small">Create slots (label + optional time range). <strong>Max</strong> = headcount per slot (0 or empty = no limit). Trainees select these on the travel registration step.</p>
    @if(session('success'))<div class="alert alert-success py-2 small">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white small fw-semibold">Add slot</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.travel.slots.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small mb-0">Label <span class="text-danger">*</span></label>
                    <input type="text" name="slot_label" class="form-control form-control-sm" required placeholder="8AM-9AM" maxlength="100">
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-0">From</label>
                    <input type="time" name="time_start" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-0">To</label>
                    <input type="time" name="time_end" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-0">Max</label>
                    <input type="number" name="max_capacity" class="form-control form-control-sm" min="0" placeholder="0=∞">
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-0">Sort</label>
                    <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
                </div>
                <div class="col-md-2 form-check align-self-end mb-1">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="nAct" checked>
                    <label class="form-check-label small" for="nAct">Active</label>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-column gap-2">
    @forelse($slots as $slot)
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2">
                <form method="POST" action="{{ route('admin.travel.slots.update', $slot) }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Label</label>
                        <input type="text" name="slot_label" class="form-control form-control-sm" value="{{ $slot->slot_label }}" required maxlength="100">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">From</label>
                        <input type="time" name="time_start" class="form-control form-control-sm" value="{{ $slot->time_start ? \Illuminate\Support\Str::substr($slot->time_start, 0, 5) : '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">To</label>
                        <input type="time" name="time_end" class="form-control form-control-sm" value="{{ $slot->time_end ? \Illuminate\Support\Str::substr($slot->time_end, 0, 5) : '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Max</label>
                        <input type="number" name="max_capacity" class="form-control form-control-sm" min="0" value="{{ $slot->max_capacity }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Sort</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $slot->sort_order }}" min="0">
                    </div>
                    <div class="col-md-2 form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="act{{ $slot->id }}" {{ $slot->is_active ? 'checked' : '' }}>
                        <label class="form-check-label small" for="act{{ $slot->id }}">Active</label>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.travel.slots.destroy', $slot) }}" class="mt-1" onsubmit="return confirm('Delete this slot?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted">No slots yet. Add one above.</p>
    @endforelse
    </div>
</div>
@endsection
