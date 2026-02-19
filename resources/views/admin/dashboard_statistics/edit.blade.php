@extends('admin.layouts.master')

@section('title', 'Edit Dashboard Statistics - Sargam | LBSNAA')

@section('content')
@php
    $itemsByType = $snapshot->items->groupBy('chart_type');
    $getItems = function($type) use ($itemsByType) {
        return $itemsByType->get($type, collect())->sortBy('sort_order')->values();
    };
@endphp
<div class="container-fluid">
    <x-breadcrum title="Edit snapshot" />

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="border-left: 4px solid #004a93;">
        <div class="card-header bg-transparent border-0 py-3">
            <h2 class="h5 mb-0 fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i>
                Edit snapshot — {{ $snapshot->snapshot_date->format('d M Y') }}{{ $snapshot->title ? ' (' . $snapshot->title . ')' : '' }}
            </h2>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.dashboard-statistics.update', $snapshot) }}" method="POST" id="dashboard-statistics-form">
                @csrf
                @method('PATCH')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="snapshot_date" class="form-label">Snapshot Date <span class="text-danger">*</span></label>
                        <input type="date" name="snapshot_date" id="snapshot_date" value="{{ old('snapshot_date', $snapshot->snapshot_date->format('Y-m-d')) }}"
                            class="form-control form-control-lg @error('snapshot_date') is-invalid @enderror" required>
                        @error('snapshot_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="title" class="form-label">Title (optional)</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $snapshot->title) }}" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default', $snapshot->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">Use as default for charts</label>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-fill mb-3" id="chartTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">Social Groups</button></li>
                    <li class="nav-item"><button class="nav-link" id="gender-tab" data-bs-toggle="tab" data-bs-target="#gender" type="button" role="tab">Gender</button></li>
                    <li class="nav-item"><button class="nav-link" id="age-tab" data-bs-toggle="tab" data-bs-target="#age" type="button" role="tab">Age</button></li>
                    <li class="nav-item"><button class="nav-link" id="stream-tab" data-bs-toggle="tab" data-bs-target="#stream" type="button" role="tab">Stream</button></li>
                    <li class="nav-item"><button class="nav-link" id="cadre-tab" data-bs-toggle="tab" data-bs-target="#cadre" type="button" role="tab">Cadre</button></li>
                    <li class="nav-item"><button class="nav-link" id="domicile-tab" data-bs-toggle="tab" data-bs-target="#domicile" type="button" role="tab">Domicile</button></li>
                </ul>

                <div class="tab-content" id="chartTabsContent">
                    <div class="tab-pane fade show active" id="social" role="tabpanel">
                        <p class="text-muted small">Distribution by social group (Female / Male counts).</p>
                        <div id="social_rows" class="mb-3">
                            @foreach($getItems('social_groups')->isEmpty() ? [['label'=>'General','female_count'=>0,'male_count'=>0],['label'=>'OBC','female_count'=>0,'male_count'=>0],['label'=>'SC','female_count'=>0,'male_count'=>0],['label'=>'ST','female_count'=>0,'male_count'=>0]] : $getItems('social_groups') as $i => $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="social_groups">
                                <div class="col-md-4">
                                    <input type="text" name="social_groups[label][]" class="form-control" placeholder="Category" value="{{ is_array($item) ? ($item['label'] ?? '') : $item->label }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="social_groups[female_count][]" class="form-control" placeholder="Female" min="0" value="{{ is_array($item) ? ($item['female_count'] ?? 0) : $item->female_count }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="social_groups[male_count][]" class="form-control" placeholder="Male" min="0" value="{{ is_array($item) ? ($item['male_count'] ?? 0) : $item->male_count }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_social_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>

                    <div class="tab-pane fade" id="gender" role="tabpanel">
                        <p class="text-muted small">Gender distribution (percentage).</p>
                        <div id="gender_rows">
                            @foreach($getItems('gender')->isEmpty() ? [['label'=>'Female','value'=>43.4],['label'=>'Male','value'=>56.6]] : $getItems('gender') as $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="gender">
                                <div class="col-md-6">
                                    <input type="text" name="gender[label][]" class="form-control" placeholder="Label" value="{{ is_array($item) ? ($item['label'] ?? '') : $item->label }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.1" name="gender[value][]" class="form-control" placeholder="%" min="0" max="100" value="{{ is_array($item) ? ($item['value'] ?? 0) : $item->value }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_gender_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>

                    <div class="tab-pane fade" id="age" role="tabpanel">
                        <p class="text-muted small">Age group distribution (Female / Male counts).</p>
                        <div id="age_rows">
                            @foreach($getItems('age')->isEmpty() ? [['label'=>'18-25','female_count'=>0,'male_count'=>0],['label'=>'26-30','female_count'=>0,'male_count'=>0],['label'=>'31-35','female_count'=>0,'male_count'=>0]] : $getItems('age') as $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="age">
                                <div class="col-md-4">
                                    <input type="text" name="age[label][]" class="form-control" placeholder="Age group" value="{{ is_array($item) ? ($item['label'] ?? '') : $item->label }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="age[female_count][]" class="form-control" placeholder="Female" min="0" value="{{ is_array($item) ? ($item['female_count'] ?? 0) : $item->female_count }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="age[male_count][]" class="form-control" placeholder="Male" min="0" value="{{ is_array($item) ? ($item['male_count'] ?? 0) : $item->male_count }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_age_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>

                    <div class="tab-pane fade" id="stream" role="tabpanel">
                        <p class="text-muted small">Highest stream wise distribution (count per stream).</p>
                        <div id="stream_rows">
                            @foreach($getItems('stream')->isEmpty() ? [] : $getItems('stream') as $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="stream">
                                <div class="col-md-6">
                                    <input type="text" name="stream[label][]" class="form-control" placeholder="Stream" value="{{ $item->label }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="stream[value][]" class="form-control" placeholder="Count" min="0" value="{{ $item->value }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                            @if($getItems('stream')->isEmpty())
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="stream">
                                <div class="col-md-6"><input type="text" name="stream[label][]" class="form-control" placeholder="Stream"></div>
                                <div class="col-md-4"><input type="number" name="stream[value][]" class="form-control" placeholder="Count" min="0" value="0"></div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_stream_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>

                    <div class="tab-pane fade" id="cadre" role="tabpanel">
                        <p class="text-muted small">Cadre wise distribution (Female / Male counts).</p>
                        <div id="cadre_rows">
                            @foreach($getItems('cadre')->isEmpty() ? [] : $getItems('cadre') as $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="cadre">
                                <div class="col-md-4">
                                    <input type="text" name="cadre[label][]" class="form-control" placeholder="Cadre" value="{{ $item->label }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="cadre[female_count][]" class="form-control" placeholder="Female" min="0" value="{{ $item->female_count }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="cadre[male_count][]" class="form-control" placeholder="Male" min="0" value="{{ $item->male_count }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                            @if($getItems('cadre')->isEmpty())
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="cadre">
                                <div class="col-md-4"><input type="text" name="cadre[label][]" class="form-control" placeholder="Cadre"></div>
                                <div class="col-md-3"><input type="number" name="cadre[female_count][]" class="form-control" placeholder="Female" min="0" value="0"></div>
                                <div class="col-md-3"><input type="number" name="cadre[male_count][]" class="form-control" placeholder="Male" min="0" value="0"></div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_cadre_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>

                    <div class="tab-pane fade" id="domicile" role="tabpanel">
                        <p class="text-muted small">Domicile state / UT distribution (count per state).</p>
                        <div id="domicile_rows">
                            @foreach($getItems('domicile') as $item)
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="domicile">
                                <div class="col-md-6">
                                    <input type="text" name="domicile[label][]" class="form-control" placeholder="State / UT" value="{{ $item->label }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="domicile[value][]" class="form-control" placeholder="Count" min="0" value="{{ $item->value }}">
                                </div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endforeach
                            @if($getItems('domicile')->isEmpty())
                            <div class="row g-2 align-items-end mb-2 chart-row" data-chart="domicile">
                                <div class="col-md-6"><input type="text" name="domicile[label][]" class="form-control" placeholder="State / UT"></div>
                                <div class="col-md-4"><input type="number" name="domicile[value][]" class="form-control" placeholder="Count" min="0" value="0"></div>
                                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-row w-100"><i class="bi bi-dash-lg"></i></button></div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_domicile_row"><i class="bi bi-plus-lg me-1"></i> Add row</button>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Snapshot</button>
                    <a href="{{ route('admin.dashboard-statistics.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.dashboard_statistics._form_scripts')
@endsection
