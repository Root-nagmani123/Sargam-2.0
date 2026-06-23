<script>
(function () {
    function initSmModuleModals() {
        if (typeof bootstrap === 'undefined') {
            return;
        }
        var moduleEditData = @json($smModuleEditData ?? []);
        var updateUrlTemplate = @json(route('subject-module.update', ['subject_module' => '__PK__']));

        function appendModalToBody(modalEl) {
            if (modalEl && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        }

        /* ---- Add modal ---- */
        var addModalEl = document.getElementById('smAddModuleModal');
        var addFormEl = document.getElementById('smAddModuleForm');

        if (addModalEl && addFormEl && !addFormEl.dataset.smModuleBound) {
            addFormEl.dataset.smModuleBound = '1';
            appendModalToBody(addModalEl);

            addModalEl.addEventListener('show.bs.modal', function () {
                appendModalToBody(addModalEl);
            });

            addModalEl.addEventListener('hidden.bs.modal', function () {
                if (addFormEl) {
                    addFormEl.reset();
                }
                var statusSelect = document.getElementById('sm_add_active_inactive');
                if (statusSelect) {
                    statusSelect.value = '1';
                }
            });

            var shouldOpenAdd = @json(
                old('module_form') !== 'edit' && (
                    request()->get('open_add_module') === '1' ||
                    (old('module_form') === 'add' && $errors->any())
                )
            );
            if (shouldOpenAdd) {
                bootstrap.Modal.getOrCreateInstance(addModalEl).show();
                if (new URLSearchParams(window.location.search).get('open_add_module') === '1' && window.history.replaceState) {
                    var addUrl = new URL(window.location.href);
                    addUrl.searchParams.delete('open_add_module');
                    window.history.replaceState({}, '', addUrl);
                }
            }
        }

        /* ---- Edit modal ---- */
        var editModalEl = document.getElementById('smEditModuleModal');
        var editFormEl = document.getElementById('smEditModuleForm');

        if (editModalEl && editFormEl && !editFormEl.dataset.smModuleBound) {
            editFormEl.dataset.smModuleBound = '1';
            appendModalToBody(editModalEl);

            editModalEl.addEventListener('show.bs.modal', function () {
                appendModalToBody(editModalEl);
            });

            function populateEditForm(data, pk) {
                editFormEl.action = updateUrlTemplate.replace('__PK__', pk);
                document.getElementById('sm_edit_module_pk_hidden').value = pk;
                document.getElementById('sm_edit_module_name').value = data.module_name || '';
                document.getElementById('sm_edit_active_inactive').value = String(data.active_inactive ?? '1');
            }

            function openEditModal(pk, dataOverride) {
                var pkStr = String(pk);
                var data = dataOverride || moduleEditData[pkStr] || moduleEditData[pk];
                if (!data) {
                    return;
                }
                populateEditForm(data, pkStr);
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            }

            window.openSmEditModuleModal = openEditModal;

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.sm-edit-module-btn');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                openEditModal(btn.getAttribute('data-id'));
            });

            var shouldOpenEdit = @json(old('module_form') === 'edit' && $errors->any());
            if (shouldOpenEdit) {
                openEditModal(@json(old('sm_edit_module_pk')), {
                    module_name: @json(old('module_name')),
                    active_inactive: @json(old('active_inactive', '1'))
                });
            } else {
                var openEditPk = new URLSearchParams(window.location.search).get('open_edit_module');
                if (openEditPk) {
                    openEditModal(openEditPk);
                    if (window.history.replaceState) {
                        var editUrl = new URL(window.location.href);
                        editUrl.searchParams.delete('open_edit_module');
                        window.history.replaceState({}, '', editUrl);
                    }
                }
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmModuleModals);
    } else {
        initSmModuleModals();
    }
})();
</script>
