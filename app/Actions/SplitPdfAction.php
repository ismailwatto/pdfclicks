<?php

declare(strict_types=1);

namespace App\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PageBoundaries;

final readonly class SplitPdfAction
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp');
        $this->ensureTempDirectoryExists();
    }

    /**
     * Get the total number of pages in a PDF
     */
    public function getPageCount(UploadedFile $file): int
    {
        try {
            $pdf = new Fpdi();

            return $pdf->setSourceFile($file->getRealPath());
        } catch (Exception $e) {
            throw new Exception('Unable to read PDF file: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Split PDF into individual pages
     */
    public function splitPdfIntoIndividualPages(UploadedFile $file): array
    {
        try {
            $totalPages = $this->getPageCount($file);
            $splitFiles = [];
            $sessionId = session()->getId();

            // Create progress tracking
            $this->updateProgress($sessionId, 20, 'Reading PDF structure...');

            for ($pageNum = 1; $pageNum <= $totalPages; $pageNum++) {
                $outputFileName = 'page-'.$pageNum.'.pdf';
                $outputPath = $this->tempDir.'/'.$sessionId.'_'.$outputFileName;

                // Create new PDF for this page
                $pdf = new Fpdi();
                $pdf->setSourceFile($file->getRealPath());

                // Import and use the page
                $templateId = $pdf->importPage($pageNum, PageBoundaries::MEDIA_BOX);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Save the page
                $pdf->Output($outputPath, 'F');

                $splitFiles[] = [
                    'filename' => $outputFileName,
                    'file_path' => $outputPath,
                    'page' => $pageNum,
                    'size' => filesize($outputPath),
                ];

                // Update progress
                $progress = 20 + (($pageNum / $totalPages) * 70);
                $this->updateProgress($sessionId, (int) $progress, "Processing page {$pageNum} of {$totalPages}...");
            }

            $this->updateProgress($sessionId, 100, 'Split completed!');

            return [
                'success' => true,
                'total_pages' => $totalPages,
                'split_files' => $splitFiles,
                'message' => "PDF successfully split into {$totalPages} individual pages",
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to split PDF: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Split PDF by custom page ranges
     */
    public function splitPdfByRanges(UploadedFile $file, array $pageRanges): array
    {
        try {
            $totalPages = $this->getPageCount($file);
            $splitFiles = [];
            $sessionId = session()->getId();
            $totalRanges = count($pageRanges);

            // Validate ranges
            $this->validatePageRanges($pageRanges, $totalPages);

            $this->updateProgress($sessionId, 20, 'Reading PDF structure...');

            foreach ($pageRanges as $index => $range) {
                $startPage = (int) $range['start'];
                $endPage = (int) $range['end'];

                $outputFileName = 'pages-'.$startPage.'-'.$endPage.'.pdf';
                $outputPath = $this->tempDir.'/'.$sessionId.'_'.$outputFileName;

                // Create new PDF for this range
                $pdf = new Fpdi();
                $pdf->setSourceFile($file->getRealPath());

                // Add all pages in the range
                for ($pageNum = $startPage; $pageNum <= $endPage; $pageNum++) {
                    $templateId = $pdf->importPage($pageNum, PageBoundaries::MEDIA_BOX);
                    $size = $pdf->getTemplateSize($templateId);

                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }

                // Save the range
                $pdf->Output($outputPath, 'F');

                $splitFiles[] = [
                    'filename' => $outputFileName,
                    'file_path' => $outputPath,
                    'start_page' => $startPage,
                    'end_page' => $endPage,
                    'page_count' => $endPage - $startPage + 1,
                    'size' => filesize($outputPath),
                ];

                // Update progress
                $progress = 20 + (($index + 1) / $totalRanges * 70);
                $this->updateProgress($sessionId, (int) $progress, 'Processing range '.($index + 1)." of {$totalRanges}...");
            }

            $this->updateProgress($sessionId, 100, 'Split completed!');

            return [
                'success' => true,
                'total_pages' => $totalPages,
                'split_files' => $splitFiles,
                'message' => 'PDF successfully split into '.count($splitFiles).' files by ranges',
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to split PDF by ranges: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Clean up temporary files for a session
     */
    public function cleanupTempFiles(string $sessionId): void
    {
        try {
            $files = glob($this->tempDir.'/'.$sessionId.'_*');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Also clean up progress file
            $progressFile = $this->tempDir.'/progress_'.$sessionId.'.json';
            if (file_exists($progressFile)) {
                unlink($progressFile);
            }
        } catch (Exception $e) {
            Log::warning('Could not cleanup temp files: '.$e->getMessage());
        }
    }

    /**
     * Validate page ranges
     */
    private function validatePageRanges(array $pageRanges, int $totalPages): void
    {
        foreach ($pageRanges as $index => $range) {
            $startPage = (int) $range['start'];
            $endPage = (int) $range['end'];

            if ($startPage < 1 || $endPage < 1) {
                throw new Exception('Range '.($index + 1).': Page numbers must be greater than 0');
            }

            if ($startPage > $totalPages || $endPage > $totalPages) {
                throw new Exception('Range '.($index + 1).": Page numbers cannot exceed total pages ({$totalPages})");
            }

            if ($startPage > $endPage) {
                throw new Exception('Range '.($index + 1).': Start page cannot be greater than end page');
            }
        }
    }

    /**
     * Update progress file
     */
    private function updateProgress(string $sessionId, int $progress, string $status): void
    {
        try {
            $progressFile = $this->tempDir.'/progress_'.$sessionId.'.json';
            $progressData = [
                'progress' => $progress,
                'status' => $status,
                'updated_at' => now()->toISOString(),
            ];

            file_put_contents($progressFile, json_encode($progressData));
        } catch (Exception $e) {
            // Don't break the process if progress update fails
            Log::warning('Could not update progress: '.$e->getMessage());
        }
    }

    /**
     * Ensure temp directory exists
     */
    private function ensureTempDirectoryExists(): void
    {
        if (! file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
}
