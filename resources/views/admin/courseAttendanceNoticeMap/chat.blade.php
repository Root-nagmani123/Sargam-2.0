@extends('admin.layouts.master')

@section('title', 'Conversation - Sargam | LBSNAA')

@section('setup_content')

<div class="container-fluid"> 
<x-breadcrum title="Conversation" />
            <x-session_message />

            <div class="bg-white p-4 rounded shadow-sm mt-3" style="border-left: 4px solid #004a93;">

                <h5 class="text-center fw-bold mb-3">{{ $template_details->course_name ?? 'Course Name' }}</h5>
            <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
            <hr>

            <p class="mb-1">{{ $type == 'memo' ? 'SHOW CAUSE MEMO' : 'SHOW CAUSE NOTICE' }}</p>
            <p><strong>Date:</strong> {{ $template_details && $template_details->session_date ? \Carbon\Carbon::parse($template_details->session_date)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }} </p>

            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>No. of Session(s)</th>
                            <th>Topics</th>
                            <th>Venue</th>
                            <th>Session(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $template_details && $template_details->session_date ? \Carbon\Carbon::parse($template_details->session_date)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }}</td>
                            <td>1</td>
                            <td>{{ $template_details->subject_topic ?? 'Topic Name' }}</td>
                            <td>{{ $template_details->venue_name ?? 'Venue' }}</td>
                            <td>{{ $template_details->session_time ?? '' }}</td>
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
                                <td>{{ $row->display_name ?? 'N/A' }}</td>
                                <td>{{ $row->student_decip_incharge_msg }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->created_date ?? 'now', 'UTC')->timezone('Asia/Kolkata')->format('d-m-Y h:i A') }}</td>
                                <td>---</td>
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


                {{-- Reply Form --}}
                @if($memoNotice->isEmpty() || optional($memoNotice->first())->notice_status == 1)
                <div class="border p-3 bg-light rounded">
                    <form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                        action="{{ route('memo.notice.management.memo_notice_conversation_student') }}">
                        @csrf

                        <input type="hidden" name="memo_notice_id" value="{{ $id }}">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="student_id" value="{{ $memoNotice[0]->student_id ?? '' }}">

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4" name="message"
                                    placeholder="Type your message here..."></textarea>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Upload Document (if any)</label>
                                <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Less than 2 MB (jpg, jpeg, png, pdf)</small>
                            </div>
                        </div>

                        <hr>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Send</button>
                            @if(hasRole('Student-OT'))
                            <a href="{{ url('admin/memo-notice-management/user') }}" class="btn btn-outline-secondary">Back</a>
                            @else
                            <a href="{{ url('admin/memo-notice-management') }}" class="btn btn-outline-secondary">Back</a>
                            @endif
                        </div>
                    </form>
                </div>
                @else
                <div class="alert alert-warning mt-3">
                    <strong>{{ $type == 'memo' ? 'Memo' : 'Notice' }} Closed:</strong> This {{ $type == 'memo' ? 'memo' : 'notice' }} has been closed. You cannot reply to it.
                </div>

                @php
                    $conclusionTypeName = '';
                    if (!empty($template_details->conclusion_type_pk)) {
                        $conclusionTypeName = $memo_conclusion_master->firstWhere('pk', $template_details->conclusion_type_pk)->discussion_name ?? '';
                    }
                @endphp

                @if(!empty($template_details->conclusion_type_pk) || !empty($template_details->conclusion_remark))
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
                @endif

            </div>
</div>

@endsection