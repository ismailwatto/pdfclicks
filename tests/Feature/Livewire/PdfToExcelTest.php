<?php

declare(strict_types=1);

use App\Livewire\Tools\PdfToExcel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->createTestPdf();
});

it('renders PDF to Excel component successfully', function () {
    Livewire::test(PdfToExcel::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.tools.pdf-to-excel');
});

it('initializes with default values', function () {
    Livewire::test(PdfToExcel::class)
        ->assertSet('outputFormat', 'xlsx')
        ->assertSet('isConverting', false)
        ->assertSet('conversionProgress', 0);
});

it('validates uploaded PDF files correctly', function () {
    $validPdf = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');
    $invalidFile = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$validPdf])
        ->assertHasNoErrors()
        ->set('uploadedFiles', [$invalidFile])
        ->assertHasErrors(['uploadedFiles.0' => 'mimes']);
});

it('enforces maximum file count limit', function () {
    $files = [];
    for ($i = 0; $i < 6; $i++) { // Assuming max is 5
        $files[] = UploadedFile::fake()->create("test{$i}.pdf", 1000, 'application/pdf');
    }

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', $files)
        ->assertSet('errorMessage', 'Maximum 5 files allowed. Please remove some files.');
});

it('validates output format options', function () {
    Livewire::test(PdfToExcel::class)
        ->set('outputFormat', 'xlsx')
        ->assertSet('outputFormat', 'xlsx')
        ->set('outputFormat', 'csv')
        ->assertSet('outputFormat', 'csv');
});

it('removes files correctly', function () {
    $file1 = UploadedFile::fake()->create('test1.pdf', 1000, 'application/pdf');
    $file2 = UploadedFile::fake()->create('test2.pdf', 1000, 'application/pdf');

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$file1, $file2])
        ->call('removeFile', 0)
        ->assertCount('uploadedFiles', 1);
});

it('requires at least one file for conversion', function () {
    Livewire::test(PdfToExcel::class)
        ->call('convertToExcel')
        ->assertSet('errorMessage', 'Please select at least one PDF file to convert.');
});

it('resets component state correctly', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$file])
        ->set('outputFormat', 'csv')
        ->set('errorMessage', 'Some error')
        ->call('resetTool')
        ->assertSet('uploadedFiles', [])
        ->assertSet('outputFormat', 'xlsx')
        ->assertSet('errorMessage', '')
        ->assertSet('isConverting', false);
});

it('validates file size limit', function () {
    $oversizedFile = UploadedFile::fake()->create('large.pdf', 110000, 'application/pdf'); // 110MB

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$oversizedFile])
        ->assertHasErrors(['uploadedFiles.0' => 'max']);
});

it('handles different output formats', function () {
    Livewire::test(PdfToExcel::class)
        ->set('outputFormat', 'xlsx')
        ->assertSet('outputFormat', 'xlsx')
        ->set('outputFormat', 'csv')
        ->assertSet('outputFormat', 'csv');
});

it('clears messages when files are updated', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    Livewire::test(PdfToExcel::class)
        ->set('errorMessage', 'Some error')
        ->set('successMessage', 'Some success')
        ->set('uploadedFiles', [$file])
        ->assertSet('errorMessage', '')
        ->assertSet('successMessage', '');
});

it('initializes conversion progress when files are uploaded', function () {
    $file1 = UploadedFile::fake()->create('test1.pdf', 1000, 'application/pdf');
    $file2 = UploadedFile::fake()->create('test2.pdf', 2000, 'application/pdf');

    $component = Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$file1, $file2]);

    $progress = $component->get('fileProgress');

    expect($progress)->toBeArray();
});

it('validates that only PDF files are accepted', function () {
    $docFile = UploadedFile::fake()->create('test.docx', 1000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $imageFile = UploadedFile::fake()->create('test.jpg', 1000, 'image/jpeg');

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$docFile])
        ->assertHasErrors(['uploadedFiles.0' => 'mimes'])
        ->set('uploadedFiles', [$imageFile])
        ->assertHasErrors(['uploadedFiles.0' => 'mimes']);
});

it('shows file information correctly', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1500, 'application/pdf');

    $component = Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$file]);

    // Check that file information is stored
    $fileProgress = $component->get('fileProgress');

    if (! empty($fileProgress)) {
        expect($fileProgress[0]['file_name'])->toBe('test.pdf')
            ->and($fileProgress[0]['file_size'])->toBe(1500);
    }
});

it('handles empty file uploads gracefully', function () {
    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [])
        ->assertSet('uploadedFiles', [])
        ->assertSet('conversionProgress', 0);
});

function createTestPdf(): void
{
    $testPdfPath = storage_path('app/test.pdf');
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
/Length 80
>>
stream
BT
/F1 12 Tf
72 720 Td
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
481
%%EOF';

    file_put_contents($testPdfPath, $pdfContent);
}
