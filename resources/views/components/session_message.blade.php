@if (session('success'))
    <div class="alert customize-alert rounded-pill alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Success - </strong> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert customize-alert rounded-pill alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Error - </strong> {{ session('error') }}
    </div>
@endif
@if (session('errors'))
    @foreach (session('errors')->all() as $error)
        <div class="alert customize-alert rounded-pill alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong>Error - </strong> {{ $error }}
        </div>
    @endforeach
@endif
{{-- Validation errors are shown only below each field (no duplicate list at top) --}}
<div id="status-msg"></div>

@if (session('success') || session('error') || session('errors'))
    <script>
        (function () {
            // Auto-dismiss flash alerts after 2 seconds
            setTimeout(function () {
                document.querySelectorAll('.alert.customize-alert').forEach(function (el) {
                    if (window.bootstrap && bootstrap.Alert) {
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    } else {
                        el.classList.remove('show');
                        setTimeout(function () { el.remove(); }, 200);
                    }
                });
            }, 2000);
        })();
    </script>
@endif
