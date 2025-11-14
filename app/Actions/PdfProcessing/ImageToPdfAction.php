<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use Exception;
use TCPDF;

final class ImageToPdfAction extends BasePdfAction
{
    public function process(): array
    {
        try {
            $this->updateProgress(10, 'Initializing image to PDF conversion...');

            $images = $this->getOption('images', [$this->inputFile]);
            $pageFormat = $this->getOption('page_format', 'A4');
            $orientation = $this->getOption('orientation', 'portrait');
            $fitToPage = $this->getOption('fit_to_page', true);

            if (! is_array($images)) {
                $images = [$images];
            }

            $this->updateProgress(20, 'Creating PDF document...');

            $pdf = new TCPDF($orientation, 'mm', $pageFormat, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('PDFClick');
            $pdf->SetTitle('Converted from Images');
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);

            $totalImages = count($images);

            foreach ($images as $index => $imagePath) {
                $this->updateProgress(
                    (int) (30 + (60 * ($index / $totalImages))),
                    'Processing image '.($index + 1)." of {$totalImages}..."
                );

                // Validate image file
                if (! file_exists($imagePath)) {
                    throw new Exception("Image file not found: {$imagePath}");
                }

                $imageInfo = getimagesize($imagePath);
                if (! $imageInfo) {
                    throw new Exception("Invalid image file: {$imagePath}");
                }

                // Add new page
                $pdf->AddPage();

                if ($fitToPage) {
                    // Fit image to page while maintaining aspect ratio
                    $pageWidth = $pdf->getPageWidth();
                    $pageHeight = $pdf->getPageHeight();

                    $imageWidth = $imageInfo[0];
                    $imageHeight = $imageInfo[1];

                    // Calculate scaling to fit page
                    $scaleX = $pageWidth / $imageWidth;
                    $scaleY = $pageHeight / $imageHeight;
                    $scale = min($scaleX, $scaleY);

                    $scaledWidth = $imageWidth * $scale;
                    $scaledHeight = $imageHeight * $scale;

                    // Center the image
                    $x = ($pageWidth - $scaledWidth) / 2;
                    $y = ($pageHeight - $scaledHeight) / 2;

                    $pdf->Image($imagePath, $x, $y, $scaledWidth, $scaledHeight);
                } else {
                    // Use original image size
                    $pdf->Image($imagePath, 0, 0);
                }
            }

            $this->updateProgress(90, 'Saving PDF file...');

            $outputFile = $this->generateOutputPath('pdf', 'converted');
            $pdf->Output($outputFile, 'F');

            $this->updateProgress(100, 'Conversion completed!');

            return $this->buildResult([
                'converted_file' => $outputFile,
                'images_processed' => $totalImages,
                'page_format' => $pageFormat,
                'orientation' => $orientation,
                'fit_to_page' => $fitToPage,
            ]);

        } catch (Exception $e) {
            $this->handleError(new Exception('Image to PDF conversion failed: '.$e->getMessage()));
        }
    }
}
