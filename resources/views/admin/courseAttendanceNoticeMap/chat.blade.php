@extends('admin.layouts.master')

@section('title', 'Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<div class="container-fluid">

    <x-breadcrum title="Conversation" />
    <x-session_message />
    <div class="container-fluid bg-white p-4 rounded shadow-sm">
        <h5 class="text-center fw-bold mb-3">88th Foundation Course</h5>
        <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
        <hr>

        <p class="mb-1">SHOW CAUSE NOTICE</p>
        <p><strong>Date:</strong> 22/11/2013</p>

        <p>It has been brought to the notice of the undersigned that you were absent without prior authorization from
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
                        <td class="{{ $loop->first ? 'fw-bold text-danger' : '' }}">
                            {{ $row->display_name ?? 'N/A' }}
                        </td>

                        <td class="{{ $loop->first ? 'text-danger' : '' }}">
                            {{ $row->student_decip_incharge_msg }}
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}
                        </td>

                        <td>
                            {{-- Add delete button here if needed --}}
                            ---
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
        @if($memoNotice->isNotEmpty())
        @if($memoNotice->first()->notice_status == 1)
        <div class="border p-3 bg-light rounded">
            <form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                action="{{ route('memo.notice.management.memo_notice_conversation_student') }}">
                @csrf
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif


                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <input type="hidden" name="memo_notice_id" value="{{ $id }}">
                            <input type="hidden" name="student_id" value="{{ $memoNotice[0]->student_id ?? '' }}">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4" name="message"
                                placeholder="Type your message here...">{{ old('message') }}</textarea>
                            @error('message')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Upload Document (if any)</label>
                            <input type="file" name="document" class="form-control">
                            <small class="text-muted">Less than 2 MB (jpg, jpeg, png, pdf)</small>
                            @error('document')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="gap-3 text-end">
                    <button type="submit" class="btn btn-primary">Send</button>
                    <a href="#" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
            @else
            <div class="alert alert-warning mt-3">
                <strong>Notice Closed:</strong> This notice has been closed. You cannot reply to it.
                @endif
                @endif

        </div>
    </div>
</div>
@endsection