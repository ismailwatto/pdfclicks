<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\CloudConvertAction;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

// 3. PowerPoint to PDF
final class PowerpointToPdf extends Component
{
    use WithFileUploads;

    public $uploadedFile;

    public $isConverting = false;

    public $conversionProgress = 0;

    public $conversionStatus = '';

    public $jobId = '';

    public $convertedFile = '';

    public $errorMessage = '';

    public $successMessage = '';

    public array $rules = [
        'uploadedFile' => 'required|file|mimes:ppt,pptx|max:20480', // 20MB max
    ];

    public array $messages = [
        'uploadedFile.required' => 'Please select a PowerPoint presentation to convert.',
        'uploadedFile.mimes' => 'Only PowerPoint files (.ppt, .pptx) are allowed.',
        'uploadedFile.max' => 'File size must be less than 20MB.',
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

    public function convertToPdf(): void
    {
        $this->validate();

        try {
            $this->startConversion();
            $cloudConvert = new CloudConvertAction();
            $result = $cloudConvert->convert($this->uploadedFile, 'pdf');
            $this->handleConversionSuccess($result);
        } catch (Exception $e) {
            $this->handleConversionError($e);
        }
    }

    public function checkProgress(): void
    {
        if (! $this->jobId || ! $this->isConverting) {
            return;
        }

        try {
            $cloudConvert = new CloudConvertAction();
            $status = $cloudConvert->getJobStatus($this->jobId);

            if (isset($status['error'])) {
                $this->handleConversionError(new Exception($status['error']));

                return;
            }

            $this->updateProgress($status);
        } catch (Exception $e) {
            $this->handleConversionError($e);
        }
    }

    public function downloadFile()
    {
        if (! $this->convertedFile || ! file_exists($this->convertedFile)) {
            $this->errorMessage = 'Converted file not found.';

            return null;
        }

        return response()->download($this->convertedFile, 'converted-presentation.pdf');
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.powerpoint-to-pdf');
    }

    private function startConversion(): void
    {
        $this->isConverting = true;
        $this->conversionProgress = 10;
        $this->conversionStatus = 'Initializing conversion...';
        $this->clearMessages();
    }

    private function updateProgress(array $status): void
    {
        switch ($status['status']) {
            case 'waiting':
                $this->conversionProgress = 25;
                $this->conversionStatus = 'Waiting in queue...';
                break;
            case 'processing':
                $this->conversionProgress = 50;
                $this->conversionStatus = 'Converting presentation...';
                break;
            case 'finished':
                $this->conversionProgress = 100;
                $this->conversionStatus = 'Conversion completed!';
                $this->isConverting = false;
                break;
            case 'error':
                $this->handleConversionError(new Exception('Conversion failed'));
                break;
        }
    }

    private function handleConversionSuccess(array $result): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 100;
        $this->conversionStatus = 'Conversion completed successfully!';
        $this->jobId = $result['job_id'];
        $this->convertedFile = $result['converted_file'];
        $this->successMessage = 'PowerPoint presentation successfully converted to PDF!';
        $this->dispatch('downloadReady');
    }

    private function handleConversionError(Throwable $e): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->errorMessage = 'Conversion failed: '.$e->getMessage();
    }

    private function resetAll(): void
    {
        $this->uploadedFile = null;
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->jobId = '';
        $this->convertedFile = '';
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
