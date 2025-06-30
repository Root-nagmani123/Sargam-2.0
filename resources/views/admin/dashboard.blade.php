@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4 pb-0" data-simplebar="init">
                    <div class="simplebar-wrapper" style="margin: -24px -24px 0px;">
                        <div class="simplebar-height-auto-observer-wrapper">
                            <div class="simplebar-height-auto-observer"></div>
                        </div>
                        <div class="simplebar-mask">
                            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                    aria-label="scrollable content" style="height: auto; overflow: hidden;">
                                    <div class="simplebar-content" style="padding: 24px 24px 0px;">
                                        <div class="row flex-nowrap">
                                            <div class="col">
                                                <div class="card primary-gradient">
                                                    <div class="card-body text-center px-9 pb-4">
                                                        <div
                                                            class="d-flex align-items-center justify-content-center round-48 rounded text-bg-primary flex-shrink-0 mb-3 mx-auto">
                                                            <iconify-icon icon="solar:dollar-minimalistic-linear"
                                                                class="fs-7 text-white"></iconify-icon>
                                                        </div>
                                                        <h6 class="fw-normal fs-3 mb-1">Total Orders</h6>
                                                        <h4
                                                            class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                                            16,689</h4>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-white fs-2 fw-semibold text-nowrap">View
                                                            Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="card warning-gradient">
                                                    <div class="card-body text-center px-9 pb-4">
                                                        <div
                                                            class="d-flex align-items-center justify-content-center round-48 rounded text-bg-warning flex-shrink-0 mb-3 mx-auto">
                                                            <iconify-icon icon="solar:recive-twice-square-linear"
                                                                class="fs-7 text-white"></iconify-icon>
                                                        </div>
                                                        <h6 class="fw-normal fs-3 mb-1">Return Item</h6>
                                                        <h4
                                                            class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                                            148</h4>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-white fs-2 fw-semibold text-nowrap">View
                                                            Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="card secondary-gradient">
                                                    <div class="card-body text-center px-9 pb-4">
                                                        <div
                                                            class="d-flex align-items-center justify-content-center round-48 rounded text-bg-secondary flex-shrink-0 mb-3 mx-auto">
                                                            <iconify-icon icon="ic:outline-backpack"
                                                                class="fs-7 text-white"></iconify-icon>
                                                        </div>
                                                        <h6 class="fw-normal fs-3 mb-1">Annual Budget</h6>
                                                        <h4
                                                            class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                                            $156K</h4>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-white fs-2 fw-semibold text-nowrap">View
                                                            Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="card danger-gradient">
                                                    <div class="card-body text-center px-9 pb-4">
                                                        <div
                                                            class="d-flex align-items-center justify-content-center round-48 rounded text-bg-danger flex-shrink-0 mb-3 mx-auto">
                                                            <iconify-icon icon="ic:baseline-sync-problem"
                                                                class="fs-7 text-white"></iconify-icon>
                                                        </div>
                                                        <h6 class="fw-normal fs-3 mb-1">Cancel Orders</h6>
                                                        <h4
                                                            class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                                            64</h4>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-white fs-2 fw-semibold text-nowrap">View
                                                            Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="card success-gradient">
                                                    <div class="card-body text-center px-9 pb-4">
                                                        <div
                                                            class="d-flex align-items-center justify-content-center round-48 rounded text-bg-success flex-shrink-0 mb-3 mx-auto">
                                                            <iconify-icon icon="ic:outline-forest"
                                                                class="fs-7 text-white"></iconify-icon>
                                                        </div>
                                                        <h6 class="fw-normal fs-3 mb-1">Total Income</h6>
                                                        <h4
                                                            class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                                            $36,715</h4>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-white fs-2 fw-semibold text-nowrap">View
                                                            Details</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="simplebar-placeholder" style="width: 1140px; height: 279px;"></div>
                    </div>
                    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                    </div>
                    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                        <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection