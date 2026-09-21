@extends('admin.layouts.master')

@section('title', 'Question Paper Management')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Question Paper Management"></x-breadcrum>

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <div class="row align-items-end">
                <div class="col-md-6">
                    <label for="examination_drive_id" class="form-label">Examination Drive</label>
                    <select id="examination_drive_id" class="form-select">
                        @forelse ($drives as $drive)
                            <option value="{{ $drive->id }}" @selected($drive->id == $selectedDriveId)>
                                {{ $driveLabels[$drive->id] }}
                            </option>
                        @empty
                            <option value="">No published examination drive available</option>
                        @endforelse
                    </select>
                </div>

                <div class="col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end gap-2 flex-wrap">
                        <a href="{{ route('coe.question_paper.unfreeze.index') }}"
                           class="btn btn-outline-secondary position-relative">
                            Unfreeze Requests
                            @if ($pendingUnfreezeCount)
                                <span class="badge bg-warning">{{ $pendingUnfreezeCount }}</span>
                            @endif
                        </a>

                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#deadlineModal"
                                @disabled($progress['total'] === 0)>
                            <i class="material-icons align-middle" style="font-size:20px;">event</i>
                            Set Deadline
                        </button>

                        <button type="button" class="btn btn-primary"
                                data-bs-toggle="modal" data-bs-target="#syncModal"
                                @disabled($drives->isEmpty())>
                            <i class="material-icons align-middle" style="font-size:20px;">add</i>
                            Generate Papers
                        </button>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row text-center mb-3">
                <div class="col-4">
                    <h4 class="mb-0">{{ $progress['total'] }}</h4>
                    <small class="text-muted">Total Papers</small>
                </div>
                <div class="col-4">
                    <h4 class="mb-0 text-success">{{ $progress['finalized'] }}</h4>
                    <small class="text-muted">Finalized</small>
                </div>
                <div class="col-4">
                    <h4 class="mb-0 text-warning">{{ $progress['pending'] }}</h4>
                    <small class="text-muted">Pending</small>
                </div>
            </div>

            {!! $dataTable->table(['class' => 'table']) !!}
        </div>
    </div>
</div>

{{-- Generate: creates a paper row for every written component that lacks one. --}}
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('coe.question_paper.sync') }}" class="modal-content">
            @csrf
            <input type="hidden" name="examination_drive_id" class="drive-id-field" value="{{ $selectedDriveId }}">

            <div class="modal-header">
                <h5 class="modal-title">Generate Question Papers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    A paper is created for every component of this drive that is sat as a written
                    examination. Components marked as assignment or system-scored are skipped, and
                    papers that already exist are left untouched.
                </p>

                <label for="sync_deadline" class="form-label">Deadline (optional)</label>
                <input type="date" id="sync_deadline" name="deadline" class="form-control">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

{{-- Deadline: applies only to papers still being written. --}}
<div class="modal fade" id="deadlineModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('coe.question_paper.deadline') }}" class="modal-content">
            @csrf
            <input type="hidden" name="examination_drive_id" class="drive-id-field" value="{{ $selectedDriveId }}">

            <div class="modal-header">
                <h5 class="modal-title">Set Deadline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                <input type="date" id="deadline" name="deadline" class="form-control" required>

                <small class="text-muted d-block mt-2">
                    Frozen and finalized papers keep the deadline they were written to.
                </small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var driveSelect = document.getElementById('examination_drive_id');

            if (!driveSelect) {
                return;
            }

            driveSelect.addEventListener('change', function () {
                // The counts above the table are rendered server-side, so the
                // drive change is a reload rather than an ajax redraw.
                var url = new URL(window.location.href);
                url.searchParams.set('examination_drive_id', this.value);
                window.location.href = url.toString();
            });
        });
    </script>
@endpush
