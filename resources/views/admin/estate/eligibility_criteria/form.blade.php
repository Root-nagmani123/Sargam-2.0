@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Eligibility Criteria - Sargam')

{{-- Full-page Add / Edit. The index opens the same fields in a modal; this page
     stays for direct links and posts to the same store / update routes. --}}
@php
    $selects = [
        ['name' => 'salary_grade_master_pk', 'label' => 'Pay Scale', 'options' => $payScales, 'placeholder' => 'Select pay scale'],
        ['name' => 'estate_unit_type_master_pk', 'label' => 'Unit Type', 'options' => $unitTypes, 'placeholder' => 'Select unit type'],
        ['name' => 'estate_unit_sub_type_master_pk', 'label' => 'Unit Sub Type', 'options' => $unitSubTypes, 'placeholder' => 'Select unit sub type'],
    ];
@endphp

@section('setup_content')
<div class="container-fluid em-page">
    <x-breadcrum :title="($item ? 'Edit' : 'Add') . ' Eligibility Criteria'" />

    <x-session_message />

    <section class="card ds-card" aria-labelledby="emFormTitle">
        <div class="ds-card-header">
            <h2 class="em-card-title" id="emFormTitle">{{ $item ? 'Edit' : 'Add' }} Eligibility Criteria</h2>
        </div>
        <div class="ds-card-body">
            <form action="{{ $item ? route('admin.estate.eligibility-criteria.update', $item->pk) : route('admin.estate.eligibility-criteria.store') }}"
                method="POST" class="em-form-narrow">
                @csrf
                @if($item) @method('PUT') @endif

                @foreach($selects as $s)
                @php
                    $err = $errors->first($s['name']);
                    $sel = (string) old($s['name'], $item->{$s['name']} ?? '');
                @endphp
                <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
                    <label class="ds-form-label" for="{{ $s['name'] }}">{{ $s['label'] }}<span class="ds-req" aria-hidden="true">*</span></label>
                    <select class="form-select @if($err) is-invalid @endif" id="{{ $s['name'] }}" name="{{ $s['name'] }}"
                        data-searchable="true" data-placeholder="{{ $s['placeholder'] }}" required aria-required="true"
                        @if($err) aria-invalid="true" aria-describedby="{{ $s['name'] }}_error" @endif>
                        <option value="">{{ $s['placeholder'] }}</option>
                        @foreach($s['options'] as $pk => $label)
                        <option value="{{ $pk }}" @selected($sel === (string) $pk)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($err)
                    <div class="invalid-feedback d-block" id="{{ $s['name'] }}_error">{{ $err }}</div>
                    @endif
                </div>
                @endforeach

                <div class="ds-form-footer">
                    <a href="{{ route('admin.estate.eligibility-criteria.index') }}" class="btn ds-btn-cancel">Cancel</a>
                    <button type="submit" class="btn ds-btn-primary">{{ $item ? 'Update' : 'Add' }} Criteria</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('styles')
{{-- Select2 JS is global and dropdown-search.js picks up data-searchable selects. --}}
@include('admin.layouts.partials.select2-assets')
@include('admin.estate.partials.master_styles')
@endpush
