@extends('admin.layouts.master')

@section('title', 'Country - Sargam | Lal Bahadur')

@section('content')

    <div class="container-fluid">
        <x-breadcrum title="Country" />
        <x-session_message />
        
        <!-- start Vertical Steps Example -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Add Country</h4>
                <hr>
                <form action="{{ route('master.country.store') }}" method="POST">
                    @csrf
                    <div class="row" id="country_fields">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label class="form-label">Country Name :</label>
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="country_name[]"
                                                placeholder="Country Name" value="{{ old('country_name.0') }}">
                                            @error('country_name.0')
                                                <p class="text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ (old('active_inactive', $country->active_inactive ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ (old('active_inactive', $country->active_inactive ?? 1) == 2) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                                    <i class="material-icons menu-icon">send</i> Submit
                                </button>
                            </div>
                </form>




            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>


@endsection

@section('scripts')
<script>
    function addCountryField() {
            var newField = `
    <div class="row">
        <div class="col-sm-10">
           <label class="form-label">Country Name :</label>
           <div class="mb-3">
               <input type="text" class="form-control" name="country_name[]" placeholder="Country Name">
               @error('country_name.*')
                   <p class="text-danger">{{ $message }}</p>
               @enderror
           </div>
       </div>
       <div class="col-sm-2">
           <label class="form-label">&nbsp;</label>
           <div class="mb-3">
               <button onclick="removeCountryField(this);" class="btn btn-danger fw-medium" type="button">
                   <i class="material-icons menu-icon">remove</i>
               </button>
           </div>
       </div>
   </div>
`;
        document.getElementById('country_fields').insertAdjacentHTML('beforeend', newField);
    }

    function removeCountryField(button) {
        button.closest('.row').remove();
    }
</script>

@ensection