@extends('admin.layouts.master')

@section('title', 'Appellation Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Appellation Master"></x-breadcrum>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Appellation Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <a href="{{ route('master.appellation.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Appellation
                        </a>
                    </div>
                </div>
            </div>
            <hr>

            {!! $dataTable->table(['class' => 'table']) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

    @if(session('success'))
    <script>
        (function() {
            var toastHtml = '<div class="toast align-items-center text-bg-success border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                '<div class="d-flex"><div class="toast-body">{{ session('success') }}</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
            var div = document.createElement('div');
            div.innerHTML = toastHtml;
            document.body.appendChild(div);
            setTimeout(function() { div.remove(); }, 4000);
        })();
    </script>
    @endif

    @if(session('error'))
    <script>
        (function() {
            var toastHtml = '<div class="toast align-items-center text-bg-danger border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
                '<div class="d-flex"><div class="toast-body">{{ session('error') }}</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
            var div = document.createElement('div');
            div.innerHTML = toastHtml;
            document.body.appendChild(div);
            setTimeout(function() { div.remove(); }, 4000);
        })();
    </script>
    @endif
@endpush
