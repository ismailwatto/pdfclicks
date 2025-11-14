<div>
    <!-- File Upload -->
    <div class="mb-6">
        <label for="pdf-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PDF File
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedPdf" 
                id="pdf-upload"
                accept=".pdf"
                class="hidden"
                {{ $isProcessing ? 'disabled' : '' }}
            >
            <label 
                for="pdf-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50 transition-colors {{ $isProcessing ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedPdf)
                            <span class="font-medium text-red-600">{{ $uploadedPdf->getClientOriginalName() }}</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">PDF files up to 50MB</p>
                </div>
            </label>
        </div>
        @error('uploadedPdf') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Image Quality -->
    <div class="mb-6">
        <label for="imageQuality" class="block text-sm font-medium text-gray-700 mb-2">
            Image Quality (DPI)
        </label>
        <select 
            id="imageQuality" 
            wire:model="imageQuality" 
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
            {{ $isProcessing ? 'disabled' : '' }}
        >
            <option value="72">72 DPI (Small file size)</option>
            <option value="96">96 DPI (Web quality)</option>
            <option value="150">150 DPI (High quality)</option>
            <option value="300">300 DPI (Print quality)</option>
        </select>
        @error('imageQuality') 
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Convert Button -->
    <div class="mb-6">
        <button 
            wire:click="convertToPng"
            class="w-full bg-[#E5322D] text-white py-3 px-4 rounded-lg font-medium hover:bg-red-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
            {{ !$uploadedPdf || $isProcessing ? 'disabled' : '' }}
        >
            @if($isProcessing)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Converting...
            @else
                <i class="fas fa-images mr-2"></i>
                Convert to PNG
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isProcessing || $processingProgress > 0)
        <div class="mb-6 p-4 bg-red-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-red-700">Conversion Progress</span>
                <span class="text-sm text-red-600">{{ $processingProgress }}%</span>
            </div>
            <div class="w-full bg-red-200 rounded-full h-2">
                <div 
                    class="bg-red-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $processingProgress }}%"
                ></div>
            </div>
            @if($processingStatus)
                <p class="text-sm text-red-700 mt-2">
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
    @if(!empty($convertedImages) && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-medium text-gray-900">Converted PNG Images</p>
                    <p class="text-sm text-gray-600">{{ count($convertedImages) }} image(s)</p>
                </div>
                <button 
                    wire:click="downloadAllImages"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download All (ZIP)
                </button>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($convertedImages as $image)
                    <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Page {{ $image['page'] }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($image['size'] / 1024, 2) }} KB</p>
                            </div>
                            <button 
                                wire:click="downloadImage('{{ $image['file_path'] }}', '{{ $image['filename'] }}')"
                                class="text-blue-600 hover:text-blue-700 p-2 rounded-full transition"
                                title="Download {{ $image['filename'] }}"
                            >
                                <i class="fas fa-download text-sm"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Reset Button -->
    @if($uploadedPdf || $errorMessage || $successMessage)
        <div class="text-center">
            <button 
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                {{ $isProcessing ? 'disabled' : '' }}
            >
                <i class="fas fa-redo mr-1"></i>
                Convert Another PDF
            </button>
        </div>
    @endif

    <!-- Loading Overlay -->
    @if($isProcessing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-gray-700">Converting your PDF...</p>
                <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
            </div>
        </div>
    @endif
</div>

<script>
    setInterval(() => {
        if (@this.isProcessing) {
            @this.call('getProgress');
        }
    }, 2000);

    window.addEventListener('livewire:upload-start', () => {
        document.getElementById('pdf-upload').classList.add('pointer-events-none', 'opacity-50');
    });

    window.addEventListener('livewire:upload-finish', () => {
        document.getElementById('pdf-upload').classList.remove('pointer-events-none', 'opacity-50');
    });
</script>
