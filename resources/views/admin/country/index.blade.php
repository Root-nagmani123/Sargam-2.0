@extends('admin.layouts.master')

@section('title', 'Country List')

@section('setup_content')
<div class="container-fluid country-page">
    <x-breadcrum title="Country List" />

    {{-- Primary action above the card, per new-design-index-page.md §1. There are
         no status pills on this grid, so the row is right-aligned buttons alone. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('master.country.create') }}" class="btn btn-primary px-3 py-2 rounded-1">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Country
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2): nothing to filter on this grid, so search alone, right-aligned. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <x-programme-dt-search :action="route('master.country.index')"
                        placeholder="Search country" label="Search by country name">
                        <input type="hidden" name="per_page" value="{{ request('per_page', '10') }}">
                    </x-programme-dt-search>
                </div>
            </div>

            <div class="programme-dt-panel">
            <div class="table-responsive">
                {{-- data-sargam-dt-ui="false": Laravel paginates this grid and the
                     footer below is hand-written, so the global enhancer must not
                     claim it (§5 "Opting out"). --}}
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                    data-sargam-dt-ui="false">
                    <thead>
                        <tr>
                            <th class="col">#</th>
                            <th class="col">Country Name</th>
                            <th class="col">Status</th>
                            <th class="col">Actions</th>

                        </tr>
                    </thead>
                    <tbody>
                        @forelse($countries as $index => $country)
                        <tr>
                            {{-- firstItem() keeps the numbering running across pages; $index alone restarts at 1 on page 2. --}}
                            <td>{{ $countries->firstItem() + $index }}</td>
                            <td>{{ $country->country_name }}</td>

                            <td>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="country_master" data-column="active_inactive"
                                        data-id="{{ $country->pk }}"
                                        {{ $country->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">

                                <div class="d-inline-flex align-items-center gap-2" role="group"
                                    aria-label="Country actions">

                                    <!-- Edit -->
                                    <a href="{{ route('master.country.edit', $country->pk) }}"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                        aria-label="Edit country">
                                        <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                                        <span class="d-none d-md-inline">Edit</span>
                                    </a>

                                    <!-- Delete -->
                                    @if($country->active_inactive == 1)
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                        disabled aria-disabled="true" title="Cannot delete active country">
                                        <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                                        <span class="d-none d-md-inline">Delete</span>
                                    </button>
                                    @else
                                    <form action="{{ route('master.country.delete', $country->pk) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                            aria-label="Delete country">
                                            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                                            <span class="d-none d-md-inline">Delete</span>
                                        </button>
                                    </form>
                                    @endif

                                </div>


                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                @if (request('search') !== null && request('search') !== '')
                                    No country matches “{{ request('search') }}”.
                                    <a href="{{ route('master.country.index') }}">Clear the search</a>.
                                @else
                                    No countries have been added yet.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer variant B (§4B): pagination left, "Showing [N] of M items" right. --}}
            <x-programme-dt-footer :paginator="$countries" per-page-id="countryPerPage" />
            </div>

        </div>
    </div>
</div>
@endsection