<?php

declare(strict_types=1);

use App\Actions\PdfProcessing\PdfToImageAction;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->testPdfPath = storage_path('app/test.pdf');
    $this->createTestPdf();
});

afterEach(function () {
    if (file_exists($this->testPdfPath)) {
        unlink($this->testPdfPath);
    }
});

it('converts PDF to JPG images successfully')->skip(function () {
    return ! extension_loaded('imagick');
}, 'Imagick extension not available')->group('imagick', function () {
    $action = new PdfToImageAction($this->testPdfPath, [
        'format' => 'jpg',
        'quality' => 90,
        'dpi' => 150,
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['format'])->toBe('jpg')
        ->and($result['quality'])->toBe(90)
        ->and($result['dpi'])->toBe(150)
        ->and($result['files'])->toBeArray()
        ->and($result['page_count'])->toBeInt();
});

it('converts PDF to PNG images successfully')->skip(function () {
    return ! extension_loaded('imagick');
}, 'Imagick extension not available')->group('imagick', function () {
    $action = new PdfToImageAction($this->testPdfPath, [
        'format' => 'png',
        'quality' => 100,
        'dpi' => 300,
    ]);

    $result = $action->process();

    expect($result['format'])->toBe('png')
        ->and($result['quality'])->toBe(100)
        ->and($result['dpi'])->toBe(300)
        ->and($result['success'])->toBeTrue();
});

it('uses default values when options not provided')->skip(function () {
    return ! extension_loaded('imagick');
}, 'Imagick extension not available')->group('imagick', function () {
    $action = new PdfToImageAction($this->testPdfPath);

    $result = $action->process();

    expect($result['format'])->toBe('jpg')
        ->and($result['quality'])->toBe(90)
        ->and($result['dpi'])->toBe(300);
});

it('creates correct file structure for each page')->skip(function () {
    return ! extension_loaded('imagick');
}, 'Imagick extension not available')->group('imagick', function () {
    $action = new PdfToImageAction($this->testPdfPath, [
        'format' => 'jpg',
    ]);

    $result = $action->process();

    foreach ($result['files'] as $file) {
        expect($file)->toHaveKeys([
            'path',
            'name',
            'page_number',
            'size',
        ])
            ->and($file['page_number'])->toBeInt()
            ->and($file['size'])->toBeInt()
            ->and(file_exists($file['path']))->toBeTrue();
    }
});

it('generates sequential page numbers', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }

    $action = new PdfToImageAction($this->testPdfPath);
    $result = $action->process();

    $pageNumbers = array_column($result['files'], 'page_number');
    $expectedNumbers = range(1, count($result['files']));

    expect($pageNumbers)->toEqual($expectedNumbers);
});

it('includes file size information for each image', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }

    $action = new PdfToImageAction($this->testPdfPath);
    $result = $action->process();

    foreach ($result['files'] as $file) {
        expect($file['size'])->toBeInt()
            ->and($file['size'])->toBeGreaterThan(0);
    }
});

it('throws exception when missing required extensions', function () {
    // Mock the extension check
    if (extension_loaded('imagick') || extension_loaded('gd')) {
        $this->markTestSkipped('Required extensions are available');
    }

    expect(fn () => new PdfToImageAction($this->testPdfPath))
        ->toThrow(Exception::class, 'PDF to image conversion requires either Imagick or GD extension');
});

it('throws exception for invalid PDF file', function () {
    $invalidPath = storage_path('app/invalid.pdf');
    file_put_contents($invalidPath, 'not a pdf');

    expect(fn () => new PdfToImageAction($invalidPath))
        ->toThrow(Exception::class);

    unlink($invalidPath);
});

it('includes processing time in results', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }

    $action = new PdfToImageAction($this->testPdfPath);
    $result = $action->process();

    expect($result['processing_time'])->toBeFloat()
        ->and($result['processing_time'])->toBeGreaterThan(0);
});

it('handles different DPI settings correctly', function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }

    $dpiValues = [72, 150, 300, 600];

    foreach ($dpiValues as $dpi) {
        $action = new PdfToImageAction($this->testPdfPath, ['dpi' => $dpi]);
        $result = $action->process();

        expect($result['dpi'])->toBe($dpi)
            ->and($result['success'])->toBeTrue();
    }
});

function createTestPdf(): void
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
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test PDF) Tj
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
%%EOF';

    file_put_contents(test()->testPdfPath, $pdfContent);
}
