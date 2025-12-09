@extends('admin.layouts.master')

@section('title', 'Notice Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Notice Conversation" />
    <x-session_message />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="gap-2 text-end">
                    <a href="{{route('memo.notice.management.index')}}" class="btn btn-outline-secondary">Back</a>
                </div>
            <h5 class="text-center fw-bold mb-3">88th Foundation Course</h5>
            <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr>

            <p class="mb-1">SHOW CAUSE NOTICE</p>
            <p><strong>Date:</strong> 22/11/2013</p>

            <p>It has been brought to the notice of the undersigned that you were absent without prior authorization
                from
                following session(s)...</p>

            <div class="table-responsive mb-3">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
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
                            <td>22-11-2013</td>
                            <td>1</td>
                            <td>Lorem ipsum dolor sit amet.</td>
                            <td>Lorem, ipsum.</td>
                            <td>06:00-07:00</td>
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
                <p>In absence of online explanation and your personal appearance, unilateral decision may be taken.</p>
            </div>

            <p><strong>ALBY VARGHESE, A42</strong><br>
                Remarks: Show Cause Notice for 22.11.13</p>

            <p class="text-end"><strong>Rajesh Arya</strong><br>Deputy Director Sr. & I/C Discipline 88th F.C.</p>

            <!-- Exemption Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
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
                <table class="table table-bordered">
                    <thead class="table-light text-center">
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
                                {{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}
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
                        <div class="col-md-6">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <input type="hidden" name="memo_notice_id" value="{{ $id }}">
                            <label class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="current_date" name="date" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Time</label>
                            <input type="time" class="form-control" id="current_time" name="time" readonly>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Message <span class="text-muted">*</span></label>
                                <textarea class="form-control" rows="4" name="message"
                                    placeholder="Type your message here..."></textarea>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Upload Document (if any)</label>
                                <input type="file" class="form-control" name="document">
                                <small class="text-muted">Less than 1 MB type (jpg,jpeg,png,pdf)</small>


                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="1">OPEN</option>
                                    <option value="2">CLOSED</option>
                                </select>
                            </div>
                        </div>
                        @if($type == 'memo')
                       

                        <div id="conclusion_div" style="display: none;">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Conclusion Type</label>
                                    <select class="form-select" name="conclusion_type">
                                        <option value="">Select Conclusion Type</option>
                                        @foreach($memo_conclusion_master as $conclusion)
                                        <option value="{{ $conclusion->pk }}">{{ $conclusion->discussion_name }}
                                        </option>
                                        @endforeach
                                    </select>
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

                    @endif


                    <hr>
                    <div class="gap-2 text-end">
                        <button type="submit" class="btn btn-primary">Send</button>
                        <a href="{{route('memo.notice.management.index')}}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
                @endif
                @if( isset($memoNotice->first()->notice_status) && $memoNotice->first()->notice_status == 2 )
                <div class="alert alert-warning mt-3">
                    <strong>Notice Closed:</strong> This notice has been closed. You cannot reply to it.
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endsection
    @section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var statusSelector = document.getElementById('status');
        var conclusionDiv = document.getElementById('conclusion_div');

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
    @endsection