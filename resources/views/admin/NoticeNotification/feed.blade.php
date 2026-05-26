@extends('admin.layouts.master')

@section('title', 'All notices')

@push('styles')
@include('admin.NoticeNotification.partials.module-styles')
@include('admin.communications.partials.comms-feed-cards-styles')
@endpush

@section('setup_content')
<div class="container-fluid notice-module-page">
    <x-breadcrum
        title="All notices"
        :items="[
            ['label' => 'Home', 'url' => route('admin.dashboard')],
            ['label' => 'All notices'],
        ]"
    />
    <x-session_message />

    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width:2.75rem;height:2.75rem;">
            <i class="bi bi-bell fs-5" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="h4 mb-0 fw-bold">Communications</h1>
            <p class="text-muted small mb-0">Browse notices by category</p>
        </div>
    </div>

    @if($noticeCategoryTabs->isEmpty())
        <div class="card notice-card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-file-earmark-text display-4 d-block mb-3 opacity-50" aria-hidden="true"></i>
                <p class="mb-0 fw-semibold">No notices match your filters.</p>
                @if($q !== '')
                    <a href="{{ route('admin.notice.feed') }}" class="btn btn-outline-primary btn-sm rounded-3 mt-3">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear search
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="card notice-card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3 p-md-4" id="notice-feed-root">
                <div class="d-flex flex-column flex-md-row flex-wrap align-items-stretch align-items-md-center justify-content-between gap-3 notice-feed-toolbar mb-4">
                    <ul class="nav nav-pills notice-feed-pills flex-nowrap mb-0" role="tablist" aria-label="Notice categories">
                        @foreach($noticeCategoryTabs as $idx => $tab)
                        <li class="nav-item" role="presentation">
                            <button
                                type="button"
                                class="nav-link {{ $tab['key'] === $activeTabKey ? 'active' : '' }}"
                                data-feed-tab="{{ $tab['key'] }}"
                                role="tab"
                                aria-selected="{{ $tab['key'] === $activeTabKey ? 'true' : 'false' }}"
                            >{{ $tab['label'] }}</button>
                        </li>
                        @endforeach
                    </ul>
                    <form method="get" action="{{ route('admin.notice.feed') }}" class="notice-feed-search flex-shrink-0 w-100 w-md-auto" style="min-width: 220px; max-width: 320px;">
                        <input type="hidden" name="tab" id="notice-feed-tab-input" value="{{ $activeTabKey }}">
                        @if(!empty($highlightNoticePk))
                        <input type="hidden" name="notice" value="{{ $highlightNoticePk }}">
                        @endif
                        <div class="input-group input-group-sm shadow-sm">
                            <span class="input-group-text rounded-start-pill"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
                            <input type="search" name="q" value="{{ $q }}" class="form-control rounded-end-pill" placeholder="Search" autocomplete="off">
                        </div>
                    </form>
                </div>

                @foreach($noticeCategoryTabs as $tab)
                    @php $isActive = $tab['key'] === $activeTabKey; @endphp
                    <div class="notice-feed-pane {{ $isActive ? '' : 'd-none' }}" data-feed-pane="{{ $tab['key'] }}" role="tabpanel">
                        <div class="comms-hub-section-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="comms-hub-feed-icon rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                    <i class="bi bi-megaphone-fill"></i>
                                </span>
                                <h2 class="h6 mb-0 fw-bold text-body">
                                    <span class="badge text-bg-primary rounded-pill me-1">{{ str_pad((string) $tab['total'], 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $tab['total'] === 1 ? 'Notice' : 'Notices' }}
                                </h2>
                            </div>
                            @if(hasRole('Admin') || hasRole('Super Admin'))
                            <a href="{{ route('admin.notice.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="bi bi-gear me-1" aria-hidden="true"></i>Manage notices
                            </a>
                            @endif
                        </div>

                        <div class="comms-hub-feed-list vstack gap-2">
                        @forelse($tab['notices'] as $notice)
                            @php
                                $when = $notice->display_date ?? $notice->created_at ?? null;
                                $whenCarbon = $when ? \Carbon\Carbon::parse($when) : null;
                                $whenLabel = $whenCarbon ? $whenCarbon->format('d/m/Y h:i A') : '—';
                                $plainDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($notice->description ?? ''))));
                                $isHighlighted = !empty($highlightNoticePk) && (int) $notice->pk === (int) $highlightNoticePk;
                                $descPreview = \Illuminate\Support\Str::words($plainDesc, 20, '…');
                                $hasMoreDesc = $plainDesc !== '' && \Illuminate\Support\Str::wordCount($plainDesc) > 20;
                            @endphp
                            <article
                                id="notice-feed-card-{{ $notice->pk }}"
                                class="notice-feed-card comms-hub-notice-card comms-hub-desc-expandable shadow-sm {{ $isHighlighted ? 'notice-feed-card-highlight' : '' }}"
                                tabindex="0"
                            >
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                    <div class="min-w-0 flex-grow-1">
                                        <h3 class="notice-feed-card-title h6 fw-semibold mb-1">{{ $notice->notice_title }}</h3>
                                        @if(!empty($notice->subcategory_name))
                                        <span class="badge rounded-pill text-bg-info border border-info-subtle">
                                            <i class="bi bi-tag-fill me-1" aria-hidden="true"></i>{{ $notice->subcategory_name }}
                                        </span>
                                        @endif
                                    </div>
                                    <div class="notice-feed-card-meta small text-muted text-md-end flex-shrink-0">
                                        <i class="bi bi-person me-1" aria-hidden="true"></i>{{ $notice->creator_display ?? '—' }}
                                        <span class="mx-1 opacity-50">·</span>
                                        <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $whenLabel }}
                                    </div>
                                </div>
                                @if($plainDesc !== '')
                                <div class="comms-hub-desc-wrap">
                                    <p class="comms-hub-desc-preview notice-feed-card-body small text-body-secondary lh-base mb-0">{{ $descPreview }}</p>
                                    @if($hasMoreDesc)
                                    <div class="comms-hub-desc-detail-block">
                                        <div class="small text-muted mb-1 fw-semibold">Full description</div>
                                        <div class="notice-feed-card-body small text-body-secondary lh-base">{{ $plainDesc }}</div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                                @if(!empty($notice->document))
                                <div class="mt-3 pt-2 border-top border-light-subtle">
                                    <a href="{{ asset('storage/' . $notice->document) }}" target="_blank" rel="noopener"
                                        class="btn btn-sm btn-outline-danger rounded-pill">
                                        <i class="bi bi-paperclip me-1" aria-hidden="true"></i>View attachment
                                    </a>
                                </div>
                                @endif
                            </article>
                        @empty
                            <div class="comms-hub-empty-state text-center text-muted py-4">
                                <i class="bi bi-inbox d-block mb-2 fs-3 opacity-50" aria-hidden="true"></i>
                                <p class="small mb-0 fw-semibold">No notices in this category.</p>
                            </div>
                        @endforelse
                        </div>
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
        @if(!empty($highlightNoticePk))
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('notice-feed-card-{{ (int) $highlightNoticePk }}');
            if (el) {
                setTimeout(function () {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 200);
            }
        });
        </script>
        @endif
    @endif
</div>
@endsection
