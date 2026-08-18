@php $isActive = (int) $category->status === 1; @endphp
<span class="status-pill badge {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
    {{ $isActive ? 'Active' : 'Inactive' }}
</span>
