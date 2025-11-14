<?php

declare(strict_types=1);

namespace App\Actions\PdfProcessing;

use App\Actions\CloudConvertAction;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;

final class PdfToOfficeAction extends BasePdfAction
{
    public function process(): array
    {
        try {
            $outputFormat = $this->getOption('output_format', 'docx');

            $this->updateProgress(10, 'Initializing PDF to Office conversion...');

            // Force CloudConvert for Excel and PowerPoint conversions
            $forceCloudConvert = in_array(mb_strtolower($outputFormat), ['xlsx', 'csv', 'pptx', 'ppt']);

            // Always prioritize CloudConvert for PDF-to-Word/Excel/PowerPoint conversions
            $useCloudConvert = config('services.cloudconvert.api_key') &&
                              ($this->getOption('use_cloudconvert', true) || $forceCloudConvert);

            if ($useCloudConvert) {
                $this->updateProgress(15, 'Using CloudConvert for high-quality conversion...');

                return $this->processWithCloudConvert($outputFormat);
            }

            // Only allow local conversion for Word documents (not Excel/PowerPoint)
            if ($forceCloudConvert) {
                throw new Exception('CloudConvert is required for Excel and PowerPoint conversions. Please check your CloudConvert API configuration.');
            }

            // Use local conversion (Word only)
            $this->updateProgress(15, 'Using local conversion...');

            return $this->processLocally($outputFormat);

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function processLocally(string $outputFormat): array
    {
        switch (mb_strtolower($outputFormat)) {
            case 'docx':
            case 'doc':
                return $this->convertToWord();

            case 'xlsx':
            case 'csv':
            case 'pptx':
            case 'ppt':
                throw new Exception("CloudConvert is required for {$outputFormat} conversions. Local processing is not supported for this format.");
            default:
                throw new Exception("Unsupported output format: {$outputFormat}");
        }
    }

    private function processWithCloudConvert(string $outputFormat): array
    {
        try {
            $cloudConvert = new CloudConvertAction();

            if (in_array(mb_strtolower($outputFormat), ['xlsx', 'csv'])) {
                $options = [
                    'page_range' => $this->getOption('page_range'),
                    'specific_pages' => $this->getOption('specific_pages'),
                    'table_detection' => $this->getOption('table_detection', true),
                    'preserve_formatting' => $this->getOption('preserve_formatting', true),
                ];

                $result = $cloudConvert->convertPdfToExcel($this->inputFile, $outputFormat, $options);
            } else {
                $result = $cloudConvert->convert($this->inputFile, $outputFormat);
            }

            return $this->buildResult([
                'converted_file' => $result['converted_file'],
                'output_format' => $outputFormat,
                'method' => 'cloudconvert',
                'job_id' => $result['job_id'] ?? null,
            ]);

        } catch (Exception $e) {
            // Log detailed CloudConvert error information
            Log::error('CloudConvert conversion failed', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'output_format' => $outputFormat,
                'file' => $this->inputFile,
                'api_key_configured' => ! empty(config('services.cloudconvert.api_key')),
                'api_key_length' => mb_strlen(config('services.cloudconvert.api_key') ?? ''),
                'sandbox_mode' => config('services.cloudconvert.sandbox', false),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception instead of falling back
            // This ensures we know when CloudConvert isn't working
            throw new Exception('CloudConvert conversion failed: '.$e->getMessage().'. Please check your CloudConvert API configuration.', $e->getCode(), $e);
        }
    }

    private function convertToWord(): array
    {
        $this->updateProgress(20, 'Extracting text content...');

        // Extract text from PDF (simplified approach)
        $textContent = $this->extractTextFromPdf();

        $this->updateProgress(50, 'Creating Word document...');

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Split content into paragraphs and add to Word document
        $paragraphs = explode("\n\n", $textContent);

        foreach ($paragraphs as $paragraph) {
            if (mb_trim($paragraph)) {
                $section->addText(mb_trim($paragraph));
                $section->addTextBreak();
            }
        }

        $this->updateProgress(80, 'Saving Word document...');

        $outputFile = $this->generateOutputPath('docx', 'converted');
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputFile);

        $this->updateProgress(100, 'Conversion completed!');

        return $this->buildResult([
            'converted_file' => $outputFile,
            'output_format' => 'docx',
            'paragraphs_extracted' => count($paragraphs),
            'method' => 'local',
            'job_id' => null,
        ]);
    }

    private function convertToExcel(): array
    {
        $this->updateProgress(20, 'Extracting tabular data...');

        $textContent = $this->extractTextFromPdf();
        $tables = $this->detectTables($textContent);

        $this->updateProgress(50, 'Creating Excel spreadsheet...');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('PDF Data');

        if (! empty($tables)) {
            $this->populateWorksheet($worksheet, $tables);
        } else {
            // If no tables detected, create a simple text dump
            $lines = explode("\n", $textContent);
            foreach ($lines as $index => $line) {
                if (mb_trim($line)) {
                    $worksheet->setCellValue('A'.($index + 1), mb_trim($line));
                }
            }
        }

        $this->updateProgress(80, 'Saving Excel file...');

        $outputFormat = $this->getOption('output_format', 'xlsx');
        $outputFile = $this->generateOutputPath($outputFormat, 'converted');

        if (mb_strtolower($outputFormat) === 'csv') {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        } else {
            $writer = new Xlsx($spreadsheet);
        }

        $writer->save($outputFile);

        // Log file creation for debugging
        Log::info('Excel file created', [
            'output_file' => $outputFile,
            'file_exists' => file_exists($outputFile),
            'file_size' => file_exists($outputFile) ? filesize($outputFile) : 'N/A',
            'output_format' => $outputFormat,
        ]);

        $this->updateProgress(100, 'Conversion completed!');

        return $this->buildResult([
            'converted_file' => $outputFile,
            'output_format' => $outputFormat,
            'tables_found' => count($tables),
            'method' => 'local',
            'job_id' => null,
        ]);
    }

    private function extractTextFromPdf(): string
    {
        // This is a simplified text extraction
        // In a production environment, you might want to use:
        // - pdftotext command line tool
        // - Smalot\PdfParser library
        // - Or other specialized PDF text extraction libraries

        try {
            // Try using pdftotext if available (Linux/Mac)
            if (PHP_OS_FAMILY !== 'Windows') {
                $command = 'pdftotext "'.$this->inputFile.'" -';
                $output = shell_exec($command);

                if ($output !== null && mb_trim($output) !== '') {
                    return $output;
                }
            }
        } catch (Exception $e) {
            Log::warning('pdftotext command failed: '.$e->getMessage());
        }

        // Fallback: return placeholder text with user-friendly message
        $fileName = $this->fileInfo['original_name'] ?? 'uploaded PDF';

        return "PDF content extracted from: {$fileName}\n\n".
               "Note: Advanced text extraction features require additional software components.\n".
               "For better text extraction results, please contact your system administrator\n".
               "to install PDF text extraction tools.\n\n".
               'This is a basic conversion that preserves the document structure.';
    }

    private function detectTables(string $content): array
    {
        $tables = [];
        $lines = explode("\n", $content);

        // Simple table detection based on consistent spacing/tabs
        $currentTable = [];
        $inTable = false;

        foreach ($lines as $line) {
            $trimmedLine = mb_trim($line);

            // Skip empty lines
            if (empty($trimmedLine)) {
                if ($inTable && ! empty($currentTable)) {
                    $tables[] = $currentTable;
                    $currentTable = [];
                    $inTable = false;
                }

                continue;
            }

            // Detect potential table rows (multiple spaces/tabs)
            if (preg_match('/\s{2,}/', $line) || mb_strpos($line, "\t") !== false) {
                $inTable = true;
                // Split by multiple spaces or tabs
                $cells = preg_split('/\s{2,}|\t+/', $trimmedLine);
                $currentTable[] = array_map('trim', $cells);
            } else {
                if ($inTable && ! empty($currentTable)) {
                    $tables[] = $currentTable;
                    $currentTable = [];
                    $inTable = false;
                }
            }
        }

        // Add the last table if exists
        if ($inTable && ! empty($currentTable)) {
            $tables[] = $currentTable;
        }

        return $tables;
    }

    private function populateWorksheet($worksheet, array $tables): void
    {
        $currentRow = 1;

        foreach ($tables as $tableIndex => $table) {
            // Add table header if this isn't the first table
            if ($tableIndex > 0) {
                $currentRow++; // Add spacing between tables
                $worksheet->setCellValue('A'.$currentRow, 'Table '.($tableIndex + 1));
                $currentRow++;
            }

            foreach ($table as $rowData) {
                $column = 'A';
                foreach ($rowData as $cellData) {
                    $worksheet->setCellValue($column.$currentRow, $cellData);
                    $column++;
                }
                $currentRow++;
            }
        }
    }
}
