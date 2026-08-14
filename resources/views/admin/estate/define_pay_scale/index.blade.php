@extends('admin.layouts.master')

@section('title', 'Define Pay Scale - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Define Pay Scale" />

    <x-session_message />

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Pay Scale</h1>
                    <p class="text-muted small mb-0">Manage eligibility mapping (salary grade, unit type and unit sub type) from estate_eligibility_mapping.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-pay-scale.create') }}" class="btn btn-primary"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            {{-- Toolbar (new-design-index-page.md §2): search right-aligned; this
                 grid has no filters, so the row carries the search alone. --}}
            <x-programme-dt-toolbar :action="url()->current()"
                placeholder="Search pay scale" label="Search by pay scale, unit type or sub type" />

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" data-sargam-dt-ui="false">
                    <thead>
                        <tr>
                            <th>S.NO.</th>
                            <th>Pay Scale / Salary Grade</th>
                            <th>Unit Type</th>
                            <th>Unit Sub Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td>{{ $items->firstItem() + $index }}</td>
                            <td>{{ $row->salaryGrade ? $row->salaryGrade->display_label_text : '-' }}</td>
                            <td>{{ $row->unitType ? $row->unitType->name : '-' }}</td>
                            <td>{{ $row->unitSubType ? $row->unitSubType->name : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.estate.define-pay-scale.edit', $row->pk) }}" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No eligibility mapping found. <a href="{{ route('admin.estate.define-pay-scale.create') }}">Add one</a> to define pay scale mapping.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-programme-dt-footer :paginator="$items" per-page-id="payScalePerPage" />
        </div>
    </div>
</div>
@endsection
