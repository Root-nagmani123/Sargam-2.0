@extends('admin.layouts.master')
@section('title', 'Peer Evaluation Form | Sargam Admin')
@section('setup_content')
    <div class="container-fluid py-4">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            {{-- Header Section --}}
            <div class="card-header bg-gradient-primary py-4 border-0">
                <div class="d-flex align-items-center">
                    <div class="icon-wrapper bg-white bg-opacity-20 rounded-circle p-3 me-3">
                        <i class="material-icons material-symbols-rounded" style="font-size: 2.5rem;">assignment</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold text-white">Peer Evaluation Form</h3>
                        <p class="mb-0 opacity-75 small">Evaluate your peers with detailed feedback</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                {{-- Success Alert --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="material-icons material-symbols-rounded text-success me-3" style="font-size: 1.5rem;">check_circle</i>
                            <div class="flex-grow-1">
                                <strong>Success!</strong> {{ session('success') }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- store() refuses a submission by redirecting back with 'error'
                     (not in the group, form closed, score over the column's max,
                     Distribute Marks over the group's pool). Without this the
                     rejection was silent and the OT saw an unchanged form. --}}
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="material-icons material-symbols-rounded text-danger me-3" style="font-size: 1.5rem;">error</i>
                            <div class="flex-grow-1">
                                <strong>Not submitted.</strong> {{ session('error') }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Why the form below is read-only: group inactive, form not
                     switched on, or today is outside the event's start/end dates.
                     Same sentence store() would answer with. --}}
                @if (!empty($closedReason))
                    <div class="alert alert-warning border-0 rounded-3 shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="material-icons material-symbols-rounded text-warning me-3" style="font-size: 1.5rem;">lock_clock</i>
                            <div class="flex-grow-1">
                                <strong>This evaluation is not open.</strong> {{ $closedReason }}
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Group Selection --}}
                <div class="mb-4">
                    <form method="GET" action="{{ route('peer.index') }}" id="groupForm">
                        <div class="row">
                            {{-- Group selection form content if needed --}}
                        </div>
                    </form>
                </div>

                @if ($selectedGroupId && count($members) > 0)
                    @php
                        $selectedGroup = $groups->where('id', $selectedGroupId)->first();

                        // The cap on one box, per criterion, from the same call
                        // store() validates with: Rate Peers caps on the column's
                        // own Max Marks, Distribute Marks on the group's pool -
                        // there the marks come out of one shared budget, so a box
                        // held to the column max stopped an OT giving a bigger
                        // share to one peer while the pool still had marks in it.
                        $cellMax = $columns->mapWithKeys(fn ($column) => [
                            $column->id => \App\Support\PeerEvaluationForm::cellMax($column, $selectedGroup),
                        ]);
                        $pool = \App\Support\PeerEvaluationForm::poolFor($selectedGroup);
                        $distributeColumns = $columns->where(
                            'evaluation_type',
                            \App\Models\PeerColumn::TYPE_DISTRIBUTE_MARKS
                        );
                        // "10.00" -> "10", "7.50" -> "7.5". Marks are decimals but
                        // read as counts.
                        $trim = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                    @endphp
                    <form method="POST" action="{{ route('peer.store') }}" id="evaluationForm">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">

                        {{-- Evaluation Card --}}
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-light border-0 py-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <h5 class="mb-0 fw-bold text-primary">
                                        <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.25rem;">groups</i>
                                        Evaluation Form
                                    </h5>
                                    <div class="mt-2 mt-md-0">
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                            <i class="material-icons material-symbols-rounded me-1 align-middle" style="font-size: 1rem;">group</i>
                                            {{ $selectedGroup->group_name }}
                                        </span>
                                        <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill ms-2">
                                            <i class="material-icons material-symbols-rounded me-1 align-middle" style="font-size: 1rem;">people</i>
                                            {{ count($members) }} {{ count($members) == 1 ? 'Member' : 'Members' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                {{-- Distribute Marks is ONE pool shared out across
                                     the whole group, not a scale applied to each
                                     peer - so who gets nothing is part of the
                                     answer, and how much is left matters while you
                                     type. Without this the OT learned they were
                                     over the pool only by submitting and being
                                     turned away by store(), which checks the very
                                     same total. --}}
                                @if ($distributeColumns->isNotEmpty() && $pool > 0)
                                    <div class="alert alert-info border-0 rounded-3 mx-4 mt-4 mb-0 d-flex flex-wrap align-items-center gap-2"
                                         id="peerPoolBanner" data-pool="{{ $trim($pool) }}" role="status">
                                        <i class="material-icons material-symbols-rounded" style="font-size: 1.25rem;">savings</i>
                                        <span>
                                            Share out up to <strong>{{ $trim($pool) }}</strong> marks in total across your peers.
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill ms-auto">
                                            Given <strong id="peerPoolUsed">0</strong> &middot;
                                            Remaining <strong id="peerPoolLeft">{{ $trim($pool) }}</strong>
                                        </span>
                                    </div>
                                @endif

                                {{-- Table Section --}}
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-semibold text-uppercase small text-muted border-0 py-3 ps-4">Sr.No</th>
                                                <th class="fw-semibold text-uppercase small text-muted border-0 py-3">OT Name / Participant Name</th>
                                                <th class="fw-semibold text-uppercase small text-muted border-0 py-3">OT Code</th>
                                                @foreach ($columns as $column)
                                                    <th class="fw-semibold text-uppercase small text-muted border-0 py-3 text-center">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <span class="mb-1">{{ $column->column_name }}</span>
                                                            <small class="text-muted fw-normal">(0-{{ $trim($cellMax[$column->id]) }})</small>
                                                        </div>
                                                    </th>
                                                @endforeach
                                                {{-- Remarks: one free-text note per evaluated OT, stored in
                                                     peer_evaluation_remarks and shown on that OT's Evaluation
                                                     Report beside this evaluator's scores. Shown outright
                                                     whenever a criterion asks for one (peer_columns.has_remarks,
                                                     set on Manage Evaluation Columns) — the tick-box that used
                                                     to reveal it is gone, and store() applies the same gate, so
                                                     the column always has somewhere to save to. --}}
                                                @if ($allowsRemarks)
                                                    <th class="fw-semibold text-uppercase small text-muted border-0 py-3 peer-remarks-col">
                                                        Remarks
                                                    </th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($members as $index => $member)
                                                <tr class="evaluation-row">
                                                    <td class="ps-4 fw-medium text-muted">{{ $index + 1 }}</td>
                                                    {{-- The cell carried only the avatar, so the column read as
                                                         a row of single letters. first_name already holds the
                                                         full display name (peersFor COALESCEs student_master
                                                         display_name over the member row's user_name), so the
                                                         name just had to be printed. --}}
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-circle bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                                                <span class="fw-bold">{{ mb_substr($member->first_name, 0, 1) }}</span>
                                                            </div>
                                                            <span class="fw-medium text-dark">{{ $member->first_name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                                        {{ $member->ot_code }}
                                                        </span>
                                                    </td>
                                                    @foreach ($columns as $column)
                                                        <td class="text-center">
                                                            <div class="score-input-wrapper">
                                                                {{-- Rate Peers caps on the COLUMN's own Max Marks;
                                                                     Distribute Marks caps on the group's pool -
                                                                     PeerEvaluationForm::cellMax() decides which, and
                                                                     store() validates the box with the same call. --}}
                                                                {{-- min="0": store() accepts 0..max, and the box
                                                                     defaults to 0, so min="1" made every untouched
                                                                     score fail the browser's own validation. --}}
                                                                <input type="number"
                                                                    min="0"
                                                                    max="{{ $trim($cellMax[$column->id]) }}"
                                                                    step="any"
                                                                    name="scores[{{ $member->id }}][{{ $column->id }}]"
                                                                    data-evaluation-type="{{ $column->evaluation_type }}"
                                                                    class="form-control form-control-lg text-center score-input fw-bold border-2"
                                                                    value="{{ old("scores.{$member->id}.{$column->id}", $answers['scores'][$member->id][$column->id] ?? 0) }}"
                                                                    required
                                                                    @if (!empty($closedReason)) disabled @endif
                                                                    onchange="validateScore(this)"
                                                                    aria-label="Score for {{ $column->column_name }}">
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                    @if ($allowsRemarks)
                                                        <td class="peer-remarks-col">
                                                            <textarea name="remarks[{{ $member->id }}]"
                                                                      class="form-control"
                                                                      rows="2"
                                                                      maxlength="2000"
                                                                      @if (!empty($closedReason)) disabled @endif
                                                                      placeholder="Optional note about {{ $member->first_name }}"
                                                                      aria-label="Remarks for {{ $member->first_name }}">{{ old("remarks.{$member->id}", $answers['remarks'][$member->id] ?? '') }}</textarea>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Reflection Fields Section --}}
                                @if(count($reflectionFields) > 0)
                                    <div class="p-4 bg-light border-top">
                                        <h5 class="fw-bold text-primary mb-4">
                                            <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.25rem;">comment</i>
                                            Reflection & Feedback
                                        </h5>
                                        @foreach ($reflectionFields as $field)
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold mb-2 text-dark">
                                                    {{ $field->field_label }}
                                                </label>
                                                <textarea 
                                                    name="reflections[{{ $field->id }}]"
                                                    class="form-control reflection-textarea border-2 rounded-3"
                                                    rows="5"
                                                    maxlength="5000"
                                                    @if (!empty($closedReason)) disabled @endif
                                                    placeholder="Enter your detailed description for {{ $field->field_label }}..."
                                                    style="resize: vertical;">{{ old("reflections.{$field->id}", $answers['reflections'][$field->id] ?? '') }}</textarea>
                                                <div class="form-text">
                                                    <i class="material-icons material-symbols-rounded me-1 align-middle" style="font-size: 0.875rem;">info</i>
                                                    Provide thoughtful and constructive feedback
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Action Buttons --}}
                                <div class="card-footer bg-white border-0 p-4 d-flex gap-3 flex-wrap">
                                    {{-- Disabled, not hidden: the OT can still read back what they
                                         submitted after the window closes. store() refuses it either
                                         way - this only saves them the round trip. --}}
                                    <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow-sm fw-semibold"
                                        @if (!empty($closedReason)) disabled @endif>
                                        <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.125rem;">send</i>
                                        Submit Evaluation
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-lg px-5 rounded-pill fw-semibold" onclick="resetScores()"
                                        @if (!empty($closedReason)) disabled @endif>
                                        <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.125rem;">refresh</i>
                                        Reset Scores
                                    </button>
                                    <div class="ms-auto d-flex align-items-center text-muted small">
                                        <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1rem;">info</i>
                                        <span>Please ensure all fields are filled before submitting</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @elseif($selectedGroupId)
                    <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-0">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <i class="material-icons material-symbols-rounded text-warning" style="font-size: 2.5rem;">warning</i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading fw-bold mb-2">No Members Found</h5>
                                <p class="mb-0">No members found in the selected group. Please contact administrator to add members to this group.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-0">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <i class="material-icons material-symbols-rounded text-info" style="font-size: 2.5rem;">info</i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading fw-bold mb-2">Get Started</h5>
                                <p class="mb-0">Please select a group to start the evaluation process.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 0 is a score, and marks are decimals.
        //
        // This read the box with parseInt and forced anything under 1 back up to
        // 1, so an OT could not give a peer nothing and 7.5 silently became 7.
        // Under Distribute Marks that made the form unfillable: the pool only
        // stretches so far, so most peers legitimately get 0 - and the submit
        // handler below then refused to post while any box still held one.
        function validateScore(input) {
            const parsedMax = parseFloat(input.getAttribute('max'));
            const maxMarks = isNaN(parsedMax) ? 10 : parsedMax;
            const raw = String(input.value).trim();

            if (raw === '') {
                // Left empty rather than filled in for them: the value must not
                // jump back under the cursor of someone retyping a score. The
                // submit handler is what reports a box still blank at the end.
                showScoreFeedback(input, 'error');
            } else {
                const value = parseFloat(raw);

                if (isNaN(value) || value < 0) {
                    input.value = 0;
                    showScoreFeedback(input, 'error');
                } else if (value > maxMarks) {
                    input.value = maxMarks;
                    showScoreFeedback(input, 'error');
                } else {
                    showScoreFeedback(input, 'success');
                }
            }

            // Update visual state
            updateInputState(input);
            updatePool();
        }

        /**
         * The Distribute Marks pool, counted as it is spent.
         *
         * Only the distribute boxes count towards it - a Rate Peers score is not
         * money out of the same purse. store() checks the same total server-side;
         * this is so the OT can see it before being turned away.
         *
         * @return {number} marks still to hand out (negative = over the pool)
         */
        function updatePool() {
            const banner = document.getElementById('peerPoolBanner');
            if (!banner) { return 0; }

            const pool = parseFloat(banner.dataset.pool) || 0;
            let used = 0;

            document.querySelectorAll('.score-input[data-evaluation-type="distribute_marks"]').forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value)) { used += value; }
            });

            // Marks are decimals, so the running total picks up float noise
            // (0.1 + 0.2). Rounded to the 2dp the scores themselves are stored at.
            const round = (n) => Math.round(n * 100) / 100;
            const left = round(pool - used);

            document.getElementById('peerPoolUsed').textContent = round(used);
            document.getElementById('peerPoolLeft').textContent = left;
            banner.classList.toggle('alert-danger', left < 0);
            banner.classList.toggle('alert-info', left >= 0);

            return left;
        }

        function showScoreFeedback(input, type) {
            // Remove existing feedback
            const existingFeedback = input.parentElement.querySelector('.score-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            // Add visual feedback
            if (type === 'error') {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            } else {
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
            }
        }

        function updateInputState(input) {
            const value = parseFloat(input.value) || 0;
            const parsedMax = parseFloat(input.getAttribute('max'));
            const maxMarks = isNaN(parsedMax) || parsedMax <= 0 ? 10 : parsedMax;
            
            // Remove all state classes
            input.classList.remove('score-low', 'score-medium', 'score-high');
            
            // Add appropriate class based on value
            if (value > 0) {
                const percentage = (value / maxMarks) * 100;
                if (percentage < 40) {
                    input.classList.add('score-low');
                } else if (percentage < 70) {
                    input.classList.add('score-medium');
                } else {
                    input.classList.add('score-high');
                }
            }
        }

        function resetScores() {
            const form = document.getElementById('evaluationForm');
            if (!form) return;

            // Use Bootstrap modal or modern confirmation
            if (confirm('Are you sure you want to reset all scores to 0? This action cannot be undone.')) {
                const scoreInputs = form.querySelectorAll('.score-input');
                scoreInputs.forEach(input => {
                    input.value = 0;
                    input.classList.remove('is-valid', 'is-invalid', 'score-low', 'score-medium', 'score-high');
                });
                
                // Show toast notification
                showNotification('All scores have been reset', 'info');
            }
        }

        function showNotification(message, type = 'success') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow-lg`;
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            const iconName = type === 'success' ? 'check_circle' : 'info';
            toast.innerHTML = `
                <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.25rem;">${iconName}</i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Initialize score inputs on page load
        document.addEventListener('DOMContentLoaded', function() {
            const scoreInputs = document.querySelectorAll('.score-input');
            scoreInputs.forEach(input => {
                // Add event listeners
                input.addEventListener('input', function() {
                    validateScore(this);
                });
                
                input.addEventListener('blur', function() {
                    validateScore(this);
                });

                // Initialize state
                updateInputState(input);
            });

            // The pool starts out reflecting whatever was saved last time, not 0 -
            // the boxes reopen filled in with the previous submission.
            updatePool();

            // Form submission handling with better UX
            const evaluationForm = document.getElementById('evaluationForm');
            if (evaluationForm) {
                evaluationForm.addEventListener('submit', function(e) {
                    // EMPTY, not "under 1". 0 is a score an OT is entitled to give,
                    // and under Distribute Marks most peers may well get one - the
                    // pool only stretches so far. Treating 0 as unfilled meant the
                    // form could not be submitted at all unless every peer got at
                    // least 1, which the pool often cannot pay for.
                    const scoreInputs = this.querySelectorAll('.score-input');
                    const emptyScores = Array.from(scoreInputs).filter(input => {
                        return String(input.value).trim() === '' || isNaN(parseFloat(input.value));
                    });

                    if (emptyScores.length > 0) {
                        e.preventDefault();
                        showNotification(`Please fill all ${emptyScores.length} empty score field(s) before submitting.`, 'warning');

                        // Highlight empty fields
                        emptyScores.forEach(input => {
                            input.classList.add('is-invalid');
                        });
                        emptyScores[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

                        return false;
                    }

                    // Over the Distribute Marks pool: store() refuses this and sends
                    // the whole form back, so it is worth catching here - the OT can
                    // see which boxes to take marks off while they are still looking
                    // at them.
                    const remaining = updatePool();

                    if (remaining < 0) {
                        e.preventDefault();
                        showNotification(`You have handed out ${Math.abs(remaining)} marks more than this group's pool. Take some back before submitting.`, 'warning');
                        document.getElementById('peerPoolBanner')
                            .scrollIntoView({ behavior: 'smooth', block: 'center' });

                        return false;
                    }

                    // Check reflection fields
                    const reflectionFields = this.querySelectorAll('.reflection-textarea');
                    const emptyReflections = Array.from(reflectionFields).filter(textarea => {
                        return !textarea.value.trim();
                    });

                    if (emptyReflections.length > 0) {
                        if (!confirm('Some reflection fields are empty. Do you want to submit anyway?')) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    // Final confirmation
                    if (!confirm('Are you sure you want to submit your evaluation? This action cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="material-icons material-symbols-rounded me-2 align-middle spin-icon" style="font-size: 1.125rem;">refresh</i> Submitting...';
                    }
                });
            }

            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });

        // Auto-submit form when group changes (if group selector exists)
        const groupSelect = document.getElementById('group_id');
        if (groupSelect) {
            groupSelect.addEventListener('change', function() {
                document.getElementById('groupForm').submit();
            });
        }
    
</script>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
        }

        .bg-gradient-primary {
            background: var(--primary-gradient) !important;
        }

        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #dee2e6;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .score-input-wrapper {
            display: inline-block;
            position: relative;
        }

        .score-input {
            width: 90px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border-width: 2px !important;
        }

        .score-input:focus {
            transform: scale(1.1);
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25) !important;
            border-color: #28a745 !important;
        }

        .score-input.score-low {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .score-input.score-medium {
            border-color: #ffc107;
            background-color: #fffbf0;
        }

        .score-input.score-high {
            border-color: #28a745;
            background-color: #f0fff4;
        }

        .score-input.is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.98-.98-.98-.98L1.32 4.77l.98.98-.98.98zm2.5-2.5L5.78 4.3l.98-.98L6.76 2.34l-.98-.98-.98.98.98.98z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .score-input.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .reflection-textarea {
            transition: all 0.3s ease;
        }

        .reflection-textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }

        .avatar-circle {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }

        .alert {
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .badge {
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .icon-wrapper {
            backdrop-filter: blur(10px);
        }

        .spin-icon {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Material Icons alignment */
        .material-icons.material-symbols-rounded {
            vertical-align: middle;
            line-height: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .score-input {
                width: 70px;
                font-size: 1rem;
            }

            .btn-lg {
                padding: 0.625rem 1.5rem;
                font-size: 0.9rem;
            }

            .card-header h3 {
                font-size: 1.25rem;
            }

            .avatar-circle {
                width: 35px !important;
                height: 35px !important;
                font-size: 0.75rem;
            }
        }

        /* Print styles */
        @media print {
            .btn, .card-footer {
                display: none;
            }

            .card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
        }

        /* Accessibility improvements */
        .score-input:focus-visible,
        .reflection-textarea:focus-visible {
            outline: 3px solid #667eea;
            outline-offset: 2px;
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition-property: color, background-color, border-color, transform, box-shadow;
            transition-duration: 0.2s;
            transition-timing-function: ease;
        }
    </style>
@endsection
