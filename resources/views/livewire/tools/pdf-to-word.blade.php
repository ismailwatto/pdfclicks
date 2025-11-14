<div>
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PDF Document
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFile" 
                id="file-upload"
                accept=".pdf"
                class="hidden"
                {{ $isConverting ? 'disabled' : '' }}
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50 transition-colors {{ $isConverting ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-file-pdf text-4xl text-red-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedFile)
                            <span class="font-medium text-red-600">{{ $uploadedFile->getClientOriginalName() }}</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">PDF up to 10MB</p>
                </div>
            </label>
        </div>
        @error('uploadedFile') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Convert Button -->
    <div class="mb-6">
        <button 
            wire:click="convertToWord"
            class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors disabled:bg-red-300 disabled:cursor-not-allowed"
            {{ !$uploadedFile || $isConverting ? 'disabled' : '' }}
        >
            @if($isConverting)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Converting to Word...
            @else
                <i class="fas fa-file-word mr-2"></i>
                Convert to Word
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isConverting || $conversionProgress > 0)
        <div class="mb-6 p-4 bg-red-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-red-700">Conversion Progress</span>
                <span class="text-sm text-red-600">{{ $conversionProgress }}%</span>
            </div>
            <div class="w-full bg-red-200 rounded-full h-2">
                <div 
                    class="bg-red-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $conversionProgress }}%"
                ></div>
            </div>
            @if($conversionStatus)
                <p class="text-sm text-red-700 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ $conversionStatus }}
                </p>
            @endif
        </div>
    @endif

    <!-- Success Message -->
    @if($successMessage)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <span class="text-green-700">{{ $successMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Error Message -->
    @if($errorMessage)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <span class="text-red-700">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Download Section -->
    @if($convertedFile && !$isConverting)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">Word Document Ready</p>
                    <p class="text-sm text-gray-600">Your PDF has been converted to editable Word document</p>
                </div>
                <button 
                    wire:click="downloadFile"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download DOCX
                </button>
            </div>
        </div>
    @endif

    <!-- Reset Button -->
    @if($uploadedFile || $errorMessage || $successMessage)
        <div class="text-center">
            <button 
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                {{ $isConverting ? 'disabled' : '' }}
            >
                <i class="fas fa-redo mr-1"></i>
                Convert Another Document
            </button>
        </div>
    @endif

    <!-- Loading Spinner Overlay -->
    @if($isConverting)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-red-600 mb-4"></i>
                    <p class="text-gray-700">Converting your PDF...</p>
                    <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Auto-refresh progress every 2 seconds while converting
    setInterval(() => {
        if (@this.isConverting) {
            @this.call('checkProgress');
        }
    }, 2000);

    // Handle file upload loading state
    window.addEventListener('livewire:upload-start', () => {
        document.getElementById('file-upload').classList.add('pointer-events-none', 'opacity-50');
    });

    window.addEventListener('livewire:upload-finish', () => {
        document.getElementById('file-upload').classList.remove('pointer-events-none', 'opacity-50');
    });

    // Handle download ready event
    window.addEventListener('downloadReady', () => {
        setTimeout(() => {
            @this.call('downloadFile');
        }, 1000);
    });
</script>