@props(['label', 'value'])

<div class="flex flex-col">
    <span class="text-sm text-gray-500">{{ $label }}</span>
    <span class="font-medium">{{ $value ?? '-' }}</span>
</div>
