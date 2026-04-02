<style>
    .modal.ts-dropdown-open .modal-body {
        overflow-x: hidden !important;
    }

    @media (max-width: 991.98px) {
        .modal .modal-body {
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<script>
    (function () {
        if (window.MessModalDropdownStability) return;

        function getModalBody(modalEl) {
            return modalEl ? modalEl.querySelector('.modal-body') : null;
        }

        window.MessModalDropdownStability = {
            onOpen: function (modalEl) {
                if (!modalEl) return null;
                var modalBody = getModalBody(modalEl);
                var state = {
                    scrollTop: modalBody ? modalBody.scrollTop : 0
                };
                modalEl.classList.add('ts-dropdown-open');
                return state;
            },
            onClose: function (modalEl, state) {
                if (!modalEl) return;
                var modalBody = getModalBody(modalEl);
                if (modalBody && state && typeof state.scrollTop === 'number') {
                    modalBody.scrollTop = state.scrollTop;
                }
                modalEl.classList.remove('ts-dropdown-open');
            },
            keepScroll: function (modalEl, state) {
                var modalBody = getModalBody(modalEl);
                if (modalBody && state && typeof state.scrollTop === 'number') {
                    modalBody.scrollTop = state.scrollTop;
                }
            }
        };
    })();
</script>
