@extends(config('guest-ta-claim.layout', 'guesttaclaim::layouts.app'))



@section('content')
    <div class="gtc-shell">
        @include('guesttaclaim::partials.sidebar')
        <main class="gtc-main" id="gtc-content">
            @include('guesttaclaim::partials.flash')
            @yield('gtc-content')
        </main>
    </div>
    @include('guesttaclaim::partials.session-modal')
    @include('guesttaclaim::partials.action-modals')
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
@endpush
