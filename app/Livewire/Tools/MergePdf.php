<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfMergeAction;
use App\Traits\PdfToolsBase;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

final class MergePdf extends Component
{
    use PdfToolsBase, WithFileUploads;

    public $uploadedFiles = [];

    public $isProcessing = false;

    public $processingProgress = 0;

    public $conversionProgress = 0;

    public $processingStatus = '';

    public $mergedFile = '';

    public $errorMessage = '';

    public $successMessage = '';

    public array $rules = [
        'uploadedFiles' => 'required|array|min:2|max:10',
        'uploadedFiles.*' => 'required|file|mimes:pdf|max:10240',
    ];

    public array $messages = [
        'uploadedFiles.required' => 'Please select at least 2 PDF files to merge.',
        'uploadedFiles.min' => 'Please select at least 2 PDF files to merge.',
        'uploadedFiles.max' => 'You can merge up to 10 PDF files at once.',
        'uploadedFiles.*.mimes' => 'Only PDF files are allowed.',
        'uploadedFiles.*.max' => 'Each file must be less than 10MB.',
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

    public function mergePdfs(): void
    {
        $this->validate();

        try {
            $this->startProcessing();

            // Prepare uploaded files as paths array
            $filePaths = [];
            foreach ($this->uploadedFiles as $file) {
                $tempPath = $file->store('temp', 'local');
                $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

                if (! file_exists($fullPath)) {
                    throw new Exception("Failed to store uploaded file: {$file->getClientOriginalName()}");
                }

                $filePaths[] = $fullPath;
            }

            $mergeAction = new PdfMergeAction($filePaths, []);
            $result = $mergeAction->process();
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
        if (! $this->mergedFile || ! file_exists($this->mergedFile)) {
            $this->errorMessage = 'Merged file not found.';

            return null;
        }

        return response()->download($this->mergedFile, 'merged-document.pdf');
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.merge-pdf');
    }

    public function moveUp($index): void
    {
        if ($index > 0 && isset($this->uploadedFiles[$index])) {
            $temp = $this->uploadedFiles[$index];
            $this->uploadedFiles[$index] = $this->uploadedFiles[$index - 1];
            $this->uploadedFiles[$index - 1] = $temp;
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    public function moveDown($index): void
    {
        if ($index < count($this->uploadedFiles) - 1 && isset($this->uploadedFiles[$index])) {
            $temp = $this->uploadedFiles[$index];
            $this->uploadedFiles[$index] = $this->uploadedFiles[$index + 1];
            $this->uploadedFiles[$index + 1] = $temp;
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    private function startProcessing(): void
    {
        $this->isProcessing = true;
        $this->processingProgress = 20;
        $this->processingStatus = 'Preparing files for merging...';
        $this->clearMessages();
    }

    private function handleProcessingSuccess(array $result): void
    {
        $this->isProcessing = false;
        $this->processingProgress = 100;
        $this->processingStatus = 'PDF files merged successfully!';
        $this->mergedFile = $result['merged_file'];

        $fileCount = count($this->uploadedFiles);
        $totalPages = $result['total_pages'] ?? 0;

        $this->successMessage = "Successfully merged {$fileCount} PDF files into one document with {$totalPages} pages!";
        $this->dispatch('downloadReady');
    }

    private function handleProcessingError(Throwable $e): void
    {
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->errorMessage = 'Merge failed: '.$e->getMessage();
    }

    private function resetAll(): void
    {
        $this->uploadedFiles = [];
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->mergedFile = '';
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
