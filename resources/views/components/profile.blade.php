<div class="text-center mb-3 mt-2">
    <div class="mx-auto mb-2" style="width:170px; height:170px;">
        <img src="{{ get_profile_pic() }}" onerror="this.src='{{ asset('images/dummypic.jpeg') }}';" alt="User Photo"
            class="img-fluid rounded-circle w-100 h-100">
    </div>

    <h5 class="fw-bold mb-0 text-white">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h5>
    <p class="text-secondary small mb-0 text-white">
    <p class="text-secondary small mb-0 text-white">

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
    <a href="{{ route('member.edit', Auth::user()->user_id) }}" class="text-white fw-bold">Edit Profile</a><span class="mx-2 text-white">|</span> <a href="{{ route('admin.password.change_password') }}" class="text-white fw-bold">Change
        Password</a>
        @endif
</div>
<hr class="my-2">
