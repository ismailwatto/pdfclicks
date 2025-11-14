<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use App\Traits\FileValidation;
use App\Traits\ProgressTracking;
use App\Traits\TemporaryFileManagement;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class BasePdfAction
{
    use FileValidation, ProgressTracking, TemporaryFileManagement;

    protected string $inputFile;

    protected string $outputPath;

    protected array $options;

    protected array $fileInfo;

    public function __construct(string $inputFile, array $options = [])
    {
        $this->options = $options;
        $this->checkDependencies();
        $this->prepareInputFile($inputFile);
        $this->cleanupOldTempFiles(); // Clean up old files on each run
    }

    public function __destruct()
    {
        $this->cleanupTempFiles();
    }

    abstract public function process(): array;

    final public function execute(string $inputFile, array $options = []): array
    {
        $this->inputFile = $inputFile;
        $this->options = array_merge($this->options, $options);
        $this->prepareInputFile($inputFile);

        return $this->process();
    }

    protected function checkDependencies(): void
    {
        // Override in child classes to check specific dependencies
        // Base implementation checks common requirements
        $this->checkCommonDependencies();
    }

    protected function checkCommonDependencies(): void
    {
        $missingExtensions = [];

        // Check for common extensions
        if (! extension_loaded('fileinfo')) {
            $missingExtensions[] = 'fileinfo';
        }

        if (! empty($missingExtensions)) {
            throw new Exception('Missing required PHP extensions: '.implode(', ', $missingExtensions).'. Please contact your system administrator.');
        }
    }

    protected function prepareInputFile(string $inputFile): void
    {
        try {
            $this->validatePdfFile($inputFile);
            $this->fileInfo = $this->prepareTempFile($inputFile);
            $this->inputFile = $this->fileInfo['path'];
        } catch (Exception $e) {
            $this->cleanupTempFiles();
            throw $e;
        }
    }

    protected function validateInput(): void
    {
        $this->validateFileExists($this->inputFile);
        $this->validatePdfFile($this->inputFile);
    }

    protected function prepareOutput(string $extension = 'pdf', string $prefix = 'converted'): string
    {
        $this->outputPath = $this->generateOutputPath($extension, $prefix);

        return $this->outputPath;
    }

    protected function buildResult(array $additionalData = []): array
    {
        $baseResult = [
            'success' => true,
            'original_file' => $this->fileInfo['original_name'],
            'original_size' => $this->fileInfo['size'],
            'processing_time' => $this->getProcessingTime(),
        ];

        return array_merge($baseResult, $additionalData);
    }

    protected function getProcessingTime(): float
    {
        return microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }

    protected function handleError(Exception $e): void
    {
        $this->cleanupTempFiles();
        Log::error('PDF Processing Error', [
            'action' => static::class,
            'error' => $e->getMessage(),
            'file' => $this->fileInfo['original_name'] ?? 'unknown',
            'trace' => $e->getTraceAsString(),
        ]);

        throw $e;
    }

    protected function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    protected function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    protected function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    protected function validateOutputDirectory(): void
    {
        $directory = dirname($this->outputPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! is_writable($directory)) {
            throw new Exception("Output directory is not writable: {$directory}");
        }
    }

    protected function getFileInfo(): array
    {
        return $this->fileInfo;
    }
}
