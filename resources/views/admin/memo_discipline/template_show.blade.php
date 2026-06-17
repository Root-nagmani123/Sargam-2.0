@extends('admin.layouts.master')

@section('title', ' Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Conversation" />
    <x-session_message />
    @if(session('error') == 'document_error')
    <div class="alert alert-danger">
        {{ 'The document size exceeds the maximum limit of 1 MB or invalid file type. Please upload a valid document.' }}
    </div>
    @endif
    <div class="card" >
        <div class="card-body">
            <div class="gap-2 text-end">
                    <a href="{{ route('memo.discipline.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            <h5 class="text-center fw-bold mb-3">{{ $memo->course->course_name ?? 'Course Name' }}</h5>
            <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr>

            <p class="mb-1">SHOW CAUSE Memo</p>
            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($memo->date)->format('d/m/Y') }}</p>

            <p>It has been brought to the notice of the undersigned that you were absent without prior authorization
                from
                following session(s)...</p>

           {{-- <div class="table-responsive mb-3">
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
                            <td>{{ $memo->subject_topic ?? 'Topic Name' }}</td>
                            <td>{{ $memo->venue_name ?? 'Venue' }}</td>
                            <td>{{ $memo->session_time ?? '06:00-07:00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div> --}}

            <div class="mb-4">
                <p class="fw-bold">You are advised to do the following:</p>
                <ul>
                    <li>Reply to this Memo online through this <a href="#">conversation</a></li>
                    <li>Appear <a href="#">in person before the undersigned at 1800 hrs on next working day</a></li>
                </ul>
                <p>{!! $memo->template->content ?? '' !!}</p>
            </div>

            <p><strong>{{ $memo->student->display_name ?? 'Student Name' }}, {{ $memo->student->generated_OT_code ?? 'OT Code' }}</strong><br>
                Remarks: Show Cause Memo for {{ \Carbon\Carbon::parse($memo->date)->format('d/m/Y') }}</p>

            <div class="text-end">
                @if(!empty($memo->template->signature_image))
                    <img src="{{ Storage::url($memo->template->signature_image) }}" alt="Signature" style="max-height:60px;display:block;margin-left:auto;margin-bottom:4px;">
                @endif
                <strong>{{ $memo->template->director_name ?? 'Director Name' }}</strong><br>{{ $memo->template->director_designation ?? 'Director Designation' }}
            </div>

            <!-- Exemption Table -->
           
            <!-- Conversation Section -->
            <h6 class="fw-bold">Conversation</h6>
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Conversation</th>
                            <th>Date & Time</th>
                            <!-- <th>Delete</th> -->
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody id="disciplineConvBody">
                       @forelse ($memo->messages as $row)
                            <tr data-pk="{{ $row->pk }}">
                                <td>{{ $row->student->display_name ?? 'Admin' }}</td>
                                <td>{{ $row->student_decip_incharge_msg }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}</td>
                                <td>
                                    @if ($row->doc_upload)
                                        <a href="{{ asset('storage/'.$row->doc_upload) }}" target="_blank">View</a>
                                    @else ---
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr id="disciplineEmptyRow">
                                <td colspan="4" class="text-center text-muted">No conversation found.</td>
                            </tr>
                            @endforelse
                    </tbody>

                </table>
            </div>

            <!-- Reply Form -->
            @if($memo->status == 2)
            <div class="border p-3 bg-light rounded">
                <form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                    action="{{ route('memo.discipline.conversation.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <input type="hidden" name="role_type" value="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Super Admin') || hasRole('Training Induction Admin')) ? 'f' : 'OT' }}">
                            <input type="hidden" name="created_by" value="{{ auth()->user()->user_id }}">
                            <input type="hidden" name="memo_discipline_id" value="{{ $memo->pk }}">
                            <label class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="current_date" name="date" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Time</label>
                            <input type="time" class="form-control" id="current_time" name="time" readonly>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Conversation <span class="text-muted">*</span></label>
                                <textarea class="form-control" rows="4" name="student_decip_incharge_msg"
                                    placeholder="Type your message here..."></textarea>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Upload Document (if any)</label>
                                <input type="file" class="form-control" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Less than 1 MB type (jpg,jpeg,png,pdf)</small>


                            </div>
                        </div>
                        @if(hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Internal Faculty') || hasRole('Guest Faculty'))
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="1">OPEN</option>
                                    <option value="2">CLOSED</option>
                                </select>
                            </div>
                        </div>
                        @endif
                       
                       

                        <div id="conclusion_div" style="display: none;">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Conclusion Type</label>
                                    <select class="form-select" name="conclusion_type" id="conclusion_type">
                                        <option value="">Select Conclusion Type</option>
                                        @foreach($memo_conclusion_master as $conclusion)
                                        <option value="{{ $conclusion->pk }}">{{ $conclusion->discussion_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3" id="deduction_div" >
                                        <label class="form-label">Mark of Deduction</label>
                                        <input type="number" class="form-control" name="mark_of_deduction"
                                            placeholder="Enter number of deduction" value="{{ $memo->mark_deduction_submit ?? '' }}">
                                    </div>
                            </div>
                            
                            <div class="col-6">

                                <div class="mb-3">
                                    <label class="form-label">Conclusion Remark</label>
                                    <textarea class="form-control" rows="4" name="conclusion_remark"
                                        placeholder="Type your conclusion message here..."></textarea>
                                    <small class="text-muted">This will be sent to the student as a conclusion of the
                                        memo.</small>
                                </div>
                            </div>
                        </div>
                    </div>



                    <hr>
                    <div class="gap-2 text-end">
                        <button type="submit" class="btn btn-primary">Send</button>
                        <a href="{{route('memo.discipline.index')}}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
                @endif
            @if($memo->status == 3)
            <div class="alert alert-secondary mt-3">
                <strong><i class="bi bi-lock me-1"></i> Memo Closed</strong>
            </div>

            <div class="card border mt-3">
                <div class="card-header bg-light fw-semibold">Conclusion Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-1">Conclusion Type</label>
                            <div class="fw-semibold">{{ $conclusion_type_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-1">Final Mark Deduction</label>
                            <div class="fw-semibold text-danger">{{ $memo->final_mark_deduction ?? '—' }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Conclusion Remark</label>
                            <div class="fw-semibold">{{ $memo->conclusion_remark ?? '—' }}</div>
                        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        var statusSelector = document.getElementById('status');
        var conclusionDiv = document.getElementById('conclusion_div');
        var conclusionTypeSelector = document.getElementById('conclusion_type');

        if (statusSelector) {
            statusSelector.addEventListener('change', function() {
                // Show or hide the conclusion section based on the selected status
                if (this.value == '2') {
                    conclusionDiv.style.display = 'block';
                } else {
                    conclusionDiv.style.display = 'none';
                }
            });

            // Default check on page load
            if (statusSelector.value == '2') {
                conclusionDiv.style.display = 'block';
            }
        }
        if(conclusionTypeSelector ){
            conclusionTypeSelector.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var discussionName = selectedOption.text;

        //   if(discussionName === 'Marks Deduction'){
        //         // If 'Others' is selected, make the conclusion remark required
        //           document.getElementById('deduction_div').style.display = 'block';
        //     } else {
        //         // Otherwise, remove the required attribute
        //         document.getElementById('deduction_div').style.display = 'none';
        //     }
        });
        }

        // Set date and time fields
        const now = new Date();
        const date = now.toISOString().split('T')[0];
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const time = `${hours}:${minutes}`;

        document.getElementById('current_date').value = date;
        document.getElementById('current_time').value = time;
       
    });

    </script>
    <script>
    /* ── Real-time discipline memo chat polling ── */
    (function () {
        var memoId  = {{ $memo->pk }};
        var pollUrl = '/memo/discipline/messages/' + memoId;
        var body    = document.getElementById('disciplineConvBody');
        var lastPk  = 0;

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
            var emptyRow = document.getElementById('disciplineEmptyRow');
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
                '<td>' + docCell + '</td>';

            body.appendChild(tr);
            if (lastPk < msg.pk) lastPk = msg.pk;
        }

        function poll() {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch(pollUrl + '?last_pk=' + lastPk, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.content : ''
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (msgs) { msgs.forEach(appendMessage); })
            .catch(function () {});
        }

        setInterval(poll, 4000);
    })();
    </script>
    @endsection