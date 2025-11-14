<?php

declare(strict_types=1);

use App\Actions\PdfProcessing\PdfCompressionAction;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    // Create a simple test PDF file for testing
    $this->testPdfPath = storage_path('app/test.pdf');
    $this->createTestPdf();
});

afterEach(function () {
    // Clean up test files
    if (file_exists($this->testPdfPath)) {
        unlink($this->testPdfPath);
    }
});

it('compresses a PDF file successfully', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'medium',
        'remove_metadata' => false,
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['compressed_file'])->toBeString()
        ->and($result['compression_ratio'])->toBeFloat()
        ->and($result['quality_level'])->toBe('medium')
        ->and($result['metadata_removed'])->toBeFalse();

    expect(file_exists($result['compressed_file']))->toBeTrue();
});

it('compresses PDF with high quality level', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'high',
        'remove_metadata' => false,
    ]);

    $result = $action->process();

    expect($result['quality_level'])->toBe('high')
        ->and($result['success'])->toBeTrue();
});

it('compresses PDF with maximum compression', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'maximum',
        'remove_metadata' => true,
    ]);

    $result = $action->process();

    expect($result['quality_level'])->toBe('maximum')
        ->and($result['metadata_removed'])->toBeTrue()
        ->and($result['success'])->toBeTrue();
});

it('removes metadata when requested', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'medium',
        'remove_metadata' => true,
    ]);

    $result = $action->process();

    expect($result['metadata_removed'])->toBeTrue();
});

it('calculates compression ratio correctly', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'medium',
    ]);

    $result = $action->process();

    expect($result['compression_ratio'])->toBeFloat()
        ->and($result['compression_ratio'])->toBeGreaterThanOrEqual(0)
        ->and($result['compression_ratio'])->toBeLessThanOrEqual(100);
});

it('includes file size information in results', function () {
    $action = new PdfCompressionAction($this->testPdfPath);

    $result = $action->process();

    expect($result)->toHaveKeys([
        'original_size',
        'compressed_size',
        'size_reduction',
        'original_size_formatted',
        'compressed_size_formatted',
        'size_reduction_formatted',
    ]);
});

it('throws exception for invalid PDF file', function () {
    $invalidPath = storage_path('app/invalid.pdf');
    file_put_contents($invalidPath, 'not a pdf');

    expect(fn () => new PdfCompressionAction($invalidPath))
        ->toThrow(Exception::class);

    unlink($invalidPath);
});

it('throws exception for non-existent file', function () {
    expect(fn () => new PdfCompressionAction('/non/existent/file.pdf'))
        ->toThrow(Exception::class);
});

it('includes processing time in results', function () {
    $action = new PdfCompressionAction($this->testPdfPath);

    $result = $action->process();

    expect($result['processing_time'])->toBeFloat()
        ->and($result['processing_time'])->toBeGreaterThan(0);
});

function createTestPdf(): void
{
    // Create a minimal valid PDF for testing
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
