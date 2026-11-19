{{--
    Full-page Add / Edit for an Estate master. The index pages open the same form
    in a modal (partials/master_crud.blade.php); this page stays for direct links
    and posts to the same store / update routes with the same fields.

    Design: docs/design.md — .ds-card, .ds-form-label / .ds-req, .ds-form-footer,
    .ds-btn-primary / .ds-btn-cancel; styles in partials/master_styles.blade.php.

    Expects $cfg: title, singular, routePrefix, fields (see master_crud) and
    $item (null for Add).
--}}
@php
    $isEdit = ! empty($item);
    $indexUrl = route($cfg['routePrefix'] . '.index');
@endphp

<div class="container-fluid em-page">
    {{-- Trail comes from the menu tree; the component adds Back on create/edit routes. --}}
    <x-breadcrum :title="($isEdit ? 'Edit ' : 'Add ') . $cfg['singular']" />

    <x-session_message />

    <section class="card ds-card" aria-labelledby="emFormTitle">
        <div class="ds-card-header">
            <h2 class="em-card-title" id="emFormTitle">{{ $isEdit ? 'Edit' : 'Add' }} {{ $cfg['singular'] }}</h2>
        </div>
        <div class="ds-card-body">
            <form action="{{ $isEdit ? route($cfg['routePrefix'] . '.update', $item->pk) : route($cfg['routePrefix'] . '.store') }}"
                method="POST" class="em-form-narrow">
                @csrf
                @if($isEdit) @method('PUT') @endif

                @foreach($cfg['fields'] as $f)
                @php
                    $id = $f['name'];
                    $err = $errors->first($f['name']);
                    $val = old($f['name'], $item->{$f['name']} ?? '');
                @endphp
                <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
                    <label class="ds-form-label" for="{{ $id }}">
                        {{ $f['label'] }}@if(! empty($f['required']))<span class="ds-req" aria-hidden="true">*</span>@endif
                    </label>
                    @if(($f['type'] ?? 'text') === 'textarea')
                    <textarea class="form-control @if($err) is-invalid @endif" id="{{ $id }}" name="{{ $f['name'] }}" rows="3"
                        @if(! empty($f['maxlength'])) maxlength="{{ $f['maxlength'] }}" @endif
                        @if(! empty($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif
                        @if(! empty($f['required'])) required aria-required="true" @endif
                        @if($err) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>{{ $val }}</textarea>
                    @else
                    <input type="text" class="form-control @if($err) is-invalid @endif" id="{{ $id }}" name="{{ $f['name'] }}"
                        value="{{ $val }}" autocomplete="off"
                        @if(! empty($f['maxlength'])) maxlength="{{ $f['maxlength'] }}" @endif
                        @if(! empty($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif
                        @if(! empty($f['required'])) required aria-required="true" @endif
                        @if($err) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
                    @endif
                    @if($err)
                    <div class="invalid-feedback d-block" id="{{ $id }}_error">{{ $err }}</div>
                    @endif
                </div>
                @endforeach

                <div class="ds-form-footer">
                    <a href="{{ $indexUrl }}" class="btn ds-btn-cancel">Cancel</a>
                    <button type="submit" class="btn ds-btn-primary">{{ $isEdit ? 'Update' : 'Add' }} {{ $cfg['singular'] }}</button>
                </div>
            </form>
        </div>
    </section>
</div>

@push('styles')
@include('admin.estate.partials.master_styles')
@endpush
