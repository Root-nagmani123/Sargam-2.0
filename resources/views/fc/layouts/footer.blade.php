     <!-- accessibility html -->
  <!-- accessibility panel -->
  <div class="uwaw uw-light-theme gradient-head uwaw-initial paid_widget" id="uw-main">
    <div class="relative second-panel">
      <h3>Accessibility options by LBSNAA</h3>
      <div class="uwaw-close" onclick="closeMain()"></div>
    </div>
    <div class="uwaw-body">
      <div class="lang">
        <div class="lang_head">
          <i></i>
          <span>Language</span>

        </div>
        <div class="language_drop" id="google_translate_element">
          <!-- google translate list coming inside here -->
        </div>
      </div>
      <div class="h-scroll">
        <div class="uwaw-features">
          <div class="uwaw-features__item reset-feature" id="featureItem_sp">
            <button id="speak" class="uwaw-features__item__i" data-uw-reader-content="Enable the UserWay screen reader"
              aria-label="Enable the UserWay screen reader" aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-speaker"> </span>
              </span>
              <span class="uwaw-features__item__name">Screen Reader</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon_sp" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem">
            <button id="btn-s9" class="uwaw-features__item__i" data-uw-reader-content="Enable the UserWay screen reader"
              aria-label="Enable the UserWay screen reader" aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-bigger-text"> </span> </span><span class="uwaw-features__item__name">Bigger
                Text</span>
              <div class="uwaw-features__item__steps reset-steps" id="featureSteps">
                <!-- Steps span tags will be dynamically added here -->
              </div>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-st">
            <button id="btn-small-text" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-small-text"> </span> </span><span class="uwaw-features__item__name">Small
                Text</span>
              <div class="uwaw-features__item__steps reset-steps" id="featureSteps-st">
                <!-- Steps span tags will be dynamically added here -->
              </div>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-st" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-lh">
            <button id="btn-s12" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-line-hight"> </span>
              </span>
              <span class="uwaw-features__item__name">Line Height</span>
              <div class="uwaw-features__item__steps reset-steps" id="featureSteps-lh">
                <!-- Steps span tags will be dynamically added here -->
              </div>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-lh" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-ht">
            <button id="btn-s10" onclick="highlightLinks()" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-highlight-links"> </span>
              </span>
              <span class="uwaw-features__item__name">Highlight Links</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ht" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-ts">
            <button id="btn-s13" onclick="increaseAndReset()" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-text-spacing"> </span>
              </span>
              <span class="uwaw-features__item__name">Text Spacing</span>
              <div class="uwaw-features__item__steps reset-steps" id="featureSteps-ts">
                <!-- Steps span tags will be dynamically added here -->
              </div>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ts" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-df">
            <button id="btn-df" onclick="toggleFontFeature()" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-dyslexia-font"> </span>
              </span>
              <span class="uwaw-features__item__name">Dyslexia Friendly</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-df" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-hi">
            <button id="btn-s11" onclick="toggleImages()" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-hide-images"> </span>
              </span>
              <span class="uwaw-features__item__name">Hide Images</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-hi" style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-Cursor">
            <button id="btn-cursor" onclick="toggleCursorFeature()" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-cursor"> </span>
              </span>
              <span class="uwaw-features__item__name">Cursor</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-cursor"
                style="display: none">
              </span>
            </button>
          </div>

          <div class="uwaw-features__item reset-feature" id="featureItem-ht-dark">
            <button id="dark-btn" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__name">
                <span class="light_dark_icon">
                  <input type="checkbox" class="light_mode uwaw-featugres__item__i" id="checkbox" />
                  <label for="checkbox" class="checkbox-label">
                    <!-- <i class="fas fa-moon-stars"></i> -->
                    <i class="fas fa-moon-stars">
                      <span class="icon icon-moon"></span>
                    </i>
                    <i class="fas fa-sun">
                      <span class="icon icon-sun"></span>
                    </i>
                    <span class="ball"></span>
                  </label>
                </span>
                <span class="uwaw-features__item__name">Light-Dark</span>
              </span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ht-dark"
                style="display: none; pointer-events: none">
              </span>
            </button>
          </div>

          <!-- Invert Colors Widget -->

          <div class="uwaw-features__item reset-feature" id="featureItem-ic">
            <button id="btn-invert" class="uwaw-features__item__i"
              data-uw-reader-content="Enable the UserWay screen reader" aria-label="Enable the UserWay screen reader"
              aria-pressed="false">
              <span class="uwaw-features__item__icon">
                <span class="icon icon-invert"> </span>
              </span>
              <span class="uwaw-features__item__name">Invert Colors</span>
              <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ic" style="display: none">
              </span>
            </button>
          </div>
        </div>
      </div>
      <!-- Reset Button -->

    </div>
    <div class="reset-panel">

      <!-- copyright accessibility bar -->
      <div class="copyrights-accessibility">
        <button class="btn-reset-all" id="reset-all" onclick="resetAll()">
          <span class="reset-icon"> </span>
          <span class="reset-btn-text">Reset All Settings</span>
        </button>
        <a href="https://www.ux4g.gov.in" target="_blank" class="copyright-text">
          <span class="uwaw-features__item__name ux4g-copy ux4g-copyright">Created by</span>
          <img src="images/ux4g-logo.svg" alt="logo" loading="lazy" width="93" height="25" />
        </a>
      </div>
    </div>
  </div>
    <!-- Footer -->
    <!-- Footer -->
    <footer class="mt-4 text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{date('Y')}} Lal Bahadur Shastri National Academy of
                        Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-md-4 text-end">
                    <ul class="list-unstyled d-flex justify-content-end mb-0">
                        <li class="me-3">
                            <a href="#" class="text-white text-decoration-none" style="font-size: 14px; font-family: Inter;">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-none" style="font-size: 14px; font-family: Inter;">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
      <!-- google translate -->
  <!-- Google Translate Code -->
  <script type="text/javascript">
    function googleTranslateElementInit() {
      new google.translate.TranslateElement({ pageLanguage: 'en' }, 'google_translate_element');
    }
  </script>
  <script type="text/javascript" src="{{asset('admin_assets/js/google-translate.js')}}"></script>
  <!-- End Google Translate Code -->

  <!-- accessibility -->
  <script src="{{asset('admin_assets/js/weights.js')}}"></script>
  <!-- font increase -->
  <script>

    var $affectedElements = $("*"); // Can be extended, ex. $("div, p, span.someClass")

    // Storing the original size in a data attribute so size can be reset
    $affectedElements.each(function () {
      var $this = $(this);
      $this.data("orig-size", $this.css("font-size"));
    });

    $("#btn-increase").click(function () {
      changeFontSize(1);
    })

    $("#btn-decrease").click(function () {
      changeFontSize(-1);
    })

    $("#btn-orig").click(function () {
      $affectedElements.each(function () {
        var $this = $(this);
        $this.css("font-size", $this.data("orig-size"));
      });
    })

    function changeFontSize(direction) {
      $affectedElements.each(function () {
        var $this = $(this);
        $this.css("font-size", parseInt($this.css("font-size")) + direction);
      });
    }
  </script>
  <script src="https://cdn.ux4g.gov.in/UX4G@2.0.5/js/ux4g.min.js"></script>