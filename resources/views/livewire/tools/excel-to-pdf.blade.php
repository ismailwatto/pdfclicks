<div>
    <div>
    <!-- File Upload Section -->
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select Excel Document
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFile" 
                id="file-upload"
                accept=".xls,.xlsx,.csv"
                class="hidden"
                {{ $isConverting ? 'disabled' : '' }}
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-green-400 hover:bg-green-50 transition-colors {{ $isConverting ? 'pointer-events-none opacity-50' : '' }}"
            >
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedFile)
                            <span class="font-medium text-green-600">{{ $uploadedFile->getClientOriginalName() }}</span>
                        @else
                            Click to upload or drag and drop
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">XLS, XLSX, CSV up to 10MB</p>
                </div>
            </label>
        </div>
        @error('uploadedFile') 
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
        @enderror
    </div>

    <!-- Conversion Options -->
    @if($uploadedFile && $worksheetNames && count($worksheetNames) > 0)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Conversion Options</h3>
            
            <div class="space-y-4">
                <!-- Worksheet Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Worksheets to Convert</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="convertAllSheets" 
                                id="convert-all"
                                class="mr-2"
                                {{ $isConverting ? 'disabled' : '' }}
                            >
                            <label for="convert-all" class="text-sm text-gray-700">Convert all worksheets</label>
                        </div>
                        
                        @if(!$convertAllSheets)
                            <div class="ml-6 space-y-2 max-h-32 overflow-y-auto">
                                @foreach($worksheetNames as $index => $sheetName)
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedSheets" 
                                            value="{{ $index }}"
                                            id="sheet-{{ $index }}"
                                            class="mr-2"
                                            {{ $isConverting ? 'disabled' : '' }}
                                        >
                                        <label for="sheet-{{ $index }}" class="text-sm text-gray-700">{{ $sheetName }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Page Orientation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page Orientation</label>
                    <div class="flex space-x-4">
                        <div class="flex items-center">
                            <input 
                                type="radio" 
                                wire:model="pageOrientation" 
                                value="portrait" 
                                id="portrait"
                                class="mr-2"
                                {{ $isConverting ? 'disabled' : '' }}
                            >
                            <label for="portrait" class="text-sm text-gray-700">Portrait</label>
                        </div>
                        <div class="flex items-center">
                            <input 
                                type="radio" 
                                wire:model="pageOrientation" 
                                value="landscape" 
                                id="landscape"
                                class="mr-2"
                                {{ $isConverting ? 'disabled' : '' }}
                            >
                            <label for="landscape" class="text-sm text-gray-700">Landscape</label>
                        </div>
                    </div>
                </div>

                <!-- Paper Size -->
                <div>
                    <label for="paper-size" class="block text-sm font-medium text-gray-700 mb-2">Paper Size</label>
                    <select 
                        wire:model="paperSize" 
                        id="paper-size"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        {{ $isConverting ? 'disabled' : '' }}
                    >
                        <option value="A4">A4</option>
                        <option value="A3">A3</option>
                        <option value="Letter">Letter</option>
                        <option value="Legal">Legal</option>
                    </select>
                </div>

                <!-- Fit to Page -->
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model="fitToPage" 
                        id="fit-to-page"
                        class="mr-2"
                        {{ $isConverting ? 'disabled' : '' }}
                    >
                    <label for="fit-to-page" class="text-sm text-gray-700">Fit content to page width</label>
                </div>
            </div>
        </div>
    @endif

    <!-- Convert Button -->
    <div class="mb-6">
        <button 
            wire:click="convertToPdf"
            class="w-full bg-[#1f7a1f] text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors disabled:bg-green-200 cursor-pointer disabled:cursor-not-allowed"
            {{ !$uploadedFile || $isConverting ? 'disabled' : '' }}
        >
            @if($isConverting)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Converting...
            @else
                <i class="fas fa-file-pdf mr-2"></i>
                Convert to PDF
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isConverting || $conversionProgress > 0)
        <div class="mb-6 p-4 bg-green-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-green-700">Conversion Progress</span>
                <span class="text-sm text-green-600">{{ $conversionProgress }}%</span>
            </div>
            <div class="w-full bg-green-200 rounded-full h-2">
                <div 
                    class="bg-green-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $conversionProgress }}%"
                ></div>
            </div>
            @if($conversionStatus)
                <p class="text-sm text-green-700 mt-2">
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
    @if($convertedFiles && count($convertedFiles) > 0 && !$isConverting)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            @if(count($convertedFiles) === 1)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Converted PDF Ready</p>
                        <p class="text-sm text-gray-600">Your Excel document has been successfully converted</p>
                    </div>
                    <button 
                        wire:click="downloadFile(0)"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download PDF
                    </button>
                </div>
            @else
                <h3 class="text-sm font-medium text-gray-700 mb-3">Converted Files ({{ count($convertedFiles) }})</h3>
                
                <!-- Download All Button -->
                <div class="mb-4">
                    <button 
                        wire:click="downloadAllFiles"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download All as ZIP
                    </button>
                </div>
                
                <!-- Individual Files -->
                <div class="space-y-2">
                    @foreach($convertedFiles as $index => $file)
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                            <div class="flex items-center">
                                <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">
                                        {{ $file['name'] ?? 'Worksheet ' . ($index + 1) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        PDF Document
                                    </p>
                                </div>
                            </div>
                            <button 
                                wire:click="downloadFile({{ $index }})"
                                class="bg-green-600 text-white px-3 py-1 rounded text-xs font-medium hover:bg-green-700 transition-colors"
                            >
                                <i class="fas fa-download mr-1"></i>
                                Download
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
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
                    <i class="fas fa-spinner fa-spin text-4xl text-green-600 mb-4"></i>
                    <p class="text-gray-700">Converting your Excel document...</p>
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
            if (@this.convertedFiles && @this.convertedFiles.length === 1) {
                @this.call('downloadFile', 0);
            } else {
                @this.call('downloadAllFiles');
            }
        }, 1000);
    });

    // Handle worksheet selection changes
    document.addEventListener('livewire:updated', () => {
        if (@this.convertAllSheets) {
            @this.selectedSheets = [];
        }
    });
</script>
</div>
