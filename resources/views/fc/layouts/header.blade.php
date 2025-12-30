   <!-- Top Blue Bar (Govt of India) - Hidden on mobile -->
   <div class="top-header d-none d-md-block">
       <div class="container">
           <div class="row align-items-center">
               <div class="col-md-3 d-flex align-items-center">
                   <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                       alt="GoI Logo" height="30">
                   <span class="ms-2" style="font-size: 14px;">Government of India</span>
               </div>
               <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                   <ul class="nav justify-content-end align-items-center">
                       <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                               style=" font-size: 12px;">Skip to Main Content</a></li>
                       <!-- <span class="text-muted me-3 ms-3">|</span>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                   src="{{ asset('images/text_to_speech.png') }}" alt="" width="20"><span class="ms-1"
                                   style=" font-size: 12px;">Screen Reader</span></a></li>
                       <span class="text-muted me-3 ms-3">|</span>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                               style=" font-size: 12px;">A+</a></li>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                               style=" font-size: 12px;">A</a></li>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                               style=" font-size: 12px;">A-</a></li>
                       <span class="text-muted me-3 ms-3">|</span>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                   src="{{ asset('images/contrast.png') }}" alt="" width="20"></a></li>
                       <span class="text-muted me-3 ms-3">|</span>
                       <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                   src="{{ asset('images/Regular.png') }}" alt="" width="20">
                               <span><select name="lang" id="" class="form-select form-select-sm"
                                       style="width: 100px; display: inline-block; font-size: 14px;  background-color: transparent; border: none;color: #fff;font-size: 12px;">"
                                       <option value="">Language</option>
                                       <option value="en" selected>English</option>
                                   </select></span></a></li> -->
                       <span class="text-muted me-3 ">|</span>
                       <li class="nav-item"><a class="text-white text-decoration-none" id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;"><img
                                   src="{{ asset('images/accessible.png') }}" alt="" width="20">
                               <span class="text-white ms-1" style=" font-size: 12px;">
                                   More
                               </span>
                           </a>

                       </li>

                   </ul>
               </div>
           </div>
       </div>
   </div>
   <!-- Sticky Header -->
   <div class="header sticky-top bg-white shadow-sm">
       <div class="container">
           <nav class="navbar navbar-expand-lg navbar-light">
               <div class="container-fluid px-0">
                   <!-- Logo 1 -->
                   <a class="navbar-brand me-1 me-md-2" href="#">
                       <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="Logo 1"
                           class="img-fluid" height="80">
                   </a>
                   <!-- Divider - Hidden on mobile -->
                   <span class="vr mx-1 mx-md-2 d-none d-sm-block"></span>
                   <!-- Logo 2 -->
                   <a class="navbar-brand me-auto" href="#">
                       <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" class="img-fluid" height="80">
                   </a>

                   <!-- Mobile toggle button -->
                   <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                       aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                       <span class="navbar-toggler-icon"></span>
                   </button>

                   <!-- Navigation Menu -->
                   <!-- Navigation Menu -->
                   <div class="collapse navbar-collapse" id="navbarNav">
                       <ul class="navbar-nav ms-auto align-items-lg-center">
                           <li class="nav-item">
                               <a class="nav-link fw-medium" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa" target="_blank">About Us</a>
                           </li>
                           <li class="nav-item">
                               <a class="nav-link fw-medium" href="https://www.lbsnaa.gov.in/footer_menu/contact-us" target="_blank">Contact</a>
                           </li>
                           <li class="nav-item mt-2 mt-lg-0">
                               <a class="btn btn-outline-primary" href="{{ route('fc.login') }}">Login</a>
                           </li>
                       </ul>
                   </div>
               </div>
           </nav>
       </div>
   </div>
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
      </div>
    </div>
  </div>