<?php

declare(strict_types=1);

use App\Actions\PdfProcessing\PdfSplitAction;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->testPdfPath = storage_path('app/test.pdf');
    $this->createMultiPagePdf();
});

afterEach(function () {
    if (file_exists($this->testPdfPath)) {
        unlink($this->testPdfPath);
    }
});

it('splits PDF into all pages successfully', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'all',
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['split_mode'])->toBe('all')
        ->and($result['files'])->toBeArray()
        ->and($result['original_pages'])->toBeInt()
        ->and($result['extracted_pages'])->toBeInt();

    expect($result['extracted_pages'])->toBe($result['original_pages']);

    foreach ($result['files'] as $file) {
        expect(file_exists($file['path']))->toBeTrue();
    }
});

it('splits PDF by page range successfully', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'range',
        'page_range' => '1-2',
    ]);

    $result = $action->process();

    expect($result['split_mode'])->toBe('range')
        ->and($result['extracted_pages'])->toBe(2)
        ->and($result['success'])->toBeTrue();

    $pageNumbers = array_column($result['files'], 'page_number');
    expect($pageNumbers)->toContain(1)
        ->and($pageNumbers)->toContain(2);
});

it('splits PDF by specific pages successfully', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'specific',
        'specific_pages' => '1,3',
    ]);

    $result = $action->process();

    expect($result['split_mode'])->toBe('specific')
        ->and($result['extracted_pages'])->toBe(2)
        ->and($result['success'])->toBeTrue();

    $pageNumbers = array_column($result['files'], 'page_number');
    expect($pageNumbers)->toContain(1)
        ->and($pageNumbers)->toContain(3)
        ->and($pageNumbers)->not()->toContain(2);
});

it('parses complex page ranges correctly', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'range',
        'page_range' => '1,3-4',
    ]);

    $result = $action->process();

    $pageNumbers = array_column($result['files'], 'page_number');
    expect($pageNumbers)->toContain(1)
        ->and($pageNumbers)->toContain(3)
        ->and($pageNumbers)->toContain(4)
        ->and($pageNumbers)->not()->toContain(2);
});

it('handles out-of-bounds page numbers gracefully', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'specific',
        'specific_pages' => '1,2,10,20', // 10 and 20 are out of bounds
    ]);

    $result = $action->process();

    expect($result['success'])->toBeTrue();

    $pageNumbers = array_column($result['files'], 'page_number');
    expect($pageNumbers)->toContain(1)
        ->and($pageNumbers)->toContain(2)
        ->and($pageNumbers)->not()->toContain(10)
        ->and($pageNumbers)->not()->toContain(20);
});

it('throws exception for invalid split mode', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'invalid',
    ]);

    expect(fn () => $action->process())
        ->toThrow(Exception::class, 'Invalid split mode: invalid');
});

it('throws exception when page range is missing for range mode', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'range',
    ]);

    expect(fn () => $action->process())
        ->toThrow(Exception::class, 'Page range is required for range split mode');
});

it('throws exception when specific pages are missing for specific mode', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'specific',
    ]);

    expect(fn () => $action->process())
        ->toThrow(Exception::class, 'Specific pages are required for specific split mode');
});

it('includes file size information for each split file', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'all',
    ]);

    $result = $action->process();

    foreach ($result['files'] as $file) {
        expect($file)->toHaveKeys([
            'path',
            'name',
            'page_number',
            'size',
        ])
            ->and($file['size'])->toBeInt()
            ->and($file['size'])->toBeGreaterThan(0);
    }
});

it('generates correct file names for split pages', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'specific',
        'specific_pages' => '1,3',
    ]);

    $result = $action->process();

    foreach ($result['files'] as $file) {
        $expectedName = "page-{$file['page_number']}.pdf";
        expect($file['name'])->toBe($expectedName);
    }
});

it('includes processing time in results', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'all',
    ]);

    $result = $action->process();

    expect($result['processing_time'])->toBeFloat()
        ->and($result['processing_time'])->toBeGreaterThan(0);
});

it('removes duplicate pages from specific pages list', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'specific',
        'specific_pages' => '1,1,2,2,1',
    ]);

    $result = $action->process();

    $pageNumbers = array_column($result['files'], 'page_number');
    $uniquePages = array_unique($pageNumbers);

    expect(count($pageNumbers))->toBe(count($uniquePages))
        ->and($pageNumbers)->toContain(1)
        ->and($pageNumbers)->toContain(2);
});

function createMultiPagePdf(): void
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
/Kids [3 0 R 4 0 R 5 0 R 6 0 R]
/Count 4
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 7 0 R
>>
>>
/Contents 8 0 R
>>
endobj

4 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 7 0 R
>>
>>
/Contents 9 0 R
>>
endobj

5 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 7 0 R
>>
>>
/Contents 10 0 R
>>
endobj

6 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Resources <<
/Font <<
/F1 7 0 R
>>
>>
/Contents 11 0 R
>>
endobj

7 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

8 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Page 1) Tj
ET
endstream
endobj

9 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Page 2) Tj
ET
endstream
endobj

10 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Page 3) Tj
ET
endstream
endobj

11 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Page 4) Tj
ET
endstream
endobj

xref
0 12
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000130 00000 n 
0000000288 00000 n 
0000000446 00000 n 
0000000604 00000 n 
0000000762 00000 n 
0000000840 00000 n 
0000000934 00000 n 
0000001028 00000 n 
0000001123 00000 n 
trailer
<<
/Size 12
/Root 1 0 R
>>
startxref
1218
%%EOF';

    file_put_contents(test()->testPdfPath, $pdfContent);
}
