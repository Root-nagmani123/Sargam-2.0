<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form id="pathoForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="otcode" value="{{ $ot->otcode }}">
            <input type="hidden" name="course" value="{{ $course }}">
            <div class="row g-2 mb-2">
                <div class="col-md-3"><strong>Name:</strong> {{ $ot->otname }}</div>
                <div class="col-md-2"><strong>OT Code:</strong> {{ $ot->otcode }}</div>
                <div class="col-md-2"><strong>Gender:</strong> {{ $ot->gender }}</div>
                <div class="col-md-2"><strong>DOB:</strong> {{ $ot->dob }}</div>
                <div class="col-md-3"><strong>Mobile:</strong> {{ $ot->mobileno }}</div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-2"><strong>Height:</strong> {{ $height ?: '—' }}</div>
                <div class="col-md-2"><strong>Weight:</strong> {{ $weight ?: '—' }}</div>
                <div class="col-md-2"><strong>BMI:</strong> {{ $bmi ?: '—' }} {{ $bmiClass ? "($bmiClass)" : '' }}</div>
                <div class="col-md-2"><strong>Pulse:</strong> {{ $activities['pulse'] ?? '—' }}</div>
                <div class="col-md-2"><strong>BP:</strong> {{ $activities['bp'] ?? '—' }}</div>
            </div>
            @if($preHistory)
                <div class="alert alert-warning py-2 small">Pre-history exists for this OT.</div>
            @endif

            <div class="mb-2">
                <label class="form-label small">Upload Pathology Report (PDF)</label>
                <input type="file" name="file1" id="file1" class="form-control form-control-sm" accept="application/pdf">
                <div class="small mt-1">
                    @foreach($pathReports as $pr)
                        @if($pr->path_report)
                            <a href="{{ asset($pr->path_report) }}" target="_blank">View report</a><br>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small">Final Findings</label>
                <textarea class="form-control form-control-sm" rows="3" name="textfindings"></textarea>
            </div>
            <div class="small mb-2">
                @foreach($finalFindings as $i => $ff)
                    {{ $i + 1 }} - {{ $ff->findings }}<br>
                @endforeach
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Submit</button>
        </form>
    </div>
</div>
<script>
$('#pathoForm').on('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    $.ajax({
        type: 'POST',
        url: "{{ route('fc-reg.admin.activities.medical.upload') }}",
        data: fd,
        contentType: false,
        processData: false,
        success: function(resp) {
            if (resp.status === 'ok') {
                alert('Saved successfully.');
                location.reload();
            } else {
                alert('Error while saving.');
            }
        }
    });
});
</script>
