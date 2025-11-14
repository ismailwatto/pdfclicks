<div>
    <!-- File Upload Section -->
    <div class="mb-6">
        <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Select PDF Document to Split
        </label>
        <div class="relative">
            <input 
                type="file" 
                wire:model="uploadedFile" 
                id="file-upload"
                accept=".pdf"
                class="hidden"
                @if($isConverting) disabled @endif
            >
            <label 
                for="file-upload" 
                class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors @if($isConverting) pointer-events-none opacity-50 @endif"
            >
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">
                        @if($uploadedFile)
                            <span class="font-medium text-blue-600">{{ $uploadedFile->getClientOriginalName() }}</span>
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

    <!-- PDF Info Display -->
    @if($uploadedFile && $totalPages > 0)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-2">PDF Information</h3>
            <p class="text-sm text-gray-600">
                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                Total pages: <span class="font-medium">{{ $totalPages }}</span>
            </p>
        </div>
    @endif

    <!-- Split Mode Selection -->
    @if($uploadedFile && $totalPages > 0)
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Split Mode</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <input 
                        type="radio" 
                        wire:model.live="splitMode" 
                        value="individual" 
                        id="mode-individual"
                        class="sr-only"
                        @if($isConverting) disabled @endif
                    >
                    <label for="mode-individual" class="block p-4 border-2 rounded-lg cursor-pointer transition-colors @if($splitMode === 'individual') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-blue-300 @endif @if($isConverting) pointer-events-none opacity-50 @endif">
                        <div class="flex items-center">
                            <div class="w-4 h-4 border-2 rounded-full mr-3 @if($splitMode === 'individual') border-blue-500 bg-blue-500 @else border-gray-300 @endif flex items-center justify-center">
                                @if($splitMode === 'individual')
                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Individual Pages</h4>
                                <p class="text-sm text-gray-600">Split each page into separate PDF files</p>
                                <p class="text-xs text-gray-500 mt-1">Creates {{ $totalPages }} individual files</p>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="relative">
                    <input 
                        type="radio" 
                        wire:model.live="splitMode" 
                        value="ranges" 
                        id="mode-ranges"
                        class="sr-only"
                        @if($isConverting) disabled @endif
                    >
                    <label for="mode-ranges" class="block p-4 border-2 rounded-lg cursor-pointer transition-colors @if($splitMode === 'ranges') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-blue-300 @endif @if($isConverting) pointer-events-none opacity-50 @endif">
                        <div class="flex items-center">
                            <div class="w-4 h-4 border-2 rounded-full mr-3 @if($splitMode === 'ranges') border-blue-500 bg-blue-500 @else border-gray-300 @endif flex items-center justify-center">
                                @if($splitMode === 'ranges')
                                    <div class="w-2 h-2 bg-white rounded-full"></div>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Page Ranges</h4>
                                <p class="text-sm text-gray-600">Split by custom page ranges</p>
                                <p class="text-xs text-gray-500 mt-1">Define specific page ranges to split</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    @endif

    <!-- Page Ranges Configuration -->
    @if($uploadedFile && $totalPages > 0 && $splitMode === 'ranges')
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-medium text-gray-700">Page Ranges</label>
                <button 
                    wire:click="addPageRange"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    @if($isConverting) disabled @endif
                >
                    <i class="fas fa-plus mr-1"></i>
                    Add Range
                </button>
            </div>
            
            <div class="space-y-3">
                @foreach($pageRanges as $index => $range)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2 flex-1">
                            <label class="text-sm text-gray-600">From:</label>
                            <input 
                                type="number" 
                                wire:model="pageRanges.{{ $index }}.start"
                                min="1" 
                                max="{{ $totalPages }}"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                @if($isConverting) disabled @endif
                            >
                            <label class="text-sm text-gray-600">To:</label>
                            <input 
                                type="number" 
                                wire:model="pageRanges.{{ $index }}.end"
                                min="1" 
                                max="{{ $totalPages }}"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                @if($isConverting) disabled @endif
                            >
                        </div>
                        @if(count($pageRanges) > 1)
                            <button 
                                wire:click="removePageRange({{ $index }})"
                                class="text-red-600 hover:text-red-800 p-1"
                                @if($isConverting) disabled @endif
                            >
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        @endif
                    </div>
                    @error("pageRanges.{$index}.start") 
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                    @error("pageRanges.{$index}.end") 
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> 
                    @enderror
                @endforeach
            </div>
            @error('pageRanges') 
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p> 
            @enderror
        </div>
    @endif

    <!-- Split Button -->
    <div class="mb-6">
        <button 
            wire:click="splitPdf"
            class="w-full bg-[#E5322D] text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors disabled:bg-red-200 disabled:cursor-not-allowed"
            @if(!$uploadedFile || $isConverting) disabled @endif
        >
            @if($isConverting)
                <i class="fas fa-spinner fa-spin mr-2"></i>
                @if($splitMode === 'individual')
                    Splitting into Individual Pages...
                @else
                    Splitting by Page Ranges...
                @endif
            @else
                <i class="fas fa-cut mr-2"></i>
                @if($splitMode === 'individual')
                    Split into Individual Pages
                @else
                    Split by Page Ranges
                @endif
            @endif
        </button>
    </div>

    <!-- Progress Section -->
    @if($isConverting || $conversionProgress > 0)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-blue-700">Split Progress</span>
                <span class="text-sm text-blue-600">{{ $conversionProgress }}%</span>
            </div>
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: {{ $conversionProgress }}%"
                ></div>
            </div>
            @if($conversionStatus)
                <p class="text-sm text-blue-700 mt-2">
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

    <!-- Split Files Results -->
    @if(!empty($splitFiles) && !$isConverting)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-3">
                @if($splitMode === 'individual')
                    Individual Pages ({{ count($splitFiles) }})
                @else
                    Split Files ({{ count($splitFiles) }})
                @endif
            </h3>
            
            <!-- Download All Button -->
            <div class="mb-4">
                <button 
                    wire:click="downloadAllFiles"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    @if($splitMode === 'individual')
                        Download All Pages as ZIP
                    @else
                        Download All Files as ZIP
                    @endif
                </button>
            </div>
            
            <!-- Individual Files -->
            <div class="space-y-2">
                @foreach($splitFiles as $index => $file)
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                        <div class="flex items-center">
                            <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-700">
                                    @if($splitMode === 'individual')
                                        Page {{ $file['page'] ?? $index + 1 }}
                                    @else
                                        Pages {{ $file['start_page'] ?? 'N/A' }} - {{ $file['end_page'] ?? 'N/A' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $file['filename'] ?? basename($file['file_path'] ?? '') }}
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
        </div>
    @endif

    <!-- Reset Button -->
    @if($uploadedFile || $errorMessage || $successMessage)
        <div class="text-center">
            <button 
                wire:click="resetConverter"
                class="text-gray-600 hover:text-gray-800 underline"
                @if($isConverting) disabled @endif
            >
                <i class="fas fa-redo mr-1"></i>
                Split Another PDF
            </button>
        </div>
    @endif

    <!-- Loading Spinner Overlay -->
    @if($isConverting)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-700">
                        @if($splitMode === 'individual')
                            Splitting PDF into individual pages...
                        @else
                            Splitting PDF by page ranges...
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-2">Please wait, this may take a few moments</p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- JavaScript for enhanced functionality -->
<script>
    document.addEventListener('livewire:init', () => {
        // Auto-refresh progress every 2 seconds while converting
        setInterval(() => {
            if (window.Livewire.find('{{ $this->getId() }}').isConverting) {
                window.Livewire.find('{{ $this->getId() }}').call('checkProgress');
            }
        }, 2000);
    });

    // Handle file upload states
    document.addEventListener('livewire:upload-start', () => {
        const fileInput = document.getElementById('file-upload');
        const label = fileInput.nextElementSibling;
        if (label) {
            label.classList.add('pointer-events-none', 'opacity-50');
        }
    });

    document.addEventListener('livewire:upload-finish', () => {
        const fileInput = document.getElementById('file-upload');
        const label = fileInput.nextElementSibling;
        if (label) {
            label.classList.remove('pointer-events-none', 'opacity-50');
        }
    });

    document.addEventListener('livewire:upload-error', () => {
        const fileInput = document.getElementById('file-upload');
        const label = fileInput.nextElementSibling;
        if (label) {
            label.classList.remove('pointer-events-none', 'opacity-50');
        }
    });
</script>