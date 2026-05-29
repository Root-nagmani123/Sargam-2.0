<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
(function () {
    function initMnmAddTemplateModal() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $ = jQuery;
        var $modal = $('#mnmAddTemplateModal');
        var $form = $('#mnmAddTemplateForm');
        if (!$modal.length || !$form.length || $form.data('mnmTemplateBound')) {
            return;
        }
        $form.data('mnmTemplateBound', true);

        if (!$modal.parent().is('body')) {
            $modal.appendTo('body');
        }

        $modal.on('show.bs.modal', function () {
            if (!$modal.parent().is('body')) {
                $modal.appendTo('body');
            }
        });

        function initSummernote() {
            var $content = $('#content');
            if (!$content.length || typeof $.fn.summernote === 'undefined') {
                return;
            }
            if ($content.next('.note-editor').length) {
                return;
            }
            $content.summernote({
                height: 220,
                placeholder: 'Write here...',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'table', 'hr']],
                    ['view', ['codeview', 'help']]
                ]
            });
        }

        function destroySummernote() {
            var $content = $('#content');
            if ($content.length && $content.next('.note-editor').length && typeof $.fn.summernote !== 'undefined') {
                $content.summernote('destroy');
            }
        }

        function resetTemplateForm() {
            destroySummernote();
            if ($form[0]) {
                $form[0].reset();
            }
            $('#mnm_template_course_master_pk option[disabled]').prop('selected', true);
            $('#mnm_template_memo_notice_type option[disabled]').prop('selected', true);
        }

        $modal.on('shown.bs.modal', function () {
            initSummernote();
            if ($('#content').summernote && $('#content').next('.note-editor').length) {
                var oldContent = @json(old('content', ''));
                if (oldContent) {
                    $('#content').summernote('code', oldContent);
                }
            }
        });

        $modal.on('hidden.bs.modal', resetTemplateForm);

        $form.on('submit', function () {
            if ($('#content').next('.note-editor').length) {
                $('#content').val($('#content').summernote('code'));
            }
        });

        var shouldOpen = @json(
            request()->get('open_add_template') === '1' ||
            $errors->any() ||
            old('title') ||
            old('memo_notice_type')
        );
        if (shouldOpen) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mnmAddTemplateModal')).show();
            if (new URLSearchParams(window.location.search).get('open_add_template') === '1' && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('open_add_template');
                window.history.replaceState({}, '', url);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMnmAddTemplateModal);
    } else {
        initMnmAddTemplateModal();
    }
})();
</script>
