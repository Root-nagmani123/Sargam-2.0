<div class="text-center mb-3 mt-2">
                <div class="mx-auto mb-1" style="width:170px; height:170px;">
    <img src="{{ get_profile_pic() }}"
         onerror="this.src='{{ asset('images/dummypic.jpeg') }}';"
         alt="User Photo"
         class="img-fluid rounded-circle w-100 h-100 object-fit-cover shadow-sm">
</div>
 

                <h5 class="fw-bold mb-0">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h5>
                <p class="text-primary small mb-0 mt-2 fw-bold"  >
            
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
                    <a href="" class="text-danger fw-semibold">Edit Profile</a> | <a href="" class="text-danger fw-semibold">Change Password</a>
            </div>
            <hr class="my-2">