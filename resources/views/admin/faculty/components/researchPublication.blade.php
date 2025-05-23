<div>

    <div class="row">
        <div class="col-6">
            
            <x-input
                type="file"
                name="researchpublications"
                label="Research Publications :"
                placeholder="Research Publications"
                formLabelClass="form-label"
                required="true"
                helperSmallText="Please upload your research publications, if any"
                />

                @if( !empty($faculty->Rech_Publi_Upload_path) )
                    <br>
                    <span class="text-info text-bold">Previously Uploaded Document</span>
                    <a href="{{ asset($faculty->Rech_Publi_Upload_path) }}" target="_blank" class="rounded-circle" title="View Document">
                        <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                    </a>
                @endif
        </div>
        <div class="col-6">
            
            <x-input
                type="file"
                name="professionalmemberships"
                label="Professional Memberships :"
                placeholder="Professional Memberships"
                formLabelClass="form-label"
                required="true"
                helperSmallText="Please upload your professional memberships, if any"
                />
                <span>

                
                    @if( !empty($faculty->Professional_Memberships_doc_upload_path) )
                        <br>
                        <span class="text-info text-bold">Previously Uploaded Document</span>
                        <a href="{{ asset($faculty->Professional_Memberships_doc_upload_path) }}" target="_blank" class="rounded-circle" title="View Document">
                            <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                        </a>
                    @endif
                </span>
        </div>
        <div class="col-6 mt-3">
            
            <x-input
                type="file"
                name="recommendationdetails"
                label="Reference/Recommendation Details :"
                placeholder="Reference/Recommendation Details"
                formLabelClass="form-label"
                required="true"
                helperSmallText="Please upload your reference/recommendation details, if any"
                />

            @if( !empty($faculty->Reference_Recommendation) )
                <br>
                <span class="text-info text-bold">Previously Uploaded Document</span>
                <a href="{{ asset($faculty->Reference_Recommendation) }}" target="_blank" class="rounded-circle" title="View Document">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
            @endif
        </div>
        <div class="col-6 mt-3">
            
            <x-input
                type="date"
                name="joiningdate"
                label="Joining Date :"
                placeholder="Joining Date"
                formLabelClass="form-label"
                required="true"
                value="{{ optional($faculty->joining_date)->format('Y-m-d') }}"
            />
            
        </div>
    </div>
</div>