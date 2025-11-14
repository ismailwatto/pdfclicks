# PDF Tools Documentation

## Overview

This documentation covers all PDF processing tools available in the PDFClick application. The tools are built using Laravel Livewire components and PHP processing actions, providing a comprehensive suite for PDF manipulation.

## Architecture

### Components Structure

```
app/
├── Actions/PdfProcessing/          # Backend processing logic
│   ├── BasePdfAction.php          # Base class for all PDF actions
│   ├── PdfCompressionAction.php   # PDF compression functionality
│   ├── PdfToImageAction.php       # PDF to image conversion
│   ├── PdfToOfficeAction.php      # PDF to Office formats
│   ├── PdfSplitAction.php         # PDF splitting functionality
│   ├── PdfMergeAction.php         # PDF merging functionality
│   └── ImageToPdfAction.php       # Image to PDF conversion
├── Livewire/Tools/                # Frontend Livewire components
│   ├── CompressPdf.php            # PDF compression interface
│   ├── PdfToExcel.php             # PDF to Excel conversion
│   ├── PdfToWord.php              # PDF to Word conversion
│   ├── PdfToJpg.php               # PDF to JPG conversion
│   ├── PdfToPng.php               # PDF to PNG conversion
│   ├── SplitPdf.php               # PDF splitting interface
│   ├── MergePdf.php               # PDF merging interface
│   └── [Other conversion tools]   # Additional conversion tools
└── Traits/                        # Shared functionality
    ├── FileValidation.php         # File validation utilities
    ├── PdfToolsBase.php           # Base PDF tools functionality
    └── TemporaryFileManagement.php # Temporary file handling
```

## Available Tools

### 1. PDF Compression (`CompressPdf`)

**Purpose**: Reduces PDF file size while maintaining quality.

**Features**:
- Multiple quality levels (high, medium, maximum)
- Adjustable image quality (10-100%)
- Metadata removal option
- Optimization for different use cases (web, print, general)
- Batch processing (up to 10 files)

**Usage Example**:
```php
$action = new PdfCompressionAction($inputFile, [
    'quality_level' => 'medium',
    'image_quality' => 75,
    'remove_metadata' => true,
    'optimize_for' => 'web'
]);

$result = $action->process();
```

**Input**: PDF files (max 100MB each)
**Output**: Compressed PDF files
**Dependencies**: FPDI library

---

### 2. PDF to Image Conversion (`PdfToImageAction`)

**Purpose**: Converts PDF pages to image formats.

**Features**:
- Support for JPG and PNG formats
- Adjustable DPI (72-600)
- Quality settings (10-100)
- Page-by-page conversion
- Automatic alpha channel handling

**Usage Example**:
```php
$action = new PdfToImageAction($inputFile, [
    'format' => 'jpg',
    'quality' => 90,
    'dpi' => 300
]);

$result = $action->process();
```

**Input**: PDF files
**Output**: Image files (JPG/PNG)
**Dependencies**: Imagick extension

---

### 3. PDF to Office Conversion (`PdfToOfficeAction`)

**Purpose**: Converts PDF documents to Microsoft Office formats.

**Features**:
- Word document output (DOCX)
- Excel spreadsheet output (XLSX/CSV)
- Table detection and extraction
- Text paragraph preservation
- Fallback text extraction

**Usage Example**:
```php
$action = new PdfToOfficeAction($inputFile, [
    'output_format' => 'docx'
]);

$result = $action->process();
```

**Input**: PDF files
**Output**: Word documents, Excel spreadsheets, CSV files
**Dependencies**: PhpOffice/PhpWord, PhpOffice/PhpSpreadsheet

---

### 4. PDF Splitting (`PdfSplitAction`)

**Purpose**: Splits PDF documents into individual pages or ranges.

**Features**:
- Split all pages individually
- Extract specific page ranges (e.g., "1-5", "1,3,5-8")
- Extract specific pages (e.g., "1,3,5")
- Page validation and error handling

**Usage Example**:
```php
$action = new PdfSplitAction($inputFile, [
    'split_mode' => 'range',
    'page_range' => '1-5'
]);

$result = $action->process();
```

**Split Modes**:
- `all`: Extract all pages
- `range`: Extract page ranges
- `specific`: Extract specific pages

**Input**: PDF files
**Output**: Individual PDF files for each page/range
**Dependencies**: FPDI library

---

### 5. PDF Merging (`PdfMergeAction`)

**Purpose**: Combines multiple PDF documents into a single file.

**Features**:
- Merge unlimited PDF files
- Preserve page orientations
- Maintain page order
- Progress tracking
- File size reporting

**Usage Example**:
```php
$action = new PdfMergeAction($inputFiles, [
    'output_name' => 'merged_document'
]);

$result = $action->process();
```

**Input**: Array of PDF file paths (minimum 2 files)
**Output**: Single merged PDF file
**Dependencies**: FPDI library

---

## Base Classes and Traits

### BasePdfAction

The foundation class for all PDF processing actions.

**Key Features**:
- Input file validation
- Temporary file management
- Progress tracking
- Error handling
- Common dependency checks

**Methods**:
- `process()`: Abstract method implemented by child classes
- `validateInput()`: Validates input files
- `buildResult()`: Standardizes result format
- `handleError()`: Centralized error handling

### PdfToolsBase Trait

Shared functionality for Livewire components.

**Features**:
- File upload handling
- Progress tracking
- Message management
- Download functionality
- ZIP creation for multiple files

### FileValidation Trait

Comprehensive file validation utilities.

**Validations**:
- File existence checks
- MIME type validation
- File size limits
- PDF structure validation

### TemporaryFileManagement Trait

Handles temporary file operations.

**Features**:
- Secure temporary file creation
- Automatic cleanup
- Path generation
- Storage management

## Error Handling

### Common Error Types

1. **File Validation Errors**:
   - Invalid file format
   - File size exceeds limit
   - Corrupted PDF structure

2. **Processing Errors**:
   - Missing dependencies
   - Insufficient memory
   - Disk space issues

3. **Configuration Errors**:
   - Invalid parameters
   - Missing required options

### Error Response Format

```php
[
    'success' => false,
    'error' => 'Error message',
    'error_code' => 'ERROR_CODE',
    'debug_info' => [] // Only in debug mode
]
```

## Testing

### Test Structure

```
tests/
├── Unit/Actions/                   # Unit tests for processing actions
│   ├── PdfCompressionActionTest.php
│   ├── PdfToImageActionTest.php
│   ├── PdfToOfficeActionTest.php
│   ├── PdfSplitActionTest.php
│   └── PdfMergeActionTest.php
└── Feature/Livewire/              # Feature tests for Livewire components
    ├── CompressPdfTest.php
    └── PdfToExcelTest.php
```

### Running Tests

```bash
# Run all PDF tool tests
./vendor/bin/pest --group=pdf-tools

# Run specific test file
./vendor/bin/pest tests/Unit/Actions/PdfCompressionActionTest.php

# Run with coverage
./vendor/bin/pest --coverage
```

### Test Categories

1. **Unit Tests**: Test individual action classes
2. **Feature Tests**: Test Livewire component interactions
3. **Integration Tests**: Test end-to-end workflows

## Dependencies

### Required PHP Extensions

- `fileinfo`: File type detection
- `imagick`: Image processing (for PDF to image conversion)
- `gd`: Alternative image processing
- `zip`: Archive creation

### Composer Packages

- `setasign/fpdi`: PDF manipulation
- `phpoffice/phpword`: Word document creation
- `phpoffice/phpspreadsheet`: Excel/CSV creation
- `laravel/framework`: Core framework
- `livewire/livewire`: Frontend components

### Optional Dependencies

- `pdftotext`: Advanced text extraction (Linux/Mac)
- `ghostscript`: Advanced PDF processing

## Configuration

### File Limits

```php
// Maximum file sizes (configurable)
'pdf_max_size' => 100 * 1024 * 1024, // 100MB
'image_max_size' => 50 * 1024 * 1024, // 50MB
'max_files_per_batch' => 10,
```

### Processing Options

```php
// Compression quality levels
'compression_levels' => [
    'high' => ['quality' => 90, 'optimization' => 'minimal'],
    'medium' => ['quality' => 75, 'optimization' => 'balanced'],
    'maximum' => ['quality' => 50, 'optimization' => 'aggressive']
],

// Image conversion settings
'image_formats' => ['jpg', 'png'],
'dpi_options' => [72, 150, 300, 600],
```

## Security Considerations

### File Upload Security

- MIME type validation
- File extension verification
- Content-based validation
- Size limitations
- Temporary file isolation

### Processing Security

- Input sanitization
- Path traversal prevention
- Memory limit enforcement
- Execution time limits

### Output Security

- Secure file naming
- Directory isolation
- Access control
- Automatic cleanup

## Performance Optimization

### Memory Management

- Stream processing for large files
- Chunked file reading
- Automatic garbage collection
- Memory limit monitoring

### Processing Optimization

- Parallel processing support
- Progress tracking
- Incremental updates
- Resource cleanup

### Caching

- Temporary file caching
- Result caching (optional)
- Dependency caching

## Troubleshooting

### Common Issues

1. **Memory Exhaustion**:
   - Increase PHP memory limit
   - Process files in smaller batches
   - Enable streaming mode

2. **Missing Extensions**:
   - Install required PHP extensions
   - Check extension configuration
   - Verify system dependencies

3. **File Permission Issues**:
   - Check storage directory permissions
   - Verify web server user permissions
   - Configure temporary directory access

4. **Processing Timeouts**:
   - Increase PHP execution time limit
   - Use queue processing for large files
   - Implement background processing

### Debug Mode

Enable debug logging in `.env`:

```env
LOG_LEVEL=debug
PDF_TOOLS_DEBUG=true
```

### Log Locations

- Application logs: `storage/logs/laravel.log`
- PDF processing logs: `storage/logs/pdf-processing.log`
- Error logs: `storage/logs/error.log`

## API Reference

### Action Classes

Each action class implements the following interface:

```php
interface PdfActionInterface
{
    public function __construct(string $inputFile, array $options = []);
    public function process(): array;
}
```

### Result Format

All actions return results in this standardized format:

```php
[
    'success' => bool,
    'original_file' => string,
    'original_size' => int,
    'processing_time' => float,
    // Tool-specific results...
]
```

### Progress Tracking

Progress updates are provided through the `ProgressTracking` trait:

```php
$this->updateProgress(50, 'Processing page 1 of 2...');
```

## Contributing

### Adding New Tools

1. Create action class extending `BasePdfAction`
2. Implement required abstract methods
3. Add Livewire component
4. Create comprehensive tests
5. Update documentation

### Code Standards

- Follow PSR-12 coding standards
- Use strict type declarations
- Implement comprehensive error handling
- Add proper documentation comments
- Include unit and feature tests

### Testing Requirements

- Minimum 90% code coverage
- Test all error conditions
- Include integration tests
- Performance benchmarks for large files

---

*This documentation is maintained alongside the codebase and should be updated when adding new features or modifying existing functionality.*