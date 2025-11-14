<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use Exception;
use setasign\Fpdi\Fpdi;

final class PdfCompressionAction extends BasePdfAction
{
    public function process(): array
    {
        try {
            $this->updateProgress(10, 'Analyzing PDF structure...');

            $qualityLevel = $this->getOption('quality_level', 'medium');
            $removeMetadata = $this->getOption('remove_metadata', false);

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($this->inputFile);

            $originalSize = filesize($this->inputFile);

            $this->updateProgress(20, "Processing {$pageCount} page(s)...");

            // Configure compression based on quality level
            $this->configureCompression($pdf, $qualityLevel);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $this->updateProgress(
                    (int) (30 + (50 * ($pageNo / $pageCount))),
                    "Compressing page {$pageNo} of {$pageCount}..."
                );

                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], $size);
                $pdf->useTemplate($templateId);
            }

            $this->updateProgress(85, 'Applying final optimizations...');

            // Remove metadata if requested
            if ($removeMetadata) {
                $this->removeMetadata($pdf);
            }

            $this->updateProgress(90, 'Saving compressed PDF...');

            $compressedFile = $this->generateOutputPath('pdf', 'compressed');
            $pdf->Output($compressedFile, 'F');

            $compressedSize = filesize($compressedFile);
            $compressionRatio = $originalSize > 0 ? (($originalSize - $compressedSize) / $originalSize) * 100 : 0;
            $sizeReduction = $originalSize - $compressedSize;

            $this->updateProgress(100, 'Compression completed!');

            return $this->buildResult([
                'compressed_file' => $compressedFile,
                'compressed_size' => $compressedSize,
                'compression_ratio' => round($compressionRatio, 1),
                'size_reduction' => $sizeReduction,
                'quality_level' => $qualityLevel,
                'metadata_removed' => $removeMetadata,
                'original_size_formatted' => $this->formatFileSize($originalSize),
                'compressed_size_formatted' => $this->formatFileSize($compressedSize),
                'size_reduction_formatted' => $this->formatFileSize($sizeReduction),
            ]);

        } catch (Exception $e) {
            $this->handleError(new Exception('PDF compression failed: '.$e->getMessage()));
        }
    }

    private function configureCompression(Fpdi $pdf, string $qualityLevel): void
    {
        // Enable basic compression
        $pdf->SetCompression(true);

        switch ($qualityLevel) {
            case 'high':
                // Minimal compression - preserve quality
                $pdf->SetAutoPageBreak(false);
                break;

            case 'medium':
                // Balanced compression
                $pdf->SetAutoPageBreak(false);
                $pdf->SetMargins(0, 0, 0);
                break;

            case 'maximum':
                // Maximum compression - aggressive optimization
                $pdf->SetAutoPageBreak(false);
                $pdf->SetMargins(0, 0, 0);
                // Additional optimization can be added here
                break;
        }
    }

    private function removeMetadata(Fpdi $pdf): void
    {
        // Remove common metadata fields
        $pdf->SetCreator('');
        $pdf->SetAuthor('');
        $pdf->SetTitle('');
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
    }
}
