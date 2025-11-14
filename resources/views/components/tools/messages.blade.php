@props([
    'success' => '',
    'error' => ''
])

<!-- Success Message -->
@if($success)
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-2"></i>
            <span class="text-green-700">{{ $success }}</span>
        </div>
    </div>
@endif

<!-- Error Message -->
@if($error)
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
            <span class="text-red-700">{{ $error }}</span>
        </div>
    </div>
@endif