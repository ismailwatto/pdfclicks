<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfToOfficeAction;
use App\Traits\PdfToolsBase;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

final class PdfToExcel extends Component
{
    use PdfToolsBase, WithFileUploads;

    public $uploadedFile;

    public $isConverting = false;

    public $conversionProgress = 0;

    public $conversionStatus = '';

    public $jobId = '';

    public $convertedFile = '';

    public $convertedFiles = [];

    public $errorMessage = '';

    public $successMessage = '';

    // PDF to Excel specific properties
    public $outputFormat = 'xlsx';

    public $pageSelection = 'all';

    public $startPage = 1;

    public $endPage = null;

    public $specificPages = '';

    public $tableDetection = true;

    public $preserveFormatting = true;

    public $separateFiles = false;

    public $totalPages = 0;

    public function mount(): void
    {
        $this->resetAll();
    }

    public function updatedUploadedFile(): void
    {
        $this->validateOnly('uploadedFile');
        $this->clearMessages();

        if ($this->uploadedFile) {
            $this->analyzePdf();
        }
    }

    public function updatedPageSelection(): void
    {
        $this->clearMessages();

        if ($this->pageSelection === 'all') {
            $this->startPage = 1;
            $this->endPage = $this->totalPages ?: null;
            $this->specificPages = '';
        }
    }

    public function convertToExcel(): void
    {
        $this->validate();

        if ($this->pageSelection === 'range' && $this->startPage > $this->endPage) {
            $this->errorMessage = 'Start page cannot be greater than end page.';

            return;
        }

        try {
            $this->startConversion();

            // Check if uploaded file is valid
            if (! $this->uploadedFile || ! $this->uploadedFile->isValid()) {
                throw new Exception('Invalid or corrupted uploaded file');
            }

            // Store the uploaded file temporarily and get the full path
            try {
                $tempPath = $this->uploadedFile->store('temp', 'local');
                if (! $tempPath) {
                    throw new Exception('Failed to store uploaded file - store() returned false');
                }
            } catch (Exception $e) {
                throw new Exception('Error storing uploaded file: '.$e->getMessage());
            }

            $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

            // Log file information for debugging
            Log::info('PDF to Excel conversion starting', [
                'original_name' => $this->uploadedFile->getClientOriginalName(),
                'uploaded_file_size' => $this->uploadedFile->getSize(),
                'uploaded_file_path' => $this->uploadedFile->getRealPath(),
                'temp_path' => $tempPath,
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
                'storage_app_path' => storage_path('app'),
                'storage_private_path' => storage_path('app/private'),
                'storage_app_exists' => is_dir(storage_path('app')),
                'storage_private_exists' => is_dir(storage_path('app/private')),
                'temp_dir_exists' => is_dir(storage_path('app/private/temp')),
                'temp_dir_writable' => is_writable(storage_path('app/private/temp')),
            ]);

            // Verify the file exists at the expected location
            if (! file_exists($fullPath)) {
                throw new Exception("Failed to store uploaded file at: {$fullPath}. Check storage permissions and disk space.");
            }

            $options = $this->buildConversionOptions();
            $options['output_format'] = $this->outputFormat;

            // Use the processing action with the stored file path
            $action = new PdfToOfficeAction($fullPath, $options);
            $result = $action->process();

            $this->handleConversionSuccess($result);
        } catch (Exception $e) {
            $this->handleConversionError($e);
        }
    }

    public function checkProgress(): void
    {
        // Progress tracking is now handled by the processing action internally
        // This method can be kept for backward compatibility but is no longer needed
        if (! $this->isConverting) {
            return;
        }
    }

    public function downloadFile(int $index = 0)
    {
        // Log download attempt for debugging
        Log::info('Download attempt', [
            'converted_file' => $this->convertedFile,
            'converted_file_exists' => $this->convertedFile ? file_exists($this->convertedFile) : false,
            'converted_files_count' => is_array($this->convertedFiles) ? count($this->convertedFiles) : 0,
            'index' => $index,
            'output_format' => $this->outputFormat,
        ]);

        if ($this->convertedFile && file_exists($this->convertedFile)) {
            $extension = $this->outputFormat === 'xlsx' ? 'xlsx' : 'csv';
            $filename = 'converted-document.'.$extension;

            return response()->download($this->convertedFile, $filename);
        }

        if (isset($this->convertedFiles[$index]) && file_exists($this->convertedFiles[$index]['path'])) {
            $file = $this->convertedFiles[$index];

            return response()->download($file['path'], $file['name']);
        }

        $this->errorMessage = 'Converted file not found.';
        Log::warning('Download failed - file not found', [
            'converted_file' => $this->convertedFile,
            'converted_files' => $this->convertedFiles,
        ]);

        return null;
    }

    public function downloadAllFiles()
    {
        if (empty($this->convertedFiles)) {
            $this->errorMessage = 'No converted files found.';

            return null;
        }

        return $this->createZipDownload($this->convertedFiles, 'converted-excel-files');
    }

    public function resetConverter(): void
    {
        $this->resetAll();
        $this->clearMessages();
    }

    public function render()
    {
        return view('livewire.tools.pdf-to-excel');
    }

    protected function rules(): array
    {
        return [
            'uploadedFile' => 'required|file|mimes:pdf|max:15360', // 15MB max
            'outputFormat' => 'in:xlsx,csv',
            'pageSelection' => 'in:all,range,specific',
            'startPage' => 'required_if:pageSelection,range|integer|min:1',
            'endPage' => 'required_if:pageSelection,range|integer|min:1',
            'specificPages' => 'required_if:pageSelection,specific|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'uploadedFile.required' => 'Please select a PDF document to convert.',
            'uploadedFile.mimes' => 'Only PDF files are allowed.',
            'uploadedFile.max' => 'File size must be less than 15MB.',
            'outputFormat.in' => 'Please select a valid output format.',
            'startPage.required_if' => 'Start page is required when using page range.',
            'endPage.required_if' => 'End page is required when using page range.',
            'specificPages.required_if' => 'Please specify page numbers.',
        ];
    }

    private function analyzePdf(): void
    {
        try {
            // Simple page count estimation - in production you might want to use a PDF library
            // For now, we'll estimate based on file size
            $fileSize = $this->uploadedFile->getSize();
            $this->totalPages = max(1, (int) ($fileSize / 100000)); // Rough estimation

            if ($this->pageSelection === 'all') {
                $this->endPage = $this->totalPages;
            }
        } catch (Exception $e) {
            $this->errorMessage = 'Error analyzing PDF: '.$e->getMessage();
            $this->totalPages = 1;
        }
    }

    private function buildConversionOptions(): array
    {
        $options = [
            'table_detection' => $this->tableDetection,
            'preserve_formatting' => $this->preserveFormatting,
            'separate_files' => $this->separateFiles,
        ];

        if ($this->pageSelection === 'range') {
            $options['page_range'] = "{$this->startPage}-{$this->endPage}";
        } elseif ($this->pageSelection === 'specific') {
            $options['specific_pages'] = $this->specificPages;
        }

        return $options;
    }

    private function startConversion(): void
    {
        $this->isConverting = true;
        $this->conversionProgress = 10;
        $this->conversionStatus = 'Initializing PDF to Excel conversion...';
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
                $this->conversionStatus = 'Extracting data from PDF...';
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

        if (isset($result['converted_file'])) {
            $this->convertedFile = $result['converted_file'];

            // Log conversion success for debugging
            Log::info('Conversion success in Livewire', [
                'converted_file' => $this->convertedFile,
                'file_exists' => file_exists($this->convertedFile),
                'file_size' => file_exists($this->convertedFile) ? filesize($this->convertedFile) : 'N/A',
                'result_keys' => array_keys($result),
            ]);

            $this->successMessage = $this->generateSuccessMessage($result);
        }

        $this->dispatch('conversionComplete');
    }

    private function handleConversionError(Throwable $e): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';

        // Log the full error for debugging
        Log::error('PDF to Excel conversion failed: '.$e->getMessage(), [
            'file' => $this->uploadedFile ? $this->uploadedFile->getClientOriginalName() : 'unknown',
            'trace' => $e->getTraceAsString(),
        ]);

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
        $this->outputFormat = 'xlsx';
        $this->pageSelection = 'all';
        $this->startPage = 1;
        $this->endPage = null;
        $this->specificPages = '';
        $this->tableDetection = true;
        $this->preserveFormatting = true;
        $this->separateFiles = false;
        $this->totalPages = 0;
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }
}
