<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfToImageAction;
use App\Traits\PdfToolsBase;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;
use ZipArchive;

// 9. PDF to JPG
final class PdfToJpg extends Component
{
    use PdfToolsBase, WithFileUploads;

    public $uploadedFile;

    public $imageQuality = 'high'; // 'low', 'medium', 'high'

    public $isProcessing = false;

    public $processingProgress = 0;

    public $processingStatus = '';

    public $convertedImages = [];

    public $errorMessage = '';

    public $successMessage = '';

    public array $rules = [
        'uploadedFile' => 'required|file|mimes:pdf|max:20480', // 20MB max
        'imageQuality' => 'required|in:low,medium,high',
    ];

    public array $messages = [
        'uploadedFile.required' => 'Please select a PDF document to convert.',
        'uploadedFile.mimes' => 'Only PDF files are allowed.',
        'uploadedFile.max' => 'File size must be less than 20MB.',
        'imageQuality.required' => 'Please select image quality.',
    ];

    public function mount(): void
    {
        $this->resetAll();
    }

    public function updatedUploadedFile(): void
    {
        $this->validateOnly('uploadedFile');
        $this->clearMessages();
    }

    public function convertToJpg(): void
    {
        $this->validate();

        try {
            $this->startProcessing();

            // Store the uploaded file temporarily
            $tempPath = $this->uploadedFile->store('temp', 'local');
            $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

            if (! file_exists($fullPath)) {
                throw new Exception("Failed to store uploaded file at: {$fullPath}");
            }

            $options = [
                'format' => 'jpg',
                'quality' => $this->getQualityValue(),
                'dpi' => 300,
            ];

            $action = new PdfToImageAction($fullPath, $options);
            $result = $action->process();
            $this->handleProcessingSuccess($result);
        } catch (Exception $e) {
            $this->handleProcessingError($e);
        }
    }

    public function downloadImage($index)
    {
        if (! isset($this->convertedImages[$index]) || ! file_exists($this->convertedImages[$index])) {
            $this->errorMessage = 'Image file not found.';

            return null;
        }

        return response()->download($this->convertedImages[$index], "page-{$index}.jpg");
    }

    public function downloadAllImages()
    {
        if (empty($this->convertedImages)) {
            $this->errorMessage = 'No converted images available.';

            return null;
        }

        // Create ZIP file with all images
        $zipFile = storage_path('app/temp/pdf-images.zip');
        $zip = new ZipArchive();

        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            foreach ($this->convertedImages as $index => $image) {
                $zip->addFile($image, "page-{$index}.jpg");
            }
            $zip->close();

            return response()->download($zipFile, 'pdf-images.zip');
        }

        $this->errorMessage = 'Failed to create ZIP file.';

        return null;
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.pdf-to-jpg');
    }

    private function getQualityValue(): int
    {
        return match ($this->imageQuality) {
            'low' => 60,
            'medium' => 80,
            'high' => 95,
            default => 80,
        };
    }

    private function startProcessing(): void
    {
        $this->isProcessing = true;
        $this->processingProgress = 20;
        $this->processingStatus = 'Converting PDF to JPG images...';
        $this->clearMessages();
    }

    private function handleProcessingSuccess(array $result): void
    {
        $this->isProcessing = false;
        $this->processingProgress = 100;
        $this->processingStatus = 'PDF converted to JPG successfully!';

        // Extract file paths from result
        $this->convertedImages = [];
        if (isset($result['files']) && is_array($result['files'])) {
            foreach ($result['files'] as $file) {
                $this->convertedImages[] = $file['path'];
            }
        }

        $imageCount = count($this->convertedImages);
        $method = $result['method'] ?? 'local';
        $methodText = $method === 'cloudconvert' ? ' (via CloudConvert)' : ' (locally)';

        $this->successMessage = "PDF successfully converted to {$imageCount} JPG image".
                               ($imageCount === 1 ? '' : 's').$methodText.'!';
    }

    private function handleProcessingError(Throwable $e): void
    {
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->errorMessage = 'Conversion failed: '.$e->getMessage();
    }

    private function resetAll(): void
    {
        $this->uploadedFile = null;
        $this->imageQuality = 'high';
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->convertedImages = [];
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
