@extends('admin.layouts.master')

@section('title', ($type == 'memo' ? 'Memo Conversation' : 'Notice Conversation') . ' - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="{{ $type == 'memo' ? 'Memo Conversation' : 'Notice Conversation' }}" />
    <x-session_message />
    @if(session('error') == 'document_error')
    <div class="alert alert-danger">
        {{ 'The document size exceeds the maximum limit of 1 MB or invalid file type. Please upload a valid document.' }}
    </div>
    @endif
    <div class="card" >
        <div class="card-body">
            <div class="gap-2 text-end">
                    <a href="{{route('memo.notice.management.index')}}" class="btn btn-outline-secondary">Back</a>
                </div>
            <h5 class="text-center fw-bold mb-3">{{ $template_details->course_name ?? 'Course Name' }}</h5>
            <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr>

            <p class="mb-1">{{ $type == 'memo' ? 'SHOW CAUSE MEMO' : 'SHOW CAUSE NOTICE' }}</p>
            <p><strong>Date:</strong> {{ $template_details && $template_details->session_date ? \Carbon\Carbon::parse($template_details->session_date)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }} </p>

            <p>It has been brought to the notice of the undersigned that you were absent without prior authorization
                from
                following session(s)...</p>

            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>No. of Session(s)</th>
                            <th>Topics</th>
                            <th>
                                Venue
                            </th>
                            <th>Session(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $template_details && $template_details->session_date ? \Carbon\Carbon::parse($template_details->session_date)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }}</td>
                            <td>1</td>
                            <td>{{ $template_details->subject_topic ?? 'Topic Name' }}</td>
                            <td>{{ $template_details->venue_name ?? 'Venue' }}</td>
                            <td>{{ $template_details->session_time ?? '06:00-07:00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-4">
                {!! $template_details->content ?? '<p>It has been brought to the notice of the undersigned that you were absent without prior authorization from following session(s).</p>' !!}
            </div>

            <p>
                <strong>{{ $template_details->display_name ?? 'Student Name' }}, {{ $template_details->generated_OT_code ?? 'OT Code' }}</strong><br>
                Remarks: {{ $type == 'memo' ? 'Show Cause Memo' : 'Show Cause Notice' }} for {{ $template_details && $template_details->session_date ? \Carbon\Carbon::parse($template_details->session_date)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }}
            </p>

            <div class="text-end">
                @if(!empty($template_details->signature_image))
                    <img src="{{ Storage::url($template_details->signature_image) }}" alt="Signature" style="max-height:60px;display:block;margin-left:auto;margin-bottom:4px;">
                @endif
                <strong>{{ $template_details->director_name ?? 'Director Name' }}</strong><br>{{ $template_details->director_designation ?? 'Director Designation' }}
            </div>

            <!-- Exemption Table -->
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Total Exemption Have</th>
                            <th>Total Exemption Taken</th>
                            <th>MOD on SAT / SUN</th>
                            <th>Exemption Balance (if any)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>3</td>
                            <td>0</td>
                            <td>0</td>
                            <td>3</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Conversation Section -->
            <h6 class="fw-bold">Conversation</h6>
            <div class="table-responsive mb-4">
                <table class="table" id="conversationTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Conversation</th>
                            <th>Date & Time</th>
                            <th>Delete</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody id="conversationBody">
                        @forelse ($memoNotice as $row)
                        <tr data-pk="{{ $row->pk }}">
                            <td>{{ $row->display_name ?? 'N/A' }}</td>
                            <td>{{ $row->student_decip_incharge_msg }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->created_date ?? 'now', 'UTC')->timezone('Asia/Kolkata')->format('d-m-Y h:i A') }}</td>
                            <td>
                                @if($row->notice_status == 1)
                                <form action="{{ route('memo.notice.management.noticedeleteMessage', ['id' => $row->pk, 'type' => $type]) }}"
                                    method="POST" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($row->doc_upload)
                                <a href="{{ asset('storage/' . $row->doc_upload) }}" target="_blank">View</a>
                                @else ---
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyRow"><td colspan="5" class="text-center text-muted">No conversation found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Reply Form -->
          
           @if($memoNotice->isEmpty() || $memoNotice->first()->notice_status == 1)

                    <div class="border p-3 bg-light rounded">
                    <form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                        action="{{ route('memo.notice.management.memo_notice_conversation') }}">
                    @csrf

                    <div class="row g-3 mb-3">

                        <!-- Hidden -->
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="memo_notice_id" value="{{ $id }}">

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="current_date" name="date" readonly>
                        </div>

                        <!-- Time -->
                        <div class="col-md-6">
                            <label class="form-label">Select Time</label>
                            <input type="time" class="form-control" id="current_time" name="time" readonly>
                        </div>

                        <!-- Message -->
                        <div class="col-12">
                            <label class="form-label">Message *</label>
                            <textarea class="form-control" rows="4" name="message">{{ old('message') }}</textarea>
                            @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <!-- Upload -->
                        <div class="col-6">
                            <label class="form-label">Upload Document</label>
                            <input type="file" class="form-control" id="conv_doc_input" name="document" accept=".jpg,.jpeg,.png,.pdf">
                            <div id="conv_file_preview" class="mt-1"></div>
                            <small class="text-muted">Allowed: JPG, PNG, PDF · Max 2 MB</small>
                        </div>

                        <!-- Status -->
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>OPEN</option>
                                <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>CLOSED</option>
                            </select>
                        </div>

                    </div>

                    {{-- ================= CONCLUSION SECTION ================= --}}
                    @if($type == 'memo' || $type == 'notice')

                    <div class="row g-3 mt-3" id="conclusion_div"
                        style="{{ old('status') == 2 ? '' : 'display:none;' }}">

                        <!-- Conclusion Type -->
                        <div class="col-6">
                            <label class="form-label">Conclusion Type</label>
                            <select class="form-select" name="conclusion_type" id="conclusion_type">
                                <option value="">Select Conclusion Type</option>
                                @foreach($memo_conclusion_master as $conclusion)
                                    <option value="{{ $conclusion->pk }}"
                                            {{ old('conclusion_type') == $conclusion->pk ? 'selected' : '' }}>
                                        {{ $conclusion->discussion_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Mark of Deduction -->
                        <div class="col-6">
                            <div id="deduction_div"
                                style="{{ old('conclusion_type') == 2 ? '' : 'display:none;' }}">
                                <label class="form-label">Mark of Deduction</label>
                                <input type="number" class="form-control"
                                    name="mark_of_deduction"
                                    step="0.01" min="0"
                                    value="{{ old('mark_of_deduction') }}">
                                @error('mark_of_deduction')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Conclusion Remark -->
                        <div class="col-12">
                            <label class="form-label">Conclusion Remark</label>
                            <textarea class="form-control" rows="3"
                                    name="conclusion_remark">{{ old('conclusion_remark') }}</textarea>
                        </div>

                    </div>
                    @endif

                    <hr>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Send</button>

                        <a href="{{ route('memo.notice.management.index') }}" class="btn btn-secondary">Back</a>
                    </div>

                    </form>
                    </div>
                    @endif
                    @if((isset($memoNotice->first()->communication_status) && ($memoNotice->first()->communication_status == 2)))
                        <div class="alert alert-warning mt-3">
                    <strong>Memo Closed:</strong> This memo has been closed. You cannot reply to it.
                </div>
                    @elseif( isset($memoNotice->first()->notice_status) && ($memoNotice->first()->notice_status == 2) )
                <div class="alert alert-warning mt-3">
                    <strong>Notice Closed:</strong> This notice has been closed. You cannot reply to it.
                </div>
                 @endif

                @php
                    $isClosed = (isset($memoNotice->first()->communication_status) && $memoNotice->first()->communication_status == 2)
                             || (isset($memoNotice->first()->notice_status) && $memoNotice->first()->notice_status == 2);
                    $conclusionTypeName = '';
                    if (!empty($template_details->conclusion_type_pk)) {
                        $conclusionTypeName = $memo_conclusion_master->firstWhere('pk', $template_details->conclusion_type_pk)->discussion_name ?? '';
                    }
                @endphp

                @if($isClosed && (!empty($template_details->conclusion_type_pk) || !empty($template_details->conclusion_remark)))
                <div class="card border-secondary mt-3">
                    <div class="card-header fw-semibold bg-light">Conclusion Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($conclusionTypeName)
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Conclusion Type</label>
                                <p class="mb-0 fw-semibold">{{ $conclusionTypeName }}</p>
                            </div>
                            @endif
                            @if(!empty($template_details->mark_of_deduction))
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Mark of Deduction</label>
                                <p class="mb-0 fw-semibold">{{ $template_details->mark_of_deduction }}</p>
                            </div>
                            @endif
                            @if(!empty($template_details->conclusion_remark))
                            <div class="col-12">
                                <label class="form-label text-muted mb-1">Conclusion Remark</label>
                                <p class="mb-0">{{ $template_details->conclusion_remark }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endsection
    @section('scripts')
<script>
/* ── Real-time chat polling ── */
(function () {
    var pollUrl  = "{{ route('memo.notice.management.getNewMessages', [$id, $type]) }}";
    var body     = document.getElementById('conversationBody');
    var lastPk   = 0;

    // Seed lastPk from currently rendered rows
    body.querySelectorAll('tr[data-pk]').forEach(function (r) {
        var pk = parseInt(r.getAttribute('data-pk'), 10);
        if (pk > lastPk) lastPk = pk;
    });

    function escHtml(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function appendMessage(msg) {
        var emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        var tr = document.createElement('tr');
        tr.setAttribute('data-pk', msg.pk);

        var docCell = msg.doc_upload
            ? '<a href="/storage/' + escHtml(msg.doc_upload) + '" target="_blank">View</a>'
            : '---';

        tr.innerHTML =
            '<td>' + escHtml(msg.display_name || 'N/A') + '</td>' +
            '<td>' + escHtml(msg.student_decip_incharge_msg || '') + '</td>' +
            '<td>' + escHtml(msg.formatted_date || '') + '</td>' +
            '<td><span class="text-muted">N/A</span></td>' +
            '<td>' + docCell + '</td>';

        body.appendChild(tr);
        if (lastPk < msg.pk) lastPk = msg.pk;
    }

    function poll() {
        fetch(pollUrl + '?last_pk=' + lastPk, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (msgs) {
            msgs.forEach(appendMessage);
        })
        .catch(function () {}); // silent on network error
    }

    setInterval(poll, 4000);
})();
/* ── end polling ── */

document.addEventListener('DOMContentLoaded', function () {

    const status = document.getElementById('status');
    const conclusionDiv = document.getElementById('conclusion_div');
    const conclusionType = document.getElementById('conclusion_type');
    const deductionDiv = document.getElementById('deduction_div');

    function toggleConclusion() {
        if (status && status.value == '2') {
            conclusionDiv.style.display = 'flex';
        } else {
            conclusionDiv.style.display = 'none';
        }
    }

    function toggleDeduction() {
        if (!conclusionType) return;

        const selectedText = conclusionType.options[conclusionType.selectedIndex].text;

        if (selectedText === 'Marks Deduction') {
            deductionDiv.style.display = 'block';
        } else {
            deductionDiv.style.display = 'none';
            if (deductionDiv.querySelector('input')) {
                deductionDiv.querySelector('input').value = '';
            }
        }
    }

    // Events
    if (status) status.addEventListener('change', toggleConclusion);
    if (conclusionType) conclusionType.addEventListener('change', toggleDeduction);

    // On page load
    toggleConclusion();
    toggleDeduction();

    // File preview with type & size
    const convDocInput = document.getElementById('conv_doc_input');
    const convFilePreview = document.getElementById('conv_file_preview');
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
    const MAX_SIZE = 2 * 1024 * 1024;

    if (convDocInput) {
        convDocInput.addEventListener('change', function () {
            convFilePreview.innerHTML = '';
            const file = this.files[0];
            if (!file) return;

            const ext = file.name.split('.').pop().toUpperCase();
            const sizeKB = (file.size / 1024).toFixed(1);
            const sizeLabel = file.size >= 1024 * 1024
                ? (file.size / (1024 * 1024)).toFixed(2) + ' MB'
                : sizeKB + ' KB';
            const isValidType = ALLOWED_TYPES.includes(file.type);
            const isValidSize = file.size <= MAX_SIZE;

            if (!isValidType || !isValidSize) {
                const msg = !isValidType
                    ? 'Invalid file type. Only JPG, PNG, PDF allowed.'
                    : 'File too large. Max 2 MB allowed.';
                convFilePreview.innerHTML = `<span class="badge bg-danger mt-1">⚠ ${msg}</span>`;
                convDocInput.value = '';
                return;
            }

            convFilePreview.innerHTML = `
                <span class="badge bg-light border text-dark mt-1" style="font-size:.78rem;">
                    📎 ${file.name} &nbsp;|&nbsp; <strong>${ext}</strong> &nbsp;|&nbsp; ${sizeLabel}
                </span>`;
        });
    }

    // Set current date & time
    const now = new Date();
    document.getElementById('current_date').value = now.toISOString().split('T')[0];
    document.getElementById('current_time').value =
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0');

});
</script>
    @endsection