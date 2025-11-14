<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use Exception;
use setasign\Fpdi\Fpdi;

final class PdfMergeAction extends BasePdfAction
{
    private array $inputFiles = [];

    public function __construct(array $inputFiles, array $options = [])
    {
        $this->options = $options;
        $this->prepareInputFiles($inputFiles);
        $this->cleanupOldTempFiles();
    }

    public function process(): array
    {
        try {
            $this->updateProgress(10, 'Preparing to merge PDF files...');

            if (count($this->inputFiles) < 2) {
                throw new Exception('At least 2 PDF files are required for merging');
            }

            $mergedPdf = new Fpdi();
            $totalFiles = count($this->inputFiles);
            $totalPages = 0;

            foreach ($this->inputFiles as $index => $fileInfo) {
                $this->updateProgress(
                    (int) (20 + (70 * ($index / $totalFiles))),
                    'Merging file '.($index + 1)." of {$totalFiles}: {$fileInfo['original_name']}"
                );

                $pageCount = $mergedPdf->setSourceFile($fileInfo['path']);

                for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                    $templateId = $mergedPdf->importPage($pageNum);
                    $size = $mergedPdf->getTemplateSize($templateId);

                    $mergedPdf->AddPage($size['orientation'], $size);
                    $mergedPdf->useTemplate($templateId);
                    $totalPages++;
                }
            }

            $this->updateProgress(90, 'Saving merged PDF...');

            $outputFile = $this->generateOutputPath('pdf', 'merged');
            $mergedPdf->Output($outputFile, 'F');

            $this->updateProgress(100, 'PDF merge completed!');

            return [
                'success' => true,
                'merged_file' => $outputFile,
                'total_files_merged' => $totalFiles,
                'total_pages' => $totalPages,
                'output_size' => filesize($outputFile),
                'input_files' => array_column($this->inputFiles, 'original_name'),
                'processing_time' => $this->getProcessingTime(),
            ];

        } catch (Exception $e) {
            $this->handleError(new Exception('PDF merge failed: '.$e->getMessage()));
        }
    }

    protected function buildResult(array $additionalData = []): array
    {
        // Override to handle multiple input files
        return [
            'success' => true,
            'input_files' => array_column($this->inputFiles, 'original_name'),
            'total_input_size' => array_sum(array_column($this->inputFiles, 'size')),
            'processing_time' => $this->getProcessingTime(),
        ] + $additionalData;
    }

    private function prepareInputFiles(array $inputFiles): void
    {
        foreach ($inputFiles as $file) {
            try {
                $this->validatePdfFile($file);
                $fileInfo = $this->prepareTempFile($file);
                $this->inputFiles[] = $fileInfo;
            } catch (Exception $e) {
                $this->cleanupTempFiles();
                throw new Exception('Invalid PDF file: '.$e->getMessage());
            }
        }
    }
}
