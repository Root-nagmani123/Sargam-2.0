<script>
(function () {
    function initSmSubjectModals() {
        if (typeof bootstrap === 'undefined') {
            return;
        }
        var subjectEditData = @json($smSubjectEditData ?? []);
        var updateUrlTemplate = @json(route('subject.update', ['subject' => '__PK__']));

        function appendModalToBody(modalEl) {
            if (modalEl && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        }

        /* ---- Add modal ---- */
        var addModalEl = document.getElementById('smAddSubjectModal');
        var addFormEl = document.getElementById('smAddSubjectForm');

        if (addModalEl && addFormEl && !addFormEl.dataset.smSubjectBound) {
            addFormEl.dataset.smSubjectBound = '1';
            appendModalToBody(addModalEl);

            addModalEl.addEventListener('show.bs.modal', function () {
                appendModalToBody(addModalEl);
            });

            addModalEl.addEventListener('hidden.bs.modal', function () {
                if (addFormEl) {
                    addFormEl.reset();
                }
                var statusSelect = document.getElementById('sm_add_status');
                if (statusSelect) {
                    statusSelect.value = '1';
                }
            });

            var shouldOpenAdd = @json(
                old('subject_form') !== 'edit' && (
                    request()->get('open_add_subject') === '1' ||
                    (old('subject_form') === 'add' && $errors->any())
                )
            );
            if (shouldOpenAdd) {
                bootstrap.Modal.getOrCreateInstance(addModalEl).show();
                if (new URLSearchParams(window.location.search).get('open_add_subject') === '1' && window.history.replaceState) {
                    var addUrl = new URL(window.location.href);
                    addUrl.searchParams.delete('open_add_subject');
                    window.history.replaceState({}, '', addUrl);
                }
            }
        }

        /* ---- Edit modal ---- */
        var editModalEl = document.getElementById('smEditSubjectModal');
        var editFormEl = document.getElementById('smEditSubjectForm');

        if (editModalEl && editFormEl && !editFormEl.dataset.smSubjectBound) {
            editFormEl.dataset.smSubjectBound = '1';
            appendModalToBody(editModalEl);

            editModalEl.addEventListener('show.bs.modal', function () {
                appendModalToBody(editModalEl);
            });

            function populateEditForm(data, pk) {
                editFormEl.action = updateUrlTemplate.replace('__PK__', pk);
                document.getElementById('sm_edit_subject_pk_hidden').value = pk;
                document.getElementById('sm_edit_major_subject_name').value = data.major_subject_name || '';
                document.getElementById('sm_edit_short_name').value = data.short_name || '';
                document.getElementById('sm_edit_status').value = String(data.status ?? '1');
            }

            function openEditModal(pk, dataOverride) {
                var pkStr = String(pk);
                var data = dataOverride || subjectEditData[pkStr] || subjectEditData[pk];
                if (!data) {
                    return;
                }
                populateEditForm(data, pkStr);
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            }

            window.openSmEditSubjectModal = openEditModal;

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.sm-edit-subject-btn');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                openEditModal(btn.getAttribute('data-id'));
            });

            var shouldOpenEdit = @json(old('subject_form') === 'edit' && $errors->any());
            if (shouldOpenEdit) {
                openEditModal(@json(old('sm_edit_subject_pk')), {
                    major_subject_name: @json(old('major_subject_name')),
                    short_name: @json(old('short_name')),
                    status: @json(old('status', '1'))
                });
            } else {
                var openEditPk = new URLSearchParams(window.location.search).get('open_edit_subject');
                if (openEditPk) {
                    openEditModal(openEditPk);
                    if (window.history.replaceState) {
                        var editUrl = new URL(window.location.href);
                        editUrl.searchParams.delete('open_edit_subject');
                        window.history.replaceState({}, '', editUrl);
                    }
                }
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmSubjectModals);
    } else {
        initSmSubjectModals();
    }
})();
</script>
