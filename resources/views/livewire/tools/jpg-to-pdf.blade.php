<div>
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Upload JPG Images
        </label>
        <div class="relative">
            <input
                type="file"
                wire:model="uploadedFiles"
                id="file-upload"
                multiple
                accept=".jpg,.jpeg"
                class="hidden"
                {{ $isProcessing ? 'disabled' : '' }}
            >
            <label
                for="file-upload"
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors {{ $isProcessing ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if (count($uploadedFiles))
                            <span class="font-medium text-blue-600">{{ count($uploadedFiles) }} file(s) selected</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">JPG, JPEG up to 10MB each</p>
                </div>
            </label>
        </div>
        @error('uploadedFiles') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
        @error('uploadedFiles.*') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
    </div>

    @if (count($uploadedFiles))
        <div class="grid grid-cols-2 gap-2 mb-6">
            @foreach ($uploadedFiles as $index => $file)
                <div class="flex items-center justify-between px-3 py-2 bg-gray-100 border rounded">
                    <span class="truncate text-sm">{{ $file->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeFile({{ $index }})" class="ml-2 text-red-500 hover:text-red-700 text-xs">Remove</button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <label for="pageSize" class="block text-sm font-medium text-gray-700 mb-2">Page Size</label>
            <select wire:model="pageSize" id="pageSize" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="A4">A4</option>
                <option value="A3">A3</option>
                <option value="Letter">Letter</option>
                <option value="Legal">Legal</option>
            </select>
            @error('pageSize') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="orientation" class="block text-sm font-medium text-gray-700 mb-2">Orientation</label>
            <select wire:model="orientation" id="orientation" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>
            @error('orientation') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mb-6">
        <button
            wire:click="convertToPdf"
            class="w-full bg-[#E5322D] text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            {{ !count($uploadedFiles) || $isProcessing ? 'disabled' : '' }}
        >
            @if($isProcessing)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Converting...
            @else
                <i class="fas fa-file-pdf mr-2"></i>
                Convert to PDF
            @endif
        </button>
    </div>

    @if($isProcessing || $processingProgress > 0)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-700">Conversion Progress</span>
                <span class="text-sm text-blue-600">{{ $processingProgress }}%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out" style="width: {{ $processingProgress }}%"></div>
            </div>
            @if($processingStatus)
                <p class="text-sm text-blue-700 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ $processingStatus }}
                </p>
            @endif
        </div>
    @endif

    @if($successMessage)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <span class="text-green-700">{{ $successMessage }}</span>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <span class="text-red-700">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    @if($convertedFile && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">Converted PDF Ready</p>
                    <p class="text-sm text-gray-600">Your images have been successfully converted</p>
                </div>
                <button
                    wire:click="downloadFile"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download PDF
                </button>
            </div>
        </div>
    @endif

    @if(count($uploadedFiles) || $errorMessage || $successMessage)
        <div class="text-center">
            <button
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                {{ $isProcessing ? 'disabled' : '' }}
            >
                <i class="fas fa-redo mr-1"></i>
                Convert Another Set
            </button>
        </div>
    @endif

    @if($isProcessing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-700">Converting your images...</p>
                    <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
                </div>
            </div>
        </div>
    @endif

    <script>
        window.addEventListener('downloadReady', () => {
            setTimeout(() => {
                @this.call('downloadFile');
            }, 1000);
        });
    </script>
</div>
