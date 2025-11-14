<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Actions\PdfProcessing\PdfToImageAction;
use App\Traits\PdfToolsBase;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

final class PdfToPng extends Component
{
    use PdfToolsBase, WithFileUploads;

    public $uploadedPdf;

    public $imageQuality = 150;

    public $isProcessing = false;

    public $processingProgress = 0;

    public $processingStatus = '';

    public $successMessage = '';

    public $errorMessage = '';

    public $convertedImages = [];

    public $pdfPageCount = 0;

    public array $rules = [
        'uploadedPdf' => 'required|file|mimes:pdf|max:51200',
        'imageQuality' => 'required|integer|min:72|max:300',
    ];

    public array $messages = [
        'uploadedPdf.required' => 'Please select a PDF file.',
        'uploadedPdf.file' => 'The uploaded file must be a valid file.',
        'uploadedPdf.mimes' => 'Only PDF files are allowed.',
        'uploadedPdf.max' => 'The PDF file must be less than 50MB.',
        'imageQuality.required' => 'Please select image quality.',
        'imageQuality.integer' => 'Image quality must be a number.',
        'imageQuality.min' => 'Image quality must be at least 72 DPI.',
        'imageQuality.max' => 'Image quality cannot exceed 300 DPI.',
    ];

    public function mount(): void
    {
        $this->resetConverter();
    }

    public function updatedUploadedPdf(): void
    {
        $this->validate([
            'uploadedPdf' => 'required|file|mimes:pdf|max:51200',
        ]);

        if ($this->uploadedPdf) {
            try {
                // Store temporarily to get page count
                $tempPath = $this->uploadedPdf->store('temp', 'local');
                $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

                if (file_exists($fullPath)) {
                    // Use FPDI to get page count
                    $pdf = new \setasign\Fpdi\Fpdi();
                    $this->pdfPageCount = $pdf->setSourceFile($fullPath);

                    // Clean up temp file
                    unlink($fullPath);
                }
            } catch (Exception $e) {
                $this->errorMessage = 'Error reading PDF: '.$e->getMessage();
                $this->uploadedPdf = null;
            }
        }
    }

    public function convertToPng(): void
    {
        $this->validate();

        if (! $this->uploadedPdf) {
            $this->errorMessage = 'Please select a PDF file.';

            return;
        }

        $this->isProcessing = true;
        $this->processingProgress = 0;
        $this->processingStatus = 'Initializing conversion...';
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            // Store the uploaded file temporarily
            $tempPath = $this->uploadedPdf->store('temp', 'local');
            $fullPath = storage_path('app/private').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $tempPath);

            if (! file_exists($fullPath)) {
                throw new Exception("Failed to store uploaded file at: {$fullPath}");
            }

            $options = [
                'format' => 'png',
                'quality' => 100, // PNG is lossless, so we use max quality
                'dpi' => $this->imageQuality, // Use imageQuality as DPI for PNG
            ];

            $action = new PdfToImageAction($fullPath, $options);
            $result = $action->process();

            // Convert result to expected format for the Blade template
            $this->convertedImages = [];
            if (isset($result['files']) && is_array($result['files'])) {
                foreach ($result['files'] as $file) {
                    $this->convertedImages[] = [
                        'file_path' => $file['path'],
                        'filename' => $file['name'],
                        'page' => $file['page_number'] ?? 1,
                        'size' => $file['size'] ?? (file_exists($file['path']) ? filesize($file['path']) : 0),
                    ];
                }
            }

            $imageCount = count($this->convertedImages);
            $method = $result['method'] ?? 'local';
            $methodText = $method === 'cloudconvert' ? ' (via CloudConvert)' : ' (locally)';

            $this->successMessage = "PDF successfully converted to {$imageCount} PNG image".
                                   ($imageCount === 1 ? '' : 's').$methodText.'!';
            $this->processingProgress = 100;
            $this->processingStatus = 'Conversion completed successfully!';

        } catch (Exception $e) {
            $this->errorMessage = 'Error: '.$e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function downloadImage($filePath, $fileName)
    {
        if (! file_exists($filePath)) {
            $this->errorMessage = 'Image file not found. Please convert again.';

            return null;
        }

        return response()->download($filePath, $fileName);
    }

    public function downloadAllImages()
    {
        if (empty($this->convertedImages)) {
            $this->errorMessage = 'No images available for download.';

            return null;
        }

        $zip = new ZipArchive();
        $zipFileName = 'converted-images-'.time().'.zip';
        $zipPath = storage_path('app/temp/'.session()->getId().'_'.$zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            foreach ($this->convertedImages as $image) {
                if (file_exists($image['file_path'])) {
                    $zip->addFile($image['file_path'], $image['filename']);
                }
            }
            $zip->close();

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend();
        }

        $this->errorMessage = 'Failed to create zip file.';

        return null;
    }

    public function resetConverter(): void
    {
        $this->uploadedPdf = null;
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStatus = '';
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->convertedImages = [];
        $this->pdfPageCount = 0;
        $this->imageQuality = 150;
    }

    public function getProgress(): void
    {
        $sessionId = session()->getId();
        $progressFile = storage_path('app/temp/progress_'.$sessionId.'.json');

        if (file_exists($progressFile)) {
            $progressData = json_decode(file_get_contents($progressFile), true);
            $this->processingProgress = $progressData['progress'] ?? 0;
            $this->processingStatus = $progressData['status'] ?? '';
        }
    }

    public function render()
    {
        return view('livewire.tools.pdf-to-png');
    }
}
