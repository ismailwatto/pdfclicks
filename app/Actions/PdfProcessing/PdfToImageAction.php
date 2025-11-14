<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use App\Actions\CloudConvertAction;
use Exception;
use Illuminate\Support\Facades\Log;
use Imagick;

final class PdfToImageAction extends BasePdfAction
{
    public function process(): array
    {
        try {
            $this->updateProgress(10, 'Initializing PDF to image conversion...');

            // Try local Imagick first if available
            if (extension_loaded('imagick')) {
                try {
                    $this->updateProgress(15, 'Using local Imagick for conversion...');

                    return $this->processWithImagick();
                } catch (Exception $e) {
                    Log::warning('Local Imagick conversion failed, trying CloudConvert', [
                        'error' => $e->getMessage(),
                        'file' => $this->inputFile,
                    ]);
                    // Continue to CloudConvert fallback
                }
            }

            // Fall back to CloudConvert
            $this->updateProgress(25, 'Using CloudConvert for conversion...');

            return $this->processWithCloudConvert();

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    protected function checkDependencies(): void
    {
        parent::checkDependencies();

        // Check if we have local capability OR CloudConvert is configured
        $hasLocalCapability = extension_loaded('imagick');
        $hasCloudConvert = config('services.cloudconvert.api_key');

        if (! $hasLocalCapability && ! $hasCloudConvert) {
            throw new Exception('PDF to image conversion requires either Imagick extension or CloudConvert API. Please install Imagick or configure CloudConvert API key.');
        }
    }

    private function processWithImagick(): array
    {
        $format = $this->getOption('format', 'jpg');
        $quality = $this->getOption('quality', 90);
        $dpi = $this->getOption('dpi', 300);

        $imagick = new Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($this->inputFile);

        $pageCount = $imagick->getNumberImages();
        $convertedFiles = [];

        $this->updateProgress(20, "Processing {$pageCount} page(s) with Imagick...");

        foreach ($imagick as $pageIndex => $page) {
            $this->updateProgress(
                (int) (30 + (60 * ($pageIndex / $pageCount))),
                'Converting page '.($pageIndex + 1)." of {$pageCount}..."
            );

            $page->setImageFormat($format);
            $page->setImageCompressionQuality($quality);

            // Remove alpha channel for JPG format
            if (mb_strtolower($format) === 'jpg' || mb_strtolower($format) === 'jpeg') {
                $page->setImageBackgroundColor('white');
                $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            }

            $outputFile = $this->generateOutputPath($format, 'page-'.($pageIndex + 1));
            $page->writeImage($outputFile);

            $convertedFiles[] = [
                'path' => $outputFile,
                'name' => 'page-'.($pageIndex + 1).'.'.$format,
                'page_number' => $pageIndex + 1,
                'size' => filesize($outputFile),
            ];
        }

        $imagick->clear();
        $imagick->destroy();

        $this->updateProgress(100, 'Conversion completed with Imagick!');

        return $this->buildResult([
            'files' => $convertedFiles,
            'format' => $format,
            'page_count' => $pageCount,
            'quality' => $quality,
            'dpi' => $dpi,
            'method' => 'imagick',
        ]);
    }

    private function processWithCloudConvert(): array
    {
        $format = $this->getOption('format', 'jpg');
        $quality = $this->getOption('quality', 90);
        $dpi = $this->getOption('dpi', 300);

        $this->updateProgress(25, 'Uploading to CloudConvert...');

        try {
            $cloudConvert = new CloudConvertAction();
            $options = [
                'quality' => $quality,
                'dpi' => $dpi,
            ];

            $result = $cloudConvert->convertPdfToImages($this->inputFile, $options);
        } catch (Exception $e) {
            // If CloudConvert fails, try local fallback using FPDI + TCPDF
            Log::warning('CloudConvert failed, attempting local fallback', [
                'error' => $e->getMessage(),
                'file' => $this->inputFile,
            ]);

            if (extension_loaded('imagick')) {
                try {
                    $this->updateProgress(30, 'CloudConvert failed, using local Imagick...');

                    return $this->processWithImagick();
                } catch (Exception $imagickError) {
                    Log::warning('Local Imagick fallback also failed', [
                        'imagick_error' => $imagickError->getMessage(),
                        'cloudconvert_error' => $e->getMessage(),
                    ]);
                }
            }

            // Try alternative local approach using FPDI + basic image conversion
            try {
                $this->updateProgress(35, 'Trying alternative local conversion...');

                return $this->processWithFpdiAlternative();
            } catch (Exception $fpdiError) {
                Log::error('All conversion methods failed', [
                    'cloudconvert_error' => $e->getMessage(),
                    'fpdi_error' => $fpdiError->getMessage(),
                ]);
            }

            // If all methods fail, provide helpful error message
            throw new Exception('PDF to image conversion failed. CloudConvert error: '.$e->getMessage().'. Please ensure Imagick extension is installed for local processing.');
        }

        $this->updateProgress(90, 'Downloading converted images...');

        // Convert CloudConvert result to our format
        $convertedFiles = [];
        if (isset($result['converted_images'])) {
            foreach ($result['converted_images'] as $index => $imagePath) {
                $convertedFiles[] = [
                    'path' => $imagePath,
                    'name' => 'page-'.($index + 1).'.'.$format,
                    'page_number' => $index + 1,
                    'size' => file_exists($imagePath) ? filesize($imagePath) : 0,
                ];
            }
        }

        $this->updateProgress(100, 'Conversion completed with CloudConvert!');

        return $this->buildResult([
            'files' => $convertedFiles,
            'format' => $format,
            'page_count' => count($convertedFiles),
            'quality' => $quality,
            'dpi' => $dpi,
            'method' => 'cloudconvert',
            'job_id' => $result['job_id'] ?? null,
        ]);
    }

    private function processWithFpdiAlternative(): array
    {
        $format = $this->getOption('format', 'jpg');

        // This is a simplified fallback that doesn't create images but provides
        // a graceful failure message with instructions
        throw new Exception('Local PDF to image conversion requires Imagick extension. CloudConvert is also unavailable. Please install php-imagick extension or configure CloudConvert API.');
    }
}
