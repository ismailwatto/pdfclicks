<?php

declare(strict_types=1);

use App\Actions\PdfProcessing\PdfToOfficeAction;
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

it('converts PDF to Word document successfully', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'docx',
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['output_format'])->toBe('docx')
        ->and($result['converted_file'])->toBeString()
        ->and($result['paragraphs_extracted'])->toBeInt();

    expect(file_exists($result['converted_file']))->toBeTrue();
});

it('converts PDF to Excel document successfully', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'xlsx',
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['output_format'])->toBe('xlsx')
        ->and($result['converted_file'])->toBeString()
        ->and($result['tables_found'])->toBeInt();

    expect(file_exists($result['converted_file']))->toBeTrue();
});

it('converts PDF to CSV document successfully', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'csv',
    ]);

    $result = $action->process();

    expect($result['output_format'])->toBe('csv')
        ->and($result['success'])->toBeTrue()
        ->and(file_exists($result['converted_file']))->toBeTrue();
});

it('uses default format when not specified', function () {
    $action = new PdfToOfficeAction($this->testPdfPath);

    $result = $action->process();

    expect($result['output_format'])->toBe('docx')
        ->and($result['success'])->toBeTrue();
});

it('throws exception for unsupported format', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'unsupported',
    ]);

    expect(fn () => $action->process())
        ->toThrow(Exception::class, 'Unsupported output format: unsupported');
});

it('handles doc format as Word document', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'doc',
    ]);

    $result = $action->process();

    expect($result['output_format'])->toBe('docx') // Should convert to docx internally
        ->and($result['success'])->toBeTrue();
});

it('includes processing time in results', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'docx',
    ]);

    $result = $action->process();

    expect($result['processing_time'])->toBeFloat()
        ->and($result['processing_time'])->toBeGreaterThan(0);
});

it('includes original file information in results', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'docx',
    ]);

    $result = $action->process();

    expect($result)->toHaveKeys([
        'original_file',
        'original_size',
        'processing_time',
    ]);
});

it('creates different output files for different formats', function () {
    $docxAction = new PdfToOfficeAction($this->testPdfPath, ['output_format' => 'docx']);
    $xlsxAction = new PdfToOfficeAction($this->testPdfPath, ['output_format' => 'xlsx']);

    $docxResult = $docxAction->process();
    $xlsxResult = $xlsxAction->process();

    expect($docxResult['converted_file'])->not()->toBe($xlsxResult['converted_file'])
        ->and(pathinfo($docxResult['converted_file'], PATHINFO_EXTENSION))->toBe('docx')
        ->and(pathinfo($xlsxResult['converted_file'], PATHINFO_EXTENSION))->toBe('xlsx');
});

it('handles empty PDF gracefully', function () {
    $emptyPdfPath = storage_path('app/empty.pdf');
    $this->createEmptyPdf($emptyPdfPath);

    $action = new PdfToOfficeAction($emptyPdfPath, [
        'output_format' => 'docx',
    ]);

    $result = $action->process();

    expect($result['success'])->toBeTrue()
        ->and(file_exists($result['converted_file']))->toBeTrue();

    unlink($emptyPdfPath);
});

it('throws exception for invalid PDF file', function () {
    $invalidPath = storage_path('app/invalid.pdf');
    file_put_contents($invalidPath, 'not a pdf');

    expect(fn () => new PdfToOfficeAction($invalidPath, ['output_format' => 'docx']))
        ->toThrow(Exception::class);

    unlink($invalidPath);
});

it('detects tables in structured content', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'xlsx',
    ]);

    $result = $action->process();

    expect($result['tables_found'])->toBeInt()
        ->and($result['tables_found'])->toBeGreaterThanOrEqual(0);
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
/Length 100
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test PDF Content) Tj
0 -20 Td
(Name    Age    City) Tj
0 -20 Td
(John    25     NYC) Tj
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
501
%%EOF';

    file_put_contents(test()->testPdfPath, $pdfContent);
}

function createEmptyPdf(string $path): void
{
    $emptyPdfContent = '%PDF-1.4
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
>>
endobj

xref
0 4
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
trailer
<<
/Size 4
/Root 1 0 R
>>
startxref
184
%%EOF';

    file_put_contents($path, $emptyPdfContent);
}
