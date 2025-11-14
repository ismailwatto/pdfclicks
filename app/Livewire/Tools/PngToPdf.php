<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\ImagePdfConversionAction;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;

final class PngToPdf extends Component
{
    use WithFileUploads;

    public $uploadedFiles = [];

    public $pdfQuality = 'medium';

    public $pageSize = 'A4';

    public $orientation = 'portrait';

    public $isProcessing = false;

    public $processingProgress = 0;

    public $processingStatus = '';

    public $successMessage = '';

    public $errorMessage = '';

    public $convertedPdf;

    public array $rules = [
        'uploadedFiles.*' => 'required|image|mimes:png,jpg,jpeg|max:10240',
        'pdfQuality' => 'required|in:high,medium,low',
        'pageSize' => 'required|in:A4,Letter,Legal',
        'orientation' => 'required|in:portrait,landscape',
    ];

    public array $messages = [
        'uploadedFiles.*.required' => 'Please select at least one PNG file.',
        'uploadedFiles.*.image' => 'The file must be a valid image.',
        'uploadedFiles.*.mimes' => 'Only PNG, JPG, and JPEG files are allowed.',
        'uploadedFiles.*.max' => 'Each file must be less than 10MB.',
    ];

    public function mount(): void
    {
        $this->resetConverter();
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate([
            'uploadedFiles.*' => 'required|image|mimes:png,jpg,jpeg|max:10240',
        ]);
    }

    public function removeFile($index): void
    {
        if (isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    public function convertToPdf(): void
    {
        $this->validate();

        if (count($this->uploadedFiles) === 0) {
            $this->errorMessage = 'Please select at least one PNG file.';

            return;
        }

        $this->isProcessing = true;
        $this->processingProgress = 0;
        $this->processingStatus = 'Initializing conversion...';
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $conversionAction = new ImagePdfConversionAction();

            // Start polling for progress updates
            $this->dispatch('start-progress-polling');

            $result = $conversionAction->convertPngToPdf($this->uploadedFiles);

            if ($result['success']) {
                $this->convertedPdf = $result;
                $this->successMessage = $result['message'];
                $this->processingProgress = 100;
                $this->processingStatus = 'Conversion completed successfully!';
            } else {
                $this->errorMessage = 'Conversion failed. Please try again.';
            }

        } catch (Exception $e) {
            $this->errorMessage = 'Error: '.$e->getMessage();
        } finally {
            $this->isProcessing = false;
            $this->dispatch('stop-progress-polling');
        }
    }

    public function downloadPdf()
    {
        if (! $this->convertedPdf) {
            $this->errorMessage = 'No PDF file available for download.';

            return null;
        }

        $filePath = $this->convertedPdf['file_path'];
        $fileName = $this->convertedPdf['filename'];

        if (file_exists($filePath)) {
            return response()->download($filePath, $fileName)->deleteFileAfterSend();
        }

        $this->errorMessage = 'PDF file not found. Please convert again.';

        return null;
    }

    public function resetConverter(): void
    {
        $this->uploadedFiles = [];
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->convertedPdf = null;
        $this->pdfQuality = 'medium';
        $this->pageSize = 'A4';
        $this->orientation = 'portrait';
    }

    public function getProgress(): void
    {
        $sessionId = session()->getId();
        $progressFile = storage_path('app/temp/progress_'.$sessionId.'.json');

        if (file_exists($progressFile)) {
            $progressData = json_decode(file_get_contents($progressFile), true);
            $this->processingProgress = $progressData['progress'] ?? 0;
            $this->processingStatus = $progressData['status'] ?? '';
        }
    }

    public function render()
    {
        return view('livewire.tools.png-to-pdf');
    }
}
