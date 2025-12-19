<!-- Collapse Button with ARIA labels and better focus management -->
<div class="d-flex align-items-center justify-content-end" style="margin-right: 0.5rem;">
    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)" data-bs-toggle="tooltip"
        data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Collapse/Expand Menu Bar"
        aria-label="Toggle menu">

        <i id="sidebarToggleIcon" class="material-icons menu-icon material-symbols-rounded text-primary"
            style="font-size: 40px;">
            keyboard_double_arrow_left
        </i>

    </a>
</div>
<div class="text-center mb-3 mt-2">
    <div class="mx-auto mb-2" style="width:170px; height:170px;">
        <img src="{{ get_profile_pic() }}" onerror="this.src='{{ asset('images/dummypic.jpeg') }}';" alt="User Photo"
            class="img-fluid rounded-circle w-100 h-100 object-fit-cover shadow-sm">
    </div>

    <h5 class="fw-bold mb-0 text-dark">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h5>
    <p class="text-secondary small mb-0 text-dark">
    <p class="text-secondary small mb-0 text-dark">

        @php
        $roles = session('user_roles', []);
        if(in_array('Student-OT', $roles)){
        $service_find = service_find();
        $roles = ['Student-OT ('.$service_find.')'];
        }
        if(!in_array('Student-OT', $roles) && Auth::user()->user_category == 'E'){


        $roleString = implode(', ', $roles);
        $roles = ['Employee (' . $roleString . ')'];
        }

        @endphp

        {{ !empty($roles) ? implode(', ', $roles) : 'No role assigned' }}

    </p>
      @if(! hasRole('Student-OT'))  
    <a href="{{ route('member.edit', Auth::user()->user_id) }}" class="text-danger fw-bold">Edit Profile</a> | <a href="{{ route('admin.password.change_password') }}" class="text-danger fw-bold">Change
        Password</a>
        @endif
</div>
<hr class="my-2">