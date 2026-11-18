@extends('admin.layouts.master')

@section('title', 'Result Entry')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Result Entry" :showBack="false" />

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" aria-label="Course lifecycle">
            @foreach (['active' => 'Active', 'archived' => 'Archived'] as $key => $label)
                <li class="nav-item">
                    <a href="{{ route('admin.athletics.results.index', ['tab' => $key]) }}"
                       class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $tab === $key ? 'active' : '' }}"
                       @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
                </li>
            @endforeach
        </ul>
        <div class="d-flex flex-wrap gap-2">
            @include('admin.athletics.partials.export_actions', ['route' => 'admin.athletics.results.export', 'params' => ['tab' => $tab], 'prefix' => 'amRes'])
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amResTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amResTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course</th>
                                <th scope="col">Drives</th>
                                <th scope="col">Result Entry Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                @php $url = route('admin.athletics.results.course', $course->pk); @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \App\Http\Controllers\Admin\Athletics\AthleticsController::courseLabel($course) }}</td>
                                    <td data-order="{{ $course->drive_count }}"><a class="am-count-link" href="{{ $url }}">{{ $course->drive_count }}</a></td>
                                    <td>@include('admin.athletics.partials.status_tag', ['status' => $course->complete ? 'complete' : 'pending'])</td>
                                    <td class="text-center">
                                        <a class="am-act" href="{{ $url }}">
                                            <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">{{ $course->complete ? 'visibility' : 'add_task' }}</span></span>
                                            <span class="am-act__label">{!! $course->complete ? 'View' : 'Complete<br>Result Entry' !!}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amResTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amResTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amResTable',
        key: 'amResGrid:hidden:v1',
        serial: 0,
        noSort: [0, 4],
        exportKeys: ['sno', 'course', 'drives', 'status', ''],
        links: ['amResCsv', 'amResExcel', 'amResPdf', 'amResPrint'],
        colvisGrid: '#amResTableColvisGrid'
    });
});
</script>
@endpush
