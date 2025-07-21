@extends('admin.layouts.master')

@section('title', 'Notifications - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Notifications" />
    <x-session_message />
    <div class="row">
        <div class="col-12">
            <div class="card" id="notification-card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="vstack gap-3">
                        <div class="pb-3 border-bottom">
                            <a href="javascript:void(0)" class="text-dark fw-semibold link-primary fs-5">Admin Summary</a>
                            <a href="javascript:void(0)" class="text-muted fs-4 link-primary ms-3 text-decoration-none text-nowrap text-end">View Details</a>
                        </div>
                        <div class="pb-3 border-bottom">
                            <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                            </ul>
                        </div>
                    </div>
                    <div class="vstack gap-3">
                        <div class="pb-3 border-bottom">
                            <a href="javascript:void(0)" class="text-dark fw-semibold link-primary fs-5">Campus Tweets</a>
                            <a href="javascript:void(0)" class="text-muted fs-4 link-primary ms-3 text-decoration-none text-nowrap text-end">View Details</a>
                        </div>
                        <div class="pb-3 border-bottom">
                            <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                             <li class="list-group-item">
                                You have <a href="javascript:void(0)" class="link-primary">336</a> unread notices and total <a href="javascript:void(0)" class="link-primary">340</a> notices.
                            </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection