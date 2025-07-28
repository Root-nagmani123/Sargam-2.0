@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Roles" />

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-0">Edit Role</h4>
            <hr>

            <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="title">Name:</label>
                        <input type="text" class="form-control" id="title" name="name" value="{{ $role->name }}">
                        @error('name')
                            <p class="text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <h3>Permissions:</h3>
                    <div class="accordion my-3" id="permissionAccordion">
                        @foreach ($grouped as $groupName => $subGroups)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ Str::slug($groupName) }}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ Str::slug($groupName) }}">
                                        <i class="bi bi-dash-square text-warning me-2"></i>
                                        {{ ucfirst($groupName) }} <em class="ms-2">({{ ucfirst($groupName) }} Management)</em>
                                    </button>
                                </h2>
                                <div id="collapse-{{ Str::slug($groupName) }}" class="accordion-collapse collapse show">
                                    <div class="accordion-body ps-4">
                                        <div class="accordion" id="accordion-{{ Str::slug($groupName) }}">
                                            @foreach ($subGroups as $subGroupName => $items)
                                                <div class="accordion-item mb-2">
                                                    <h2 class="accordion-header" id="heading-{{ Str::slug($subGroupName) }}">
                                                        <button class="accordion-button collapsed" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-{{ Str::slug($groupName . '-' . $subGroupName) }}">
                                                            <i class="bi bi-dash-square text-success me-2"></i>
                                                            {{ ucfirst(str_replace('-', ' ', $subGroupName)) }}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse-{{ Str::slug($groupName . '-' . $subGroupName) }}"
                                                        class="accordion-collapse collapse">
                                                        <div class="accordion-body row">
                                                            @foreach ($items as $perm)
                                                                <div class="col-md-3 mb-2">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            name="permission[{{ $perm->id }}]"
                                                                            value="{{ $perm->id }}"
                                                                            id="perm_{{ $perm->id }}"
                                                                            {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="perm_{{ $perm->id }}">
                                                                            <strong>{{ $perm->display_name }}</strong>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('permission')
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-actions">
                    <div class="card-body border-top">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="{{ route('admin.roles.index') }}" class="btn bg-danger-subtle text-danger ms-6">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
