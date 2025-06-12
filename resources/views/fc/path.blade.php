@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<!-- Main Content Box -->

<main style="flex: 1;">
     <div class="container mt-5">
        <div class="text-center">
            <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Choose Your Path</h4>
            <p class="text-muted" style="font-size: 20px;">Please select the appropriate option based on your current
                status. </p>
        </div>
        <div class="container my-5">
            <div class="row g-4 mt-5">
                <!-- Register Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="icon-circle" style="background-color: #dcfce7;">
                                <i class="material-icons menu-icon fs-3"
                                    style="color: #16a32a;transform: rotateY(180deg);">person_add</i>
                            </div>
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Register for Foundation Course
                            </h5>
                            <p class="text-muted">Registration for the 99th Foundation Course</p>

                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">This path is for:</p>
                            <ul class="text-start">
                                <li>Newly selected IAS/IPS/IFoS officers</li>
                                <li>First-time course participants</li>
                                <li>Officers without prior exemptions <span class="text-danger">*</span></li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li>Personal and educational documents</li>
                                <li>Bank account details</li>
                                <li>Medical information</li>
                                <li>Photo and signature uploads</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Important Dates:</p>
                            <ul class="text-start">
                                <li>Registration opening date: <strong>18th July 2024</strong></li>
                                <li>Last date for registration: <strong>28th July 2024</strong></li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <a href="{{route('fc.register_form')}}" class="btn btn-success custom-btn" style="background-color: #16a32a; border: #16a32a;">Start Registration</a>
                        </div>
                    </div>
                </div>

                <!-- Exemption Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="icon-circle" style="background-color: #fff4e5;">
                                <i class="material-icons menu-icon fs-2" style="color: #ea5803;">article</i>
                            </div>
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Apply for Exemption</h5>
                            <p class="text-muted">Submit an exemption application if you qualify</p>

                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Available exemptions:</p>
                            <ul class="text-start">
                                <li>Appearing in CSE Mains 2024</li>
                                <li>Already attended Foundation Course</li>
                                <li>Medical grounds</li>
                                <li>Opting out after registration</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li>Valid reason for exemption</li>
                                <li>Supporting documents</li>
                                <li>Contact information</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Important Dates:</p>
                            <ul class="text-start">
                                <li>Exemption applications open: <strong>18th July 2024</strong></li>
                                <li>Last date for registration: <strong>5th August 2024</strong></li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <a href="{{route('fc.exemption_category')}}" class="btn btn-warning custom-btn text-white" style="background-color: #ea5803; border: #ea5803;">Apply for Exemption</a>
                        </div>
                    </div>
                </div>

                <!-- Login Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="icon-circle" style="background-color: #e5f2ff;">
                                <i class="material-icons menu-icon fs-2" style="color: #2563eb;">login</i>

                            </div>
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Already Registered?</h5>
                            <p class="text-muted">Login to continue your application or view status</p>
                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Access your:</p>
                            <ul class="text-start">
                                <li style="color: #4b5563; font-size: 14px;">Saved registration progress</li>
                                <li style="color: #4b5563; font-size: 14px;">Application status</li>
                                <li style="color: #4b5563; font-size: 14px;">Document uploads</li>
                                <li style="color: #4b5563; font-size: 14px;">Submission history</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li style="color: #4b5563; font-size: 14px;">Your registered mobile number</li>
                                <li style="color: #4b5563; font-size: 14px;">Application reference ID</li>
                                <li style="color: #4b5563; font-size: 14px;">Web authentication code</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary custom-btn "
                                style="background-color: #2563eb; border: #2563eb;">Login to Dashboard</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-5">
                <div class="col-9">
                    <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Frequently Asked Questions</h4>
                    <span class="text-muted">Find your query from this list of frequently asked questions</span>
                </div>
                <div class="col-3 text-end">
                    <button class="btn btn-outline-primary">View All
                        FAQs</button>
                </div>
            </div>
            <div class="mt-5">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Accordion Item #1
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the first item’s accordion body.</strong> It is shown by default, until
                                the collapse plugin adds the appropriate classes that we use to style each element.
                                These classes control the overall appearance, as well as the showing and hiding via CSS
                                transitions. You can modify any of this with custom CSS or overriding our default
                                variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Accordion Item #2
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the second item’s accordion body.</strong> It is hidden by default,
                                until the collapse plugin adds the appropriate classes that we use to style each
                                element. These classes control the overall appearance, as well as the showing and hiding
                                via CSS transitions. You can modify any of this with custom CSS or overriding our
                                default variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Accordion Item #3
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the third item’s accordion body.</strong> It is hidden by default, until
                                the collapse plugin adds the appropriate classes that we use to style each element.
                                These classes control the overall appearance, as well as the showing and hiding via CSS
                                transitions. You can modify any of this with custom CSS or overriding our default
                                variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection