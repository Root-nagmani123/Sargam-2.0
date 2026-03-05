<div class="text-center mb-3 mt-2 px-2">
    <div class="mx-auto mb-3 position-relative" style="width: 170px; height: 170px;">
        <img src="{{ get_profile_pic() }}" data-fallback="{{ asset('images/dummypic.jpeg') }}" onerror="this.src=this.dataset.fallback" alt="User Photo"
            class="img-fluid rounded-circle w-100 h-100 object-fit-cover border border-2 border-white border-opacity-25 shadow-sm">
    </div>

    <h5 class="fw-semibold mb-1 text-white lh-sm">{{ Auth::user() ? Auth::user()->first_name : 'Guest' }} {{ Auth::user() ? Auth::user()->last_name : '' }}</h5>
    <p class="small mb-2 text-white text-opacity-75">

        @php
        $roles = session('user_roles', []);
        
        if(in_array('Student-OT', $roles)){
        $service_find = service_find();
        $roles = ['Student-OT ('.$service_find.')'];
        }
        if(!in_array('Student-OT', $roles) && Auth::user() && Auth::user()->user_category == 'E'){


        $roleString = implode(', ', $roles);
        $roles = ['Employee (' . $roleString . ')'];
        }

        @endphp

        <span class="badge bg-white bg-opacity-10 text-white text-wrap fw-normal">{{ !empty($roles) ? implode(', ', $roles) : 'No role assigned' }}</span>

    </p>
    @if(! hasRole('Student-OT') && Auth::user())
    <div class="d-flex flex-wrap align-items-center justify-content-center gap-1 gap-sm-2">
        <a href="{{ route('member.edit', Auth::user()->user_id) }}" class="btn btn-sm btn-link text-white text-decoration-none fw-semibold p-0 py-1">Edit Profile</a>
        <span class="text-white text-opacity-50 d-none d-sm-inline" aria-hidden="true">|</span>
        <a href="{{ route('admin.password.change_password') }}" class="btn btn-sm btn-link text-white text-decoration-none fw-semibold p-0 py-1 small">Change Password</a>
    </div>
    @endif
</div>
<hr class="my-2 border-white border-opacity-10">
