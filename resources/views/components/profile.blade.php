<div class="text-center mb-3">
                <div class="mx-auto mb-1" style="width:170px; height:170px;">
                    <img src="https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="User Photo"
                        class="img-fluid rounded-circle w-100 h-100 object-fit-cover shadow-sm" style="margin-top:-16px;">
                </div>

                <h5 class="fw-bold mb-0 text-white">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h5>
                <p class="text-secondary small mb-0 text-white"><p class="text-secondary small mb-0 text-white">
            
                    @php
                        $roles = session('user_roles', []);
                        if(in_array('Student-OT', $roles)){
                            $service_find = service_find();
                            $roles = ['Student-OT ('.$service_find.')'];
                        }
                        if(!in_array('Student-OT', $roles) && Auth::user()->user_category == 'E'){
                            $designation = employee_designation_search();
                            print_r($designation);
                            $roles = ['Employee ('.$designation.')'];
                        }
                      
                    @endphp

                    {{ !empty($roles) ? implode(', ', $roles) : 'No role assigned' }}

                    </p>
            </div>