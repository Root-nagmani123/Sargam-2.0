@extends('admin.layouts.master')

@section('title', 'Upcoming Events - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Upcoming Events"></x-breadcrum>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
          <div class="row">
            <div class="col-6">
                  <h4>Upcoming Events</h4>
            </div>
            <div class="col-6">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary">Add New Events</button>
                    </div>
            </div>
          </div>
            <hr class="my-2">
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card blog position-relative overflow-hidden hover-img" style="background: url('https://bootstrapdemos.adminmart.com/matdash/dist/assets/images/blog/blog-img9.jpg') no-repeat center center / cover; height: 250px;">
                        <div class="card-body position-relative">
                            <div class="d-flex flex-column justify-content-between h-100">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-title="Mollie Underwood">
                                        <img src="{{ asset('assets/images/profile/user-4.jpg') }}" alt="matdash-img"
                                            class="rounded-circle img-fluid" width="40" height="40">
                                    </div>
                                    <span class="badge text-bg-primary fs-2 fw-semibold">Gadget</span>
                                </div>
                                <div>
                                    <a href="#"
                                        class="fs-7 my-4 fw-semibold text-white d-block lh-sm text-primary">Early Black
                                        Friday
                                        Amazon deals: cheap TVs, headphones, laptops</a>
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="d-flex align-items-center gap-2 text-white fs-3 fw-normal">
                                            <i class="material-icons fs-5">person</i>
                                            6006
                                        </div>
                                        <div class="d-flex align-items-center gap-2 text-white fs-3 fw-normal">
                                            <i class="material-icons fs-5">message</i>
                                            3
                                        </div>
                                        <div class="d-flex align-items-center gap-1 text-white fw-normal ms-auto">
                                            <i class="material-icons fs-5">location_on</i>
                                            <small>Fri, Jan 13</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection