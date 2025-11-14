<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfCompressionAction;
use App\Traits\PdfToolsBase;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

final class CompressPdf extends Component
{
    use PdfToolsBase, WithFileUploads;

    public $uploadedFiles = [];

    public $isCompressing = false;

    public $compressionProgress = [];

    public $overallProgress = 0;

    public $compressionStatus = '';

    public $jobIds = [];

    public $compressedFiles = [];

    public $compressionStats = [];

    public $errorMessage = '';

    public $successMessage = '';

    // Compression options
    public $qualityLevel = 'medium';

    public $imageQuality = 75;

    public $removeMetadata = false;

    public $optimizeFor = 'general';

    public $maxFiles = 10;

    public function mount(): void
    {
        $this->resetAll();
    }

    public function updatedUploadedFiles(): void
    {
        $this->validateOnly('uploadedFiles.*');
        $this->clearMessages();

        if (count($this->uploadedFiles) > $this->maxFiles) {
            $this->errorMessage = "Maximum {$this->maxFiles} files allowed. Please remove some files.";

            return;
        }

        $this->initializeProgress();
    }

    public function updatedQualityLevel(): void
    {
        // Update image quality based on quality level
        $qualityMap = [
            'high' => 90,
            'medium' => 75,
            'maximum' => 50,
        ];

        $this->imageQuality = $qualityMap[$this->qualityLevel];
    }

    public function removeFile(int $index): void
    {
        if (isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles); // Re-index array
            $this->initializeProgress();
        }
    }

    public function compressPdfs(): void
    {
        $this->validate();

        if (empty($this->uploadedFiles)) {
            $this->errorMessage = 'Please select at least one PDF file to compress.';

            return;
        }

        try {
            $this->startCompression();
            $options = $this->buildCompressionOptions();

            foreach ($this->uploadedFiles as $index => $file) {
                try {
                    $this->updateFileProgress($index, 10, 'Starting compression...');

                    // Store the uploaded file temporarily and get the full path
                    $tempPath = $file->store('temp', 'local');
                    $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

                    // Log file information for debugging
                    Log::info('PDF compression starting', [
                        'file_index' => $index,
                        'original_name' => $file->getClientOriginalName(),
                        'temp_path' => $tempPath,
                        'full_path' => $fullPath,
                        'file_exists' => file_exists($fullPath),
                        'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
                    ]);

                    // Verify the file exists at the expected location
                    if (! file_exists($fullPath)) {
                        throw new Exception("Failed to store uploaded file at: {$fullPath}");
                    }

                    // Use the processing action with the stored file path
                    $action = new PdfCompressionAction($fullPath, $options);
                    $result = $action->process();

                    $this->handleFileCompressionSuccess($index, $result);
                } catch (Exception $e) {
                    $this->handleFileCompressionError($index, $e);
                }
            }

            $this->handleOverallCompressionComplete();
        } catch (Exception $e) {
            $this->handleCompressionError($e);
        }
    }

    public function downloadFile(int $index)
    {
        if (isset($this->compressedFiles[$index]) && file_exists($this->compressedFiles[$index]['path'])) {
            $file = $this->compressedFiles[$index];

            return response()->download($file['path'], $file['name']);
        }

        $this->errorMessage = 'Compressed file not found.';

        return null;
    }

    public function downloadAllFiles()
    {
        if (empty($this->compressedFiles)) {
            $this->errorMessage = 'No compressed files found.';

            return null;
        }

        return $this->createZipDownload($this->compressedFiles, 'compressed-pdfs');
    }

    public function resetCompressor(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.compress-pdf');
    }

    protected function rules(): array
    {
        return [
            'uploadedFiles.*' => 'required|file|mimes:pdf|max:102400', // 100MB max per file
            'qualityLevel' => 'in:high,medium,maximum',
            'imageQuality' => 'integer|min:10|max:100',
            'optimizeFor' => 'in:web,print,general',
        ];
    }

    protected function messages(): array
    {
        return [
            'uploadedFiles.*.required' => 'Please select PDF documents to compress.',
            'uploadedFiles.*.mimes' => 'Only PDF files are allowed.',
            'uploadedFiles.*.max' => 'Each file size must be less than 100MB.',
            'qualityLevel.in' => 'Please select a valid quality level.',
            'imageQuality.integer' => 'Image quality must be a number.',
            'imageQuality.min' => 'Image quality must be at least 10%.',
            'imageQuality.max' => 'Image quality cannot exceed 100%.',
        ];
    }

    private function initializeProgress(): void
    {
        $this->compressionProgress = [];
        foreach ($this->uploadedFiles as $index => $file) {
            $this->compressionProgress[$index] = [
                'progress' => 0,
                'status' => 'Ready',
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ];
        }
    }

    private function buildCompressionOptions(): array
    {
        return [
            'quality_level' => $this->qualityLevel,
            'image_quality' => $this->imageQuality,
            'remove_metadata' => $this->removeMetadata,
            'optimize_for' => $this->optimizeFor,
        ];
    }

    private function startCompression(): void
    {
        $this->isCompressing = true;
        $this->overallProgress = 0;
        $this->compressionStatus = 'Initializing compression...';
        $this->compressedFiles = [];
        $this->compressionStats = [];
        $this->clearMessages();
    }

    private function updateFileProgress(int $index, int $progress, string $status): void
    {
        if (isset($this->compressionProgress[$index])) {
            $this->compressionProgress[$index]['progress'] = $progress;
            $this->compressionProgress[$index]['status'] = $status;
        }

        $this->updateOverallProgress();
    }

    private function updateOverallProgress(): void
    {
        $totalProgress = 0;
        $fileCount = count($this->compressionProgress);

        if ($fileCount > 0) {
            foreach ($this->compressionProgress as $fileProgress) {
                $totalProgress += $fileProgress['progress'];
            }
            $this->overallProgress = (int) ($totalProgress / $fileCount);
        }

        if ($this->overallProgress >= 100) {
            $this->compressionStatus = 'All files compressed successfully!';
        } else {
            $completedFiles = count($this->compressedFiles);
            $this->compressionStatus = "Compressing files... ({$completedFiles}/{$fileCount} completed)";
        }
    }

    private function handleFileCompressionSuccess(int $index, array $result): void
    {
        $this->updateFileProgress($index, 100, 'Completed');

        $originalFile = $this->uploadedFiles[$index];
        $compressedFileName = pathinfo($originalFile->getClientOriginalName(), PATHINFO_FILENAME).'_compressed.pdf';

        $this->compressedFiles[$index] = [
            'path' => $result['compressed_file'],
            'name' => $compressedFileName,
            'original_size' => $result['original_size'] ?? 0,
            'compressed_size' => $result['compressed_size'] ?? 0,
            'compression_ratio' => $result['compression_ratio'] ?? 0,
        ];

        $this->compressionStats[$index] = [
            'original_size' => $this->formatFileSize($result['original_size'] ?? 0),
            'compressed_size' => $this->formatFileSize($result['compressed_size'] ?? 0),
            'compression_ratio' => $result['compression_ratio'] ?? 0,
        ];
    }

    private function handleFileCompressionError(int $index, Throwable $e): void
    {
        $this->updateFileProgress($index, 0, 'Error: '.$e->getMessage());
        error_log("Compression error for file {$index}: ".$e->getMessage());
    }

    private function handleOverallCompressionComplete(): void
    {
        $this->isCompressing = false;
        $successfulCompressions = count($this->compressedFiles);
        $totalFiles = count($this->uploadedFiles);

        if ($successfulCompressions === $totalFiles) {
            $this->successMessage = "Successfully compressed {$successfulCompressions} file(s)!";
        } else {
            $failedCount = $totalFiles - $successfulCompressions;
            $this->successMessage = "Compressed {$successfulCompressions} out of {$totalFiles} files. {$failedCount} file(s) failed.";
        }

        $this->dispatch('compressionComplete');
    }

    private function handleCompressionError(Throwable $e): void
    {
        $this->isCompressing = false;
        $this->overallProgress = 0;
        $this->compressionStatus = '';

        // Log the full error for debugging
        Log::error('PDF compression failed: '.$e->getMessage(), [
            'files' => collect($this->uploadedFiles)->map(fn ($f) => $f->getClientOriginalName())->toArray(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->errorMessage = 'Compression failed: '.$e->getMessage();
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';

    }

    private function resetAll(): void
    {
        $this->uploadedFiles = [];
        $this->isCompressing = false;
        $this->compressionProgress = [];
        $this->overallProgress = 0;
        $this->compressionStatus = '';
        $this->jobIds = [];
        $this->compressedFiles = [];
        $this->compressionStats = [];
        $this->qualityLevel = 'medium';
        $this->imageQuality = 75;
        $this->removeMetadata = false;
        $this->optimizeFor = 'general';
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
