<!-- Add/Edit Event Modal -->
<style>
    /* Minimal Select2 styling to match Bootstrap 5 - only for third-party library integration */
    #eventModal .select2-container--default .select2-selection--multiple,
    #eventModal .select2-container--default .select2-selection--single,
    .modal-dialog .select2-container--default .select2-selection--multiple,
    .modal-dialog .select2-container--default .select2-selection--single {
        border: var(--bs-border-width) solid var(--bs-border-color) !important;
        border-radius: var(--bs-border-radius) !important;
        min-height: calc(1.5em + 0.75rem + 2px) !important;
        background-color: var(--bs-body-bg) !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple,
    .modal-dialog .select2-container--default .select2-selection--multiple {
        padding: 0.25rem 0.5rem !important;
    }

    #eventModal .select2-container--default .select2-selection--single,
    .modal-dialog .select2-container--default .select2-selection--single {
        padding: 0.375rem 0.75rem !important;
    }

    #eventModal .select2-container--default.select2-container--focus .select2-selection--multiple,
    #eventModal .select2-container--default.select2-container--focus .select2-selection--single,
    #eventModal .select2-container--default.select2-container--open .select2-selection--multiple,
    #eventModal .select2-container--default.select2-container--open .select2-selection--single,
    .modal-dialog .select2-container--default.select2-container--focus .select2-selection--multiple,
    .modal-dialog .select2-container--default.select2-container--focus .select2-selection--single,
    .modal-dialog .select2-container--default.select2-container--open .select2-selection--multiple,
    .modal-dialog .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25) !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: var(--bs-primary) !important;
        border: none !important;
        border-radius: var(--bs-border-radius-sm) !important;
        padding: 0.25rem 0.5rem !important;
        color: var(--bs-white) !important;
        margin: 0.125rem 0.25rem 0.125rem 0 !important;
    }

    #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
    .modal-dialog .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: var(--bs-white) !important;
        margin-right: 0.25rem !important;
    }

    .select2-dropdown {
        border: var(--bs-border-width) solid var(--bs-border-color) !important;
        border-radius: var(--bs-border-radius) !important;
        box-shadow: var(--bs-box-shadow-lg) !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--bs-primary) !important;
        color: var(--bs-white) !important;
    }

    #eventModal .select2-container,
    .modal-dialog .select2-container {
        width: 100% !important;
    }

    #eventModal .select2-dropdown,
    #eventModal .select2-container--default .select2-results__options,
    .modal-dialog .select2-dropdown,
    .modal-dialog .select2-container--default .select2-results__options {
        z-index: 1060 !important;
    }

    #eventModal .select2-container--default.select2-container--open,
    .modal-dialog .select2-container--default.select2-container--open {
        z-index: 1060 !important;
    }

    .required::after {
        content: " *";
        color: var(--bs-danger);
        font-weight: 600;
    }

    /* Responsive form controls - Mobile First Approach */
    .form-select-responsive,
    .form-control-responsive {
        padding: 0.625rem 0.875rem;
        font-size: 1rem;
        min-height: 44px; /* Better touch target on mobile */
    }

    @media (min-width: 576px) {
        .form-select-responsive,
        .form-control-responsive {
            padding: 0.625rem 0.875rem;
            font-size: 1rem;
        }
    }

    @media (min-width: 768px) {
        .form-select-responsive,
        .form-control-responsive {
            padding: 0.625rem 0.875rem;
            font-size: 1.125rem;
        }
    }

    @media (min-width: 992px) {
        .form-select-responsive,
        .form-control-responsive {
            padding: 0.75rem 1rem;
            font-size: 1.125rem;
        }
    }

    /* Responsive buttons */
    .btn-responsive {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        min-height: 44px; /* Better touch target */
        white-space: nowrap;
    }

    @media (min-width: 576px) {
        .btn-responsive {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
        }
    }

    @media (min-width: 768px) {
        .btn-responsive {
            padding: 0.625rem 1.25rem;
            font-size: 1rem;
        }
    }

    /* Responsive button groups */
    .btn-group-responsive {
        flex-direction: column;
        width: 100%;
        gap: 0.5rem;
    }

    .btn-group-responsive .btn {
        width: 100%;
        border-radius: var(--bs-border-radius) !important;
        margin-bottom: 0;
        min-height: 44px;
    }

    @media (min-width: 576px) {
        .btn-group-responsive {
            flex-direction: row;
            width: auto;
            gap: 0;
        }

        .btn-group-responsive .btn {
            width: auto;
            border-radius: 0 !important;
            margin-bottom: 0;
        }

        .btn-group-responsive .btn:first-child {
            border-top-left-radius: var(--bs-border-radius) !important;
            border-bottom-left-radius: var(--bs-border-radius) !important;
        }

        .btn-group-responsive .btn:last-child {
            border-top-right-radius: var(--bs-border-radius) !important;
            border-bottom-right-radius: var(--bs-border-radius) !important;
        }
    }

    /* Responsive form labels */
    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    @media (min-width: 576px) {
        .form-label {
            font-size: 0.9rem;
        }
    }

    @media (min-width: 768px) {
        .form-label {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
    }

    /* Responsive form switches - Better touch targets */
    .form-check-input {
        width: 2.5em;
        height: 1.5em;
        min-width: 44px;
        min-height: 24px;
    }

    @media (min-width: 768px) {
        .form-check-input {
            width: 2.5em;
            height: 1.25em;
        }
    }

    /* Date input responsive styling */
    #eventModal #start_datetime {
        min-width: 140px;
        max-width: 100%;
    }

    @media (min-width: 576px) {
        #eventModal #start_datetime {
            max-width: 10rem;
        }
    }

    /* Responsive modal adjustments */
    @media (max-width: 374.98px) {
        /* Extra small devices */
        #eventModal .modal-header {
            padding: 0.75rem;
        }

        #eventModal .modal-body {
            padding: 0.75rem;
            max-height: calc(100vh - 180px);
        }

        #eventModal .card-body {
            padding: 0.75rem;
        }

        #eventModal .card-header {
            padding: 0.5rem 0.75rem;
        }

        #eventModal .modal-title {
            font-size: 0.875rem;
        }

        #eventModal .form-label {
            font-size: 0.8rem;
        }

        #eventModal .form-label i {
            font-size: 0.875rem;
        }

        #eventModal #start_datetime {
            font-size: 0.875rem;
        }
    }

    @media (min-width: 375px) and (max-width: 575.98px) {
        /* Small mobile devices */
        #eventModal .modal-body {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        #eventModal .modal-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        #eventModal #start_datetime {
            max-width: 100% !important;
            width: 100%;
            margin-top: 0.5rem;
        }

        #eventModal .modal-title span {
            word-break: break-word;
        }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
        /* Small tablets */
        #eventModal .modal-dialog {
            max-width: 540px;
            margin: 1rem auto;
        }

        #eventModal .modal-body {
            max-height: calc(100vh - 150px);
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        /* Tablets */
        #eventModal .modal-dialog {
            max-width: 720px;
            margin: 1.75rem auto;
        }

        #eventModal .modal-body {
            max-height: calc(100vh - 200px);
        }
    }

    /* ========== DESKTOP UI ENHANCEMENTS ========== */

    @media (min-width: 992px) {
        /* Desktop Base Styles */
        #eventModal .modal-dialog {
            max-width: 1000px;
            margin: 2rem auto;
        }

        #eventModal .modal-content {
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        /* Enhanced Header */
        #eventModal .modal-header {
            padding: 1.5rem 2rem;
            border-radius: 1rem 1rem 0 0;
            background: linear-gradient(135deg, #004a93 0%, #003366 100%);
        }

        #eventModal .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        #eventModal .modal-title i {
            font-size: 1.75rem;
        }

        /* Enhanced Body */
        #eventModal .modal-body {
            padding: 2rem;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
        }

        /* Enhanced Cards */
        #eventModal .card {
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 74, 147, 0.1);
        }

        #eventModal .card:hover {
            box-shadow: 0 4px 20px rgba(0, 74, 147, 0.12);
            transform: translateY(-2px);
        }

        #eventModal .card-header {
            padding: 1.25rem 2rem;
            background: linear-gradient(to right, #ffffff 0%, #f8f9fa 100%);
            border-bottom: 3px solid var(--bs-primary);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        #eventModal .card-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        #eventModal .card-header i {
            font-size: 1.25rem;
        }

        #eventModal .card-body {
            padding: 1.75rem 2rem;
        }

        /* Enhanced Form Elements */
        #eventModal .form-label {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #2c3e50;
            letter-spacing: 0.01em;
        }

        #eventModal .form-label i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }

        #eventModal .form-control,
        #eventModal .form-select {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            border: 1.5px solid #dee2e6;
            transition: all 0.2s ease;
            min-height: 48px;
        }

        #eventModal .form-control:focus,
        #eventModal .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
            transform: translateY(-1px);
        }

        #eventModal textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }

        /* Enhanced Buttons */
        #eventModal .btn {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            letter-spacing: 0.01em;
        }

        #eventModal .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #eventModal .btn-primary {
            background: linear-gradient(135deg, #004a93 0%, #003366 100%);
            border: none;
        }

        #eventModal .btn-primary:hover {
            background: linear-gradient(135deg, #003366 0%, #002244 100%);
        }

        /* Enhanced Row Spacing */
        #eventModal .row.g-3 {
            --bs-gutter-y: 1.5rem;
            --bs-gutter-x: 1.5rem;
        }

        /* Enhanced Group Type Container */
        #eventModal #type_name_container {
            padding: 1.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px dashed rgba(0, 74, 147, 0.2);
            transition: all 0.3s ease;
        }

        #eventModal #type_name_container:hover {
            border-color: rgba(0, 74, 147, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        /* Enhanced Select2 on Desktop */
        #eventModal .select2-container--default .select2-selection--single,
        #eventModal .select2-container--default .select2-selection--multiple {
            padding: 0.5rem 1rem;
            min-height: 48px;
            border-radius: 0.5rem;
            border: 1.5px solid #dee2e6;
        }

        #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
            padding: 0.4rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }

        /* Enhanced Footer */
        #eventModal .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid rgba(0, 74, 147, 0.1);
            background: #ffffff;
            border-radius: 0 0 1rem 1rem;
        }

        /* Enhanced Form Switches */
        #eventModal .form-check-input {
            width: 3em;
            height: 1.5em;
        }

        #eventModal .form-check-label {
            font-size: 1rem;
            font-weight: 600;
            padding-left: 0.75rem;
        }

        /* Enhanced Alert Messages */
        #eventModal .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            border-left-width: 4px;
        }

        /* Enhanced Date Input in Header */
        #eventModal #start_datetime {
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            border-radius: 0.5rem;
            min-width: 180px;
        }

        /* Better Visual Hierarchy */
        #eventModal section.card {
            margin-bottom: 2rem;
        }

        #eventModal section.card:last-of-type {
            margin-bottom: 0;
        }

        /* Enhanced Additional Options Cards */
        #eventModal .additional-option-card {
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        #eventModal .additional-option-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
        }

        #eventModal .feedback-options-container {
            padding: 1.5rem;
            border-radius: 0.75rem;
            border-left-width: 4px;
        }

        /* Enhanced Button Groups */
        #eventModal .btn-group-responsive .btn {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
        }

        /* Better spacing for form sections */
        #eventModal .card-body .row > [class*="col-"] {
            margin-bottom: 0;
        }

        /* Enhanced small text helpers */
        #eventModal .form-text {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            color: #6c757d;
        }

        /* Improved icon spacing */
        #eventModal .form-label i {
            width: 1.25rem;
            text-align: center;
        }
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
        /* Small desktops */
        #eventModal .modal-dialog {
            max-width: 960px;
            margin: 1.75rem auto;
        }

        #eventModal .modal-body {
            padding: 1.75rem;
        }

        #eventModal .card-body {
            padding: 1.5rem 1.75rem;
        }
    }

    @media (min-width: 1200px) {
        /* Large desktops */
        #eventModal .modal-dialog {
            max-width: 1200px;
            margin: 2rem auto;
        }

        #eventModal .modal-body {
            padding: 2.5rem;
        }

        #eventModal .card-body {
            padding: 2rem 2.5rem;
        }

        #eventModal .card-header {
            padding: 1.5rem 2.5rem;
        }

        /* Even better spacing on large screens */
        #eventModal .row.g-3 {
            --bs-gutter-y: 2rem;
            --bs-gutter-x: 2rem;
        }
    }

    /* Better Select2 mobile handling */
    @media (max-width: 575.98px) {
        #eventModal .select2-container--default .select2-selection--multiple,
        #eventModal .select2-container--default .select2-selection--single {
            min-height: 44px !important;
            padding: 0.5rem 0.75rem !important;
        }

        #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
            font-size: 0.875rem !important;
            padding: 0.25rem 0.5rem !important;
            margin: 0.125rem 0.25rem 0.125rem 0 !important;
        }
    }

    /* Responsive card spacing */
    @media (max-width: 575.98px) {
        #eventModal .card {
            margin-bottom: 1rem;
        }

        #eventModal section.card {
            margin-bottom: 1rem;
        }
    }

    /* Responsive gap adjustments */
    @media (max-width: 575.98px) {
        #eventModal .row.g-3 {
            --bs-gutter-y: 1rem;
            --bs-gutter-x: 0.75rem;
        }
    }

    /* Better text wrapping on small screens */
    @media (max-width: 575.98px) {
        #eventModal .form-label span,
        #eventModal .btn-text {
            word-break: break-word;
        }

        #eventModal .modal-title {
            line-height: 1.3;
        }
    }

    /* Touch-friendly spacing */
    @media (max-width: 767.98px) {
        #eventModal .form-check {
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        #eventModal .form-check-input {
            margin-top: 0.25rem;
        }
    }

    /* Better icon sizing on mobile */
    @media (max-width: 575.98px) {
        #eventModal .bi {
            font-size: 1rem;
        }

        #eventModal .form-label i {
            min-width: 1.25rem;
        }
    }

    /* Type name container responsive */
    #type_name_container {
        min-height: 60px;
    }

    .min-h-60 {
        min-height: 60px;
    }

    @media (max-width: 575.98px) {
        .min-h-60 {
            min-height: 50px;
        }
    }

    @media (max-width: 575.98px) {
        #type_name_container {
            padding: 0.75rem !important;
            min-height: 50px;
        }

        #type_name_container #groupTypePlaceholder {
            font-size: 0.875rem;
            flex-direction: column;
            gap: 0.5rem;
        }
    }

    /* Better textarea on mobile */
    @media (max-width: 575.98px) {
        #eventModal textarea.form-control-responsive {
            min-height: 100px;
            resize: vertical;
        }
    }

    /* Alert responsive */
    @media (max-width: 575.98px) {
        #eventModal .alert {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        #eventModal .alert small {
            font-size: 0.8rem;
        }
    }

    /* Form text helper responsive */
    @media (max-width: 575.98px) {
        #eventModal .form-text {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
    }

    /* Card header responsive */
    @media (max-width: 575.98px) {
        #eventModal .card-header h3,
        #eventModal .card-header h6 {
            font-size: 0.95rem;
        }
    }

    /* Better spacing for form sections */
    @media (max-width: 575.98px) {
        #eventModal .card-body .row > [class*="col-"] {
            margin-bottom: 0.5rem;
        }

        #eventModal .card-body .row > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
    }

    /* Modal footer responsive improvements */
    @media (max-width: 575.98px) {
        #eventModal .modal-footer {
            padding: 0.75rem;
            gap: 0.75rem;
        }

        #eventModal .modal-footer .btn {
            flex: 1;
            min-width: 0;
        }
    }

    /* Prevent horizontal scroll on very small screens */
    @media (max-width: 374.98px) {
        #eventModal {
            overflow-x: hidden;
        }

        #eventModal .modal-content {
            border-radius: 0;
        }
    }

    /* Better fullscreen modal on mobile */
    @media (max-width: 767.98px) {
        #eventModal.modal-fullscreen .modal-content {
            border-radius: 0;
            height: 100vh;
            margin: 0;
        }

        #eventModal.modal-fullscreen .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* Smooth scrolling for mobile */
    #eventModal .modal-body {
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }

    /* Better focus states for mobile */
    @media (max-width: 767.98px) {
        #eventModal .form-control:focus,
        #eventModal .form-select:focus {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }
    }

    /* Button icon spacing responsive */
    @media (max-width: 575.98px) {
        #eventModal .btn i {
            margin-right: 0.375rem !important;
        }

        #eventModal .btn-responsive {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
        }
    }

    /* Better label wrapping */
    @media (max-width: 575.98px) {
        #eventModal .form-label {
            flex-wrap: wrap;
            line-height: 1.4;
        }

        #eventModal .form-label span {
            flex: 1;
            min-width: 0;
        }
    }

    /* Improved card border visibility on mobile */
    @media (max-width: 575.98px) {
        #eventModal .card {
            border-width: 1px;
        }

        #eventModal .card-header {
            border-bottom-width: 2px;
        }
    }

    /* Better modal header on very small screens */
    @media (max-width: 374.98px) {
        #eventModal .modal-header {
            flex-wrap: wrap;
        }

        #eventModal .modal-title {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }

    /* Comprehensive responsive improvements for all screen sizes */

    /* Very Small Devices (< 400px) */
    @media (max-width: 399.98px) {
        #eventModal .modal-dialog {
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            height: 100vh !important;
        }

        #eventModal .modal-content {
            border-radius: 0 !important;
            height: 100vh !important;
            display: flex;
            flex-direction: column;
        }

        #eventModal .modal-header {
            padding: 0.75rem 0.5rem !important;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        #eventModal .modal-title {
            font-size: 0.875rem !important;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        #eventModal .modal-title span {
            display: block;
        }

        #eventModal #start_datetime {
            width: 100% !important;
            font-size: 0.8rem !important;
            padding: 0.4rem !important;
        }

        #eventModal .modal-body {
            padding: 0.75rem 0.5rem !important;
            flex: 1 1 auto;
            overflow-y: auto;
            max-height: calc(100vh - 140px) !important;
        }

        #eventModal .card {
            margin-bottom: 0.75rem !important;
        }

        #eventModal .card-header {
            padding: 0.5rem 0.75rem !important;
        }

        #eventModal .card-header h3,
        #eventModal .card-header h6 {
            font-size: 0.85rem !important;
        }

        #eventModal .card-body {
            padding: 0.75rem !important;
        }

        #eventModal .form-label {
            font-size: 0.75rem !important;
            margin-bottom: 0.35rem;
        }

        #eventModal .form-control,
        #eventModal .form-select {
            font-size: 0.8rem !important;
            padding: 0.4rem 0.5rem !important;
            min-height: 40px;
        }

        #eventModal textarea.form-control {
            min-height: 80px;
            font-size: 0.8rem !important;
        }

        #eventModal .btn-group-responsive {
            flex-direction: column;
            width: 100%;
        }

        #eventModal .btn-group-responsive .btn {
            width: 100%;
            margin-bottom: 0.5rem;
            font-size: 0.8rem !important;
            padding: 0.5rem !important;
        }

        #eventModal .btn-group-responsive .btn:last-child {
            margin-bottom: 0;
        }

        #eventModal .form-check-label {
            font-size: 0.8rem !important;
        }

        #eventModal .modal-footer {
            padding: 0.5rem !important;
            flex-direction: column;
            gap: 0.5rem;
        }

        #eventModal .modal-footer .btn {
            width: 100%;
            font-size: 0.8rem !important;
            padding: 0.5rem !important;
        }

        #eventModal .select2-container--default .select2-selection--multiple,
        #eventModal .select2-container--default .select2-selection--single {
            min-height: 40px !important;
            padding: 0.25rem 0.5rem !important;
        }

        #eventModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
            font-size: 0.75rem !important;
            padding: 0.15rem 0.35rem !important;
        }

        #eventModal #type_name_container {
            padding: 0.5rem !important;
            min-height: 40px;
        }

        #eventModal #groupTypePlaceholder {
            font-size: 0.75rem !important;
            flex-direction: column;
            gap: 0.35rem;
        }

        #eventModal .alert {
            padding: 0.4rem 0.5rem !important;
            font-size: 0.75rem !important;
        }

        #eventModal .row.g-3 {
            --bs-gutter-y: 0.5rem;
            --bs-gutter-x: 0.5rem;
        }
    }

    /* Small Mobile Devices (400px - 575px) */
    @media (min-width: 400px) and (max-width: 575.98px) {
        #eventModal .modal-dialog {
            margin: 0.5rem !important;
            max-width: calc(100vw - 1rem) !important;
            width: calc(100vw - 1rem) !important;
        }

        #eventModal .modal-content {
            max-height: calc(100vh - 1rem);
            border-radius: 0.5rem;
        }

        #eventModal .modal-header {
            padding: 0.875rem !important;
        }

        #eventModal .modal-title {
            font-size: 0.95rem !important;
        }

        #eventModal .modal-body {
            padding: 0.875rem !important;
            max-height: calc(100vh - 180px);
        }

        #eventModal .card-header {
            padding: 0.75rem !important;
        }

        #eventModal .card-body {
            padding: 0.875rem !important;
        }

        #eventModal .form-label {
            font-size: 0.85rem !important;
        }

        #eventModal .form-control,
        #eventModal .form-select {
            font-size: 0.875rem !important;
            padding: 0.5rem 0.65rem !important;
        }

        #eventModal .modal-footer {
            padding: 0.75rem !important;
        }

        #eventModal .modal-footer .btn {
            flex: 1;
            min-width: 0;
        }
    }

    /* Tablet Portrait (576px - 767px) */
    @media (min-width: 576px) and (max-width: 767.98px) {
        #eventModal .modal-dialog {
            max-width: 540px;
            margin: 1rem auto;
        }

        #eventModal .modal-content {
            max-height: calc(100vh - 2rem);
        }

        #eventModal .modal-body {
            max-height: calc(100vh - 200px);
            padding: 1rem !important;
        }

        #eventModal .card-body {
            padding: 1rem !important;
        }

        #eventModal .form-label {
            font-size: 0.9rem;
        }

        #eventModal .form-control,
        #eventModal .form-select {
            font-size: 0.95rem;
        }
    }

    /* Tablet Landscape / Small Desktop (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991.98px) {
        #eventModal .modal-dialog {
            max-width: 720px;
            margin: 1.75rem auto;
        }

        #eventModal .modal-body {
            max-height: calc(100vh - 220px);
        }

        #eventModal .card-body {
            padding: 1.25rem !important;
        }
    }

    /* Large Desktop (992px - 1199px) */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        #eventModal .modal-dialog {
            max-width: 960px;
        }
    }

    /* Extra Large Desktop (1200px+) */
    @media (min-width: 1200px) {
        #eventModal .modal-dialog {
            max-width: 1140px;
        }
    }

    /* Landscape orientation on mobile */
    @media (max-width: 767.98px) and (orientation: landscape) {
        #eventModal .modal-dialog {
            margin: 0.25rem !important;
            max-width: calc(100vw - 0.5rem) !important;
        }

        #eventModal .modal-content {
            max-height: calc(100vh - 0.5rem);
        }

        #eventModal .modal-body {
            max-height: calc(100vh - 120px);
            padding: 0.75rem !important;
        }

        #eventModal .card {
            margin-bottom: 0.75rem !important;
        }

        #eventModal .card-header {
            padding: 0.5rem 0.75rem !important;
        }

        #eventModal .card-body {
            padding: 0.75rem !important;
        }

        #eventModal .row.g-3 {
            --bs-gutter-y: 0.5rem;
        }
    }

    /* Ensure Select2 dropdowns are properly sized on mobile */
    @media (max-width: 575.98px) {
        #eventModal .select2-container {
            width: 100% !important;
        }

        #eventModal .select2-dropdown {
            max-width: calc(100vw - 2rem) !important;
        }

        #eventModal .select2-results__option {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
        }
    }

    /* Better spacing for form sections on mobile */
    @media (max-width: 767.98px) {
        #eventModal section.card {
            margin-bottom: 1rem;
        }

        #eventModal .row > [class*="col-"] {
            margin-bottom: 0.75rem;
        }

        #eventModal .row > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
    }

    /* Ensure proper scrolling on all devices */
    #eventModal .modal-body {
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }

    /* Prevent horizontal overflow */
    #eventModal,
    #eventModal .modal-content,
    #eventModal .modal-body {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Better touch targets for checkboxes and switches */
    @media (max-width: 767.98px) {
        #eventModal .form-check-input {
            width: 2.5em;
            height: 1.5em;
            min-width: 44px;
            min-height: 28px;
        }

        #eventModal .form-check-label {
            padding-left: 0.75rem;
            min-height: 44px;
            display: flex;
            align-items: center;
        }
    }

    /* Improve button group layout on mobile */
    @media (max-width: 575.98px) {
        #eventModal .btn-group-responsive {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        #eventModal .btn-group-responsive .btn {
            width: 100%;
            border-radius: 0.375rem !important;
            margin: 0;
        }
    }

    /* Better header layout on mobile */
    @media (max-width: 575.98px) {
        #eventModal .modal-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.75rem;
        }

        #eventModal .modal-header .d-flex > div:first-child {
            width: 100%;
        }

        #eventModal .modal-header .d-flex > div:last-child {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        #eventModal #start_datetime {
            width: 100% !important;
            margin-top: 0;
        }

        #eventModal .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            margin: 0;
        }
    }

    /* Ensure cards don't overflow */
    @media (max-width: 767.98px) {
        #eventModal .card {
            max-width: 100%;
            overflow-x: hidden;
        }

        #eventModal .card-body .row {
            margin-left: 0;
            margin-right: 0;
        }
    }

    /* Better textarea handling */
    @media (max-width: 575.98px) {
        #eventModal textarea {
            resize: vertical;
            min-height: 100px;
        }
    }

    /* Improve Select2 display on mobile */
    @media (max-width: 575.98px) {
        #eventModal .select2-selection__rendered {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        #eventModal .select2-selection__choice {
            margin-top: 0.25rem !important;
            margin-bottom: 0.25rem !important;
        }

        #eventModal .select2-dropdown {
            max-height: 200px !important;
            overflow-y: auto !important;
        }

        #eventModal .select2-results__options {
            max-height: 200px !important;
            overflow-y: auto !important;
        }
    }

    /* Prevent body scroll on mobile when modal is open */
    body.modal-open-mobile {
        overflow: hidden;
        position: fixed;
        width: 100%;
    }

    /* Better Select2 search box on mobile */
    @media (max-width: 575.98px) {
        #eventModal .select2-search--dropdown .select2-search__field {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            min-height: 44px;
        }
    }

    /* Ensure Select2 container doesn't overflow */
    @media (max-width: 767.98px) {
        #eventModal .select2-container {
            max-width: 100% !important;
        }

        #eventModal .select2-selection {
            max-width: 100% !important;
            overflow: hidden;
        }
    }

    /* Additional Options Section - Responsive Redesign */
    .additional-option-card {
        transition: all 0.3s ease;
        border-radius: 0.5rem;
        background-color: #ffffff;
    }

    .additional-option-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }

    .feedback-options-container {
        background-color: rgba(0, 74, 147, 0.03);
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 0.75rem;
        border-left: 3px solid var(--bs-primary);
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Better visual separation */
    #eventModal .additional-option-card.border-primary {
        background: linear-gradient(to bottom, rgba(0, 74, 147, 0.02), rgba(0, 74, 147, 0.01));
    }

    #eventModal .additional-option-card.border-success {
        background: linear-gradient(to bottom, rgba(25, 135, 84, 0.02), rgba(25, 135, 84, 0.01));
    }

    /* Mobile-first responsive styles for Additional Options */
    @media (max-width: 575.98px) {
        /* Additional Options Section */
        #eventModal #additionalOptionsHeading {
            font-size: 0.95rem !important;
        }

        #eventModal .additional-option-card {
            margin-bottom: 0.75rem;
            border-width: 2px;
        }

        #eventModal .additional-option-card:last-child {
            margin-bottom: 0;
        }

        #eventModal .additional-option-card .card-body {
            padding: 1rem !important;
        }

        /* Feedback Section */
        #eventModal #feedback_checkbox + label {
            font-size: 0.95rem !important;
            padding: 0.5rem 0;
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        #eventModal #feedback_checkbox + label i {
            font-size: 1.1rem;
        }

        #eventModal .feedback-options-container {
            padding: 0.75rem !important;
            margin-top: 0.5rem;
            border-left-width: 3px;
        }

        #eventModal .feedback-options-container .form-check {
            margin-bottom: 0.75rem !important;
            padding: 0.5rem 0;
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        #eventModal .feedback-options-container .form-check:last-of-type {
            margin-bottom: 0.5rem !important;
        }

        #eventModal .feedback-options-container .form-check-label {
            font-size: 0.875rem !important;
            padding-left: 0.75rem;
            width: 100%;
        }

        #eventModal .feedback-options-container .form-check-input {
            width: 1.5em;
            height: 1.5em;
            min-width: 24px;
            min-height: 24px;
            margin-top: 0;
            flex-shrink: 0;
        }

        #eventModal .feedback-options-container .alert {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
            margin-top: 0.5rem;
        }

        #eventModal .feedback-options-container .alert small {
            font-size: 0.75rem !important;
            line-height: 1.4;
        }

        /* Bio Attendance Section */
        #eventModal #bio_attendanceCheckbox + label {
            font-size: 0.95rem !important;
            padding: 0.75rem 0;
            min-height: 44px;
            text-align: center;
            flex-direction: column;
            gap: 0.5rem;
        }

        #eventModal #bio_attendanceCheckbox + label i {
            font-size: 1.5rem;
        }

        #eventModal #bio_attendanceCheckbox + label span {
            font-size: 0.9rem !important;
        }

        /* Form Switch Improvements */
        #eventModal .form-switch .form-check-input {
            width: 3em;
            height: 1.75em;
            min-width: 52px;
            min-height: 32px;
            margin-right: 0.75rem;
        }

        /* Better spacing */
        #eventModal .additional-option-card .card-body {
            padding: 0.875rem !important;
        }

        #eventModal .row.g-3 {
            --bs-gutter-y: 0.75rem;
        }
    }

    /* Small Mobile Devices (400px - 575px) */
    @media (min-width: 400px) and (max-width: 575.98px) {
        #eventModal .additional-option-card .card-body {
            padding: 1rem !important;
        }

        #eventModal .feedback-options-container {
            padding: 0.875rem !important;
        }

        #eventModal #bio_attendanceCheckbox + label {
            flex-direction: row;
            text-align: left;
            justify-content: flex-start;
        }

        #eventModal #bio_attendanceCheckbox + label i {
            font-size: 1.25rem;
        }
    }

    /* Tablet Portrait (576px - 767px) */
    @media (min-width: 576px) and (max-width: 767.98px) {
        #eventModal .additional-option-card {
            margin-bottom: 0;
        }

        #eventModal .feedback-options-container {
            padding: 1rem;
        }

        #eventModal #bio_attendanceCheckbox + label {
            flex-direction: column;
            text-align: center;
        }
    }

    /* Tablet Landscape and Desktop (768px+) */
    @media (min-width: 768px) {
        .feedback-options-container {
            padding: 1.25rem;
            margin-top: 1rem;
        }

        #eventModal .additional-option-card .card-body {
            padding: 1.5rem !important;
        }

        #eventModal #bio_attendanceCheckbox + label {
            flex-direction: column;
            text-align: center;
        }

        #eventModal #bio_attendanceCheckbox + label i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
    }

    /* Additional Desktop Enhancements */
    @media (min-width: 992px) {
        /* Enhanced modal backdrop */
        #eventModal.modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        /* Smooth animations */
        #eventModal .card,
        #eventModal .form-control,
        #eventModal .form-select,
        #eventModal .btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Better focus states */
        #eventModal .form-control:focus,
        #eventModal .form-select:focus {
            outline: none;
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15), 0 2px 8px rgba(0, 74, 147, 0.1);
        }

        /* Enhanced select2 focus */
        #eventModal .select2-container--default.select2-container--focus .select2-selection--single,
        #eventModal .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
        }

        /* Better card header styling */
        #eventModal .card-header {
            position: relative;
        }

        #eventModal .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0, 74, 147, 0.2), transparent);
        }

        /* Enhanced form section spacing */
        #eventModal section.card + section.card {
            margin-top: 0;
        }

        /* Better visual separation */
        #eventModal .modal-body > section:not(:last-child) {
            margin-bottom: 2rem;
            padding-bottom: 0;
        }

        /* Enhanced button group styling */
        #eventModal .btn-group-responsive {
            gap: 0.5rem;
        }

        #eventModal .btn-group-responsive .btn {
            border-radius: 0.5rem;
        }

        /* Better textarea styling */
        #eventModal textarea.form-control {
            font-family: inherit;
            line-height: 1.6;
        }

        /* Enhanced placeholder text */
        #eventModal #groupTypePlaceholder {
            font-size: 1rem;
            padding: 1rem;
        }

        #eventModal #groupTypePlaceholder i {
            font-size: 2rem;
            opacity: 0.5;
        }

        /* Better date input styling */
        #eventModal #start_datetime {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        #eventModal #start_datetime:focus {
            background: #ffffff;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }

        /* Enhanced close button */
        #eventModal .btn-close {
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }

        #eventModal .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        /* Better form validation styling */
        #eventModal .form-control.is-invalid,
        #eventModal .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.15);
        }

        #eventModal .form-control.is-valid,
        #eventModal .form-select.is-valid {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
        }

        /* Enhanced invalid feedback */
        #eventModal .invalid-feedback {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding-left: 0.5rem;
            border-left: 3px solid #dc3545;
        }

        /* Better scrollbar styling */
        #eventModal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        #eventModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #eventModal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        #eventModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    }

    /* Better visual feedback for switches */
    @media (max-width: 767.98px) {
        #eventModal .form-switch .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        #eventModal .form-switch .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }
    }

    /* Improved card borders on mobile */
    @media (max-width: 575.98px) {
        #eventModal .additional-option-card.border-primary {
            border-color: rgba(0, 74, 147, 0.4) !important;
        }

        #eventModal .additional-option-card.border-success {
            border-color: rgba(25, 135, 84, 0.4) !important;
        }
    }

    /* Ensure proper stacking on mobile */
    @media (max-width: 767.98px) {
        #eventModal .row.g-3 > [class*="col-"] {
            margin-bottom: 0.75rem;
        }

        #eventModal .row.g-3 > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
    }
</style>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen modal-fullscreen-md-down modal-xl">
        <form id="eventForm" novalidate>
            @csrf
            <div class="modal-content shadow-lg">
                <!-- Modal Header -->
                <div class="modal-header bg-primary bg-gradient text-white border-0 px-3 px-md-4 py-3 position-relative">
                    <div class="d-flex flex-column flex-sm-row w-100">
                        <h2 class="modal-title h6 mb-0 fw-bold d-flex align-items-center gap-1 gap-sm-2 flex-wrap flex-grow-1" id="eventModalTitle">
                            <span class="d-none d-sm-inline">Add Calendar Event</span>
                            <span class="d-sm-none">Add Event</span>
                        </h2>
                        <div class="d-flex flex-column flex-sm-row">
                            <label for="start_datetime" class="form-label text-white mb-0 small fw-semibold d-flex align-items-center gap-1 flex-shrink-0">
                                <i class="bi bi-calendar-date"></i>
                                <span class="d-none d-md-inline">Date</span>
                            </label>
                            <input type="date" name="start_datetime" id="start_datetime"
                                class="form-control form-control-sm bg-white bg-opacity-90 border-0 text-dark fw-semibold w-100 w-sm-auto flex-shrink-0 shadow-sm"
                                required aria-required="true">
                            <button type="button" class="btn-close btn-close-white ms-auto ms-sm-0 flex-shrink-0" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body bg-light px-3 px-md-4">
                    <!-- Basic Information -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="basicInfoHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="basicInfoHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Basic Information</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="Course_name" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-book text-primary"></i>
                                        <span>Course Name</span>
                                    </label>
                                    <select name="Course_name" id="Course_name" class="form-select form-select-responsive" required
                                        aria-required="true">
                                        <option value="">Select Course</option>
                                        @foreach($courseMaster as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="group_type" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-people text-primary"></i>
                                        <span>Group Type</span>
                                    </label>
                                    <select name="group_type" id="group_type" class="form-select form-select-responsive" required
                                        aria-required="true">
                                        <option value="">Select Group Type</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-tag text-primary"></i>
                                        <span>Group Type Name</span>
                                    </label>
                                    <div id="type_name_container" class="border border-2 border-dashed rounded-3 p-3 p-md-4 bg-light bg-opacity-50 min-h-60">
                                        <div class="text-center text-muted d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2" id="groupTypePlaceholder">
                                            <i class="bi bi-arrow-right-circle fs-6 fs-md-5"></i>
                                            <span class="text-break">Select a Group Type first</span>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-block" id="type_names_error" style="display: none;">
                                        Please select at least one Group Type Name.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="subject_module" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-grid-3x3 text-primary"></i>
                                        <span>Subject Module</span>
                                    </label>
                                    <select name="subject_module" id="subject_module" class="form-select form-select-responsive" required
                                        aria-required="true">
                                        <option value="">Select Subject Module</option>
                                        @foreach($subjects as $subject)
                                        <option value="{{ $subject->pk }}" data-id="{{ $subject->pk }}">
                                            {{ $subject->module_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="subject_name" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-journal-text text-primary"></i>
                                        <span>Subject Name</span>
                                    </label>
                                    <select name="subject_name" id="subject_name" class="form-select form-select-responsive" required
                                        aria-required="true">
                                        <option value="">Select Subject Name</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="topic" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                        <span>Topic</span>
                                    </label>
                                    <textarea name="topic" id="topic" class="form-control form-control-responsive" rows="4"
                                        placeholder="Enter topic details..." required aria-required="true"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Faculty & Venue -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="facultyVenueHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="facultyVenueHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge"></i>
                                <span>Faculty & Venue</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="faculty" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-person-circle text-primary"></i>
                                        <span>Faculty</span>
                                    </label>
                                    <div class="position-relative">
                                        <select name="faculty[]" id="faculty" class="form-select form-select-responsive" required aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Select multiple faculty members
                                    </small>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="faculty_type" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-diagram-3 text-primary"></i>
                                        <span>Faculty Type</span>
                                    </label>
                                    <select name="faculty_type" id="faculty_type" class="form-select form-select-responsive" required
                                        aria-required="true">
                                        <option value="">Select Faculty Type</option>
                                        <option value="1">Internal</option>
                                        <option value="2">Guest</option>
                                        <option value="3">Research</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="vanue" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-geo-alt text-primary"></i>
                                        <span>Location</span>
                                    </label>
                                    <select name="vanue" id="vanue" class="form-select form-select-responsive" required aria-required="true">
                                        <option value="">Select Location</option>
                                        @foreach($venueMaster as $loc)
                                        <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6" id="internalFacultyDiv">
                                    <label for="internal_faculty" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                        <i class="bi bi-person-check text-primary"></i>
                                        <span>Internal Faculty</span>
                                    </label>
                                    <div class="position-relative">
                                        <select name="internal_faculty[]" id="internal_faculty" class="form-select form-select-responsive" required
                                            aria-required="true" multiple>
                                            @foreach($facultyMaster as $faculty)
                                            <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">
                                                {{ $faculty->full_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Select internal faculty for guest sessions
                                    </small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-4" aria-labelledby="scheduleHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="scheduleHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history"></i>
                                <span>Schedule</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <!-- Shift Type -->
                            <div class="mb-3 mb-md-4">
                                <label class="form-label fw-semibold d-block required mb-2 mb-md-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-toggle-on text-primary"></i>
                                    <span>Shift Type</span>
                                </label>
                                <div class="btn-group btn-group-responsive w-100 w-md-auto" role="group" aria-label="Shift type selection">
                                    <input type="radio" class="btn-check" name="shift_type" id="normalShift" value="1"
                                        checked aria-controls="shiftSelect">
                                    <label class="btn btn-outline-primary" for="normalShift">
                                        <i class="bi bi-calendar-check me-1 me-md-2"></i><span class="d-none d-sm-inline">Normal </span>Shift
                                    </label>

                                    <input type="radio" class="btn-check" name="shift_type" id="manualShift" value="2"
                                        aria-controls="manualShiftFields">
                                    <label class="btn btn-outline-primary" for="manualShift">
                                        <i class="bi bi-calendar-event me-1 me-md-2"></i><span class="d-none d-sm-inline">Manual </span>Shift
                                    </label>
                                </div>
                            </div>

                            <!-- Normal Shift -->
                            <div id="shiftSelect" class="mb-3">
                                <label for="shift" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar-range text-primary"></i>
                                    <span>Shift</span>
                                </label>
                                <select name="shift" id="shift" class="form-select form-select-responsive" required aria-required="true">
                                    <option value="">Select Shift</option>
                                    @foreach($classSessionMaster as $shift)
                                    <option value="{{ $shift->shift_time }}">
                                        {{ $shift->shift_name }} ({{ $shift->shift_time }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Manual Shift -->
                            <div id="manualShiftFields" class="d-none">
                                <div class="mb-3 mb-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" id="fullDayCheckbox"
                                            name="fullDayCheckbox" aria-controls="dateTimeFields">
                                        <label class="form-check-label fw-semibold fs-6 fs-md-5" for="fullDayCheckbox">
                                            <i class="bi bi-calendar-day me-2"></i>Full Day Event
                                        </label>
                                    </div>
                                </div>

                                <div id="dateTimeFields">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="start_time" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                                <i class="bi bi-clock text-primary"></i>
                                                <span>Start Time</span>
                                            </label>
                                            <input type="time" name="start_time" id="start_time" class="form-control form-control-responsive"
                                                aria-describedby="startTimeHelp">
                                            <small id="startTimeHelp" class="form-text text-muted d-block mt-1">
                                                <i class="bi bi-info-circle"></i> Must be at least 1 hour from now
                                            </small>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="end_time" class="form-label fw-semibold required d-flex align-items-center gap-2">
                                                <i class="bi bi-clock-fill text-primary"></i>
                                                <span>End Time</span>
                                            </label>
                                            <input type="time" name="end_time" id="end_time" class="form-control form-control-responsive">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Additional Options -->
                    <section class="card border-0 shadow-sm mb-3 mb-md-0" aria-labelledby="additionalOptionsHeading">
                        <div class="card-header bg-white border-bottom border-primary border-2 px-3 px-md-4 py-2 py-md-3">
                            <h3 id="additionalOptionsHeading" class="h6 mb-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-sliders"></i>
                                <span>Additional Options</span>
                            </h3>
                        </div>
                        <div class="card-body px-3 px-md-4 py-3 py-md-4">
                            <div class="row g-3 g-md-4">
                                <!-- Feedback Group -->
                                <div class="col-12 col-md-8">
                                    <div class="card border border-primary border-opacity-50 h-100 shadow-sm additional-option-card">
                                        <div class="card-body p-3 p-md-4">
                                            <!-- Feedback Parent -->
                                            <div class="form-check form-switch mb-3 mb-md-4">
                                                <input class="form-check-input" type="checkbox" id="feedback_checkbox"
                                                    name="feedback_checkbox" value="1" aria-controls="feedbackOptions">
                                                <label class="form-check-label fw-bold text-primary d-flex align-items-center gap-2" for="feedback_checkbox">
                                                    <i class="bi bi-chat-square-text fs-5"></i>
                                                    <span class="fs-6 fs-md-5">Feedback</span>
                                                </label>
                                            </div>

                                            <!-- Feedback Child Options -->
                                            <div id="feedbackOptions" class="feedback-options-container d-none">
                                                <div class="form-check mb-3 mb-md-3">
                                                    <input class="form-check-input" type="checkbox" id="remarkCheckbox"
                                                        name="remarkCheckbox" value="1">
                                                    <label class="form-check-label fw-semibold d-flex align-items-center gap-2" for="remarkCheckbox">
                                                        <i class="bi bi-chat-left-text text-secondary"></i>
                                                        <span>Remark</span>
                                                    </label>
                                                </div>

                                                <div class="form-check mb-3 mb-md-3">
                                                    <input class="form-check-input" type="checkbox" id="ratingCheckbox"
                                                        name="ratingCheckbox" value="1">
                                                    <label class="form-check-label fw-semibold d-flex align-items-center gap-2" for="ratingCheckbox">
                                                        <i class="bi bi-star text-warning"></i>
                                                        <span>Rating</span>
                                                    </label>
                                                </div>

                                                <div class="alert alert-info mb-0 py-2 py-md-2">
                                                    <small class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-info-circle flex-shrink-0"></i>
                                                        <span>Select at least one feedback component.</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio Attendance (Independent) -->
                                <div class="col-12 col-md-4">
                                    <div class="card border border-success border-opacity-50 h-100 shadow-sm additional-option-card">
                                        <div class="card-body d-flex align-items-center justify-content-center p-3 p-md-4">
                                            <div class="form-check form-switch w-100">
                                                <input class="form-check-input" type="checkbox" id="bio_attendanceCheckbox"
                                                    name="bio_attendanceCheckbox" value="1">
                                                <label class="form-check-label fw-bold text-success d-flex align-items-center justify-content-center gap-2 w-100" for="bio_attendanceCheckbox">
                                                    <i class="bi bi-fingerprint fs-5"></i>
                                                    <span class="fs-6 fs-md-5 text-center">Bio Attendance</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top border-2 border-primary border-opacity-25 flex-column flex-sm-row flex-wrap gap-2 py-3 px-3 px-md-4">
                    <button type="button" class="btn btn-outline-secondary btn-responsive w-sm-100 w-auto order-2 order-sm-1" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i><span class="d-none d-sm-inline">Cancel</span><span class="d-sm-none">Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary btn-responsive w-sm-100 w-auto px-3 px-md-4 order-1 order-sm-2" id="submitEventBtn">
                        <i class="bi bi-check-circle me-2"></i>
                        <span class="btn-text">Add Event</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Allow Select2 dropdown to receive focus/clicks inside Bootstrap 5 modal (fix mouse-click on options)
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.prototype._enforceFocus) {
        var _enforceFocus = bootstrap.Modal.prototype._enforceFocus;
        bootstrap.Modal.prototype._enforceFocus = function() {
            if ($(document).find('.select2-container--open').length) return;
            _enforceFocus.apply(this, arguments);
        };
    }

    const feedbackToggle = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    const remark = document.getElementById('remarkCheckbox');
    const rating = document.getElementById('ratingCheckbox');
    const faculty_review_rating = document.getElementById('facultyReviewRatingDiv');

    feedbackToggle.addEventListener('change', function() {
        if (this.checked) {
            feedbackOptions.classList.remove('d-none');
            // if (internalFacultyDiv.style.display === 'block') {
            //     faculty_review_rating.classList.remove('d-none');
            // } else {
            //     faculty_review_rating.classList.add('d-none');
            // }

        } else {
            feedbackOptions.classList.add('d-none');
            remark.checked = false;
            rating.checked = false;
        }
    });
    const internalFacultyDiv = document.getElementById('internalFacultyDiv');
    const facultySelect = document.getElementById('faculty');
    const faculty_type = document.getElementById('faculty_type');
    // internalFacultyDiv.style.display = 'none'; // Hide initially

    // Initialize Select2 when modal is shown (dropdownParent = select's wrapper for consistent positioning)
    $('#eventModal').on('shown.bs.modal', function() {
        // Check if mobile device
        var isMobile = window.innerWidth <= 767;
        var dropdownMaxHeight = isMobile ? '200px' : '300px';

        // Initialize Select2 for faculty field - use immediate parent (position-relative wrapper) so dropdown stays below field
        if (!$('#faculty').hasClass('select2-hidden-accessible')) {
            $('#faculty').select2({
                placeholder: '👤 Select Faculty',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#faculty').parent(),
                theme: 'default',
                selectionCssClass: 'select2-modern',
                dropdownCssClass: 'select2-modern-dropdown',
                dropdownAutoWidth: false,
                closeOnSelect: isMobile ? true : false // Close on mobile for better UX
            });
        }

        // Update Select2 display if value is set programmatically (for edit mode)
        setTimeout(function() {
            if ($('#faculty').hasClass('select2-hidden-accessible') && $('#faculty').val()) {
                $('#faculty').trigger('change.select2');
            }
        }, 100);

        if (!$('#internal_faculty').hasClass('select2-hidden-accessible')) {
            $('#internal_faculty').select2({
                placeholder: '👥 Select Internal Faculty',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#internal_faculty').parent(),
                theme: 'default',
                selectionCssClass: 'select2-modern',
                dropdownCssClass: 'select2-modern-dropdown',
                dropdownAutoWidth: false,
                closeOnSelect: isMobile ? true : false
            });
        }

        // Adjust Select2 dropdown max height on mobile
        if (isMobile) {
            $('.select2-dropdown').css('max-height', dropdownMaxHeight);
            $('.select2-results__options').css('max-height', dropdownMaxHeight);
        }

        // Prevent body scroll when modal is open on mobile
        if (isMobile) {
            $('body').addClass('modal-open-mobile');
        }

    });

    // Destroy Select2 when modal is hidden to prevent conflicts
    $('#eventModal').on('hidden.bs.modal', function() {
        if ($('#faculty').hasClass('select2-hidden-accessible')) {
            $('#faculty').select2('destroy');
        }
        if ($('#internal_faculty').hasClass('select2-hidden-accessible')) {
            $('#internal_faculty').select2('destroy');
        }

        // Remove mobile body class
        $('body').removeClass('modal-open-mobile');
    });

    // Handle window resize for responsive adjustments
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Recalculate Select2 dropdown height if modal is open
            if ($('#eventModal').hasClass('show')) {
                var isMobile = window.innerWidth <= 767;
                var dropdownMaxHeight = isMobile ? '200px' : '300px';
                $('.select2-dropdown').css('max-height', dropdownMaxHeight);
                $('.select2-results__options').css('max-height', dropdownMaxHeight);
            }
        }, 250);
    });

    // Show/hide internal faculty based on faculty_type dropdown
    faculty_type.addEventListener('change', function() {
        const facultyType = this.value;
        updateinternal_faculty_data(facultyType);
    });

    function updateinternal_faculty_data(facultyType) {
        switch (facultyType) {
            case '1': // Internal
            case 1:
                // internalFacultyDiv.style.display = 'none';
                break;
            case '2': // Guest
            case 2:
                internalFacultyDiv.style.display = 'block';
                break;
            default:
                // internalFacultyDiv.style.display = 'none';
        }
    }
});
</script>