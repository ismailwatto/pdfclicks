<?php

declare(strict_types=1);

namespace App\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickException;
use setasign\Fpdi\Fpdi;

final readonly class ImagePdfConversionAction
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp');
        $this->ensureTempDirectoryExists();
    }

    /**
     * Convert PNG images to PDF
     */
    public function convertPngToPdf(array $imageFiles): array
    {
        try {
            $sessionId = session()->getId();
            $totalImages = count($imageFiles);

            $this->updateProgress($sessionId, 10, 'Initializing PDF creation...');

            // Create new PDF
            $pdf = new Fpdi();

            foreach ($imageFiles as $index => $imageFile) {
                $this->updateProgress($sessionId, 15 + (($index / $totalImages) * 70),
                    'Processing image '.($index + 1)." of {$totalImages}...");

                // Get image dimensions
                $imageInfo = getimagesize($imageFile->getRealPath());
                if ($imageInfo === false) {
                    throw new Exception('Cannot read image file: '.$imageFile->getClientOriginalName());
                }

                $width = $imageInfo[0];
                $height = $imageInfo[1];

                // Convert pixels to points (1 inch = 72 points, assuming 96 DPI)
                $widthPoints = ($width / 96) * 72;
                $heightPoints = ($height / 96) * 72;

                // Determine orientation and fit to standard page sizes
                $orientation = $width > $height ? 'L' : 'P';

                // Add page with appropriate size
                $pdf->AddPage($orientation, [$widthPoints, $heightPoints]);

                // Add image to PDF
                $pdf->Image($imageFile->getRealPath(), 0, 0, $widthPoints, $heightPoints);
            }

            // Save PDF
            $outputFileName = 'converted-images-'.time().'.pdf';
            $outputPath = $this->tempDir.'/'.$sessionId.'_'.$outputFileName;

            $pdf->Output($outputPath, 'F');

            $this->updateProgress($sessionId, 100, 'PDF creation completed!');

            return [
                'success' => true,
                'filename' => $outputFileName,
                'file_path' => $outputPath,
                'total_images' => $totalImages,
                'size' => filesize($outputPath),
                'message' => "Successfully converted {$totalImages} images to PDF",
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to convert images to PDF: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Convert PDF to PNG images
     */
    public function convertPdfToPng(UploadedFile $pdfFile, int $quality = 150): array
    {
        try {
            if (! extension_loaded('imagick')) {
                throw new Exception('ImageMagick extension is required for PDF to PNG conversion');
            }

            $sessionId = session()->getId();
            $this->updateProgress($sessionId, 10, 'Reading PDF file...');

            // Initialize Imagick
            $imagick = new Imagick();
            $imagick->setResolution($quality, $quality);

            // Read PDF
            $imagick->readImage($pdfFile->getRealPath());
            $totalPages = $imagick->getNumberImages();

            $this->updateProgress($sessionId, 20, "Processing {$totalPages} pages...");

            $convertedFiles = [];

            // Convert each page
            $imagick = $imagick->coalesceImages();
            foreach ($imagick as $pageIndex => $page) {
                $pageNum = $pageIndex + 1;

                $this->updateProgress($sessionId, 20 + (($pageIndex / $totalPages) * 70),
                    "Converting page {$pageNum} of {$totalPages}...");

                // Set format to PNG
                $page->setImageFormat('png');
                $page->setImageCompressionQuality(90);

                // Generate filename
                $outputFileName = 'page-'.$pageNum.'.png';
                $outputPath = $this->tempDir.'/'.$sessionId.'_'.$outputFileName;

                // Save PNG
                $page->writeImage($outputPath);

                $convertedFiles[] = [
                    'filename' => $outputFileName,
                    'file_path' => $outputPath,
                    'page' => $pageNum,
                    'size' => filesize($outputPath),
                ];
            }

            $imagick->clear();
            $imagick->destroy();

            $this->updateProgress($sessionId, 100, 'PDF to PNG conversion completed!');

            return [
                'success' => true,
                'total_pages' => $totalPages,
                'converted_files' => $convertedFiles,
                'message' => "Successfully converted PDF to {$totalPages} PNG images",
            ];

        } catch (ImagickException $e) {
            throw new Exception('ImageMagick error: '.$e->getMessage(), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new Exception('Failed to convert PDF to PNG: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get PDF page count
     */
    public function getPdfPageCount(UploadedFile $file): int
    {
        try {
            if (extension_loaded('imagick')) {
                $imagick = new Imagick();
                $imagick->readImage($file->getRealPath());
                $pageCount = $imagick->getNumberImages();
                $imagick->clear();
                $imagick->destroy();

                return $pageCount;
            }
            // Fallback to FPDI
            $pdf = new Fpdi();

            return $pdf->setSourceFile($file->getRealPath());

        } catch (Exception $e) {
            throw new Exception('Unable to read PDF file: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Validate image file
     */
    public function validateImageFile(UploadedFile $file): bool
    {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $allowedTypes)) {
            throw new Exception('Only PNG and JPEG images are supported');
        }

        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new Exception('Invalid image file');
        }

        return true;
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
