{{--
    view_otreport.php → medical/report.blade.php
    This view is AJAX-loaded into #spanotreport on the medical index page.
    BMI colour logic exactly as original.
--}}
<form id="pathoForm" enctype="multipart/form-data">
@csrf
<input type="hidden" name="otcode" value="{{ $ot->otcode }}">
<input type="hidden" name="course" value="{{ $course }}">

<br>
<table cellpadding="10" cellspacing="10" width="100%" class="tbl">

{{-- ── Personal Details ─────────────────────────────────────────────────── --}}
<tr>
    <td>Name of Officer Trainee</td><td>:</td><td>{{ $ot->otname }}</td>
    <td>OT Code</td><td>:</td><td>{{ $ot->otcode }}</td>
    <td>Gender</td><td>:</td><td>{{ $ot->gender }}</td>
    <td>Date of Birth</td><td>:</td><td>{{ $ot->dob }}</td>
    <td>Age</td><td>:</td><td>{{ $ot->age }}</td>
</tr>
<tr>
    <td>Father's Name</td><td>:</td><td>{{ $ot->father_name }}</td>
    <td>Mobile No</td><td>:</td><td>{{ $ot->mobileno }}</td>
    <td>Blood Group</td><td>:</td><td>{{ $ot->blood_group }}</td>
    <td>Aadhaar No.</td><td>:</td><td>{{ $ot->aadhar_no }}</td>
    <td>ABHA Id</td><td>:</td><td>{{ $ot->abha_id }}</td>
</tr>

{{-- ── Pre-History ──────────────────────────────────────────────────────── --}}
<tr><th colspan="15" style="height:30px;text-align:center;">Previous History (if any)</th></tr>
<tr>
    <td colspan="4">History of Allergy/Previous illness/injury/Disability/Asthma/Slip disc/Blood transfusion</td>
    <td>:</td><td colspan="10">{{ $preHistory->allergy_illness ?? '' }}</td>
</tr>
<tr>
    <td colspan="4">Any History of Prolonged Medication intake</td>
    <td>:</td><td colspan="10">{{ $preHistory->prolonged_medication ?? '' }}</td>
</tr>
<tr>
    <td colspan="4">Have you ever been admitted in hospital?</td>
    <td>:</td><td colspan="10">{{ $preHistory->hospital_history ?? '' }}</td>
</tr>
<tr>
    <td colspan="4">Have you ever suffered any high altitude illness?</td>
    <td>:</td><td colspan="10">{{ $preHistory->altitude_illness ?? '' }}</td>
</tr>
<tr>
    <td colspan="4">Any additional significant information about health status</td>
    <td>:</td><td colspan="10">{{ $preHistory->additional_info ?? '' }}</td>
</tr>
@if($preHistory && $preHistory->doc_path)
<tr>
    <td colspan="4">Documents of previous illness/Disability</td>
    <td>:</td>
    <td colspan="10"><a href="{{ asset($preHistory->doc_path) }}" target="_blank">View</a></td>
</tr>
@endif

{{-- ── Clinical Examination ─────────────────────────────────────────────── --}}
<tr><th colspan="15" style="height:30px;text-align:center;">Clinical Examination (Conducted by Medical Officer)</th></tr>
<tr>
    <td>Height (cms.)</td><td>:</td><td>{{ $height ?: '—' }}</td>
    <td>Weight (Kgs.)</td><td>:</td><td>{{ $weight ?: '—' }}</td>

    {{-- BMI with colour coding — exact from view_otreport.php --}}
    <td>BMI</td><td>:</td>
    @if($bmi > 0)
    <td colspan="2"
        @if($bmi < 18.5) style="background-color:yellow;color:black;"
        @elseif($bmi < 25) style="background-color:green;color:black;"
        @elseif($bmi < 30) style="background-color:orange;color:black;"
        @else style="background-color:red;color:black;" @endif>
        {{ $bmi }} ({{ $bmiClass['label'] }})
    </td>
    @else
    <td colspan="2">—</td>
    @endif

    <td>Pulse</td><td>:</td><td>{{ $activities['pulse'] ?? '—' }}</td>
</tr>
<tr>
    <td>Blood Pressure</td><td>:</td><td>{{ $activities['bp'] ?? '—' }}</td>
    <td>Consultant Remarks</td><td>:</td><td>{{ $activities['preremarks'] ?? '—' }}</td>
</tr>

{{-- ── Pathology Investigation ──────────────────────────────────────────── --}}
<tr><th colspan="15" style="height:30px;text-align:center;">Pathology Investigation (Conducted by AIIMS Rishikesh)</th></tr>
<tr>
    <td colspan="10">
        Upload Pathology Investigation Report:
        <input type="file" name="file1" id="file1" accept="application/pdf">
    </td>
    <td colspan="5">
        @foreach($pathReports as $pr)
            @if($pr->path_report)
                <a href="{{ asset($pr->path_report) }}" target="_blank">
                    View ({{ $pr->submit_dt ? $pr->submit_dt->format('d-m-Y H:i') : '' }})
                </a><br>
            @endif
        @endforeach
    </td>
</tr>

{{-- ── Final Findings ───────────────────────────────────────────────────── --}}
<tr><th colspan="15" style="height:30px;text-align:center;">Any significant finding/advice/investigations</th></tr>
<tr>
    <td colspan="15">
        <textarea cols="100" rows="5" name="textfindings" id="textfindings"></textarea>
    </td>
</tr>
<tr>
    <td colspan="15">
        @foreach($finalFindings as $i => $ff)
            {{ $i+1 }}- {{ $ff->findings }}
            ({{ $ff->submit_dt ? $ff->submit_dt->format('d-m-Y H:i') : '' }})<br><br>
        @endforeach
    </td>
</tr>
<tr>
    <td colspan="15" style="text-align:center;">
        <input type="submit" value="Submit Form"
            style="height:40px;width:200px;background:lightblue;">
    </td>
</tr>

</table>
</form>

@push('scripts')
<script>
$('#file1').on('change', function() {
    var file = this.files[0];
    if (file && file.type !== 'application/pdf') {
        alert('Please select a valid PDF file.');
        $(this).val('');
    }
});
</script>
@endpush
