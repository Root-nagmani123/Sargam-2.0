@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Chat</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Chat
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden chat-application">
        <div class="d-flex align-items-center justify-content-between gap-6 m-3 d-lg-none">
            <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
                <i class="ti ti-menu-2 fs-5"></i>
            </button>
            <form class="position-relative w-100">
                <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                    placeholder="Search Contact">
                <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
            </form>
        </div>
        <div class="d-flex">
            <div class="w-30 d-none d-lg-block border-end user-chat-box">
                <div class="px-4 pt-9 pb-6">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="position-relative">
                                <img src="{{asset('admin_assets/images/profile/user-1.jpg')}}" alt="user1" width="54" height="54"
                                    class="rounded-circle">
                                <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-semibold mb-2">David McMichael</h6>
                                <p class="mb-0 fs-2">Marketing Director</p>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a class="text-dark fs-6 nav-icon-hover" href="javascript:void(0)" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 border-bottom"
                                        href="javascript:void(0)">
                                        <span>
                                            <i class="ti ti-settings fs-4"></i>
                                        </span>Setting
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)">
                                        <span>
                                            <i class="ti ti-help fs-4"></i>
                                        </span>Help
                                        and feedback
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)">
                                        <span>
                                            <i class="ti ti-layout-board-split fs-4"></i>
                                        </span>Enable split View mode
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 border-bottom"
                                        href="javascript:void(0)">
                                        <span>
                                            <i class="ti ti-table-shortcut fs-4"></i>
                                        </span>Keyboard
                                        shortcut
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)">
                                        <span>
                                            <i class="ti ti-login fs-4"></i>
                                        </span>Sign
                                        Out
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <form class="position-relative mb-4">
                        <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh"
                            placeholder="Search Contact">
                        <i
                            class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                    </form>
                    <div class="dropdown">
                        <a class="text-muted fw-semibold d-flex align-items-center" href="javascript:void(0)"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Recent Chats<i class="ti ti-chevron-down ms-1 fs-5"></i>
                        </a>
                    </div>
                </div>
                <div class="app-chat">
                    <ul class="chat-users mb-0 mh-n100" data-simplebar="init">
                        <div class="simplebar-wrapper" style="margin: 0px;">
                            <div class="simplebar-height-auto-observer-wrapper">
                                <div class="simplebar-height-auto-observer"></div>
                            </div>
                            <div class="simplebar-mask">
                                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                    <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                        aria-label="scrollable content" style="height: auto; overflow: hidden;">
                                        <div class="simplebar-content" style="padding: 0px;">
                                            <li>
                                                <a href="javascript:void(0)"
                                                    class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-user bg-light-subtle"
                                                    id="chat_user_1" data-user-id="1">
                                                    <div class="d-flex align-items-center">
                                                        <span class="position-relative">
                                                            <img src="{{asset('admin_assets/images/profile/user-3.jpg')}}" alt="user1"
                                                                width="48" height="48" class="rounded-circle">
                                                            <span
                                                                class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                                                <span class="visually-hidden">New alerts</span>
                                                            </span>
                                                        </span>
                                                        <div class="ms-3 d-inline-block w-75">
                                                            <h6 class="mb-1 fw-semibold chat-title"
                                                                data-username="James Anderson">
                                                                Michell Flintoff
                                                            </h6>
                                                            <span
                                                                class="fs-3 text-truncate text-body-color d-block">You:
                                                                Yesterdy was great...</span>
                                                        </div>
                                                    </div>
                                                    <p class="fs-2 mb-0 text-muted">15 mins</p>
                                                </a>
                                            </li>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="simplebar-placeholder" style="width: 341px; height: 560px;"></div>
                        </div>
                        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                        </div>
                        <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                            <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                        </div>
                    </ul>
                </div>
            </div>
            <div class="w-70 w-xs-100 chat-container">
                <div class="chat-box-inner-part h-100">
                    <div class="chat-not-selected h-100 d-none">
                        <div class="d-flex align-items-center justify-content-center h-100 p-5">
                            <div class="text-center">
                                <span class="text-primary">
                                    <i class="ti ti-message-dots fs-10"></i>
                                </span>
                                <h6 class="mt-2">Open chat from the list</h6>
                            </div>
                        </div>
                    </div>
                    <div class="chatting-box d-block">
                        <div class="p-9 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                            <div class="hstack gap-3 current-chat-user-name">
                                <div class="position-relative">
                                    <img src="../assets/images/profile/user-3.jpg" alt="user1" width="48" height="48"
                                        class="rounded-circle">
                                    <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                        <span class="visually-hidden">New alerts</span>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-1 name fw-semibold"></h6>
                                    <p class="mb-0">Away</p>
                                </div>
                            </div>
                            <ul class="list-unstyled mb-0 d-flex align-items-center">
                                <li>
                                    <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                        href="javascript:void(0)">
                                        <i class="ti ti-phone"></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                        href="javascript:void(0)">
                                        <i class="ti ti-video"></i>
                                    </a>
                                </li>
                                <li>
                                    <a class="chat-menu text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                        href="javascript:void(0)">
                                        <i class="ti ti-menu-2"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="d-flex parent-chat-box">
                            <div class="chat-box w-xs-100">
                                <div class="chat-box-inner p-9" data-simplebar="init">
                                    <div class="simplebar-wrapper" style="margin: -20px;">
                                        <div class="simplebar-height-auto-observer-wrapper">
                                            <div class="simplebar-height-auto-observer"></div>
                                        </div>
                                        <div class="simplebar-mask">
                                            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                                <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                    aria-label="scrollable content"
                                                    style="height: 100%; overflow: hidden;">
                                                    <div class="simplebar-content" style="padding: 20px;">
                                                        <div class="chat-list chat active-chat" data-user-id="1">
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        I want more detailed information.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark mb-1 d-inline-block rounded-1 fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 fs-3">
                                                                        They got there early, and they got really
                                                                        good seats.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div class="rounded-2 overflow-hidden">
                                                                        <img src="../assets/images/products/product-1.jpg"
                                                                            alt="matdash-img" class="w-100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- 2 -->
                                                        <div class="chat-list chat" data-user-id="2">
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        I want more detailed information.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark mb-1 d-inline-block rounded-1 fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 fs-3">
                                                                        They got there early, and they got really
                                                                        good seats.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div class="rounded-2 overflow-hidden">
                                                                        <img src="../assets/images/products/product-1.jpg"
                                                                            alt="matdash-img" class="w-100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- 3 -->
                                                        <div class="chat-list chat" data-user-id="3">
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        I want more detailed information.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark mb-1 d-inline-block rounded-1 fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 fs-3">
                                                                        They got there early, and they got really
                                                                        good seats.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div class="rounded-2 overflow-hidden">
                                                                        <img src="../assets/images/products/product-1.jpg"
                                                                            alt="matdash-img" class="w-100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- 4 -->
                                                        <div class="chat-list chat" data-user-id="4">
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        I want more detailed information.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark mb-1 d-inline-block rounded-1 fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 fs-3">
                                                                        They got there early, and they got really
                                                                        good seats.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div class="rounded-2 overflow-hidden">
                                                                        <img src="../assets/images/products/product-1.jpg"
                                                                            alt="matdash-img" class="w-100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- 5 -->
                                                        <div class="chat-list chat" data-user-id="5">
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 d-inline-block fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div
                                                                        class="p-2 text-bg-light rounded-1 d-inline-block text-dark fs-3">
                                                                        I want more detailed information.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                                <div class="text-end">
                                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark mb-1 d-inline-block rounded-1 fs-3">
                                                                        If I don’t like something, I’ll stay away
                                                                        from it.
                                                                    </div>
                                                                    <div
                                                                        class="p-2 bg-info-subtle text-dark rounded-1 fs-3">
                                                                        They got there early, and they got really
                                                                        good seats.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                                <img src="../assets/images/profile/user-8.jpg"
                                                                    alt="user8" width="40" height="40"
                                                                    class="rounded-circle">
                                                                <div>
                                                                    <h6 class="fs-2 text-muted">
                                                                        Andrew, 2 hours ago
                                                                    </h6>
                                                                    <div class="rounded-2 overflow-hidden">
                                                                        <img src="../assets/images/products/product-1.jpg"
                                                                            alt="matdash-img" class="w-100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="simplebar-placeholder" style="width: 498px; height: 589px;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                        <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                    </div>
                                    <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                                        <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                                    </div>
                                </div>
                                <div class="px-9 py-6 border-top chat-send-message-footer">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 w-85">
                                            <a class="position-relative nav-icon-hover z-index-5"
                                                href="javascript:void(0)">
                                                <i class="ti ti-mood-smile text-dark bg-hover-primary fs-7"></i>
                                            </a>
                                            <input type="text"
                                                class="form-control message-type-box text-muted border-0 rounded-0 p-0 ms-2"
                                                placeholder="Type a Message" fdprocessedid="0p3op">
                                        </div>
                                        <ul class="list-unstyledn mb-0 d-flex align-items-center">
                                            <li>
                                                <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                                    href="javascript:void(0)">
                                                    <i class="ti ti-photo-plus"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                                    href="javascript:void(0)">
                                                    <i class="ti ti-paperclip"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5"
                                                    href="javascript:void(0)">
                                                    <i class="ti ti-microphone"></i>
                                                </a>
                                            </li>
                                        </ul>
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