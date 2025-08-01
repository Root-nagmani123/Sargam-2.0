@extends('admin.layouts.master')

@section('title', 'Memo Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Memo Conversation" />
    <x-session_message />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="gap-2 text-end">
                    <button type="submit" class="btn btn-primary">Send</button>
                    <a href="{{route('memo.notice.management.index')}}" class="btn btn-outline-secondary">Back</a>
                </div>
            <h5 class="text-center fw-bold mb-3">88th Foundation Course</h5>
        <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
        <hr>

        <p class="mb-1">Memo</p>
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
                        <th>Venue</th>
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
                    <tr>
                        <td class="">
                        </td>

                        <td class="">
                           
                        </td>

                        <td>
                        </td>

                        <td>
                            {{-- Add delete button here if needed --}}
                            <form action=""
                                method="POST" onsubmit="return confirm('Are you sure?')">
                               
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>

                        <td>
                            <a href="" target="_blank">View</a>
                          
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>

        <!-- Reply Form -->
        <div class="border p-3 bg-light rounded">
            <form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                action="">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <input type="hidden" name="memo_notice_id" value="">
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
                            <select class="form-select" name="status">
                                <option value="OPEN">OPEN</option>
                                <option value="CLOSED">CLOSED</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="gap-2 text-end">
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>


        </div>
        </div>
    </div>
</div>
@endsection
<script>
window.onload = function() {
    const now = new Date();

    // Format date as YYYY-MM-DD
    const date = now.toISOString().split('T')[0];

    // Format time as HH:MM
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const time = `${hours}:${minutes}`;

    // Set values
    document.getElementById('current_date').value = date;
    document.getElementById('current_time').value = time;
};
</script>