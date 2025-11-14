<div>
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PDF Documents to Merge
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFiles" 
                id="file-upload"
                accept=".pdf"
                multiple
                class="hidden"
                {{ $isProcessing ? 'disabled' : '' }}
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-red-300 rounded-lg cursor-pointer hover:border-red-500 hover:bg-indigo-50 transition-colors {{ $isProcessing ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-object-group text-4xl text-red-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedFiles && count($uploadedFiles) > 0)
                            <span class="font-medium text-indigo-600">{{ count($uploadedFiles) }} PDF files selected</span>
                        @else
                            Click to upload or drag and drop multiple PDF files
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">Select multiple PDF files (up to 10MB each)</p>
                </div>
            </label>
        </div>
        @error('uploadedFiles') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- File List -->
    @if($uploadedFiles && count($uploadedFiles) > 0)
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Files to Merge (Order matters):</h3>
            <div class="space-y-2">
                @foreach($uploadedFiles as $index => $file)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <span class="text-xs font-medium text-gray-500 mr-2">{{ $index + 1 }}.</span>
                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                            <span class="text-sm text-gray-700">{{ $file->getClientOriginalName() }}</span>
                            <span class="text-xs text-gray-500 ml-2">({{ round($file->getSize() / 1024, 1) }} KB)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button 
                                wire:click="moveUp({{ $index }})"
                                class="text-green-400 hover:text-gray-600 transition-colors {{ $index === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $index === 0 || $isProcessing ? 'disabled' : '' }}
                                title="Move up"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                </svg>

                            </button>
                            <button 
                                wire:click="moveDown({{ $index }})"
                                class="text-gray-400 hover:text-gray-600 transition-colors {{ $index === count($uploadedFiles) - 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $index === count($uploadedFiles) - 1 || $isProcessing ? 'disabled' : '' }}
                                title="Move down"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
</svg>

                            </button>
                            <button 
                                wire:click="removeFile({{ $index }})"
                                class="text-red-400 hover:text-red-600 transition-colors"
                                {{ $isProcessing ? 'disabled' : '' }}
                                title="Remove file"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
</svg>

                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Merge Button -->
    <div class="mb-6">
        <button 
            wire:click="mergePdfs"
            class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors disabled:bg-red-300 disabled:cursor-not-allowed"
            {{ !$uploadedFiles || count($uploadedFiles) < 2 || $isProcessing ? 'disabled' : '' }}
        >
            @if($isProcessing)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Merging PDFs...
            @else
                <i class="fas fa-object-group mr-2"></i>
                Merge {{ count($uploadedFiles) > 0 ? count($uploadedFiles) : '' }} PDFs
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isProcessing || $processingProgress > 0)
        <div class="mb-6 p-4 bg-indigo-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-indigo-700">Merge Progress</span>
                <span class="text-sm text-indigo-600">{{ $processingProgress }}%</span>
            </div>
            <div class="w-full bg-indigo-200 rounded-full h-2">
                <div 
                    class="bg-indigo-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $processingProgress }}%"
                ></div>
            </div>
            @if($processingStatus)
                <p class="text-sm text-indigo-700 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ $processingStatus }}
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
    @if($mergedFile && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">Merged PDF Ready</p>
                    <p class="text-sm text-gray-600">Your PDF files have been successfully merged</p>
                </div>
                <button 
                    wire:click="downloadFile"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download Merged PDF
                </button>
            </div>
        </div>
    @endif

    <!-- Reset Button -->
    @if($uploadedFiles || $errorMessage || $successMessage)
        <div class="text-center">
            <button 
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                {{ $isProcessing ? 'disabled' : '' }}
            >
                <i class="fas fa-redo mr-1"></i>
                Merge Another Set of Files
            </button>
        </div>
    @endif

    <!-- Loading Spinner Overlay -->
    @if($isProcessing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-4"></i>
                    <p class="text-gray-700">Merging your PDFs...</p>
                    <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Handle file upload loading state
    window.addEventListener('livewire:upload-start', () => {
        const fileInput = document.getElementById('file-upload');
        const label = fileInput.nextElementSibling;
        if (label) {
            label.classList.add('pointer-events-none', 'opacity-50');
        }
    });

    window.addEventListener('livewire:upload-finish', () => {
        const fileInput = document.getElementById('file-upload');
        const label = fileInput.nextElementSibling;
        if (label) {
            label.classList.remove('pointer-events-none', 'opacity-50');
        }
    });

    // Handle download ready event
    window.addEventListener('downloadReady', () => {
        setTimeout(() => {
            @this.call('downloadFile');
        }, 1000);
    });

    // Auto-scroll to progress section when processing starts
    document.addEventListener('livewire:update', () => {
        if (@this.isProcessing) {
            const progressSection = document.querySelector('.bg-indigo-50');
            if (progressSection) {
                progressSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
</script>