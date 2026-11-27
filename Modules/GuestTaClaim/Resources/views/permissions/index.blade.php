@extends('admin.layouts.master')

@section('title', 'Permissions')
@section('page_title', 'Permissions')
@section('page_subtitle', 'Manage Guest TA Claim access for employees.')

@push('styles')
    <style>
        .gtc-ajax-loader {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .45);
        }

        .gtc-ajax-loader[hidden] {
            display: none !important;
        }

        .gtc-ajax-loader .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: .25rem;
        }

        #permissions-panels[aria-busy="true"] > .row {
            opacity: .55;
        }

        .gtc-permission-option .form-check-input {
            float: none;
            margin: 0 .5rem 0 0;
        }

    </style>
@endpush

@section('content')
@include('guesttaclaim::partials.style')

<div class="container-fluid profile-page">
    <x-breadcrum
        title="Permissions"
        :items="[
            'Home',
            ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
            'Permissions',
        ]"
    />

    @include('guesttaclaim::partials.flash')
    <div id="permissions-ajax-message" aria-live="polite"></div>

    <div class="d-flex justify-content-end mb-3 no-print">
        @include('guesttaclaim::partials.tools', ['type' => 'permissions', 'query' => []])
    </div>

    <div id="permissions-panels" class="position-relative">
        <div id="permissions-loader" class="gtc-ajax-loader" role="status" aria-label="Loading" hidden>
            <span class="spinner-border text-primary" aria-hidden="true"></span>
            <span class="visually-hidden">Loading</span>
        </div>
        <div class="row g-3">
            <div class="col-lg-5 col-xl-4" id="permissions-employee-panel">
                @include('guesttaclaim::permissions.partials.employee-list')
            </div>
            <div class="col-lg-7 col-xl-8" id="permissions-editor-panel">
                @include('guesttaclaim::permissions.partials.permission-editor')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
    <script>
      (function () {
        var employeePanel = document.getElementById('permissions-employee-panel');
        var editorPanel = document.getElementById('permissions-editor-panel');
        var messagePanel = document.getElementById('permissions-ajax-message');
        var panels = document.getElementById('permissions-panels');
        var loader = document.getElementById('permissions-loader');
        var activeRequest = null;
        var pendingRequests = 0;

        function beginLoading() {
          pendingRequests += 1;
          loader.hidden = false;
          panels.setAttribute('aria-busy', 'true');
        }

        function endLoading() {
          pendingRequests = Math.max(0, pendingRequests - 1);
          if (pendingRequests === 0) {
            loader.hidden = true;
            panels.removeAttribute('aria-busy');
          }
        }

        function showMessage(type, message) {
          messagePanel.replaceChildren();
          var alert = document.createElement('div');
          alert.className = 'alert alert-' + type;
          alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
          alert.textContent = message;
          messagePanel.appendChild(alert);
          requestAnimationFrame(function () {
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
          });
        }

        async function loadPanels(url, historyMode) {
          if (activeRequest) activeRequest.abort();
          var request = new AbortController();
          activeRequest = request;
          beginLoading();

          try {
            var response = await fetch(url, {
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              signal: request.signal
            });
            var data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not load employees.');

            employeePanel.innerHTML = data.employeeListHtml;
            editorPanel.innerHTML = data.permissionEditorHtml;

            if (historyMode === 'push') {
              window.history.pushState({}, '', url);
            } else if (historyMode === 'replace') {
              window.history.replaceState({}, '', url);
            }
          } catch (error) {
            if (error.name !== 'AbortError') {
              showMessage('danger', error.message || 'Could not load employees. Please try again.');
            }
          } finally {
            if (activeRequest === request) activeRequest = null;
            endLoading();
          }
        }

        employeePanel.addEventListener('submit', function (event) {
          var form = event.target.closest('#permissions-search-form');
          if (!form) return;

          event.preventDefault();
          loadPanels(searchUrl(form).toString(), 'push');
        });

        function searchUrl(form) {
          var url = new URL(form.action, window.location.href);
          var params = new URLSearchParams(new FormData(form));
          params.delete('page');
          params.delete('user');
          if (!params.get('q') || !params.get('q').trim()) params.delete('q');
          url.search = params.toString();
          return url;
        }

        employeePanel.addEventListener('input', function (event) {
          var input = event.target.closest('#permissions-search-form input[name="q"]');
          if (!input || input.value.trim() !== '') return;

          var currentUrl = new URL(window.location.href);
          if (!currentUrl.searchParams.get('q')) return;

          loadPanels(searchUrl(input.form).toString(), 'push');
        });

        employeePanel.addEventListener('click', function (event) {
          var link = event.target.closest('a[href]');
          if (!link || !(link.classList.contains('gtc-emp') || link.closest('.gtc-tabs') || link.closest('.pagination'))) return;

          event.preventDefault();
          loadPanels(link.href, 'push');
        });

        editorPanel.addEventListener('click', function (event) {
          var cancelLink = event.target.closest('[data-permissions-cancel]');
          if (!cancelLink) return;

          event.preventDefault();
          loadPanels(cancelLink.href, 'none');
        });

        editorPanel.addEventListener('submit', async function (event) {
          var form = event.target.closest('#permission-update-form');
          if (!form) return;

          event.preventDefault();
          var submitButton = form.querySelector('button[type="submit"]');
          submitButton.disabled = true;
          beginLoading();

          try {
            var response = await fetch(form.action, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: new FormData(form)
            });
            var data = await response.json();
            if (!response.ok) {
              var validationErrors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
              throw new Error(validationErrors || data.message || 'Could not save permissions.');
            }

            showMessage('success', data.message);
            await loadPanels(window.location.href, 'none');
          } catch (error) {
            showMessage('danger', error.message || 'Could not save permissions. Please try again.');
          } finally {
            submitButton.disabled = false;
            endLoading();
          }
        });

        window.addEventListener('popstate', function () {
          loadPanels(window.location.href, 'none');
        });
      })();
    </script>
@endpush
