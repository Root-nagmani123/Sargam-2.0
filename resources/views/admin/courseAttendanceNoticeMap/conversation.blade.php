@extends('admin.layouts.master')

@section('title', 'Notice Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Notice Conversation" />
    <x-session_message />
    @if(session('error') == 'document_error')
    <div class="alert alert-danger">
        {{ 'The document size exceeds the maximum limit of 1 MB or invalid file type. Please upload a valid document.' }}
    </div>
    @endif
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="gap-2 text-end">
                    <a href="{{route('memo.notice.management.index')}}" class="btn btn-outline-secondary">Back</a>
                </div>
            <h5 class="text-center fw-bold mb-3">{{ $template_details->course_name ?? 'Course Name' }}</h5>
            <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr>

            <p class="mb-1">SHOW CAUSE NOTICE</p>
            <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }} </p>

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
                            <td>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
                            <td>1</td>
                            <td>{{ $template_details->subject_topic ?? 'Topic Name' }}</td>
                            <td>{{ $template_details->venue_name ?? 'Venue' }}</td>
                            <td>{{ $template_details->session_time ?? '06:00-07:00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-4">
                <p class="fw-bold">You are advised to do the following:</p>
                <ul>
                    <li>Reply to this Memo online through this <a href="#">conversation</a></li>
                    <li>Appear <a href="#">in person before the undersigned at 1800 hrs on next working day</a></li>
                </ul>
                <p>{!! $template_details->content ?? '' !!}</p>
            </div>

            <p><strong>{{ $template_details->display_name ?? 'Student Name' }}, {{ $template_details->generated_OT_code ?? 'OT Code' }}</strong><br>
                Remarks: Show Cause Notice for {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>

            <p class="text-end"><strong>{{ $template_details->director_name ?? 'Director Name' }}</strong><br>{{ $template_details->director_designation ?? 'Director Designation' }}</p>

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
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Conversation</th>
                            <th>Date & Time</th>
                            <th>Delete</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memoNotice as $row)
                        <tr>
                            <td class="{{ $loop->first ? ' ' : '' }}">
                                {{ $row->display_name ?? 'N/A' }}
                            </td>

                            <td class="{{ $loop->first ? '' : '' }}">
                                {{ $row->student_decip_incharge_msg }}
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($row->created_date ?? 'now', 'UTC')->timezone('Asia/Kolkata')->format('d-m-Y h:i A') }}
                            </td>

                            <td>
                                {{-- Add delete button here if needed --}}
                                @if($row->notice_status == 1)
                                <form
                                    action="{{ route('memo.notice.management.noticedeleteMessage', ['id' => $row->pk, 'type' =>  $type ]) }}"
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
                                @else
                                ---
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No conversation found.</td>
                        </tr>
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
                            <input type="file" class="form-control" name="document" accept=".jpg,.jpeg,.png,.pdf">
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
            </div>
        </div>
    </div>
    @endsection
    @section('scripts')
<script>
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

    // Set current date & time
    const now = new Date();
    document.getElementById('current_date').value = now.toISOString().split('T')[0];
    document.getElementById('current_time').value =
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0');

});
</script>
    @endsection