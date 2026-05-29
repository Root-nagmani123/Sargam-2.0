<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
(function () {
    function initMnmTemplateModals() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $ = jQuery;
        var templateEditData = @json($mnmTemplateEditData ?? []);
        var updateUrlTemplate = @json(route('admin.memo-notice.update', ['pk' => '__PK__']));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        function appendModalToBody($modal) {
            if ($modal.length && !$modal.parent().is('body')) {
                $modal.appendTo('body');
            }
        }

        function uploadPdfForEditor($content) {
            $('<input type="file" accept="application/pdf">')
                .on('change', function (e) {
                    var file = e.target.files[0];
                    if (!file) {
                        return;
                    }
                    var formData = new FormData();
                    formData.append('file', file);
                    $.ajax({
                        url: '/admin/upload-pdf',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        success: function (response) {
                            $content.summernote('insertText', response.url);
                        },
                        error: function () {
                            alert('PDF upload failed. Try again.');
                        }
                    });
                })
                .click();
        }

        function getSummernoteOptions($content, richToolbar) {
            if (richToolbar) {
                return {
                    tabsize: 2,
                    height: 220,
                    placeholder: 'Write here...',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['fontname', ['fontname']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph', 'align']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video', 'pdf']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ],
                    buttons: {
                        pdf: function () {
                            var ui = $.summernote.ui;
                            return ui.button({
                                contents: '<i class="note-icon-file"></i> PDF',
                                tooltip: 'Upload PDF',
                                click: function () {
                                    uploadPdfForEditor($content);
                                }
                            }).render();
                        }
                    }
                };
            }

            return {
                height: 220,
                placeholder: 'Write here...',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'table', 'hr']],
                    ['view', ['codeview', 'help']]
                ]
            };
        }

        function initSummernote($content, richToolbar) {
            if (!$content.length || typeof $.fn.summernote === 'undefined') {
                return;
            }
            if ($content.next('.note-editor').length) {
                return;
            }
            $content.summernote(getSummernoteOptions($content, richToolbar));
        }

        function destroySummernote($content) {
            if ($content.length && $content.next('.note-editor').length && typeof $.fn.summernote !== 'undefined') {
                $content.summernote('destroy');
            }
        }

        function setSummernoteCode($content, html) {
            if ($content.next('.note-editor').length) {
                $content.summernote('code', html || '');
            } else {
                $content.val(html || '');
            }
        }

        function syncSummernoteToTextarea($content) {
            if ($content.next('.note-editor').length) {
                $content.val($content.summernote('code'));
            }
        }

        /* ---- Add modal ---- */
        var $addModal = $('#mnmAddTemplateModal');
        var $addForm = $('#mnmAddTemplateForm');

        if ($addModal.length && $addForm.length && !$addForm.data('mnmTemplateBound')) {
            $addForm.data('mnmTemplateBound', true);
            appendModalToBody($addModal);

            $addModal.on('show.bs.modal', function () {
                appendModalToBody($addModal);
            });

            function resetAddForm() {
                destroySummernote($('#mnm_add_content'));
                if ($addForm[0]) {
                    $addForm[0].reset();
                }
                $('#mnm_template_course_master_pk option[disabled]').prop('selected', true);
                $('#mnm_template_memo_notice_type option[disabled]').prop('selected', true);
            }

            $addModal.on('shown.bs.modal', function () {
                initSummernote($('#mnm_add_content'), false);
                var oldContent = @json(old('template_form') === 'add' ? old('content', '') : '');
                if (oldContent) {
                    setSummernoteCode($('#mnm_add_content'), oldContent);
                }
            });

            $addModal.on('hidden.bs.modal', resetAddForm);

            $addForm.on('submit', function () {
                syncSummernoteToTextarea($('#mnm_add_content'));
            });

            var shouldOpenAdd = @json(
                old('template_form') !== 'edit' && (
                    request()->get('open_add_template') === '1' ||
                    (old('template_form') === 'add' && $errors->any())
                )
            );
            if (shouldOpenAdd) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('mnmAddTemplateModal')).show();
                if (new URLSearchParams(window.location.search).get('open_add_template') === '1' && window.history.replaceState) {
                    var addUrl = new URL(window.location.href);
                    addUrl.searchParams.delete('open_add_template');
                    window.history.replaceState({}, '', addUrl);
                }
            }
        }

        /* ---- Edit modal ---- */
        var $editModal = $('#mnmEditTemplateModal');
        var $editForm = $('#mnmEditTemplateForm');

        if ($editModal.length && $editForm.length && !$editForm.data('mnmTemplateBound')) {
            $editForm.data('mnmTemplateBound', true);
            appendModalToBody($editModal);

            $editModal.on('show.bs.modal', function () {
                appendModalToBody($editModal);
            });

            function populateEditForm(data, pk) {
                $editForm.attr('action', updateUrlTemplate.replace('__PK__', pk));
                $('#mnm_edit_template_pk_hidden').val(pk);
                $('#mnm_edit_course_master_pk').val(data.course_master_pk || '');
                $('#mnm_edit_memo_notice_type').val(data.memo_notice_type || '');
                $('#mnm_edit_title').val(data.title || '');
                $('#mnm_edit_director').val(data.director || '');
                $('#mnm_edit_designation').val(data.designation || '');
                $('#mnm_edit_content').val(data.content || '');
            }

            function openEditModal(pk, dataOverride) {
                var pkStr = String(pk);
                var data = dataOverride || templateEditData[pkStr] || templateEditData[pk];
                if (!data) {
                    return;
                }
                populateEditForm(data, pkStr);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('mnmEditTemplateModal')).show();
            }

            window.openMnmEditTemplateModal = openEditModal;

            $editModal.on('shown.bs.modal', function () {
                initSummernote($('#mnm_edit_content'), true);
                var content = $('#mnm_edit_content').val();
                if (content) {
                    setSummernoteCode($('#mnm_edit_content'), content);
                }
            });

            $editModal.on('hidden.bs.modal', function () {
                destroySummernote($('#mnm_edit_content'));
            });

            $editForm.on('submit', function () {
                syncSummernoteToTextarea($('#mnm_edit_content'));
            });

            $(document).on('click', '.mnm-edit-template-btn', function (e) {
                e.preventDefault();
                var pk = $(this).data('id');
                openEditModal(pk);
            });

            var shouldOpenEdit = @json(old('template_form') === 'edit' && $errors->any());
            if (shouldOpenEdit) {
                openEditModal(@json(old('mnm_edit_template_pk')), {
                    course_master_pk: @json(old('course_master_pk')),
                    memo_notice_type: @json(old('memo_notice_type')),
                    title: @json(old('title')),
                    director: @json(old('director')),
                    designation: @json(old('designation')),
                    content: @json(old('content'))
                });
            } else {
                var openEditPk = new URLSearchParams(window.location.search).get('open_edit_template');
                if (openEditPk) {
                    openEditModal(openEditPk);
                    if (window.history.replaceState) {
                        var editUrl = new URL(window.location.href);
                        editUrl.searchParams.delete('open_edit_template');
                        window.history.replaceState({}, '', editUrl);
                    }
                }
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMnmTemplateModals);
    } else {
        initMnmTemplateModals();
    }
})();
</script>
