@extends('admin.layouts.master')

@section('title', $house->name.' House OTs')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum :title="$house->name.' House OTs'" :showBack="true" />

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', [
            'route' => 'admin.athletics.houses.members.export', 'params' => ['house' => $house->id], 'prefix' => 'amMember',
        ])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amMemberTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amMemberTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">OT Name</th>
                                <th scope="col">User Name</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \App\Http\Controllers\Admin\Athletics\AthleticsController::courseLabel($member->course) }}</td>
                                    <td>{{ \App\Http\Controllers\Admin\Athletics\AthleticsController::otLabel($member->student) }}</td>
                                    <td>{{ $member->student->user_name ?? '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('admin.athletics.houses.members.remove', [$house, $member]) }}"
                                              class="am-act-form am-confirm-form d-inline-flex" data-danger="1"
                                              data-title="Remove from this house?" data-confirm="Yes, Remove"
                                              data-text="{{ $member->student->display_name ?? 'This OT' }} will be removed from {{ $house->name }}.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="am-act am-act--remove">
                                                <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">person_remove</span></span>
                                                <span class="am-act__label text-wrap">Remove from this house</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amMemberTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amMemberTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amMemberTable',
        key: 'amMemberGrid:hidden:v1',
        serial: 0,
        noSort: [0, 4],
        exportKeys: ['sno', 'course', 'ot', 'user_name', ''],
        links: ['amMemberCsv', 'amMemberExcel', 'amMemberPdf', 'amMemberPrint'],
        colvisGrid: '#amMemberTableColvisGrid'
    });
});
</script>
@endpush
