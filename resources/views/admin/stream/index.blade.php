@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('setup_content')
<style>
 .delete-btn{
    border:none;
    background:none;
    padding:4px;
    cursor:pointer;
    color:#0d6efd;
}

.delete-btn:hover{
    color:red;
}
    </style>

<div class="container-fluid stream-index">
    <x-breadcrum title="Stream" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Stream</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('stream.create')}}" class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Stream
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">

                        <table class="table text-nowrap w-100">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Stream Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($streams as $key => $stream)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        {{ $stream->stream_name }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="stream_master" data-column="status"
                                                data-id="{{ $stream->pk }}" {{ $stream->status == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>

                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Stream actions">

                                            <!-- Edit -->
                                            <a href="{{ route('stream.edit', $stream->pk) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit stream">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">edit</span>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($stream->status == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active stream">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">delete</span>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this stream?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete stream">
                                                    <span class="material-symbols-rounded fs-6"
                                                        aria-hidden="true">delete</span>
                                                    <span class="d-none d-md-inline">Delete</span>
                                                </button>
                                            </form>
                                            @endif

                                        </div>


                                    </td>


                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $streams->firstItem() }}
                                to {{ $streams->lastItem() }}
                                of {{ $streams->total() }} items
                            </div>

                            <div>
                                {{ $streams->links('vendor.pagination.custom') }}
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<script>
//window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
window.statusToggleUrl = "{{ route('admin.stream.toggleStatus') }}";
window.streamStoreUrl = "{{ route('stream.store') }}";
</script>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    (function() {
        'use strict';
        let streamFieldCounter = 1;
        const modalEl = document.getElementById('streamModal');
        const form = document.getElementById('streamForm');
        const formMethod = document.getElementById('streamFormMethod');
        const addSection = document.getElementById('streamAddFields');
        const editSection = document.getElementById('streamEditField');
        const streamNameEdit = document.getElementById('stream_name_edit');
        const modalTitle = document.getElementById('streamModalTitle');
        const submitText = document.getElementById('streamFormSubmitText');
        const streamFieldsContainer = document.getElementById('stream_fields');

        window.addStreamField = function() {
            streamFieldCounter++;
            const row = document.createElement('div');
            row.className = 'stream-field-row row g-2 align-items-end mb-3';
            row.setAttribute('role', 'listitem');
            const inputId = 'stream_name_' + streamFieldCounter;
            row.innerHTML = '<div class="col-12 col-md-11">' +
                '<label for="' + inputId + '" class="form-label fw-medium visually-hidden">Stream name ' + streamFieldCounter + '</label>' +
                '<input type="text" id="' + inputId + '" name="stream_name[]" class="form-control form-control-lg" ' +
                'placeholder="Enter stream name (e.g. Science, Arts, Commerce)" required autocomplete="organization">' +
                '</div><div class="col-12 col-md-1 d-flex justify-content-md-start justify-content-end">' +
                '<a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-danger" onclick="removeStreamField(this)" aria-label="Remove stream">' +
                '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span></a></div>';
            streamFieldsContainer.appendChild(row);
            row.querySelector('input').focus();
        };

        window.removeStreamField = function(btn) {
            const list = document.getElementById('stream_fields');
            const rows = list.querySelectorAll('.stream-field-row');
            if (rows.length > 1) {
                btn.closest('.stream-field-row').remove();
            }
        };

        function resetAddFields() {
            streamFieldCounter = 1;
            const rows = streamFieldsContainer.querySelectorAll('.stream-field-row');
            rows.forEach(function(r, i) { if (i > 0) r.remove(); });
            const first = streamFieldsContainer.querySelector('.stream-field-row');
            if (first) {
                const inp = first.querySelector('input');
                if (inp) { inp.value = ''; inp.id = 'stream_name_0'; }
                const addBtn = first.querySelector('a');
                if (addBtn && !addBtn.onclick) addBtn.setAttribute('onclick', 'addStreamField(this)');
            }
        }

        function openAddModal() {
            modalTitle.textContent = 'Add Stream';
            submitText.textContent = 'Save Streams';
            form.action = window.streamStoreUrl;
            formMethod.value = 'POST';
            formMethod.removeAttribute('name');
            addSection.classList.remove('d-none');
            editSection.classList.add('d-none');
            streamNameEdit.disabled = true;
            addSection.querySelectorAll('input').forEach(function(i) { i.disabled = false; });
            resetAddFields();
        }

        function openEditModal(id, name, url) {
            modalTitle.textContent = 'Edit Stream';
            submitText.textContent = 'Update';
            form.action = url;
            formMethod.value = 'PUT';
            formMethod.setAttribute('name', '_method');
            addSection.classList.add('d-none');
            editSection.classList.remove('d-none');
            addSection.querySelectorAll('input').forEach(function(i) { i.disabled = true; });
            streamNameEdit.disabled = false;
            streamNameEdit.value = name || '';
            streamNameEdit.classList.remove('is-invalid');
        }

        modalEl.addEventListener('show.bs.modal', function(e) {
            const trigger = e.relatedTarget;
            if (trigger && trigger.dataset.mode === 'add') {
                openAddModal();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            streamNameEdit.classList.remove('is-invalid');
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.open-stream-modal');
            if (btn) {
                e.preventDefault();
                openEditModal(btn.dataset.id, btn.dataset.name, btn.dataset.url);
                new bootstrap.Modal(modalEl).show();
            }
        });

        form.addEventListener('submit', function(e) {
            addSection.querySelectorAll('.is-invalid').forEach(function(i) { i.classList.remove('is-invalid'); });
            streamNameEdit.classList.remove('is-invalid');
            if (editSection.classList.contains('d-none')) {
                const inputs = addSection.querySelectorAll('input[name="stream_name[]"]');
                const filled = Array.from(inputs).filter(function(i) { return i.value.trim(); });
                if (filled.length === 0) {
                    e.preventDefault();
                    const first = streamFieldsContainer.querySelector('input');
                    if (first) { first.classList.add('is-invalid'); first.focus(); }
                }
            } else {
                if (!streamNameEdit.value.trim()) {
                    e.preventDefault();
                    streamNameEdit.classList.add('is-invalid');
                    streamNameEdit.focus();
                }
            }
        });
    })();
    </script>



<script>
document.addEventListener('change', function(e){

    const toggle = e.target.closest('.status-toggle');
    if(!toggle) return;

    fetch(window.statusToggleUrl,{
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type':'application/json'
        },
        body:JSON.stringify({
            id: toggle.dataset.id
        })
    })
    .then(r=>r.json())
    .then(res=>{

        if(res.success){
            $('#stream-table').DataTable().ajax.reload(null,false);
        } else {
            toggle.checked = !toggle.checked;
            alert('Status update failed');
        }

    })
    .catch(()=>{
        toggle.checked = !toggle.checked;
        alert('Server error');
    });

});
</script>

<script>
document.addEventListener('click', function(e){

    const btn = e.target.closest('.delete-stream');
    if(!btn) return;

    e.preventDefault();

    if(!confirm('Are you sure you want to delete this stream?')) return;

    fetch(btn.dataset.url,{
        method:'POST',
        headers:{
            'X-CSRF-TOKEN': btn.dataset.token,
            'Content-Type':'application/json'
        },
        body: JSON.stringify({
            _method: 'DELETE'
        })
    })
    .then(r => r.json())
    .then(res => {

        if(res.success){
            // reload datatable only
            $('#stream-table').DataTable().ajax.reload(null,false);
        }else{
            alert(res.message ?? 'Delete failed');
        }

    })
    .catch(()=>{
        alert('Server error');
    });

});
</script>


@endpush
