@extends('admin.layouts.master')
@section('title', 'FC SMS — Bulk Send')

@section('setup_content')
<div class="container-fluid py-3">

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>FC SMS — Bulk Send</h4>
        <span class="badge bg-secondary">Admin</span>
    </div>

    <p class="text-muted small mb-3">
        Choose a template and send. Lists are different:
        <strong>Form step incomplete</strong> = started form (1+ step done) but still pending;
        <strong>Registration pending</strong> = login exists but no form step started yet.
        Click recipient count to view users. No manual select / no send limit.
        OTP, credentials, exemption, and registration-success SMS stay automatic.
    </p>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light h-100">
                <div class="text-muted small">Programme</div>
                <div class="fw-semibold">{{ $preview['programme'] }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light h-100">
                <div class="text-muted small">Registration last date (B2)</div>
                <div class="fw-semibold">{{ $preview['last_date'] }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fc-reg.admin.sms.send') }}"
                  onsubmit="return confirm('Send this SMS template to all matching incomplete trainees? This cannot be undone.');">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">SMS template</label>
                    @foreach($templates as $key => $tpl)
                        @php $rows = $lists[$key] ?? collect(); @endphp
                        <div class="border rounded-3 p-3 mb-2">
                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                <div class="form-check flex-grow-1 mb-0">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="template"
                                           id="tpl_{{ $key }}" value="{{ $key }}"
                                           {{ old('template', 'b1') === $key ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="tpl_{{ $key }}">
                                        <span class="fw-semibold">{{ $tpl['label'] }}</span>
                                        <span class="badge bg-light text-dark border ms-1">{{ $tpl['code'] }}</span>
                                        <div class="text-muted small mt-1">{{ $tpl['help'] }}</div>
                                    </label>
                                </div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#recipients_{{ $key }}"
                                        aria-expanded="false"
                                        aria-controls="recipients_{{ $key }}">
                                    {{ number_format($tpl['count']) }} recipient(s) — view list
                                </button>
                            </div>

                            <div class="collapse mt-3" id="recipients_{{ $key }}">
                                <div class="table-responsive border rounded">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:50px;">#</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Mobile</th>
                                                @if($key === 'b1')
                                                    <th>Pending step</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($rows as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td>{{ $row['name'] !== '' ? $row['name'] : '—' }}</td>
                                                    <td><code class="small">{{ $row['user_id'] !== '' ? $row['user_id'] : '—' }}</code></td>
                                                    <td>{{ $row['mobile'] }}</td>
                                                    @if($key === 'b1')
                                                        <td><span class="badge bg-warning text-dark">{{ $row['step_name'] ?? '—' }}</span></td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $key === 'b1' ? 5 : 4 }}" class="text-muted text-center py-3">
                                                        No recipients found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Send SMS
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
