@extends('admin.layouts.master')

@section('title', 'My Nominations')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="My Nominations" :showBack="false" />

    <x-session_message />

    @forelse ($drives as $drive)
        @php $mineRow = $mine->get($drive->id); @endphp
        <div class="am-panel">
            <div class="am-panel__head d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h6 mb-1">{{ $drive->form_title }}</h2>
                    <div class="am-fact__label">
                        {{ \App\Http\Controllers\Admin\Athletics\AthleticsController::courseLabel($drive->course) }}
                        • {{ $drive->start_date->format('d M Y') }} – {{ $drive->end_date->format('d M Y') }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if ($mineRow)
                        @include('admin.athletics.partials.status_tag', ['status' => $mineRow->status])
                    @else
                        <span class="ux4g-tag-tonal-{{ $drive->isOpen() ? 'primary' : 'neutral' }}">{{ $drive->isOpen() ? 'Open' : 'Closed' }}</span>
                    @endif
                    <a href="{{ route('athletics.my-nominations.show', $drive) }}"
                       class="{{ $drive->isOpen() && ! $mineRow ? 'ux4g-btn-primary' : 'ux4g-btn-outline-primary' }} ux4g-btn-s">
                        {{ $mineRow ? 'View nomination' : ($drive->isOpen() ? 'Nominate' : 'View') }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="am-panel">
            <div class="am-panel__section text-center py-5">
                <span class="material-icons material-symbols-rounded d-block mb-2 text-secondary" aria-hidden="true">sports_score</span>
                <p class="mb-0">There is no athletics meet open for nominations right now.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
