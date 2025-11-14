@props([
    'show' => false,
    'message' => 'Converting your PDF...',
    'subMessage' => 'Please wait, this may take a few moments'
])

@if($show)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full mx-4">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-gray-700 font-medium">{{ $message }}</p>
                <p class="text-sm text-gray-500 mt-2">{{ $subMessage }}</p>
            </div>
        </div>
    </div>
@endif