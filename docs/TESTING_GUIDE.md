# PDF Tools Testing Guide

## Overview

This guide covers the comprehensive testing strategy for all PDF tools in the PDFClick application. The testing suite uses Pest PHP testing framework and includes unit tests, feature tests, and integration tests.

## Testing Architecture

### Test Organization

```
tests/
├── Pest.php                      # Pest configuration
├── Unit/Actions/                 # Unit tests for processing actions
│   ├── PdfCompressionActionTest.php
│   ├── PdfToImageActionTest.php
│   ├── PdfToOfficeActionTest.php
│   ├── PdfSplitActionTest.php
│   └── PdfMergeActionTest.php
├── Feature/Livewire/             # Feature tests for Livewire components
│   ├── CompressPdfTest.php
│   └── PdfToExcelTest.php
└── TestCase.php                  # Base test case class
```

### Test Types

1. **Unit Tests**: Test individual action classes in isolation
2. **Feature Tests**: Test Livewire component behavior and user interactions
3. **Integration Tests**: Test complete workflows from upload to download

## Running Tests

### Basic Commands

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/Actions/PdfCompressionActionTest.php

# Run tests with coverage
./vendor/bin/pest --coverage

# Run tests in parallel
./vendor/bin/pest --parallel

# Run tests with specific group
./vendor/bin/pest --group=pdf-tools
```

### Test Filtering

```bash
# Run only unit tests
./vendor/bin/pest tests/Unit/

# Run only feature tests
./vendor/bin/pest tests/Feature/

# Run tests matching pattern
./vendor/bin/pest --filter="compression"

# Run tests excluding specific groups
./vendor/bin/pest --exclude-group=slow
```

## Unit Tests for Actions

### PdfCompressionActionTest

Tests the PDF compression functionality:

```php
// Test basic compression
it('compresses a PDF file successfully', function () {
    $action = new PdfCompressionAction($this->testPdfPath, [
        'quality_level' => 'medium',
        'remove_metadata' => false,
    ]);

    $result = $action->process();

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['compressed_file'])->toBeString()
        ->and($result['compression_ratio'])->toBeFloat();
});
```

**Key Test Cases**:
- Successful compression with different quality levels
- Metadata removal functionality
- Compression ratio calculations
- File size validation
- Error handling for invalid files

### PdfToImageActionTest

Tests PDF to image conversion:

```php
// Test JPG conversion
it('converts PDF to JPG images successfully')->skip(function () {
    return !extension_loaded('imagick');
}, 'Imagick extension not available')->group('imagick', function () {
    $action = new PdfToImageAction($this->testPdfPath, [
        'format' => 'jpg',
        'quality' => 90,
        'dpi' => 150,
    ]);

    $result = $action->process();

    expect($result['format'])->toBe('jpg')
        ->and($result['files'])->toBeArray();
});
```

**Key Test Cases**:
- JPG and PNG format conversion
- DPI and quality settings
- Multi-page PDF handling
- File structure validation
- Extension dependency checks

### PdfToOfficeActionTest

Tests PDF to Office format conversion:

```php
// Test Word conversion
it('converts PDF to Word document successfully', function () {
    $action = new PdfToOfficeAction($this->testPdfPath, [
        'output_format' => 'docx',
    ]);

    $result = $action->process();

    expect($result['output_format'])->toBe('docx')
        ->and(file_exists($result['converted_file']))->toBeTrue();
});
```

**Key Test Cases**:
- Word document creation (DOCX)
- Excel spreadsheet creation (XLSX, CSV)
- Format validation
- Table detection
- Text extraction

### PdfSplitActionTest

Tests PDF splitting functionality:

```php
// Test page range splitting
it('splits PDF by page range successfully', function () {
    $action = new PdfSplitAction($this->testPdfPath, [
        'split_mode' => 'range',
        'page_range' => '1-2',
    ]);

    $result = $action->process();

    expect($result['extracted_pages'])->toBe(2);
});
```

**Key Test Cases**:
- Split all pages
- Page range extraction
- Specific page extraction
- Invalid range handling
- File naming conventions

### PdfMergeActionTest

Tests PDF merging functionality:

```php
// Test basic merging
it('merges two PDF files successfully', function () {
    $inputFiles = [$this->testPdf1Path, $this->testPdf2Path];
    
    $action = new PdfMergeAction($inputFiles);
    $result = $action->process();

    expect($result['total_files_merged'])->toBe(2)
        ->and($result['total_pages'])->toBeInt();
});
```

**Key Test Cases**:
- Two-file merging
- Multi-file merging
- Page count preservation
- File order maintenance
- Orientation handling

## Feature Tests for Livewire Components

### CompressPdfTest

Tests the compression component interface:

```php
// Test component initialization
it('initializes with default values', function () {
    Livewire::test(CompressPdf::class)
        ->assertSet('qualityLevel', 'medium')
        ->assertSet('imageQuality', 75)
        ->assertSet('isCompressing', false);
});
```

**Key Test Cases**:
- Component rendering
- Default value initialization
- File upload validation
- Quality level changes
- Progress tracking
- Error message display

### PdfToExcelTest

Tests the PDF to Excel component:

```php
// Test file validation
it('validates uploaded PDF files correctly', function () {
    $validPdf = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');
    $invalidFile = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');

    Livewire::test(PdfToExcel::class)
        ->set('uploadedFiles', [$validPdf])
        ->assertHasNoErrors()
        ->set('uploadedFiles', [$invalidFile])
        ->assertHasErrors(['uploadedFiles.0' => 'mimes']);
});
```

**Key Test Cases**:
- Component rendering
- File validation
- Output format selection
- Conversion progress
- Result display

## Test Utilities

### PDF Creation Helper

All tests use helper functions to create valid test PDFs:

```php
function createTestPdf(): void
{
    $pdfContent = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
// ... PDF structure ...
%%EOF";

    file_put_contents(test()->testPdfPath, $pdfContent);
}
```

### Multi-Page PDF Helper

For testing splitting and page-specific operations:

```php
function createMultiPagePdf(): void
{
    // Creates a 4-page PDF for testing
    $pdfContent = "..."; // Multi-page PDF structure
    file_put_contents(test()->testPdfPath, $pdfContent);
}
```

### Storage Setup

Tests use Laravel's storage faking:

```php
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
```

## Test Data and Fixtures

### Valid Test PDFs

- **Simple PDF**: Single page with basic text
- **Multi-page PDF**: 4 pages for splitting tests
- **Landscape PDF**: Different orientation for merge tests
- **Complex PDF**: Contains tables for Office conversion tests

### Invalid Test Files

- **Non-PDF files**: Text files with PDF extension
- **Corrupted PDFs**: Invalid PDF structure
- **Empty files**: Zero-byte files
- **Oversized files**: Files exceeding size limits

## Mocking and Dependencies

### Extension Mocking

Tests handle missing PHP extensions gracefully:

```php
it('throws exception when missing required extensions', function () {
    if (extension_loaded('imagick') || extension_loaded('gd')) {
        $this->markTestSkipped('Required extensions are available');
    }

    expect(fn() => new PdfToImageAction($this->testPdfPath))
        ->toThrow(Exception::class);
});
```

### Service Mocking

For external dependencies:

```php
// Mock CloudConvert service
$this->mock(CloudConvertService::class, function ($mock) {
    $mock->shouldReceive('convert')
         ->andReturn(['success' => true, 'file' => 'converted.docx']);
});
```

## Performance Testing

### Memory Usage Tests

```php
it('handles large files without memory exhaustion', function () {
    $initialMemory = memory_get_usage();
    
    $action = new PdfCompressionAction($this->largePdfPath);
    $result = $action->process();
    
    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;
    
    expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024); // 50MB limit
});
```

### Processing Time Tests

```php
it('completes processing within acceptable time limits', function () {
    $startTime = microtime(true);
    
    $action = new PdfCompressionAction($this->testPdfPath);
    $result = $action->process();
    
    $processingTime = microtime(true) - $startTime;
    
    expect($processingTime)->toBeLessThan(10); // 10 seconds limit
});
```

## Error Testing

### Exception Handling

```php
it('throws exception for invalid PDF file', function () {
    $invalidPath = storage_path('app/invalid.pdf');
    file_put_contents($invalidPath, 'not a pdf');

    expect(fn() => new PdfCompressionAction($invalidPath))
        ->toThrow(Exception::class);

    unlink($invalidPath);
});
```

### Error Recovery

```php
it('recovers gracefully from processing errors', function () {
    $corruptedPdf = $this->createCorruptedPdf();
    
    $action = new PdfCompressionAction($corruptedPdf);
    
    expect(fn() => $action->process())
        ->toThrow(Exception::class)
        ->and(file_exists($corruptedPdf))->toBeFalse(); // Cleanup occurred
});
```

## Test Coverage

### Coverage Requirements

- **Minimum Coverage**: 90% overall
- **Critical Paths**: 100% coverage for error handling
- **Edge Cases**: All boundary conditions tested

### Running Coverage Reports

```bash
# Generate HTML coverage report
./vendor/bin/pest --coverage-html=coverage

# Generate text coverage report
./vendor/bin/pest --coverage-text

# Generate Clover XML for CI
./vendor/bin/pest --coverage-clover=coverage.xml
```

## Continuous Integration

### GitHub Actions Configuration

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.2, 8.3]
        dependency-version: [prefer-lowest, prefer-stable]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: imagick, gd, fileinfo
          
      - name: Install dependencies
        run: composer install --${{ matrix.dependency-version }}
        
      - name: Run tests
        run: ./vendor/bin/pest --coverage
```

### Test Database

```php
// Use in-memory SQLite for fast tests
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
],
```

## Best Practices

### Test Organization

1. **Group related tests**: Use `describe()` blocks for logical grouping
2. **Clear test names**: Use descriptive `it()` statements
3. **Setup and teardown**: Use `beforeEach()` and `afterEach()` consistently
4. **Data providers**: Use datasets for parameterized tests

### Assertion Patterns

```php
// Fluent assertions
expect($result)->toBeArray()
    ->and($result['success'])->toBeTrue()
    ->and($result['file'])->toBeString();

// Custom expectations
expect()->extend('toBeValidPdf', function () {
    return $this->toMatch('/^%PDF-/');
});
```

### Test Data Management

1. **Minimal test data**: Use smallest possible valid files
2. **Realistic scenarios**: Include edge cases and real-world data
3. **Cleanup**: Always clean up temporary files
4. **Isolation**: Each test should be independent

## Debugging Tests

### Debug Output

```php
// Enable debug mode for specific tests
it('debugs compression process', function () {
    config(['app.debug' => true]);
    
    $action = new PdfCompressionAction($this->testPdfPath);
    $result = $action->process();
    
    dump($result); // Will show in test output
});
```

### Log Inspection

```php
// Check for specific log entries
it('logs processing steps', function () {
    Log::spy();
    
    $action = new PdfCompressionAction($this->testPdfPath);
    $action->process();
    
    Log::shouldHaveReceived('info')
       ->with('PDF compression starting', Mockery::any());
});
```

### Failed Test Investigation

```bash
# Run single failing test with verbose output
./vendor/bin/pest tests/Unit/Actions/PdfCompressionActionTest.php::it_compresses_a_pdf_file_successfully --verbose

# Stop on first failure
./vendor/bin/pest --stop-on-failure

# Show detailed assertion failures
./vendor/bin/pest --verbose
```

## Maintenance

### Updating Tests

1. **New features**: Add tests before implementing features
2. **Bug fixes**: Add regression tests for fixed bugs
3. **Refactoring**: Update tests to match new implementations
4. **Dependencies**: Update tests when upgrading dependencies

### Test Review Checklist

- [ ] All new code is tested
- [ ] Edge cases are covered
- [ ] Error conditions are tested
- [ ] Performance considerations are included
- [ ] Dependencies are properly mocked
- [ ] Cleanup is performed correctly
- [ ] Test names are descriptive
- [ ] Documentation is updated

---

*This testing guide should be followed for all new features and maintained alongside the codebase.*