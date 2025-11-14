<div>
    <!-- File Upload -->
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PDF File
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFile" 
                id="file-upload"
                accept=".pdf"
                class="hidden"
                {{ $isProcessing ? 'disabled' : '' }}
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors {{ $isProcessing ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-file-pdf text-4xl text-red-500 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedFile)
                            <span class="font-medium text-blue-600">{{ $uploadedFile->getClientOriginalName() }}</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">PDF file up to 20MB</p>
                </div>
            </label>
        </div>
        @error('uploadedFile') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Quality Select -->
    <div class="mb-6">
        <label for="imageQuality" class="block text-sm font-medium text-gray-700 mb-2">
            Select Image Quality
        </label>
        <select id="imageQuality" wire:model="imageQuality" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        @error('imageQuality') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Convert Button -->
    <div class="mb-6">
        <button 
            wire:click="convertToJpg"
            class="w-full bg-[#E5322D] text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:bg-[#E5322D]-200 cursor-pointer disabled:cursor-not-allowed"
            {{ !$uploadedFile || $isProcessing ? 'disabled' : '' }}
        >
            @if($isProcessing)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Converting...
            @else
                <i class="fas fa-image mr-2"></i>
                Convert to JPG
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isProcessing || $processingProgress > 0)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-700">Conversion Progress</span>
                <span class="text-sm text-blue-600">{{ $processingProgress }}%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $processingProgress }}%"
                ></div>
            </div>
            @if($processingStatus)
                <p class="text-sm text-blue-700 mt-2">
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
    @if(count($convertedImages) && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-medium text-gray-900">Converted Images Ready</p>
                    <p class="text-sm text-gray-600">Download all converted JPGs</p>
                </div>
                <button 
                    wire:click="downloadAllImages"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download ZIP
                </button>
            </div>

            <!-- Individual Image Downloads -->
            <ul class="list-disc pl-5 text-sm text-gray-700">
                @foreach($convertedImages as $index => $imagePath)
                    <li class="mb-1">
                        <button wire:click="downloadImage({{ $index }})" class="text-blue-600 hover:underline">
                            Download Page {{ $index + 1 }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Reset Button -->
    @if($uploadedFile || $errorMessage || $successMessage)
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

    <!-- Spinner Overlay -->
    @if($isProcessing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-700">Converting your PDF...</p>
                    <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
                </div>
            </div>
        </div>
    @endif
</div>
