    <!-- Footer -->
    <!-- Footer -->
    <footer class="mt-4 text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{date('Y')}} Lal Bahadur Shastri National Academy
                        of
                        Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-md-4 text-end">
                    <ul class="list-unstyled d-flex justify-content-end mb-0">
                        <li class="me-3">
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    <script src="{{ asset('admin_assets/js/google-translate.js') }}"></script>
    <script src="{{ asset('admin_assets/js/weights.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>

    <!-- google translate -->
    <!-- Google Translate Code -->
    <script type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en'
    }, 'google_translate_element');
}
    </script>
    <script type="text/javascript" src="js/google-translate.js"></script>
    <!-- End Google Translate Code -->

    <!-- accessibility -->
    <script src="js/weights.js"></script>
    <!-- font increase -->
    <script>
var $affectedElements = $("*"); // Can be extended, ex. $("div, p, span.someClass")

// Storing the original size in a data attribute so size can be reset
$affectedElements.each(function() {
    var $this = $(this);
    $this.data("orig-size", $this.css("font-size"));
});

$("#btn-increase").click(function() {
    changeFontSize(1);
})

$("#btn-decrease").click(function() {
    changeFontSize(-1);
})

$("#btn-orig").click(function() {
    $affectedElements.each(function() {
        var $this = $(this);
        $this.css("font-size", $this.data("orig-size"));
    });
})

function changeFontSize(direction) {
    $affectedElements.each(function() {
        var $this = $(this);
        $this.css("font-size", parseInt($this.css("font-size")) + direction);
    });
}
    </script>

    <!-- light dark theme -->

    <script>
const checkbox = document.getElementById("checkbox");
const isDarkMode = localStorage.getItem("darkMode") === "true";
checkbox.checked = isDarkMode;
const toggleDarkMode = () => {
    const isDarkMode = checkbox.checked;
    document.body.classList.toggle("dark", isDarkMode);
    localStorage.setItem("darkMode", isDarkMode);
};
checkbox.addEventListener("change", toggleDarkMode);
toggleDarkMode();
    </script>