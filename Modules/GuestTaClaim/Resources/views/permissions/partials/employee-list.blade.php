@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
@php($desigCol = config('guest-ta-claim.user_designation_column'))

<section class="gtc-card p-0 overflow-hidden">
    <div class="p-3 border-bottom">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <h2 class="mb-1">Employees</h2>
                <p class="gtc-sub">Select an employee to manage access.</p>
            </div>
            <span class="badge rounded-pill bg-light text-secondary">{{ $users->total() }}</span>
        </div>

        <form id="permissions-search-form" method="GET" action="{{ route('guest-ta-claim.permissions.index') }}" class="mt-3 no-print">
            <input type="hidden" name="dept" value="{{ $dept }}">
            <div class="input-group">
                <input type="search" name="q" class="form-control" placeholder="Search by name"
                       value="{{ request('q') }}" aria-label="Search employees by name">
                <button class="btn btn-outline-secondary" type="submit" aria-label="Search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>

    <ul class="nav gtc-tabs px-3 no-print">
        <li class="nav-item">
            <a class="nav-link {{ $dept === 'all' ? 'active' : '' }}"
               href="{{ route('guest-ta-claim.permissions.index', ['q' => request('q')]) }}">All</a>
        </li>
        @foreach ($departments as $key => $department)
            <li class="nav-item">
                <a class="nav-link {{ $dept === $key ? 'active' : '' }}"
                   href="{{ route('guest-ta-claim.permissions.index', ['dept' => $key, 'q' => request('q')]) }}">
                    {{ $department['label'] }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="list-group list-group-flush">
        @forelse ($users as $user)
            <a class="gtc-emp {{ $selected && $selected->getKey() === $user->getKey() ? 'active' : '' }}"
               href="{{ route('guest-ta-claim.permissions.index', ['user' => $user->getKey(), 'dept' => $dept, 'q' => request('q'), 'page' => request('page')]) }}"
               @if ($selected && $selected->getKey() === $user->getKey()) aria-current="page" @endif>
                <span class="gtc-avatar sm">{{ $fmt::initials($user->first_name.' '.$user->last_name) }}</span>
                <span class="flex-grow-1 min-w-0">
                    <b class="text-truncate">{{ mb_strtoupper($user->first_name.' '.$user->last_name) }}</b>
                    @if ($desigCol && $user->{$desigCol})
                        <small class="d-block text-truncate">{{ mb_strtoupper((string) $user->{$desigCol}) }}</small>
                    @endif
                </span>
                <i class="bi bi-chevron-right text-muted small" aria-hidden="true"></i>
            </a>
        @empty
            <div class="gtc-empty">No employees found.</div>
        @endforelse
    </div>

    <div class="px-3 py-2 border-top no-print">
        {{ $users->links('pagination::simple-bootstrap-5') }}
    </div>
</section>
