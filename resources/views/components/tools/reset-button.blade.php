@props([
    'show' => false,
    'disabled' => false,
    'action' => 'resetConverter'
])

@if($show)
    <div class="text-center">
        <button 
            wire:click="{{ $action }}"
            class="text-gray-600 hover:text-gray-800 underline"
            {{ $disabled ? 'disabled' : '' }}
        >
            <i class="fas fa-redo mr-1"></i>
            Convert Another Document
        </button>
    </div>
@endif