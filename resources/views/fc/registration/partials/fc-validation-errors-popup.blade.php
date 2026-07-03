@if($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var errorMessages = @json($errors->all());
    if (!errorMessages.length) {
        return;
    }

    var listHtml = '<ul style="text-align:left;margin:0;padding-left:1.25rem;">'
        + errorMessages.map(function (msg) {
            return '<li style="margin-bottom:0.35rem;">' + String(msg).replace(/</g, '&lt;') + '</li>';
        }).join('')
        + '</ul>';

    function scrollToFirstInvalid() {
        var first = document.querySelector('.is-invalid, :invalid');
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof first.focus === 'function') {
                first.focus({ preventScroll: true });
            }
        }
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Please fix the following',
            html: listHtml,
            icon: 'error',
            confirmButtonColor: '#004a93',
            confirmButtonText: 'OK'
        }).then(scrollToFirstInvalid);
    } else {
        alert(errorMessages.join('\n'));
        scrollToFirstInvalid();
    }
});
</script>
@endif
