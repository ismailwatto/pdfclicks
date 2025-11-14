@props([
    'show' => false,
    'progress' => 0,
    'status' => ''
])

@if($show)
    <div class="mb-6 p-4 bg-red-50 rounded-lg">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-red-700">Conversion Progress</span>
            <span class="text-sm text-red-600">{{ $progress }}%</span>
        </div>
        <div class="w-full bg-red-200 rounded-full h-2">
            <div 
                class="bg-red-600 h-2 rounded-full transition-all duration-300 ease-out"
                style="width: {{ $progress }}%"
            ></div>
        </div>
        @if($status)
            <p class="text-sm text-red-700 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                {{ $status }}
            </p>
        @endif
    </div>
@endif