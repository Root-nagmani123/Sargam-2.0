@extends('admin.layouts.master')

@section('title', 'Word of the Day')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <x-breadcrum title="Word of the Day (Login Page)" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card rounded-4 border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-2">How it works</h6>
            <p class="small text-body-secondary mb-0">
                Active entries are shown on the public login page. The word changes automatically at midnight (app timezone):
                each calendar day picks the next row in <strong>Sort order</strong>, then cycles back after the last entry.
                Inactive rows are skipped for rotation. This page also shows which word is displayed today.
            </p>
        </div>
    </div>

    <div class="card rounded-4 border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 py-3 px-4 d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div>
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">translate</span>
                    Entries
                </h6>
                @if($todaysWord)
                    <p class="small text-body-secondary mb-0 mt-1">
                        <strong>Today on login:</strong> {{ $todaysWord->displayLine() }}
                    </p>
                @else
                    <p class="small text-warning mb-0 mt-1">No active entry for today — add and activate at least one word.</p>
                @endif
            </div>
            <button type="button" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-1" id="openCreateWordOfDay"
                data-url="{{ route('admin.word-of-day.create') }}">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
                Add word
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width:70px;">#</th>
                            <th>Hindi</th>
                            <th>English</th>
                            <th style="width:100px;">Order</th>
                            <th style="width:110px;">Active</th>
                            <th class="pe-4" style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($words as $index => $w)
                            <tr>
                                <td class="ps-4 text-body-secondary">{{ $index + 1 }}</td>
                                <td>{{ $w->hindi_text }}</td>
                                <td>{{ $w->english_text }}</td>
                                <td>{{ $w->sort_order }}</td>
                                <td>
                                    @if($w->active_inactive)
                                        <span class="badge text-bg-success rounded-pill">Yes</span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">No</span>
                                    @endif
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.word-of-day.edit', $w->id) }}" class="text-primary openEditWordOfDay" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.word-of-day.destroy', $w->id) }}" method="POST"
                                            onsubmit="return confirm('Delete this entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-body-secondary py-5">
                                    No entries yet. Run <code class="small">php artisan db:seed --class=WordOfTheDaySeeder</code> or use <strong>Add word</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="wordOfDayModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white" id="wordOfDayModalTitle">Word of the Day</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4 placeholder-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('wordOfDayModal');
    if (!modalEl) return;

    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('#wordOfDayModalTitle');
    let wordOfDayModalInstance = null;

    function getOrCreateModal() {
        if (!wordOfDayModalInstance) {
            wordOfDayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return wordOfDayModalInstance;
    }

    function loadWordOfDayForm(url, title) {
        modalTitle.textContent = title || 'Word of the Day';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(r => {
                if (!r.ok) throw new Error('Failed to load form');
                return r.text();
            })
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">Failed to load form.</div>';
            });

        getOrCreateModal().show();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openCreateWordOfDay')?.addEventListener('click', (e) => {
            e.preventDefault();
            const url = e.currentTarget.getAttribute('data-url');
            loadWordOfDayForm(url, 'Add Word of the Day');
        });

        document.querySelectorAll('.openEditWordOfDay').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                loadWordOfDayForm(link.getAttribute('href'), 'Edit Word of the Day');
            });
        });

        @if($errors->any() && old('_wod_context'))
        @php
            $wodCtx = old('_wod_context');
        @endphp
        @if(is_string($wodCtx) && str_starts_with($wodCtx, 'edit:'))
        loadWordOfDayForm(@json(route('admin.word-of-day.edit', (int) substr($wodCtx, 5))), 'Edit Word of the Day');
        @else
        loadWordOfDayForm(@json(route('admin.word-of-day.create')), 'Add Word of the Day');
        @endif
        @endif
    });
})();
</script>
@endpush
