<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\CloudConvertAction;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

final class JpgToPdf extends Component
{
    use WithFileUploads;

    public $uploadedFiles = [];

    public $pageSize = 'A4'; // 'A4', 'A3', 'Letter', 'Legal'

    public $orientation = 'portrait'; // 'portrait', 'landscape'

    public $isProcessing = false;

    public $processingProgress = 0;

    public $processingStatus = '';

    public $convertedFile = '';

    public $errorMessage = '';

    public $successMessage = '';

    public array $rules = [
        'uploadedFiles' => 'required|array|min:1|max:20',
        'uploadedFiles.*' => 'required|file|mimes:jpg,jpeg|max:10240',
        'pageSize' => 'required|in:A4,A3,Letter,Legal',
        'orientation' => 'required|in:portrait,landscape',
    ];

    public array $messages = [
        'uploadedFiles.required' => 'Please select at least one JPG image.',
        'uploadedFiles.min' => 'Please select at least one JPG image.',
        'uploadedFiles.max' => 'You can convert up to 20 images at once.',
        'uploadedFiles.*.mimes' => 'Only JPG/JPEG files are allowed.',
        'uploadedFiles.*.max' => 'Each image must be less than 10MB.',
        'pageSize.required' => 'Please select a page size.',
        'orientation.required' => 'Please select page orientation.',
    ];

    public function mount(): void
    {
        $this->resetAll();
    }

    public function updatedUploadedFiles(): void
    {
        $this->validateOnly('uploadedFiles');
        $this->clearMessages();
    }

    public function convertToPdf(): void
    {
        $this->validate();

        try {
            $this->startProcessing();
            $cloudConvert = new CloudConvertAction();

            $options = [
                'page_size' => $this->pageSize,
                'orientation' => $this->orientation,
                'fit_to_page' => true,
            ];

            $result = $cloudConvert->convertImagesToPdf($this->uploadedFiles);
            $this->handleProcessingSuccess($result);
        } catch (Exception $e) {
            $this->handleProcessingError($e);
        }
    }

    public function removeFile($index): void
    {
        if (isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    public function downloadFile()
    {
        if (! $this->convertedFile || ! file_exists($this->convertedFile)) {
            $this->errorMessage = 'Converted file not found.';

            return null;
        }

        return response()->download($this->convertedFile, 'converted-images.pdf');
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.jpg-to-pdf');
    }

    private function startProcessing(): void
    {
        $this->isProcessing = true;
        $this->processingProgress = 20;
        $this->processingStatus = 'Converting JPG images to PDF...';
        $this->clearMessages();
    }

    private function handleProcessingSuccess(array $result): void
    {
        $this->isProcessing = false;
        $this->processingProgress = 100;
        $this->processingStatus = 'Images converted to PDF successfully!';
        $this->convertedFile = $result['converted_file'];
        $this->successMessage = count($this->uploadedFiles).' JPG images successfully converted to PDF!';
        $this->dispatch('downloadReady');
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
        $this->uploadedFiles = [];
        $this->pageSize = 'A4';
        $this->orientation = 'portrait';
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->convertedFile = '';
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
