<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait TemporaryFileManagement
{
    protected array $temporaryFiles = [];

    protected array $outputFiles = [];

    protected string $tempDirectory = 'temp/pdf-processing';

    public function __destruct()
    {
        // Only cleanup temporary processing files, not output files
        $this->cleanupTempFiles();
    }

    protected function prepareTempDirectory(): string
    {
        // Use DIRECTORY_SEPARATOR for cross-platform compatibility
        $directory = storage_path('app'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $this->tempDirectory));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }

    protected function prepareTempFile(string $file, string $suffix = ''): array
    {
        $tempDir = $this->prepareTempDirectory();

        // File is already a path, copy it to temp directory if needed
        $originalName = basename($file);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $tempFileName = Str::uuid().$suffix.'.'.$extension;
        $tempPath = $tempDir.DIRECTORY_SEPARATOR.$tempFileName;

        if (! copy($file, $tempPath)) {
            throw new Exception("Failed to copy file to temporary directory: {$file} -> {$tempPath}");
        }

        $this->temporaryFiles[] = $tempPath;

        return [
            'path' => $tempPath,
            'original_name' => $originalName,
            'extension' => $extension,
            'size' => filesize($tempPath),
        ];
    }

    protected function generateOutputPath(string $extension = 'pdf', string $prefix = 'converted'): string
    {
        $outputDir = storage_path('app/public/converted');

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $fileName = $prefix.'-'.Str::uuid().'.'.$extension;
        $outputPath = $outputDir.DIRECTORY_SEPARATOR.$fileName;

        // Track output files separately - they should not be auto-cleaned
        $this->outputFiles[] = $outputPath;

        return $outputPath;
    }

    protected function addTempFile(string $filePath): void
    {
        if (! in_array($filePath, $this->temporaryFiles)) {
            $this->temporaryFiles[] = $filePath;
        }
    }

    protected function cleanupTempFiles(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                try {
                    unlink($file);
                    Log::debug("Cleaned up temporary file: {$file}");
                } catch (Exception $e) {
                    Log::warning("Failed to cleanup temporary file {$file}: ".$e->getMessage());
                }
            }
        }

        $this->temporaryFiles = [];
    }

    protected function cleanupOldTempFiles(int $maxAgeInMinutes = 60): void
    {
        $tempDir = storage_path('app'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $this->tempDirectory));

        if (! is_dir($tempDir)) {
            return;
        }

        $cutoffTime = time() - ($maxAgeInMinutes * 60);
        $files = glob($tempDir.DIRECTORY_SEPARATOR.'*');

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                try {
                    unlink($file);
                    Log::debug("Cleaned up old temporary file: {$file}");
                } catch (Exception $e) {
                    Log::warning("Failed to cleanup old temporary file {$file}: ".$e->getMessage());
                }
            }
        }
    }

    protected function getTempFileSize(): int
    {
        $totalSize = 0;

        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                $totalSize += filesize($file);
            }
        }

        return $totalSize;
    }
}
