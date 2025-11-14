<?php

declare(strict_types=1);

use App\Actions\PdfProcessing\PdfMergeAction;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->testPdf1Path = storage_path('app/test1.pdf');
    $this->testPdf2Path = storage_path('app/test2.pdf');
    $this->testPdf3Path = storage_path('app/test3.pdf');

    $this->createTestPdf($this->testPdf1Path, 'Test PDF 1');
    $this->createTestPdf($this->testPdf2Path, 'Test PDF 2');
    $this->createTestPdf($this->testPdf3Path, 'Test PDF 3');
});

afterEach(function () {
    $files = [$this->testPdf1Path, $this->testPdf2Path, $this->testPdf3Path];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

it('merges two PDF files successfully', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['merged_file'])->toBeString()
        ->and($result['total_files_merged'])->toBe(2)
        ->and($result['total_pages'])->toBeInt()
        ->and($result['output_size'])->toBeInt()
        ->and($result['input_files'])->toBeArray();

    expect(file_exists($result['merged_file']))->toBeTrue();
});

it('merges multiple PDF files successfully', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path, $this->testPdf3Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result['total_files_merged'])->toBe(3)
        ->and($result['success'])->toBeTrue()
        ->and(count($result['input_files']))->toBe(3);
});

it('throws exception when less than 2 files provided', function () {
    $inputFiles = [$this->testPdf1Path];

    $action = new PdfMergeAction($inputFiles);

    expect(fn () => $action->process())
        ->toThrow(Exception::class, 'At least 2 PDF files are required for merging');
});

it('includes processing time in results', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result['processing_time'])->toBeFloat()
        ->and($result['processing_time'])->toBeGreaterThan(0);
});

it('includes input file information in results', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result)->toHaveKeys([
        'input_files',
        'total_input_size',
        'processing_time',
    ])
        ->and($result['input_files'])->toBeArray()
        ->and($result['total_input_size'])->toBeInt()
        ->and($result['total_input_size'])->toBeGreaterThan(0);
});

it('preserves page count from all input files', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    // Each test PDF has 1 page, so total should be 2
    expect($result['total_pages'])->toBe(2);
});

it('creates output file with valid PDF format', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    $outputFile = $result['merged_file'];

    expect(file_exists($outputFile))->toBeTrue()
        ->and(pathinfo($outputFile, PATHINFO_EXTENSION))->toBe('pdf')
        ->and(filesize($outputFile))->toBeGreaterThan(0);

    // Check if it's a valid PDF by reading the header
    $content = file_get_contents($outputFile, false, null, 0, 5);
    expect($content)->toBe('%PDF-');
});

it('throws exception for invalid PDF file', function () {
    $invalidPath = storage_path('app/invalid.pdf');
    file_put_contents($invalidPath, 'not a pdf');

    $inputFiles = [$this->testPdf1Path, $invalidPath];

    expect(fn () => new PdfMergeAction($inputFiles))
        ->toThrow(Exception::class, 'Invalid PDF file');

    unlink($invalidPath);
});

it('throws exception for non-existent file', function () {
    $inputFiles = [$this->testPdf1Path, '/non/existent/file.pdf'];

    expect(fn () => new PdfMergeAction($inputFiles))
        ->toThrow(Exception::class);
});

it('handles files with different orientations', function () {
    // Create a landscape PDF
    $landscapePdfPath = storage_path('app/landscape.pdf');
    $this->createLandscapePdf($landscapePdfPath);

    $inputFiles = [$this->testPdf1Path, $landscapePdfPath];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result['success'])->toBeTrue()
        ->and($result['total_pages'])->toBe(2);

    unlink($landscapePdfPath);
});

it('maintains input file order in result', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path, $this->testPdf3Path];

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    $expectedNames = ['test1.pdf', 'test2.pdf', 'test3.pdf'];
    expect($result['input_files'])->toBe($expectedNames);
});

it('calculates total input size correctly', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];

    $file1Size = filesize($this->testPdf1Path);
    $file2Size = filesize($this->testPdf2Path);
    $expectedTotalSize = $file1Size + $file2Size;

    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result['total_input_size'])->toBe($expectedTotalSize);
});

function createTestPdf(string $path, string $content): void
{
    $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 4 0 R
>>
>>
/Contents 5 0 R
>>
endobj

4 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

5 0 obj
<<
/Length '.(mb_strlen($content) + 20)."
>>
stream
BT
/F1 12 Tf
72 720 Td
({$content}) Tj
ET
endstream
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000273 00000 n 
0000000351 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
445
%%EOF";

    file_put_contents($path, $pdfContent);
}

function createLandscapePdf(string $path): void
{
    $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 792 612]
/Resources <<
/Font <<
/F1 4 0 R
>>
>>
/Contents 5 0 R
>>
endobj

4 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

5 0 obj
<<
/Length 54
>>
stream
BT
/F1 12 Tf
72 500 Td
(Landscape PDF) Tj
ET
endstream
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000273 00000 n 
0000000351 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
455
%%EOF';

    file_put_contents($path, $pdfContent);
}
