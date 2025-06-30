@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main style="flex: 1;">
        <div class="container mt-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item" style="font-size:20px;">Home</li>
                    <li class="breadcrumb-item" style="font-size:20px;">Exemption Category</li>
                    <li class="breadcrumb-item active" aria-current="page" style="font-size:20px;">Exemption Application</li>
                </ol>
            </nav>

            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <h4 class="fw-bold" style="color: #004a93; font-size: 24px;">
                            {{ $exemption->Exemption_name }}
                        </h4>
                    </div>
                    <p class="text-muted mb-4">Please fill in all required information for your exemption application.</p>

                    <form method="POST" action="{{ route('fc.exemption.apply', $exemption->pk) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="exemption_category" value="{{ $exemption->pk }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ex_mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ex_mobile" name="ex_mobile"
                                       placeholder="Enter mobile number" required>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_web_code" class="form-label">Web Authentication Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reg_web_code" name="reg_web_code"
                                       placeholder="Enter web auth code" required>
                            </div>

                            @if (stripos($exemption->Exemption_name, 'medical') !== false)
                                <div class="col-md-12">
                                    <label for="medical_doc" class="form-label">
                                        Upload Medical Exemption Document <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="medical_doc" name="medical_doc"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">
                                        Supported formats: PDF, Word (.doc, .docx), JPG, JPEG, PNG. Max file size: 5 MB.
                                    </small>
                                </div>
                            @endif

                            {{-- CAPTCHA --}}
                            <div class="col-md-6">
                                <label class="form-label">Verification <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ captcha_src() }}" alt="captcha" id="captchaImage"
                                         class="border rounded" height="40">
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                            onclick="refreshCaptcha()">Refresh</button>
                                </div>
                                <input type="text" class="form-control" name="captcha"
                                       placeholder="Enter captcha code" required>
                            </div>

                            {{-- Declaration --}}
                            <div class="col-md-12">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="declaration" required>
                                    <label class="form-check-label small" for="declaration">
                                        I hereby declare that the information provided above is true and correct. I understand that any false information may lead to rejection of my exemption application.
                                    </label>
                                </div>
                            </div>

                            {{-- Submit and Cancel --}}
                            <div class="col-12 d-flex justify-content-center gap-2 mt-4">
                                <button type="submit" class="btn btn-primary"
                                        style="background-color: #004a93;border-color: #004a93;">Submit Application</button>
                                <a href="{{ route('fc.choose.path') }}"
                                   onclick="return confirm('Are you sure you want to cancel your application? This action cannot be undone.')"
                                   class="btn btn-danger">Cancel Application</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function refreshCaptcha() {
            document.getElementById('captchaImage').src = '{{ captcha_src() }}' + '?' + Math.random();
        }
    </script>
@endsection

@push('scripts')
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('already_applied'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    title: 'Notice',
                    text: '{{ session('already_applied') }}',
                    icon: 'info',
                    confirmButtonColor: '#004a93',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            let errorMessages = '';
            @foreach ($errors->all() as $error)
                errorMessages += `{{ $error }}\n`;
            @endforeach

            Swal.fire({
                title: 'Validation Error',
                text: errorMessages.trim(),
                icon: 'error',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
