@extends('admin.layouts.master')

@section('title', 'Number Configuration - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Number Configuration" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6"><h4>Auto Numbering Configuration</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.number-configs.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Configuration
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <table class="table w-100 text-nowrap">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Config Type</th>
                            <th>Prefix</th>
                            <th>Current Number</th>
                            <th>Padding</th>
                            <th>Next Number Preview</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($configs as $key => $config)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ strtoupper($config->config_type) }}</strong></td>
                            <td>{{ $config->prefix }}</td>
                            <td>{{ $config->current_number }}</td>
                            <td>{{ $config->padding }} digits</td>
                            <td><code>{{ $config->prefix }}{{ str_pad($config->current_number + 1, $config->padding, '0', STR_PAD_LEFT) }}</code></td>
                            <td>
                                <a href="{{ route('admin.mess.number-configs.edit', $config->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No configurations found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
