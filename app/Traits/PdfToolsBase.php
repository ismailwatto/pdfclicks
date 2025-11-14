<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use ZipArchive;

trait PdfToolsBase
{
    public function downloadFile(int $index = 0)
    {
        if ($this->convertedFile && file_exists($this->convertedFile)) {
            $originalName = $this->uploadedFile ? $this->uploadedFile->getClientOriginalName() : 'converted-file';
            $extension = pathinfo($this->convertedFile, PATHINFO_EXTENSION);
            $downloadName = pathinfo($originalName, PATHINFO_FILENAME).'_converted.'.$extension;

            return response()->download($this->convertedFile, $downloadName);
        }

        if (property_exists($this, 'convertedFiles') &&
            isset($this->convertedFiles[$index]) &&
            file_exists($this->convertedFiles[$index])) {

            $file = $this->convertedFiles[$index];
            $downloadName = is_array($file) ? ($file['name'] ?? 'converted-file') : basename($file);
            $filePath = is_array($file) ? $file['path'] : $file;

            return response()->download($filePath, $downloadName);
        }

        $this->errorMessage = 'Converted file not found.';

        return null;
    }

    public function resetConverter(): void
    {
        $this->resetAll();
    }

    protected function startConversion(): void
    {
        $this->isConverting = true;
        $this->conversionProgress = 0;
        $this->conversionStatus = 'Starting conversion...';
        $this->clearMessages();
    }

    protected function handleSuccess(array $result): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 100;
        $this->conversionStatus = 'Conversion completed!';

        // Handle single file result
        if (isset($result['converted_file'])) {
            $this->convertedFile = $result['converted_file'];
        }

        // Handle multiple files result
        if (isset($result['files']) && is_array($result['files'])) {
            $this->convertedFile = $result['files'][0] ?? null;
            if (isset($result['files'])) {
                $this->convertedFiles = $result['files'];
            }
        }

        $this->successMessage = $this->generateSuccessMessage($result);
        $this->dispatch('conversionComplete');
    }

    protected function handleError(Exception $e): void
    {
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->errorMessage = 'Conversion failed: '.$e->getMessage();

        // Log the full error for debugging
        Log::error('PDF Tool Conversion Error', [
            'tool' => static::class,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    protected function updateProgress(int $progress, string $status): void
    {
        $this->conversionProgress = max(0, min(100, $progress));
        $this->conversionStatus = $status;
    }

    protected function generateSuccessMessage(array $result): string
    {
        $toolName = $this->getToolName();

        if (isset($result['files']) && is_array($result['files'])) {
            $count = count($result['files']);

            return "Successfully processed {$count} file(s) with {$toolName}!";
        }

        if (isset($result['compressed_file'])) {
            $ratio = $result['compression_ratio'] ?? 0;

            return "PDF compressed successfully! Reduced file size by {$ratio}%.";
        }

        if (isset($result['converted_file']) || isset($result['excel_file'])) {
            return "File successfully converted with {$toolName}!";
        }

        return 'Processing completed successfully!';
    }

    protected function getToolName(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to readable format
        $toolName = preg_replace('/([A-Z])/', ' $1', $className);
        $toolName = mb_trim($toolName);

        // Add specific tool formatting
        $toolName = str_replace(['Pdf To ', 'To Pdf', 'Pdf'], ['PDF to ', ' to PDF', 'PDF'], $toolName);

        return $toolName;
    }

    protected function clearMessages(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    protected function resetAll(): void
    {
        $this->uploadedFile = null;
        $this->isConverting = false;
        $this->conversionProgress = 0;
        $this->conversionStatus = '';
        $this->convertedFile = '';
        $this->clearMessages();

        // Reset additional properties if they exist
        if (property_exists($this, 'convertedFiles')) {
            $this->convertedFiles = [];
        }

        if (property_exists($this, 'jobId')) {
            $this->jobId = '';
        }
    }

    protected function buildOptions(): array
    {
        $options = [];

        // Common options that might exist across tools
        $commonProperties = [
            'quality', 'format', 'compression', 'pageRange', 'outputFormat',
            'imageQuality', 'qualityLevel', 'removeMetadata', 'optimizeFor',
        ];

        foreach ($commonProperties as $property) {
            if (property_exists($this, $property)) {
                $snakeCase = mb_strtolower(preg_replace('/([A-Z])/', '_$1', $property));
                $snakeCase = mb_ltrim($snakeCase, '_');
                $options[$snakeCase] = $this->$property;
            }
        }

        return $options;
    }

    protected function createZipDownload(array $files, string $zipBaseName): ?\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (empty($files)) {
            $this->errorMessage = 'No files found to download.';

            return null;
        }

        try {
            $zip = new ZipArchive();
            $zipFileName = $zipBaseName.'-'.date('Y-m-d-H-i-s').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            // Ensure temp directory exists
            $tempDir = dirname($zipPath);
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($files as $file) {
                    // Handle both array format and string paths
                    if (is_array($file)) {
                        $filePath = $file['path'] ?? $file['file_path'] ?? null;
                        $fileName = $file['name'] ?? $file['file_name'] ?? basename($filePath);
                    } else {
                        $filePath = $file;
                        $fileName = basename($file);
                    }

                    if ($filePath && file_exists($filePath)) {
                        $zip->addFile($filePath, $fileName);
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
}
