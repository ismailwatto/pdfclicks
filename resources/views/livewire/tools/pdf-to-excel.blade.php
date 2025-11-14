<div class="max-w-">
    <x-tools.file-upload 
        :file="$uploadedFile" 
        accept=".pdf"
        label="Select PDF Document"
        max-size="15MB"
        wire-model="uploadedFile"
        :converting="$isConverting"
    />

    @if ($uploadedFile)
        <!-- Configuration Options -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-4">Conversion Options</h3>
            
            <!-- Output Format -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Output Format</label>
                <select wire:model="outputFormat" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="xlsx">Excel (.xlsx)</option>
                    <option value="csv">CSV (.csv)</option>
                </select>
            </div>

            <!-- Advanced Options -->
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="tableDetection" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">Enable table detection</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="preserveFormatting" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">Preserve formatting</span>
                </label>
            </div>
        </div>
    @endif

    
    <x-tools.convert-button 
        :converting="$isConverting"
        :disabled="!$uploadedFile"
        action="convertToExcel"
        icon="fas fa-file-excel"
        text="Convert to Excel"
        loading-text="Converting to Excel..."
    />
    
    <x-tools.progress-section 
        :show="$isConverting || $conversionProgress > 0"
        :progress="$conversionProgress"
        :status="$conversionStatus"
    />
    
    <x-tools.messages 
        :success="$successMessage"
        :error="$errorMessage"
    />
    
    <x-tools.download-section 
        :show="($convertedFile && !$isConverting) || (!empty($convertedFiles) && !$isConverting)"
        :file="$convertedFile"
        :files="$convertedFiles"
        download-action="downloadFile"
        file-type="Excel"
    />
    
    <x-tools.reset-button 
        :show="$uploadedFile || $errorMessage || $successMessage"
        :disabled="$isConverting"
        action="resetConverter"
    />
    
    <x-tools.loading-overlay 
        :show="$isConverting"
        message="Converting your PDF to Excel..."
        sub-message="Extracting tables and data from your document"
    />
</div>

<script>
    // Auto-refresh progress every 2 seconds while converting
    setInterval(() => {
        if (@json($isConverting)) {
            @this.call('checkProgress');
        }
    }, 2000);

    // Handle file upload loading state
    window.addEventListener('livewire:upload-start', () => {
        const fileInput = document.getElementById('file-upload-uploadedFile');
        if (fileInput) {
            fileInput.closest('label').classList.add('pointer-events-none', 'opacity-50');
        }
    });

    window.addEventListener('livewire:upload-finish', () => {
        const fileInput = document.getElementById('file-upload-uploadedFile');
        if (fileInput) {
            fileInput.closest('label').classList.remove('pointer-events-none', 'opacity-50');
        }
    });

    // Handle conversion complete event
    window.addEventListener('livewire:init', () => {
        Livewire.on('conversionComplete', () => {
            setTimeout(() => {
                const downloadSection = document.querySelector('.bg-gray-50');
                if (downloadSection) {
                    downloadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
        });
    });
</script>