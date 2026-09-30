{{-- COE question papers — Upload (.docx dropzone). Shared by every coe-* grid;
     behaviour is CoeGrid.upload() in public/js/coe-grid.js.
     Params: $title (heading), $field (file input name). --}}
<div class="modal fade" id="qpUploadModal" tabindex="-1" aria-labelledby="qpUploadModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="qpUploadForm" novalidate>
                <div class="modal-header coe-modal-header">
                    <h5 class="modal-title" id="qpUploadModalLabel">{{ $title ?? 'Upload Question Paper' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <p class="coe-modal-meta mb-2" id="qpUploadMeta"></p>

                    {{-- The file input is #qpUploadInput (dropzone id + "Input"). --}}
                    @include('admin.coe.partials.dropzone', [
                        'id' => 'qpUpload',
                        'field' => $field ?? 'question_paper',
                        'accept' => '.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'hint' => 'File type: .docx',
                        'icon' => 'bi-file-earmark-word',
                    ])
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary" id="qpUploadSubmit" disabled>Upload Question Paper</button>
                </div>
            </form>
        </div>
    </div>
</div>
