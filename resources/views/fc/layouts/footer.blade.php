
<footer class="mt-auto">
<div style="background:#004a93; color:#ffffff;">
            <div class="container py-2 small d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span class="mb-1 mb-md-0">
                    © {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration. All rights reserved.
                </span>
                <span>
                    Site content managed by <span class="fw-semibold">LBSNAA</span> | Designed &amp; Developed by
                    <span class="fw-semibold">NeGD, MEITY</span>
                </span>
            </div>
        </div>
</footer>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
</script>
<script src="{{ asset('admin_assets/js/google-translate.js') }}"></script>
<script src="{{ asset('admin_assets/js/weights.js') }}"></script>

<!-- Google Translate Code -->
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en'
        }, 'google_translate_element');
    }
</script>
<script type="text/javascript" src="{{ asset('js/google-translate.js') }}"></script>

<!-- Accessibility font size controls -->
<script>
    var $affectedElements = $("*");

    $affectedElements.each(function () {
        var $this = $(this);
        $this.data("orig-size", $this.css("font-size"));
    });

    $("#btn-increase").click(function () {
        changeFontSize(1);
    });

    $("#btn-decrease").click(function () {
        changeFontSize(-1);
    });

    $("#btn-orig").click(function () {
        $affectedElements.each(function () {
            var $this = $(this);
            $this.css("font-size", $this.data("orig-size"));
        });
    });

    function changeFontSize(direction) {
        $affectedElements.each(function () {
            var $this = $(this);
            $this.css("font-size", parseInt($this.css("font-size")) + direction);
        });
    }
</script>

<!-- Light / dark theme toggle -->
<script>
    const checkbox = document.getElementById("checkbox");
    if (checkbox) {
        const isDarkMode = localStorage.getItem("darkMode") === "true";
        checkbox.checked = isDarkMode;
        const toggleDarkMode = () => {
            const isDarkMode = checkbox.checked;
            document.body.classList.toggle("dark", isDarkMode);
            localStorage.setItem("darkMode", isDarkMode);
        };
        checkbox.addEventListener("change", toggleDarkMode);
        toggleDarkMode();
    }
</script>