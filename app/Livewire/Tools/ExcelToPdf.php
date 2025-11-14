<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\CloudConvertAction;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

final class ExcelToPdf extends Component
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

    // Excel-specific properties
    public $worksheetNames = [];

    public $selectedSheets = [];

    public $convertAllSheets = true;

    public $pageOrientation = 'portrait';

    public $paperSize = 'A4';

    public $fitToPage = true;

    public $convertedFiles = [];

    public array $rules = [
        'uploadedFile' => 'required|file|mimes:xls,xlsx,csv|max:15360', // 15MB max
        'selectedSheets' => 'array',
        'pageOrientation' => 'in:portrait,landscape',
        'paperSize' => 'in:A4,A3,Letter,Legal',
        'fitToPage' => 'boolean',
    ];

    public array $messages = [
        'uploadedFile.required' => 'Please select an Excel document to convert.',
        'uploadedFile.mimes' => 'Only Excel files (XLS, XLSX, CSV) are allowed.',
        'uploadedFile.max' => 'File size must be less than 15MB.',
        'selectedSheets.required' => 'Please select at least one worksheet to convert.',
    ];

    public function mount(): void
    {
        $this->resetAll();
    }

    public function updatedUploadedFile(): void
    {
        $this->validateOnly('uploadedFile');
        $this->clearMessages();

        if ($this->uploadedFile) {
            $this->extractWorksheetNames();
        }
    }

    public function updatedConvertAllSheets(): void
    {
        if ($this->convertAllSheets) {
            $this->selectedSheets = [];
        }
    }

    public function convertToPdf(): void
    {
        $this->validate();

        if (! $this->convertAllSheets && empty($this->selectedSheets)) {
            $this->errorMessage = 'Please select at least one worksheet to convert.';

            return;
        }

        try {
            $this->startConversion();
            $cloudConvert = new CloudConvertAction();

            $options = [
                'quality' => 100,
                'timeout' => 900,
                'page_orientation' => $this->pageOrientation,
                'paper_size' => $this->paperSize,
                'fit_to_page' => $this->fitToPage,
            ];

            if ($this->convertAllSheets) {
                // Convert all worksheets
                $result = $cloudConvert->convert($this->uploadedFile, 'pdf');
                $this->handleSingleConversionSuccess($result);
            } else {
                // Convert selected worksheets
                $result = $cloudConvert->convertSelectedSheets(
                    $this->uploadedFile,
                    'pdf',
                    $this->selectedSheets,
                    $options
                );
                $this->handleMultipleConversionSuccess($result);
            }
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

    public function downloadFile(int $index = 0)
    {
        if ($this->convertedFile && file_exists($this->convertedFile)) {
            // Single file download
            return response()->download($this->convertedFile, 'converted-document.pdf');
        }
        if (isset($this->convertedFiles[$index]) && file_exists($this->convertedFiles[$index]['path'])) {
            // Multiple files download
            $file = $this->convertedFiles[$index];

            return response()->download($file['path'], $file['name'].'.pdf');
        }
        $this->errorMessage = 'Converted file not found.';

        return null;

    }

    public function downloadAllFiles()
    {
        if (empty($this->convertedFiles)) {
            $this->errorMessage = 'No converted files found.';

            return null;
        }

        try {
            $zip = new ZipArchive();
            $zipFileName = 'converted-excel-files-'.date('Y-m-d-H-i-s').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            // Ensure temp directory exists
            if (! file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($this->convertedFiles as $file) {
                    if (file_exists($file['path'])) {
                        $zip->addFile($file['path'], $file['name'].'.pdf');
                    }
                }
                $zip->close();

                return response()->download($zipPath, $zipFileName)->deleteFileAfterSend();
            }
            $this->errorMessage = 'Failed to create ZIP file.';

            return null;

        } catch (Exception $e) {
            $this->errorMessage = 'Error creating ZIP file: '.$e->getMessage();

            return null;
        }
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.excel-to-pdf');
    }

    private function extractWorksheetNames(): void
    {
        try {
            $reader = IOFactory::createReader(IOFactory::identify($this->uploadedFile->getRealPath()));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->uploadedFile->getRealPath());

            $this->worksheetNames = $spreadsheet->getSheetNames();

            // Set default selections
            $this->selectedSheets = [];
            $this->convertAllSheets = true;

        } catch (Exception $e) {
            $this->errorMessage = 'Error reading Excel file: '.$e->getMessage();
            $this->worksheetNames = [];
        }
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
                $this->conversionStatus = 'Converting Excel to PDF...';
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

    private function handleSingleConversionSuccess(array $result): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 100;
        $this->conversionStatus = 'Conversion completed successfully!';
        $this->jobId = $result['job_id'];
        $this->convertedFile = $result['converted_file'];
        $this->successMessage = 'Excel successfully converted to PDF!';
        $this->dispatch('downloadReady');
    }

    private function handleMultipleConversionSuccess(array $result): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 100;
        $this->conversionStatus = 'Conversion completed successfully!';
        $this->jobId = $result['job_id'];
        $this->convertedFiles = $result['converted_files'];
        $this->successMessage = count($this->convertedFiles).' worksheets successfully converted to PDF!';
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
        $this->convertedFiles = [];
        $this->worksheetNames = [];
        $this->selectedSheets = [];
        $this->convertAllSheets = true;
        $this->pageOrientation = 'portrait';
        $this->paperSize = 'A4';
        $this->fitToPage = true;
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
