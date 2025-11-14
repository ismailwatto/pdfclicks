<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use Exception;
use setasign\Fpdi\Fpdi;

final class PdfSplitAction extends BasePdfAction
{
    public function process(): array
    {
        try {
            $this->updateProgress(10, 'Analyzing PDF structure...');

            $splitMode = $this->getOption('split_mode', 'pages');
            $pageRange = $this->getOption('page_range', null);
            $specificPages = $this->getOption('specific_pages', null);

            $sourcePdf = new Fpdi();
            $pageCount = $sourcePdf->setSourceFile($this->inputFile);

            $this->updateProgress(20, "Processing {$pageCount} page(s)...");

            $pagesToSplit = $this->determinePages($splitMode, $pageCount, $pageRange, $specificPages);
            $splitFiles = [];

            foreach ($pagesToSplit as $index => $pageNum) {
                $this->updateProgress(
                    (int) (30 + (60 * ($index / count($pagesToSplit)))),
                    "Splitting page {$pageNum}..."
                );

                $outputPdf = new Fpdi();
                $outputPdf->setSourceFile($this->inputFile);
                $templateId = $outputPdf->importPage($pageNum);
                $size = $outputPdf->getTemplateSize($templateId);

                $outputPdf->AddPage($size['orientation'], $size);
                $outputPdf->useTemplate($templateId);

                $outputFile = $this->generateOutputPath('pdf', "page-{$pageNum}");
                $outputPdf->Output($outputFile, 'F');

                $splitFiles[] = [
                    'path' => $outputFile,
                    'name' => "page-{$pageNum}.pdf",
                    'page_number' => $pageNum,
                    'size' => filesize($outputFile),
                ];
            }

            $this->updateProgress(100, 'PDF split completed!');

            return $this->buildResult([
                'files' => $splitFiles,
                'original_pages' => $pageCount,
                'extracted_pages' => count($splitFiles),
                'split_mode' => $splitMode,
            ]);

        } catch (Exception $e) {
            $this->handleError(new Exception('PDF split failed: '.$e->getMessage()));
        }
    }

    private function determinePages(string $splitMode, int $pageCount, ?string $pageRange, ?string $specificPages): array
    {
        switch ($splitMode) {
            case 'all':
                return range(1, $pageCount);

            case 'range':
                if (! $pageRange) {
                    throw new Exception('Page range is required for range split mode');
                }

                return $this->parsePageRange($pageRange, $pageCount);

            case 'specific':
                if (! $specificPages) {
                    throw new Exception('Specific pages are required for specific split mode');
                }

                return $this->parseSpecificPages($specificPages, $pageCount);

            default:
                throw new Exception("Invalid split mode: {$splitMode}");
        }
    }

    private function parsePageRange(string $pageRange, int $pageCount): array
    {
        // Format: "1-5" or "1,3,5-8"
        $pages = [];
        $parts = explode(',', $pageRange);

        foreach ($parts as $part) {
            $part = mb_trim($part);

            if (mb_strpos($part, '-') !== false) {
                [$start, $end] = explode('-', $part, 2);
                $start = max(1, min((int) $start, $pageCount));
                $end = max(1, min((int) $end, $pageCount));

                if ($start <= $end) {
                    $pages = array_merge($pages, range($start, $end));
                }
            } else {
                $pageNum = max(1, min((int) $part, $pageCount));
                $pages[] = $pageNum;
            }
        }

        return array_unique($pages);
    }

    private function parseSpecificPages(string $specificPages, int $pageCount): array
    {
        // Format: "1,3,5,8"
        $pages = [];
        $parts = explode(',', $specificPages);

        foreach ($parts as $part) {
            $pageNum = (int) mb_trim($part);
            if ($pageNum >= 1 && $pageNum <= $pageCount) {
                $pages[] = $pageNum;
            }
        }

        return array_unique($pages);
    }
}
