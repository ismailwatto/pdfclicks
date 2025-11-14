<?php

declare(strict_types=1);

use App\Livewire\Tools\CompressPdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->createTestPdf();
});

it('renders compress PDF component successfully', function () {
    Livewire::test(CompressPdf::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.tools.compress-pdf');
});

it('initializes with default values', function () {
    Livewire::test(CompressPdf::class)
        ->assertSet('qualityLevel', 'medium')
        ->assertSet('imageQuality', 75)
        ->assertSet('removeMetadata', false)
        ->assertSet('optimizeFor', 'general')
        ->assertSet('isCompressing', false)
        ->assertSet('overallProgress', 0);
});

it('validates uploaded files correctly', function () {
    $validPdf = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');
    $invalidFile = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');

    Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$validPdf])
        ->assertHasNoErrors()
        ->set('uploadedFiles', [$invalidFile])
        ->assertHasErrors(['uploadedFiles.0' => 'mimes']);
});

it('enforces maximum file count limit', function () {
    $files = [];
    for ($i = 0; $i < 12; $i++) {
        $files[] = UploadedFile::fake()->create("test{$i}.pdf", 1000, 'application/pdf');
    }

    Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', $files)
        ->assertSet('errorMessage', 'Maximum 10 files allowed. Please remove some files.');
});

it('validates file size limit', function () {
    $oversizedFile = UploadedFile::fake()->create('large.pdf', 110000, 'application/pdf'); // 110MB

    Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$oversizedFile])
        ->assertHasErrors(['uploadedFiles.0' => 'max']);
});

it('updates image quality when quality level changes', function () {
    Livewire::test(CompressPdf::class)
        ->set('qualityLevel', 'high')
        ->assertSet('imageQuality', 90)
        ->set('qualityLevel', 'maximum')
        ->assertSet('imageQuality', 50);
});

it('validates quality level options', function () {
    Livewire::test(CompressPdf::class)
        ->set('qualityLevel', 'invalid')
        ->assertHasErrors(['qualityLevel' => 'in']);
});

it('validates image quality range', function () {
    Livewire::test(CompressPdf::class)
        ->set('imageQuality', 5)
        ->assertHasErrors(['imageQuality' => 'min'])
        ->set('imageQuality', 150)
        ->assertHasErrors(['imageQuality' => 'max']);
});

it('removes files correctly', function () {
    $file1 = UploadedFile::fake()->create('test1.pdf', 1000, 'application/pdf');
    $file2 = UploadedFile::fake()->create('test2.pdf', 1000, 'application/pdf');

    Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$file1, $file2])
        ->call('removeFile', 0)
        ->assertCount('uploadedFiles', 1);
});

it('requires at least one file for compression', function () {
    Livewire::test(CompressPdf::class)
        ->call('compressPdfs')
        ->assertSet('errorMessage', 'Please select at least one PDF file to compress.');
});

it('resets component state correctly', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$file])
        ->set('qualityLevel', 'high')
        ->set('errorMessage', 'Some error')
        ->call('resetCompressor')
        ->assertSet('uploadedFiles', [])
        ->assertSet('qualityLevel', 'medium')
        ->assertSet('errorMessage', '')
        ->assertSet('isCompressing', false);
});

it('shows compression progress correctly', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    $component = Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$file]);

    // Check that progress is initialized when files are uploaded
    expect($component->get('compressionProgress'))->toBeArray();
});

it('handles different optimization settings', function () {
    Livewire::test(CompressPdf::class)
        ->set('optimizeFor', 'web')
        ->assertSet('optimizeFor', 'web')
        ->set('optimizeFor', 'print')
        ->assertSet('optimizeFor', 'print')
        ->set('optimizeFor', 'general')
        ->assertSet('optimizeFor', 'general');
});

it('validates optimize for options', function () {
    Livewire::test(CompressPdf::class)
        ->set('optimizeFor', 'invalid')
        ->assertHasErrors(['optimizeFor' => 'in']);
});

it('handles metadata removal option', function () {
    Livewire::test(CompressPdf::class)
        ->set('removeMetadata', true)
        ->assertSet('removeMetadata', true)
        ->set('removeMetadata', false)
        ->assertSet('removeMetadata', false);
});

it('clears messages when files are updated', function () {
    $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');

    Livewire::test(CompressPdf::class)
        ->set('errorMessage', 'Some error')
        ->set('successMessage', 'Some success')
        ->set('uploadedFiles', [$file])
        ->assertSet('errorMessage', '')
        ->assertSet('successMessage', '');
});

it('initializes progress when files are uploaded', function () {
    $file1 = UploadedFile::fake()->create('test1.pdf', 1000, 'application/pdf');
    $file2 = UploadedFile::fake()->create('test2.pdf', 2000, 'application/pdf');

    $component = Livewire::test(CompressPdf::class)
        ->set('uploadedFiles', [$file1, $file2]);

    $progress = $component->get('compressionProgress');

    expect($progress)->toHaveCount(2)
        ->and($progress[0]['progress'])->toBe(0)
        ->and($progress[0]['status'])->toBe('Ready')
        ->and($progress[0]['file_name'])->toBe('test1.pdf')
        ->and($progress[0]['file_size'])->toBe(1000);
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

    file_put_contents($testPdfPath, $pdfContent);
}
