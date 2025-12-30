@extends('admin.layouts.master')
@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Peer Evaluation Form"></x-breadcrum>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Group Selection --}}
        <div class="card mb-4">
            {{-- <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Select Group</h5>
            </div> --}}
            <div class="card-body">
                <form method="GET" action="{{ route('peer.index') }}" id="groupForm">
                    <div class="row">
                        {{-- <div class="col-md-6">
                            <label for="group_id" class="form-label">Select Group</label>
                            <select name="group_id" id="group_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Select a Group --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" 
                                        {{ $selectedGroupId == $group->id ? 'selected' : '' }}>
                                        {{ $group->group_name }}
                                        @if (!$group->is_active)
                                            (Inactive)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div> --}}
                        {{-- <div class="col-md-6">
                            <div class="mt-4">
                                @if ($selectedGroupId)
                                    @php
                                        $memberCount = count($members);
                                        $selectedGroup = $groups->where('id', $selectedGroupId)->first();
                                    @endphp
                                    <div class="alert alert-info">
                                        <strong>Selected:</strong> {{ $selectedGroup->group_name }}
                                        | <strong>Members:</strong> {{ $memberCount }}
                                    </div>
                                @endif
                            </div>
                        </div> --}}
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedGroupId && count($members) > 0)
            <form method="POST" action="{{ route('peer.store') }}" id="evaluationForm">
                @csrf
                <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">

                <div class="card">
                    <div class="card-header  text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Evaluation Form - {{ $groups->where('id', $selectedGroupId)->first()->group_name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="">
                                    <tr>
                                        <th width="60">Sr.No</th>
                                        <th>User Name</th>
                                        <th>Group Name</th>
                                        @foreach ($columns as $column)
                                            <th>
                                                {{ $column->column_name }}
                                                <br>
                                                <small >(1-{{ $selectedGroup->max_marks }})</small>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- @foreach ($members as $index => $member)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="text-start">
                                                <strong>{{ $member->first_name }}</strong>
                                                @if ($member->user_id)
                                                    <br>
                                                    <small > - {{ $member->ot_code }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $groups->where('id', $selectedGroupId)->first()->group_name }}
                                                </span>
                                            </td>
                                            @foreach ($columns as $column)
                                                <td>
                                                    <input type="number" min="1" max="10"
                                                        name="scores[{{ $member->member_pk }}][{{ $column->id }}]"
                                                        class="form-control text-center score-input"
                                                        value="0" 
                                                        required
                                                        onchange="validateScore(this)">
                                                    <small >1-10</small>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach --}}

                                    @foreach ($members as $index => $member)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="text-start">
                                                <strong>{{ $member->first_name }}</strong>
                                                @if ($member->user_id)
                                                    <br>
                                                    <small class="text-muted"> - {{ $member->ot_code }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $groups->where('id', $selectedGroupId)->first()->group_name }}
                                                </span>
                                            </td>
                                            @foreach ($columns as $column)
                                                <td>
                                                    <input type="number" min="1" max="10"
                                                        name="scores[{{ $member->id }}][{{ $column->id }}]"
                                                        class="form-control text-center score-input" value="0" required
                                                        onchange="validateScore(this)">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                        {{-- Reflection Fields Section --}}
                        @foreach ($reflectionFields as $field)
                            <label class="form-label fw-bold mb-3">
                                <br>
                                <strong>{{ $field->field_label }} :</strong>
                            </label>
                            <textarea name="reflections[{{ $field->id }}]" class="form-control reflection-textarea" rows="4"
                                placeholder="Enter your description for {{ $field->field_label }}"></textarea>
                        @endforeach

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Evaluation
                            </button>
                            <button type="button" class="btn btn-warning btn-lg" onclick="resetScores()">
                                <i class="fas fa-redo"></i> Reset Scores
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @elseif($selectedGroupId)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                No members found in the selected group. Please contact administrator to add members to this group.
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Please select a group to start the evaluation.
            </div>
        @endif
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function validateScore(input) {
            let value = parseInt(input.value);
            if (value < 1) {
                input.value = 1;
            } else if (value > 10) {
                input.value = 10;
            }
        }

        function resetScores() {
            if (confirm('Are you sure you want to reset all scores to 0?')) {
                $('.score-input').val(0);
            }
        }

        // Auto-submit form when group changes
        document.getElementById('group_id').addEventListener('change', function() {
            document.getElementById('groupForm').submit();
        });

        // Form submission handling
        document.getElementById('evaluationForm')?.addEventListener('submit', function(e) {
            const emptyScores = $('.score-input').filter(function() {
                return $(this).val() === '' || $(this).val() === null;
            }).length;

            if (emptyScores > 0) {
                e.preventDefault();
                alert('Please fill all scores before submitting.');
                return false;
            }

            if (!confirm('Are you sure you want to submit your evaluation? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    <style>
        .table th {
            vertical-align: middle;
            font-weight: 600;
        }

        .score-input {
            width: 80px;
            margin: 0 auto;
            font-weight: bold;
        }

        .score-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .badge {
            font-size: 0.9em;
        }

        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection
