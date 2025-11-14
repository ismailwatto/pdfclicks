<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfSplitAction;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ZipArchive;

final class SplitPdf extends Component
{
    use WithFileUploads;

    public $uploadedFile;

    public $totalPages = 0;

    public $isConverting = false;

    public $conversionProgress = 0;

    public $conversionStatus = '';

    public $splitFiles = [];

    public $errorMessage = '';

    public $successMessage = '';

    public $splitMode = 'individual'; // 'individual' or 'ranges'

    public $pageRanges = [];

    public array $rules = [
        'uploadedFile' => 'required|file|mimes:pdf|max:10240', // 10MB max
        'splitMode' => 'required|in:individual,ranges',
        'pageRanges' => 'required_if:splitMode,ranges|array|min:1',
        'pageRanges.*.start' => 'required_if:splitMode,ranges|integer|min:1',
        'pageRanges.*.end' => 'required_if:splitMode,ranges|integer|min:1',
    ];

    public array $messages = [
        'uploadedFile.required' => 'Please select a PDF document to split.',
        'uploadedFile.mimes' => 'Only PDF files are allowed.',
        'uploadedFile.max' => 'File size must be less than 10MB.',
        'pageRanges.required_if' => 'Please add at least one page range.',
        'pageRanges.*.start.required_if' => 'Start page is required.',
        'pageRanges.*.end.required_if' => 'End page is required.',
        'pageRanges.*.start.min' => 'Start page must be at least 1.',
        'pageRanges.*.end.min' => 'End page must be at least 1.',
    ];

    public function mount(): void
    {
        $this->resetAll();
        $this->addPageRange(); // Add initial range for ranges mode
    }

    public function updatedUploadedFile(): void
    {
        $this->validateOnly('uploadedFile');
        $this->clearMessages();

        if ($this->uploadedFile) {
            $this->getTotalPages();
        }
    }

    public function updatedSplitMode(): void
    {
        $this->clearMessages();
        if ($this->splitMode === 'ranges' && empty($this->pageRanges)) {
            $this->addPageRange();
        }
    }

    public function addPageRange(): void
    {
        $this->pageRanges[] = [
            'start' => 1,
            'end' => $this->totalPages > 0 ? $this->totalPages : 1,
        ];
    }

    public function removePageRange(int $index): void
    {
        unset($this->pageRanges[$index]);
        $this->pageRanges = array_values($this->pageRanges); // Re-index array
    }

    public function splitPdf(): void
    {
        $this->validate();

        try {
            $this->startProcessing();

            // Store the uploaded file temporarily and get the full path
            $tempPath = $this->uploadedFile->store('temp', 'local');
            $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

            // Verify the file exists
            if (! file_exists($fullPath)) {
                throw new Exception("Failed to store uploaded file at: {$fullPath}");
            }

            $options = [];
            if ($this->splitMode === 'individual') {
                $options = [
                    'split_mode' => 'all',
                ];
            } else {
                // Validate ranges before processing
                $this->validatePageRanges();
                $options = [
                    'split_mode' => 'range',
                    'page_range' => $this->convertRangesToString(),
                ];
            }

            $splitAction = new PdfSplitAction($fullPath, $options);
            $result = $splitAction->process();

            $this->handleProcessingSuccess($result);
        } catch (Exception $e) {
            $this->handleProcessingError($e);
        }
    }

    public function downloadFile($index): ?Response
    {
        if (! isset($this->splitFiles[$index]) || ! isset($this->splitFiles[$index]['file_path'])) {
            $this->errorMessage = 'Split file not found.';

            return null;
        }

        $filePath = $this->splitFiles[$index]['file_path'];
        $fileName = $this->splitFiles[$index]['filename'];

        if (! file_exists($filePath)) {
            $this->errorMessage = 'Split file not found on disk.';

            return null;
        }

        return response()->download($filePath, $fileName);
    }

    public function downloadAllFiles(): ?Response
    {
        if (empty($this->splitFiles)) {
            $this->errorMessage = 'No split files available.';

            return null;
        }

        try {
            $tempDir = storage_path('app/temp');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipPath = $tempDir.'/split-pages-'.time().'.zip';
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($this->splitFiles as $file) {
                    $filePath = $file['file_path'];
                    if (file_exists($filePath)) {
                        $zipFileName = $this->splitMode === 'individual'
                            ? 'page-'.$file['page'].'.pdf'
                            : 'pages-'.$file['start_page'].'-'.$file['end_page'].'.pdf';

                        $zip->addFile($filePath, $zipFileName);
                    }
                }
                $zip->close();

                $zipName = $this->splitMode === 'individual'
                    ? 'split-pdf-pages.zip'
                    : 'split-pdf-ranges.zip';

                return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
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
        $this->addPageRange(); // Add initial range for ranges mode
    }

    public function checkProgress(): void
    {
        // Only check progress if we're actually converting
        if (! $this->isConverting) {
            return;
        }

        try {
            // Check for progress file
            $progressFile = storage_path('app/temp/progress_'.session()->getId().'.json');
            if (file_exists($progressFile)) {
                $progress = json_decode(file_get_contents($progressFile), true);
                if ($progress) {
                    $this->conversionProgress = $progress['progress'] ?? $this->conversionProgress;
                    $this->conversionStatus = $progress['status'] ?? $this->conversionStatus;

                    // If completed, clean up progress file
                    if ($progress['progress'] >= 100) {
                        unlink($progressFile);
                    }
                }
            } elseif ($this->conversionProgress < 90) {
                // Simple incremental progress simulation if no progress file
                $this->conversionProgress += random_int(10, 20);
                if ($this->conversionProgress > 90) {
                    $this->conversionProgress = 90;
                }
            }

        } catch (Exception $e) {
            // Don't break the conversion process if progress check fails
            Log::warning('Progress check failed: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tools.split-pdf');
    }

    private function getTotalPages(): void
    {
        try {
            if ($this->uploadedFile) {
                // Store the uploaded file temporarily to get page count
                $tempPath = $this->uploadedFile->store('temp', 'local');
                $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

                if (file_exists($fullPath)) {
                    $splitAction = new PdfSplitAction($fullPath, []);

                    // Use FPDI to get page count directly
                    $pdf = new \setasign\Fpdi\Fpdi();
                    $this->totalPages = $pdf->setSourceFile($fullPath);

                    // Clean up temp file
                    unlink($fullPath);

                    // Update existing page ranges with new total
                    if ($this->splitMode === 'ranges') {
                        foreach ($this->pageRanges as &$range) {
                            if ($range['end'] > $this->totalPages) {
                                $range['end'] = $this->totalPages;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->totalPages = 0;
            $this->errorMessage = 'Could not determine page count: '.$e->getMessage();
        }
    }

    private function validatePageRanges(): void
    {
        foreach ($this->pageRanges as $index => $range) {
            if ($range['start'] > $range['end']) {
                throw new Exception('Range '.($index + 1).': Start page cannot be greater than end page.');
            }

            if ($range['start'] > $this->totalPages) {
                throw new Exception('Range '.($index + 1).": Start page cannot be greater than total pages ({$this->totalPages}).");
            }

            if ($range['end'] > $this->totalPages) {
                throw new Exception('Range '.($index + 1).": End page cannot be greater than total pages ({$this->totalPages}).");
            }
        }
    }

    private function convertRangesToString(): string
    {
        $parts = [];
        foreach ($this->pageRanges as $range) {
            if ($range['start'] === $range['end']) {
                $parts[] = $range['start'];
            } else {
                $parts[] = $range['start'].'-'.$range['end'];
            }
        }

        return implode(',', $parts);
    }

    private function startProcessing(): void
    {
        $this->isConverting = true;
        $this->conversionProgress = 10;

        if ($this->splitMode === 'individual') {
            $this->conversionStatus = 'Splitting PDF into individual pages...';
        } else {
            $this->conversionStatus = 'Splitting PDF by page ranges...';
        }

        $this->clearMessages();
        $this->createProgressFile();
    }

    private function handleProcessingSuccess(array $result): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 100;

        // Convert the result files format to match expected structure
        $this->splitFiles = [];
        if (isset($result['files']) && is_array($result['files'])) {
            foreach ($result['files'] as $index => $file) {
                $this->splitFiles[] = [
                    'file_path' => $file['path'],
                    'filename' => $file['name'],
                    'page' => $file['page_number'],
                    'start_page' => $file['page_number'],
                    'end_page' => $file['page_number'],
                ];
            }
        }

        if ($this->splitMode === 'individual') {
            $fileCount = count($this->splitFiles);
            $this->conversionStatus = 'PDF split into individual pages completed!';
            $this->successMessage = "PDF successfully split into {$fileCount} individual page".
                                   ($fileCount === 1 ? '' : 's').'!';
        } else {
            $rangeCount = count($this->splitFiles);
            $this->conversionStatus = 'PDF split by ranges completed!';
            $this->successMessage = "PDF successfully split into {$rangeCount} file".
                                   ($rangeCount === 1 ? '' : 's').' by ranges!';
        }

        $this->updateProgressFile(100, $this->conversionStatus);
        $this->cleanupProgressFile();
    }

    private function handleProcessingError(Throwable $e): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->errorMessage = 'Split failed: '.$e->getMessage();
        $this->cleanupProgressFile();
    }

    private function resetAll(): void
    {
        $this->uploadedFile = null;
        $this->totalPages = 0;
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->splitFiles = [];
        $this->splitMode = 'individual';
        $this->pageRanges = [];
    }

    private function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    private function createProgressFile(): void
    {
        try {
            $tempDir = storage_path('app/temp');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $progressFile = $tempDir.'/progress_'.session()->getId().'.json';
            $progressData = [
                'progress' => 10,
                'status' => $this->conversionStatus,
                'started_at' => now()->toISOString(),
            ];

            file_put_contents($progressFile, json_encode($progressData));
        } catch (Exception $e) {
            Log::warning('Could not create progress file: '.$e->getMessage());
        }
    }

    private function updateProgressFile(int $progress, string $status = ''): void
    {
        try {
            $progressFile = storage_path('app/temp/progress_'.session()->getId().'.json');

            $progressData = [
                'progress' => $progress,
                'status' => $status,
                'updated_at' => now()->toISOString(),
            ];

            file_put_contents($progressFile, json_encode($progressData));
        } catch (Exception $e) {
            Log::warning('Could not update progress file: '.$e->getMessage());
        }
    }

    private function cleanupProgressFile(): void
    {
        try {
            $progressFile = storage_path('app/temp/progress_'.session()->getId().'.json');
            if (file_exists($progressFile)) {
                unlink($progressFile);
            }
        } catch (Exception $e) {
            Log::warning('Could not cleanup progress file: '.$e->getMessage());
        }
    }
}
