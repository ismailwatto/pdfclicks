@props([
    'uploadedFile' => null,
    'acceptedTypes' => '.pdf',
    'uploadLabel' => 'Select PDF Document',
    'maxSize' => '15MB',
    'isConverting' => false,
    'conversionProgress' => 0,
    'conversionStatus' => '',
    'convertedFile' => null,
    'convertedFiles' => null,
    'successMessage' => '',
    'errorMessage' => '',
    'convertAction' => 'convert',
    'convertIcon' => 'fas fa-file-word',
    'convertText' => 'Convert',
    'convertLoadingText' => 'Converting...',
    'outputType' => 'Document'
])

<div>
    <x-tools.file-upload 
        :file="$uploadedFile" 
        accept="{{ $acceptedTypes }}"
        label="{{ $uploadLabel }}"
        max-size="{{ $maxSize }}"
        wire-model="uploadedFile"
        :converting="$isConverting"
    />
    
    <x-tools.convert-button 
        :converting="$isConverting"
        :disabled="!$uploadedFile"
        action="{{ $convertAction }}"
        icon="{{ $convertIcon }}"
        text="{{ $convertText }}"
        loading-text="{{ $convertLoadingText }}"
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
        :show="($convertedFile && !$isConverting) || ($convertedFiles && !$isConverting)"
        :file="$convertedFile"
        :files="$convertedFiles"
        download-action="downloadFile"
        file-type="{{ $outputType }}"
    />
    
    <x-tools.reset-button 
        :show="$uploadedFile || $errorMessage || $successMessage"
        :disabled="$isConverting"
        action="resetConverter"
    />
    
    <x-tools.loading-overlay 
        :show="$isConverting"
        message="Processing your {{ strtolower($outputType) }}..."
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
    window.addEventListener('conversionComplete', () => {
        setTimeout(() => {
            // Auto-scroll to download section if needed
            const downloadSection = document.querySelector('[show="{{ ($convertedFile && !$isConverting) || ($convertedFiles && !$isConverting) }}"]');
            if (downloadSection) {
                downloadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 500);
    });
</script>