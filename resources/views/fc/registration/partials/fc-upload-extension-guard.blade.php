{{-- Instant client-side filename guard for FC dynamic-form step uploads (inputs with
     class .fc-file-upload, used by both flat and group/repeatable steps).

     Rejects a file whose name carries an executable extension in ANY dot-segment — this
     is what blocks a DOUBLE EXTENSION such as "report.php.pdf" or "a.phtml.jpg" on the
     spot. The existing size/type handler only inspects the LAST segment (".pdf"), so a
     double extension slipped past it in the browser; this closes that gap.

     Server-side, App\Rules\SafeUploadedDocument remains the authority and rejects the
     same names on submit — this only gives the trainee immediate feedback.

     Scope: FC registration step views only (included from step-fields / dynamic-step3).
     The joining-document checklist inputs (.fc-doc-upload-input) have their own richer
     guard and are intentionally not touched here. --}}
<script>
(function () {
    if (window.__fcExtGuardInit) { return; }   // idempotent: only bind once per page
    window.__fcExtGuardInit = true;

    // An executable/script extension appearing as ANY dot-segment of the name.
    // (\.|$) => matches whether it is the last segment (evil.pdf.php) or a middle one
    // (report.php.pdf). Allowed document types (pdf/jpg/jpeg/png/doc/docx) are absent.
    var BLOCKED = /\.(php\d?|phtml|phps|pht|phar|cgi|pl|py|rb|jsp|jspx|asp|aspx|sh|bash|bat|cmd|com|exe|msi|dll|jar|htaccess|htm|html|xhtml|shtml|svg|js|mjs|vbs|ps1)(\.|$)/i;

    // Recognised file extensions, to detect a stacked "double extension" like
    // "Document1.txt.pdf.pdf" or "scan.jpg.pdf" (mirrors the server list).
    var KNOWN_EXT = {
        pdf:1,txt:1,doc:1,docx:1,xls:1,xlsx:1,ppt:1,pptx:1,csv:1,rtf:1,odt:1,ods:1,
        jpg:1,jpeg:1,png:1,gif:1,bmp:1,webp:1,svg:1,tif:1,tiff:1,heic:1,
        zip:1,rar:1,'7z':1,gz:1,tar:1,bz2:1,
        php:1,php3:1,php4:1,php5:1,php7:1,phtml:1,phar:1,exe:1,com:1,bat:1,cmd:1,sh:1,
        bash:1,js:1,mjs:1,html:1,htm:1,xhtml:1,asp:1,aspx:1,jsp:1,pl:1,py:1,rb:1,
        dll:1,msi:1,jar:1,vbs:1,ps1:1
    };

    // True when the name carries more than one extension (two+ recognised trailing
    // segments). Incidental dots in the base ("Dr. Smith CV.pdf") have one real ext.
    function hasMultipleExtensions(name) {
        name = String(name).split(/[\\\/]/).pop();
        var segs = name.toLowerCase().split('.');
        if (segs.length < 3) { return false; }
        segs.shift();
        var count = 0;
        for (var i = 0; i < segs.length; i++) {
            if (KNOWN_EXT[segs[i].trim()]) { count++; }
        }
        return count >= 2;
    }

    var MSG_SCRIPT = 'This file is not allowed. A script extension anywhere in the name '
            + '(for example "report.php.pdf") is blocked. Please rename the file or upload a plain document.';
    var MSG_MULTI = 'This file name has more than one extension (for example "name.txt.pdf"). '
            + 'Rename it to a single extension like "name.pdf" and upload again.';

    function holderFor(input) {
        return input.closest('.mb-2, .mb-3, .col, .form-group, td, .field-block')
            || input.parentNode;
    }

    function feedbackEl(input, create) {
        var holder = holderFor(input);
        var fb = holder ? holder.querySelector('.fc-ext-guard-feedback') : null;
        if (!fb && create && holder) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback fc-ext-guard-feedback';
            fb.style.display = 'block';
            holder.appendChild(fb);
        }
        return fb;
    }

    // Capture phase (true) so this runs BEFORE the per-input size handler: clearing the
    // value here makes that handler no-op on a rejected file instead of fighting over
    // the is-invalid state.
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !input.classList
                || !input.classList.contains('fc-file-upload')) {
            return;
        }

        var file = input.files && input.files[0];

        var badReason = null;
        if (file) {
            if (BLOCKED.test(file.name)) { badReason = MSG_SCRIPT; }
            else if (hasMultipleExtensions(file.name)) { badReason = MSG_MULTI; }
        }

        if (badReason) {
            input.value = '';                       // drop the rejected file
            input.classList.add('is-invalid');
            input.dataset.extBlocked = '1';
            var fb = feedbackEl(input, true);
            if (fb) { fb.textContent = badReason; }
        } else if (input.dataset.extBlocked === '1') {
            // We set the error last time; a valid file replaced it — clear only our state.
            input.classList.remove('is-invalid');
            input.dataset.extBlocked = '';
            var old = feedbackEl(input, false);
            if (old) { old.remove(); }
        }
    }, true);
})();
</script>
