<div>
    <!-- File Upload -->
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PNG Files
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFiles" 
                id="file-upload"
                accept=".png"
                multiple
                class="hidden"
                {{ $isProcessing ? 'disabled' : '' }}
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors {{ $isProcessing ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-file-image text-4xl text-green-500 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if(count($uploadedFiles) > 0)
                            <span class="font-medium text-blue-600">{{ count($uploadedFiles) }} file(s) selected</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">PNG files up to 10MB each</p>
                </div>
            </label>
        </div>
        @error('uploadedFiles.*') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- File List -->
    @if(count($uploadedFiles) > 0 && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium text-gray-900 mb-3">Selected Files</h3>
            <div class="space-y-2">
                @foreach($uploadedFiles as $index => $file)
                    <div class="flex items-center justify-between p-2 bg-white rounded border">
                        <div class="flex items-center">
                            <i class="fas fa-file-image text-green-500 mr-2"></i>
                            <span class="text-sm text-gray-700">{{ $file->getClientOriginalName() }}</span>
                        </div>
                        <button 
                            wire:click="removeFile({{ $index }})"
                            class="text-red-500 hover:text-red-700 text-sm"
                            title="Remove"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quality Settings -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- PDF Quality -->
        <div>
            <label for="pdfQuality" class="block text-sm font-medium text-gray-700 mb-2">
                PDF Quality
            </label>
            <select 
                id="pdfQuality" 
                wire:model="pdfQuality" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            >
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            @error('pdfQuality') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Page Size -->
        <div>
            <label for="pageSize" class="block text-sm font-medium text-gray-700 mb-2">
                Page Size
            </label>
            <select 
                id="pageSize" 
                wire:model="pageSize" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            >
                <option value="A4">A4</option>
                <option value="Letter">Letter</option>
                <option value="Legal">Legal</option>
            </select>
            @error('pageSize') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Orientation -->
        <div>
            <label for="orientation" class="block text-sm font-medium text-gray-700 mb-2">
                Orientation
            </label>
            <select 
                id="orientation" 
                wire:model="orientation" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
            >
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>
            @error('orientation') 
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p> 
            @enderror
        </div>
    </div>

    <!-- Convert Button -->
    <div class="mb-6">
        <button 
            wire:click="convertToPdf"
            class="w-full bg-[#E5322D] text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition disabled:bg-red-200 disabled:cursor-not-allowed"
            {{ count($uploadedFiles) == 0 || $isProcessing ? 'disabled' : '' }}
        >
            @if($isProcessing)
                <i class="fas fa-spinner fa-spin mr-2"></i> Converting...
            @else
                <i class="fas fa-file-pdf mr-2"></i> Convert to PDF
            @endif
        </button>
    </div>

    <!-- Progress Bar -->
    @if($isProcessing || $processingProgress > 0)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-700">Progress</span>
                <span class="text-sm text-blue-600">{{ $processingProgress }}%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    style="width: {{ $processingProgress }}%"
                ></div>
            </div>
            @if($processingStatus)
                <p class="text-sm text-blue-700 mt-2">
                    <i class="fas fa-info-circle mr-1"></i> {{ $processingStatus }}
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

    <!-- Download -->
    @if($convertedPdf && !$isProcessing)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-medium text-gray-900">Download Ready</p>
                    <p class="text-sm text-gray-600">Your PDF file has been generated.</p>
                </div>
                <button 
                    wire:click="downloadPdf"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
                >
                    <i class="fas fa-download mr-2"></i> Download PDF
                </button>
            </div>
        </div>
    @endif

    <!-- Reset -->
    @if(count($uploadedFiles) > 0 || $errorMessage || $successMessage)
        <div class="text-center">
            <button 
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                {{ $isProcessing ? 'disabled' : '' }}
            >
                <i class="fas fa-redo mr-1"></i> Convert Another Set
            </button>
        </div>
    @endif

    <!-- Loading Overlay -->
    @if($isProcessing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl text-center">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                <p class="text-gray-700">Converting your PNG images...</p>
                <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
            </div>
        </div>
    @endif
</div>
