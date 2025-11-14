<div>
    <x-tools.file-upload 
        :file="$uploadedFiles" 
        accept=".pdf"
        label="Select PDF Document"
        max-size="100MB"
        wire-model="uploadedFiles"
        :converting="$isCompressing"
        multiple="true"
    />

    @if (!empty($uploadedFiles))
        <!-- Compression Options -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-4">Compression Options</h3>
            
            <!-- Quality Level -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Compression Level</label>
                <select wire:model="qualityLevel" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="high">High Quality (10-30% reduction)</option>
                    <option value="medium">Medium Quality (30-60% reduction)</option>
                    <option value="maximum">Maximum Compression (60-80% reduction)</option>
                </select>
            </div>

            <!-- Advanced Options -->
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="removeMetadata" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">Remove metadata and annotations</span>
                </label>
            </div>
        </div>
    @endif
    
    <x-tools.convert-button 
        :converting="$isCompressing"
        :disabled="empty($uploadedFiles)"
        action="compressPdfs"
        icon="fas fa-compress-arrows-alt"
        text="Compress PDF"
        loading-text="Compressing PDF..."
    />
    
    <x-tools.progress-section 
        :show="$isCompressing || $overallProgress > 0"
        :progress="$overallProgress"
        :status="$compressionStatus"
    />
    
    <x-tools.messages 
        :success="$successMessage"
        :error="$errorMessage"
    />
    
    <x-tools.download-section 
        :show="(!empty($compressedFiles) && !$isCompressing)"
        :files="$compressedFiles"
        download-action="downloadFile"
        file-type="compressed PDF"
        :multiple="true"
        download-all-action="downloadAllFiles"
    />
    
    <x-tools.reset-button 
        :show="!empty($uploadedFiles) || $errorMessage || $successMessage"
        :disabled="$isCompressing"
        action="resetCompressor"
    />
    
    <x-tools.loading-overlay 
        :show="$isCompressing"
        message="Compressing your PDF..."
        sub-message="Reducing file size while maintaining quality"
    />
</div>

<script>
    // Auto-refresh progress every 2 seconds while compressing
    setInterval(() => {
        if (@json($isCompressing)) {
            @this.call('checkProgress');
        }
    }, 2000);

    // Handle file upload loading state
    window.addEventListener('livewire:upload-start', () => {
        const fileInput = document.getElementById('file-upload-uploadedFiles');
        if (fileInput) {
            fileInput.closest('label').classList.add('pointer-events-none', 'opacity-50');
        }
    });

    window.addEventListener('livewire:upload-finish', () => {
        const fileInput = document.getElementById('file-upload-uploadedFiles');
        if (fileInput) {
            fileInput.closest('label').classList.remove('pointer-events-none', 'opacity-50');
        }
    });

    // Handle compression complete event
    window.addEventListener('livewire:init', () => {
        Livewire.on('compressionComplete', () => {
            setTimeout(() => {
                const downloadSection = document.querySelector('.bg-gray-50');
                if (downloadSection) {
                    downloadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
        });
    });
</script>
</div>