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
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    <div class="row flex-column flex-md-row align-items-stretch align-items-md-center g-2 g-md-3 mb-3">
                        <div class="col-12 col-md-6">
                            <h4 class="mb-0 fw-semibold text-body">Stream</h4>
                        </div>
                        <div class="col-12 col-md-6 d-flex justify-content-start justify-content-md-end">
                            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#streamModal" data-mode="add" aria-label="Add stream">
                                <i class="material-icons material-symbols-rounded fs-5" aria-hidden="true">add</i>
                                Add Stream
                            </button>
                        </div>
                    </div>
                    <hr class="my-3 opacity-25">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table w-100 text-nowrap', 'aria-describedby' => 'stream-table-caption']) !!}
                        <div id="stream-table-caption" class="visually-hidden">Stream list</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

{{-- Add / Edit Stream Modal (Bootstrap 5.3) --}}
<div class="modal fade" id="streamModal" tabindex="-1" aria-labelledby="streamModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background: #004a93;">
                <h5 class="modal-title text-white fw-semibold" id="streamModalLabel">
                    <span id="streamModalTitle">Add Stream</span>
                </h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="streamForm" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="streamFormMethod" value="POST">
                    {{-- Add mode: appendable stream fields --}}
                    <div id="streamAddFields" class="stream-add-fields">
                        <div id="stream_fields" class="stream-fields-list" role="list">
                            <div class="stream-field-row row g-2 align-items-end mb-3" role="listitem">
                                <div class="col-12 mx-auto">
                                    <label for="stream_name_0" class="form-label fw-medium visually-hidden">Stream name 1</label>
                                    <input type="text" id="stream_name_0" name="stream_name[]"
                                        class="form-control form-control-lg" placeholder="Enter stream name (e.g. Science, Arts, Commerce)"
                                        required autocomplete="organization">
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" onclick="addStreamField(this)" aria-label="Add another stream">
                                Add Another Stream
                            </button>
                        </div>
                    </div>
                    {{-- Edit mode: single stream field --}}
                    <div id="streamEditField" class="stream-edit-field d-none">
                        <div class="mb-3">
                            <label for="stream_name_edit" class="form-label fw-medium">Stream Name</label>
                            <input type="text" class="form-control form-control-lg" id="stream_name_edit" name="stream_name"
                                placeholder="Enter stream name (e.g. Science, Arts, Commerce)" required autocomplete="organization" value="">
                            <div id="stream_name_error" class="invalid-feedback"></div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary px-4 btn-sm">
                            <span id="streamFormSubmitText">Save Stream</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
