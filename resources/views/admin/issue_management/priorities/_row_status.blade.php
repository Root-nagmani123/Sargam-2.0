@php $isActive = (int) $priority->status === 1; @endphp
<span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
    {{ $isActive ? 'Active' : 'Inactive' }}
</span>
