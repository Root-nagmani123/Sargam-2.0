@php
    use App\Models\Athletics\AthleticsEvent;
    use App\Http\Controllers\Admin\Athletics\AthleticsController;
@endphp
@extends('admin.layouts.master')

@section('title', $drive->form_title)

@include('admin.athletics.partials.assets')

{{-- One view, two uses: the OT's form, and the admin's read-only "Preview Form"
     ($preview — no student, nothing submits). Layout follows the Figma frame
     "Athletics Meet Nomination — OT Participant". --}}
@php
    $byCategory = $drive->events->groupBy('category');
    $picked = collect(old('event_ids', $picked->all()))->map(fn ($id) => (int) $id);
    $locked = $preview || ! $drive->isOpen() || ($nomination && $nomination->status !== 'pending');
    $perCategory = $drive->max_criteria === 'event';
    $max = (int) $drive->max_nominations;
    $houseName = $house->name ?? null;
@endphp

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum :title="$preview ? 'Preview: '.$drive->form_title : 'Nomination'" :showBack="! $preview" />

    <x-session_message />

    <form method="POST" action="{{ $preview ? '#' : route('athletics.my-nominations.store', $drive) }}" id="amNomForm"
          data-limit="{{ $max }}" data-per-category="{{ $perCategory ? 1 : 0 }}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="am-panel">
                    <div class="am-panel__head d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $drive->form_title }}</h2>
                            <div class="am-fact__label">
                                {{ AthleticsController::courseLabel($drive->course) }} • Closes {{ $drive->end_date->format('d M Y') }}
                            </div>
                        </div>
                        @if ($nomination)
                            @include('admin.athletics.partials.status_tag', ['status' => $nomination->status])
                        @else
                            <span class="ux4g-tag-tonal-{{ $drive->isOpen() ? 'primary' : 'neutral' }}">{{ $drive->isOpen() ? 'Open' : 'Closed' }}</span>
                        @endif
                    </div>

                    <div class="am-panel__section">
                        <div class="row g-3">
                            <div class="col-6 col-md-3"><div class="am-fact__label">OT code</div><div class="am-fact__value">{{ $student->generated_OT_code ?? '—' }}</div></div>
                            <div class="col-6 col-md-3"><div class="am-fact__label">Name</div><div class="am-fact__value">{{ $student->display_name ?? '—' }}</div></div>
                            <div class="col-6 col-md-3"><div class="am-fact__label">Service</div><div class="am-fact__value">{{ $student?->service?->service_short_name ?: ($student?->service?->service_name ?? '—') }}</div></div>
                            <div class="col-6 col-md-3"><div class="am-fact__label">House</div><div class="am-fact__value">{{ $houseName ?? '—' }}</div></div>
                        </div>
                    </div>

                    @if (filled($drive->instructions))
                        <div class="am-panel__section">{!! nl2br(e($drive->instructions)) !!}</div>
                    @endif

                    @foreach (AthleticsEvent::CATEGORIES as $category => $label)
                        @continue(! $byCategory->has($category))
                        @php $isGroup = $category === 'group'; @endphp
                        <div class="am-panel__section" data-category="{{ $category }}">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-1">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="am-step">{{ $loop->iteration }}</span>
                                    <h3 class="h6 mb-0">Select {{ $isGroup ? '' : 'Individual ' }}{{ $label }}</h3>
                                </div>
                                <span class="ux4g-tag-tonal-primary" data-counter>
                                    {{ $isGroup ? 'Optional' : ($perCategory ? '0 of up to '.$max : 'Counts toward '.$max) }}
                                </span>
                            </div>
                            <p class="am-fact__label mb-3">
                                @if ($isGroup)
                                    1 team per house. The house in-charge will confirm the final team.
                                @elseif ($perCategory)
                                    Select up to {{ $max }} {{ strtolower($label) }}.
                                @else
                                    Select up to {{ $max }} individual events in total.
                                @endif
                            </p>
                            <div class="row g-3">
                                @foreach ($byCategory[$category] as $event)
                                    <div class="col-md-6">
                                        <label class="am-pick">
                                            <input type="checkbox" class="form-check-input" name="event_ids[]" value="{{ $event->id }}"
                                                   data-name="{{ $event->name }}" data-category="{{ $category }}"
                                                   @checked($picked->contains($event->id)) @disabled($locked)>
                                            <span>{{ $event->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @if ($isGroup && $houseName)
                                <div class="am-note mt-3">Your interest will be shared with the {{ $houseName }} house in-charge for team selection.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4">
                <div class="am-panel">
                    <div class="am-panel__section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="h6 mb-0">Your selections</h2>
                            <span class="ux4g-tag-tonal-primary" id="amNomCount">0 events</span>
                        </div>
                        <ul class="list-unstyled mb-0 am-summary" id="amNomSummary" aria-live="polite"></ul>

                        @unless ($locked)
                            <label class="am-pick mt-3 align-items-start">
                                <input type="checkbox" class="form-check-input mt-1" name="declaration" value="1" id="amNomDeclare">
                                <span>I confirm I am medically fit to take part in the selected events and will report at the venue on time.</span>
                            </label>
                            <button type="submit" class="ux4g-btn-primary ux4g-btn-m w-100 mt-3" id="amNomSubmit" disabled>
                                {{ $nomination ? 'Update nomination' : 'Submit nomination' }}
                            </button>
                            <p class="am-fact__label mt-2 mb-0">Confirm the declaration to submit.</p>
                        @else
                            <p class="am-fact__label mt-3 mb-0">
                                {{ $preview ? 'Preview only — this is how OTs will see the form.' : ($nomination && $nomination->status !== 'pending' ? 'Your nomination has been '.$nomination->status.'.' : 'Nominations are closed.') }}
                            </p>
                        @endunless
                    </div>
                </div>

                <div class="am-panel">
                    <div class="am-panel__section">
                        <h2 class="h6">Rules</h2>
                        <ul class="mb-0 ps-3">
                            @foreach (['track', 'field'] as $category)
                                @if ($perCategory && $byCategory->has($category))
                                    <li class="mb-2">{{ AthleticsEvent::CATEGORIES[$category] }}: up to {{ $max }}</li>
                                @endif
                            @endforeach
                            @unless ($perCategory)
                                <li class="mb-2">Individual events: up to {{ $max }} in total</li>
                            @endunless
                            @if ($byCategory->has('group'))
                                <li>Group events: optional, 1 team per house</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var $form = $('#amNomForm');
    var limit = Number($form.data('limit'));
    var perCategory = String($form.data('per-category')) === '1';
    var locked = {{ $locked ? 'true' : 'false' }};
    var $boxes = $form.find('input[name="event_ids[]"]');

    // Mirrors AthleticsDrive::limitError(): group events never count.
    function refresh() {
        var counts = {}, total = 0, $sum = $('#amNomSummary').empty();
        $boxes.filter(':checked').each(function () {
            var c = this.dataset.category;
            if (c !== 'group') { counts[c] = (counts[c] || 0) + 1; total++; }
            $('<li></li>').append($('<span></span>').text(this.dataset.name))
                .append('<span class="text-primary small">Selected</span>').appendTo($sum);
        });
        var n = $boxes.filter(':checked').length;
        $('#amNomCount').text(n + (n === 1 ? ' event' : ' events'));

        if (locked) { return; }
        $boxes.each(function () {
            var c = this.dataset.category;
            var full = c !== 'group' && (perCategory ? (counts[c] || 0) >= limit : total >= limit);
            this.disabled = full && !this.checked;
        });
        $form.find('.am-panel__section[data-category]').each(function () {
            var c = this.dataset.category;
            if (perCategory && c !== 'group') {
                $(this).find('[data-counter]').text((counts[c] || 0) + ' of up to ' + limit);
            }
        });
        $('#amNomSubmit').prop('disabled', !(n > 0 && $('#amNomDeclare').is(':checked')));
    }

    $form.on('change', 'input[type="checkbox"]', refresh);
    refresh();
});
</script>
@endpush
