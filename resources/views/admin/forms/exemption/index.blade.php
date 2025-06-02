@extends('admin.layouts.master')

@section('title', 'Exemption Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Exemption Master</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Exemption Master
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="datatables">
        <!-- start Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h4>Exemptions</h4>
                        </div>
                        <div class="col-6 text-end">
                            <a href="{{ route('admin.fc_exemption.create') }}" class="btn btn-primary">+ Add
                                Exemption</a>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <div class="table-responsive">
                            <table id="zero_config"
                            class="table table-striped table-bordered align-middle dataTable nowrap"
                            aria-describedby="zero_config_info">
                            <thead>
                                <tr>
                                    <th class="col">S.No</th>
                                    <th class="col">Exemption Name</th>
                                    <th class="col">Short Name</th>
                                    <th class="col">Created Date</th>
                                    <th class="col">Created By</th>
                                    <th class="col">Modified By</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($exemptions as $index => $exemption)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $exemption->Exemption_name }}</td>
                                    <td>{{ $exemption->Exemption_short_name }}</td>
                                    <td>{{ $exemption->Created_date }}</td>
                                    <td>{{ $exemption->createdByUser->name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->modifiedByUser->name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.fc_exemption.edit', $exemption->Pk) }}"
                                                class="btn btn-sm btn-info">
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.fc_exemption.destroy', $exemption->Pk) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this exemption?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>

                                    </td>
                                    <td>
                                        <!-- Toggle Button for visible -->
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="fc_exemption_master" data-column="visible"
                                                data-id="{{ $exemption->Pk }}"
                                                {{ $exemption->visible == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No exemptions found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- end Table -->
    </div>
</div>

@endsection