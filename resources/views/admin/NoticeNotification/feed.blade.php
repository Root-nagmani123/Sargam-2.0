@extends('admin.layouts.master')

@section('title', 'All notices')

@push('styles')
<style>
    .notice-feed-toolbar {
        border-radius: 12px;
        background: #f8f9fa;
        padding: 12px 14px;
        gap: 10px;
    }
    .notice-feed-tab {
        border: none;
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 0.875rem;
        font-weight: 600;
        background: #e9ecef;
        color: #495057;
        transition: background 0.2s ease, color 0.2s ease;
        cursor: pointer;
        white-space: nowrap;
    }
    .notice-feed-tab:hover {
        background: #dee2e6;
    }
    .notice-feed-tab.active {
        background: #004a93;
        color: #fff;
    }
    .notice-feed-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        padding: 1rem 1.15rem;
        margin-bottom: 12px;
    }
    .notice-feed-card-title {
        font-weight: 600;
        color: #212529;
        font-size: 1rem;
        margin: 0;
        flex: 1;
        min-width: 0;
    }
    .notice-feed-card-meta {
        font-size: 0.8125rem;
        color: #6c757d;
        white-space: nowrap;
    }
    .notice-feed-card-body {
        color: #495057;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-top: 10px;
    }
    .notice-feed-search .input-group-text {
        background: #fff;
        border-right: 0;
    }
    .notice-feed-search .form-control {
        border-left: 0;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum
        title="All notices"
        :items="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => 'All notices'],
        ]"
    />
    <x-session_message />

    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="material-symbols-rounded text-primary" style="font-size: 2rem;">notifications</span>
        <h1 class="h3 mb-0 fw-semibold">Notices</h1>
    </div>

    @if($noticeCategoryTabs->isEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <span class="material-symbols-rounded d-block mb-2" style="font-size: 3rem;">description</span>
                <p class="mb-0">No notices match your filters.</p>
                @if($q !== '')
                    <a href="{{ route('admin.notice.feed') }}" class="btn btn-sm btn-outline-primary mt-3">Clear search</a>
                @endif
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3 p-md-4" id="notice-feed-root">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 notice-feed-toolbar mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1" role="tablist" aria-label="Notice categories">
                        @foreach($noticeCategoryTabs as $idx => $tab)
                            <button
                                type="button"
                                class="notice-feed-tab {{ $tab['key'] === $activeTabKey ? 'active' : '' }}"
                                data-feed-tab="{{ $tab['key'] }}"
                                role="tab"
                                aria-selected="{{ $tab['key'] === $activeTabKey ? 'true' : 'false' }}"
                            >{{ $tab['label'] }}</button>
                        @endforeach
                    </div>
                    <form method="get" action="{{ route('admin.notice.feed') }}" class="notice-feed-search flex-shrink-0" style="min-width: 220px; max-width: 320px;">
                        <input type="hidden" name="tab" id="notice-feed-tab-input" value="{{ $activeTabKey }}">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0 rounded-start-pill"><span class="material-symbols-rounded text-muted" style="font-size: 1.1rem;">search</span></span>
                            <input type="search" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Search" autocomplete="off">
                        </div>
                    </form>
                </div>

                @foreach($noticeCategoryTabs as $tab)
                    @php $isActive = $tab['key'] === $activeTabKey; @endphp
                    <div class="notice-feed-pane {{ $isActive ? '' : 'd-none' }}" data-feed-pane="{{ $tab['key'] }}" role="tabpanel">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                            <div class="text-body-secondary small">
                                <span class="fw-semibold text-body">{{ str_pad((string) $tab['total'], 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $tab['total'] === 1 ? 'notice' : 'notices' }}
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                @if(hasRole('Admin') || hasRole('Super Admin'))
                                    <a href="{{ route('admin.notice.index') }}" class="small text-primary text-decoration-none fw-semibold">Manage notices</a>
                                @endif
                            </div>
                        </div>

                        @foreach($tab['notices'] as $notice)
                            @php
                                $when = $notice->display_date ?? $notice->created_at ?? null;
                                $whenCarbon = $when ? \Carbon\Carbon::parse($when) : null;
                                $whenLabel = $whenCarbon ? $whenCarbon->format('d/m/Y h:i A') : '—';
                                $plainDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($notice->description ?? ''))));
                            @endphp
                            <article class="notice-feed-card">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <h2 class="notice-feed-card-title">{{ $notice->notice_title }}</h2>
                                    <div class="notice-feed-card-meta text-md-end">
                                        ~by {{ $notice->creator_display ?? '—' }} on {{ $whenLabel }}
                                    </div>
                                </div>
                                @if(!empty($notice->subcategory_name))
                                    <div class="small text-muted mt-1">{{ $notice->subcategory_name }}</div>
                                @endif
                                <div class="notice-feed-card-body">
                                    {{ \Illuminate\Support\Str::limit($plainDesc, 600) }}
                                </div>
                                @if(!empty($notice->document))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" rel="noopener" class="text-danger text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1">
                                            <span class="material-symbols-rounded" style="font-size: 1rem;">attach_file</span>
                                            View attachment
                                        </a>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <script>
        (function () {
            var root = document.getElementById('notice-feed-root');
            if (!root) return;
            var tabInput = document.getElementById('notice-feed-tab-input');
            root.querySelectorAll('[data-feed-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-feed-tab');
                    root.querySelectorAll('[data-feed-tab]').forEach(function (b) {
                        b.classList.toggle('active', b === btn);
                        b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                    });
                    root.querySelectorAll('[data-feed-pane]').forEach(function (pane) {
                        pane.classList.toggle('d-none', pane.getAttribute('data-feed-pane') !== key);
                    });
                    if (tabInput) tabInput.value = key;
                    try {
                        var u = new URL(window.location.href);
                        u.searchParams.set('tab', key);
                        window.history.replaceState({}, '', u.toString());
                    } catch (e) {}
                });
            });
        })();
        </script>
    @endif
</div>
@endsection
