@props([
    'show' => false,
    'file' => null,
    'downloadAction' => 'downloadFile',
    'fileType' => 'Document',
    'files' => null
])

@if($show)
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        @if($files && is_array($files))
            <!-- Multiple files -->
            <div class="mb-4">
                <p class="font-medium text-gray-900 mb-2">{{ $fileType }} Files Ready</p>
                <p class="text-sm text-gray-600 mb-4">{{ count($files) }} file(s) have been processed successfully</p>
                
                <div class="space-y-2 mb-4">
                    @foreach($files as $index => $fileInfo)
                        <div class="flex items-center justify-between p-2 bg-white rounded border">
                            <span class="text-sm text-gray-700">
                                {{ is_array($fileInfo) ? $fileInfo['name'] : basename($fileInfo) }}
                            </span>
                            <button 
                                wire:click="{{ $downloadAction }}({{ $index }})"
                                class="text-red-600 hover:text-red-700 text-sm font-medium"
                            >
                                <i class="fas fa-download mr-1"></i>
                                Download
                            </button>
                        </div>
                    @endforeach
                </div>
                
                @if(count($files) > 1)
                    <button 
                        wire:click="downloadAllFiles"
                        class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download All Files (ZIP)
                    </button>
                @endif
            </div>
        @else
            <!-- Single file -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $fileType }} Ready</p>
                    <p class="text-sm text-gray-600">Your file has been processed successfully</p>
                </div>
                <button 
                    wire:click="{{ $downloadAction }}"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download {{ $fileType }}
                </button>
            </div>
        @endif
    </div>
@endif