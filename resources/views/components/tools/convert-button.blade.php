@props([
    'converting' => false,
    'disabled' => false,
    'action' => 'convert',
    'icon' => 'fas fa-file-word',
    'text' => 'Convert',
    'loadingText' => null
])

<div class="mb-6">
    <button 
        wire:click="{{ $action }}"
        class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors disabled:bg-red-300 disabled:cursor-not-allowed"
        {{ $disabled || $converting ? 'disabled' : '' }}
    >
        @if($converting)
            <i class="fas fa-spinner fa-spin mr-2"></i>
            {{ $loadingText ?? 'Processing...' }}
        @else
            <i class="{{ $icon }} mr-2"></i>
            {{ $text }}
        @endif
    </button>
</div>