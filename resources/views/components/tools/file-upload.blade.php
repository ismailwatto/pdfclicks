@props([
    'file' => null,
    'accept' => '.pdf',
    'label' => 'Select PDF Document',
    'maxSize' => '15MB',
    'wireModel' => 'uploadedFile',
    'converting' => false,
    'multiple' => false
])

@php
    // Handle wire-model attribute conversion
    $wireModel = $attributes->get('wire-model', $wireModel);
@endphp

<div class="mb-6">
    <label for="file-upload-{{ $wireModel }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
    </label>
    <div class="relative">
        <input 
            type="file" 
            wire:model="{{ $wireModel }}" 
            id="file-upload-{{ $wireModel }}"
            accept="{{ $accept }}"
            class="hidden"
            {{ $converting ? 'disabled' : '' }}
            {{ $multiple === 'true' || $multiple === true ? 'multiple' : '' }}
        >
        <label 
            for="file-upload-{{ $wireModel }}" 
            class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50 transition-colors {{ $converting ? 'pointer-events-none opacity-50' : '' }}"
        >
            <div class="text-center">
                <i class="fas fa-file-pdf text-4xl text-red-400 mb-2"></i>
                <p class="text-sm text-gray-600">
                    @if($file)
                        <span class="font-medium text-red-600">
                            @if(is_array($file))
                                {{ count($file) }} file(s) selected
                            @else
                                {{ $file->getClientOriginalName() }}
                            @endif
                        </span>
                    @else
                        Click to upload or drag and drop
                    @endif
                </p>
                <p class="text-xs text-gray-500">PDF up to {{ $maxSize }}</p>
            </div>
        </label>
    </div>
    @error($wireModel) 
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
    @enderror
</div>