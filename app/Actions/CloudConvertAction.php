<?php

declare(strict_types=1);

namespace App\Actions;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class CloudConvertAction
{
    private CloudConvert $cloudConvert;

    private string $apiKey;

    private bool $sandbox;

    public function __construct()
    {
        $this->apiKey = config('services.cloudconvert.api_key');
        $this->sandbox = config('services.cloudconvert.sandbox', false);

        if (empty($this->apiKey)) {
            throw new Exception('CloudConvert API key is not configured. Please set CLOUDCONVERT_API_KEY in your environment variables.');
        }

        $this->cloudConvert = new CloudConvert([
            'api_key' => $this->apiKey,
            'sandbox' => $this->sandbox,
        ]);

        // Log configuration for debugging
        Log::info('CloudConvert initialized', [
            'sandbox_mode' => $this->sandbox,
            'api_key_length' => mb_strlen($this->apiKey),
            'api_key_prefix' => mb_substr($this->apiKey, 0, 8).'...',
        ]);

        // Log successful initialization
        Log::debug('CloudConvert initialized', [
            'sandbox_mode' => $this->sandbox,
            'api_key_configured' => ! empty($this->apiKey),
        ]);
    }

    public function convert(string|UploadedFile $inputFile, string $outputFormat): array
    {
        $fileInfo = null;
        $tempFile = null;

        try {
            // Prepare input file
            $fileInfo = $this->prepareInputFile($inputFile);
            $tempFile = $fileInfo['temp_file'] ?? null;

            Log::info('Starting CloudConvert conversion', [
                'original_file' => $fileInfo['original_name'],
                'input_format' => $fileInfo['format'],
                'output_format' => $outputFormat,
                'file_size' => $fileInfo['size'],
            ]);

            // Create and execute job
            $job = $this->createConversionJob($fileInfo['path'], $outputFormat);

            Log::info('Waiting for job completion', ['job_id' => $job->getId()]);
            $this->waitForJobCompletion($job);

            Log::info('Job completed, downloading result', ['job_id' => $job->getId()]);
            $convertedFile = $this->downloadConvertedFile($job, $outputFormat);

            // Cleanup temp files
            $this->cleanupTempFiles($tempFile);

            $result = [
                'success' => true,
                'original_file' => $fileInfo['original_name'],
                'converted_file' => $convertedFile,
                'job_id' => $job->getId(),
                'input_format' => $fileInfo['format'],
                'output_format' => $outputFormat,
                'file_size' => file_exists($convertedFile) ? filesize($convertedFile) : 0,
            ];

            Log::info('CloudConvert conversion completed successfully', $result);

            return $result;

        } catch (Exception $e) {
            // Cleanup on error
            if ($tempFile) {
                $this->cleanupTempFiles($tempFile);
            }

            Log::error('CloudConvert conversion failed', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'original_file' => $fileInfo['original_name'] ?? 'unknown',
                'output_format' => $outputFormat,
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('CloudConvert conversion failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function mergePdfs(array $pdfFiles): array
    {
        if (count($pdfFiles) < 2) {
            throw new Exception('At least 2 PDF files are required for merging.');
        }

        $tempFiles = [];

        try {
            // Prepare all input files
            $preparedFiles = [];
            foreach ($pdfFiles as $file) {
                $fileInfo = $this->prepareInputFile($file);
                $preparedFiles[] = $fileInfo;
                if (isset($fileInfo['temp_file'])) {
                    $tempFiles[] = $fileInfo['temp_file'];
                }
            }

            // Create merge job
            $job = $this->createMergeJob($preparedFiles);
            $this->waitForJobCompletion($job);
            $mergedFile = $this->downloadConvertedFile($job, 'pdf');

            // Cleanup temp files
            foreach ($tempFiles as $tempFile) {
                $this->cleanupTempFiles($tempFile);
            }

            return [
                'success' => true,
                'merged_file' => $mergedFile,
                'job_id' => $job->getId(),
                'input_files_count' => count($pdfFiles),
                'output_format' => 'pdf',
            ];

        } catch (Exception $e) {
            // Cleanup temp files on error
            foreach ($tempFiles as $tempFile) {
                $this->cleanupTempFiles($tempFile);
            }
            throw new Exception('PDF merge failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function splitPdf(string|UploadedFile $pdfFile, array $options = []): array
    {
        try {
            $fileInfo = $this->prepareInputFile($pdfFile);

            // Default split options
            $splitOptions = array_merge([
                'split_mode' => 'pages', // 'pages', 'page_ranges', 'fixed_size'
                'pages' => null, // For specific pages: '1,3,5-10'
                'page_ranges' => null, // For ranges: [[1,3], [5,10]]
                'fixed_size' => null, // For fixed size chunks
            ], $options);

            $job = $this->createSplitJob($fileInfo['path'], $splitOptions);
            $this->waitForJobCompletion($job);
            $splitFiles = $this->downloadSplitFiles($job);
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

            return [
                'success' => true,
                'original_file' => $fileInfo['original_name'] ?? $pdfFile,
                'split_files' => $splitFiles,
                'job_id' => $job->getId(),
                'split_count' => count($splitFiles),
            ];

        } catch (Exception $e) {
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);
            throw new Exception('PDF split failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getJobStatus(string $jobId): array
    {
        try {
            $job = $this->cloudConvert->jobs()->get($jobId);

            return [
                'id' => $job->getId(),
                'status' => $job->getStatus(),
                'created_at' => $job->getCreatedAt(),
                'started_at' => $job->getStartedAt(),
                'ended_at' => $job->getEndedAt(),
                'tasks' => $job->getTasks()->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function convertPdfToImages(UploadedFile|string $inputFile, array $options): array
    {
        $fileInfo = $this->prepareInputFile($inputFile);

        $job = (new Job())
            ->addTask((new Task('import/upload', 'import-pdf')))
            ->addTask(
                (new Task('convert', 'convert-pdf-to-jpg'))
                    ->set('input', 'import-pdf')
                    ->set('output_format', 'jpg')
                    ->set('engine', 'office')
                    ->set('quality', $options['quality'] ?? 90)
                    ->set('pages', 'all')
                    ->set('pdf_dpi', $options['dpi'] ?? 300)
            )
            ->addTask(
                (new Task('export/url', 'export-images'))
                    ->set('input', 'convert-pdf-to-jpg')
            );

        $job = $this->cloudConvert->jobs()->create($job);

        // Upload file to import task
        $importTask = $job->getTasks()->whereName('import-pdf')[0] ?? null;
        if (! $importTask) {
            throw new Exception('Import task not found.');
        }

        $this->cloudConvert->tasks()->upload($importTask, fopen($fileInfo['path'], 'rb'));

        $this->waitForJobCompletion($job);

        // Download all exported JPG images
        $exportTask = $job->getTasks()->whereName('export-images')[0] ?? null;
        if (! $exportTask) {
            throw new Exception('Export task not found.');
        }

        $files = $exportTask->getResult()->files;
        $downloadedFiles = [];

        foreach ($files as $index => $file) {
            $fileName = Str::uuid().'-page-'.($index + 1).'.jpg';
            $outputPath = storage_path('app/public/converted/'.$fileName);

            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $fileContent = file_get_contents($file->url);
            if ($fileContent === false) {
                throw new Exception('Failed to download image.');
            }

            file_put_contents($outputPath, $fileContent);
            $downloadedFiles[] = $outputPath;
        }

        $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

        return [
            'success' => true,
            'converted_images' => $downloadedFiles,
            'job_id' => $job->getId(),
        ];
    }

    public function convertImagesToPdf(array $imageFiles): array
    {
        if ($imageFiles === []) {
            throw new Exception('At least one image file is required for conversion.');
        }

        $tempFiles = [];

        try {
            // Prepare each image file
            $preparedFiles = [];
            foreach ($imageFiles as $index => $file) {
                $fileInfo = $this->prepareInputFile($file);
                $preparedFiles[] = $fileInfo;
                if (isset($fileInfo['temp_file'])) {
                    $tempFiles[] = $fileInfo['temp_file'];
                }
            }

            // Build CloudConvert job
            $job = new Job();

            // Import tasks
            foreach ($preparedFiles as $index => $fileInfo) {
                $job->addTask(new Task('import/upload', "import-image-{$index}"));
            }

            // Convert to PDF (merge all into one)
            $job->addTask(
                (new Task('convert', 'convert-images'))
                    ->set('input', array_map(fn ($i): string => "import-image-{$i}", array_keys($preparedFiles)))
                    ->set('output_format', 'pdf')
                    ->set('engine', 'imagemagick')
                    ->set('page_size', 'a4')
                    ->set('fit', 'max')
            );

            // Export task
            $job->addTask(
                (new Task('export/url', 'export-pdf'))
                    ->set('input', 'convert-images')
            );

            $job = $this->cloudConvert->jobs()->create($job);

            // Upload each image file
            foreach ($preparedFiles as $index => $fileInfo) {
                $importTask = $job->getTasks()->whereName("import-image-{$index}")[0] ?? null;
                if (! $importTask) {
                    throw new Exception("Import task for image {$index} not found.");
                }

                $this->cloudConvert->tasks()->upload($importTask, fopen($fileInfo['path'], 'rb'));
            }

            // Wait for job to complete
            $this->waitForJobCompletion($job);

            // Download result
            $exportTask = $job->getTasks()->whereName('export-pdf')[0] ?? null;
            if (! $exportTask) {
                throw new Exception('Export task not found.');
            }

            $fileUrl = $exportTask->getResult()->files[0]->url;
            $fileName = Str::uuid().'.pdf';
            $outputPath = storage_path('app/public/converted/'.$fileName);

            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $fileContent = file_get_contents($fileUrl);
            if ($fileContent === false) {
                throw new Exception('Failed to download converted PDF.');
            }

            file_put_contents($outputPath, $fileContent);

            // Clean up temp files
            foreach ($tempFiles as $temp) {
                $this->cleanupTempFiles($temp);
            }

            return [
                'success' => true,
                'converted_file' => $outputPath,
                'job_id' => $job->getId(),
            ];

        } catch (Exception $e) {
            foreach ($tempFiles as $temp) {
                $this->cleanupTempFiles($temp);
            }
            throw new Exception('Image to PDF conversion failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function splitPdfIntoIndividualPages(string|UploadedFile $pdfFile): array
    {
        try {
            $fileInfo = $this->prepareInputFile($pdfFile);

            // Create and execute split job
            $job = $this->createIndividualPagesSplitJob($fileInfo['path']);
            $this->waitForJobCompletion($job);
            $splitFiles = $this->downloadSplitFiles($job);
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

            return [
                'success' => true,
                'original_file' => $fileInfo['original_name'] ?? basename($pdfFile),
                'split_files' => $splitFiles,
                'job_id' => $job->getId(),
                'total_pages' => count($splitFiles),
                'message' => 'PDF successfully split into '.count($splitFiles).' individual pages',
            ];

        } catch (Exception $e) {
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);
            throw new Exception('PDF split into pages failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function convertPdfToExcel(UploadedFile|string $inputFile, string $outputFormat, array $options = []): array
    {
        try {
            $fileInfo = $this->prepareInputFile($inputFile);
            $job = $this->createPdfToExcelJob($fileInfo['path'], $outputFormat, $options);
            $this->waitForJobCompletion($job);

            if ($options['separate_files'] ?? false) {
                $convertedFiles = $this->downloadMultipleFiles($job, $outputFormat);
                $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

                return [
                    'success' => true,
                    'original_file' => $fileInfo['original_name'] ?? basename($inputFile),
                    'converted_files' => $convertedFiles,
                    'job_id' => $job->getId(),
                    'output_format' => $outputFormat,
                ];
            }
            $convertedFile = $this->downloadConvertedFile($job, $outputFormat);
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

            return [
                'success' => true,
                'original_file' => $fileInfo['original_name'] ?? basename($inputFile),
                'converted_file' => $convertedFile,
                'job_id' => $job->getId(),
                'output_format' => $outputFormat,
            ];

        } catch (Exception $e) {
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);
            throw new Exception('PDF to Excel conversion failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function compressPdf(UploadedFile|string $inputFile, array $options = []): array
    {
        try {
            $fileInfo = $this->prepareInputFile($inputFile);
            $job = $this->createCompressionJob($fileInfo['path'], $options);
            $this->waitForJobCompletion($job);
            $compressedFile = $this->downloadConvertedFile($job, 'pdf');
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);

            // Get file sizes for compression stats
            $originalSize = filesize($fileInfo['path']);
            $compressedSize = filesize($compressedFile);
            $compressionRatio = round((($originalSize - $compressedSize) / $originalSize) * 100, 1);

            return [
                'success' => true,
                'original_file' => $fileInfo['original_name'] ?? basename($inputFile),
                'compressed_file' => $compressedFile,
                'job_id' => $job->getId(),
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $compressionRatio,
                'size_reduction' => $originalSize - $compressedSize,
            ];

        } catch (Exception $e) {
            $this->cleanupTempFiles($fileInfo['temp_file'] ?? null);
            throw new Exception('PDF compression failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function detectInputFormat(string $filePath): ?string
    {
        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Map file extensions to CloudConvert format names
        $formatMap = [
            'pdf' => 'pdf',
            'doc' => 'doc',
            'docx' => 'docx',
            'xls' => 'xls',
            'xlsx' => 'xlsx',
            'ppt' => 'ppt',
            'pptx' => 'pptx',
            'jpg' => 'jpg',
            'jpeg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'bmp' => 'bmp',
            'tiff' => 'tiff',
            'txt' => 'txt',
            'rtf' => 'rtf',
            'odt' => 'odt',
            'ods' => 'ods',
            'odp' => 'odp',
        ];

        return $formatMap[$extension] ?? null;
    }

    public function validateConversion(string $inputFormat, string $outputFormat): void
    {
        // Define supported conversions
        $supportedConversions = [
            'pdf' => ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'txt', 'rtf', 'jpg', 'png', 'gif'],
            'docx' => ['pdf', 'doc', 'txt', 'rtf', 'odt'],
            'doc' => ['pdf', 'docx', 'txt', 'rtf', 'odt'],
            'xlsx' => ['pdf', 'xls', 'csv', 'ods'],
            'xls' => ['pdf', 'xlsx', 'csv', 'ods'],
            'pptx' => ['pdf', 'ppt', 'odp'],
            'ppt' => ['pdf', 'pptx', 'odp'],
            'jpg' => ['pdf', 'png', 'gif', 'bmp', 'tiff'],
            'png' => ['pdf', 'jpg', 'gif', 'bmp', 'tiff'],
            'gif' => ['pdf', 'jpg', 'png', 'bmp', 'tiff'],
        ];

        if (! isset($supportedConversions[$inputFormat])) {
            throw new Exception("Input format '{$inputFormat}' is not supported by CloudConvert");
        }

        if (! in_array($outputFormat, $supportedConversions[$inputFormat])) {
            throw new Exception("Conversion from '{$inputFormat}' to '{$outputFormat}' is not supported by CloudConvert");
        }

        Log::info('Conversion validation passed', [
            'input_format' => $inputFormat,
            'output_format' => $outputFormat,
        ]);
    }

    public function getSupportedFormats(): array
    {
        return [
            'input' => ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'jpg', 'png', 'gif'],
            'output' => ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'txt', 'rtf', 'jpg', 'png', 'gif'],
            'conversions' => [
                'pdf' => ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'txt', 'rtf', 'jpg', 'png', 'gif'],
                'docx' => ['pdf', 'doc', 'txt', 'rtf'],
                'doc' => ['pdf', 'docx', 'txt', 'rtf'],
                'xlsx' => ['pdf', 'xls', 'csv'],
                'xls' => ['pdf', 'xlsx', 'csv'],
                'pptx' => ['pdf', 'ppt'],
                'ppt' => ['pdf', 'pptx'],
                'jpg' => ['pdf', 'png', 'gif'],
                'png' => ['pdf', 'jpg', 'gif'],
                'gif' => ['pdf', 'jpg', 'png'],
            ],
        ];
    }

    public function testConnection(): array
    {
        try {
            // Try to get user info to test the API connection
            $user = $this->cloudConvert->users()->me();

            // Get basic user info that should always be available
            $userInfo = [
                'connection' => 'successful',
                'api_accessible' => true,
            ];

            // Safely get user ID if available
            if (method_exists($user, 'getId') && $user->getId()) {
                $userInfo['user_id'] = $user->getId();
            }

            // Try to get credits if the method exists
            try {
                if (method_exists($user, 'getCredits')) {
                    $userInfo['credits'] = $user->getCredits();
                }
            } catch (Exception $e) {
                $userInfo['credits'] = 'unavailable';
            }

            Log::info('CloudConvert connection test successful', $userInfo);

            return $userInfo;

        } catch (Exception $e) {
            $errorInfo = [
                'connection' => 'failed',
                'api_accessible' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];

            Log::error('CloudConvert connection test failed', $errorInfo);

            return $errorInfo;
        }
    }

    private function prepareInputFile(string|UploadedFile $inputFile): array
    {
        if ($inputFile instanceof UploadedFile) {
            $originalName = $inputFile->getClientOriginalName();
            $format = mb_strtolower($inputFile->getClientOriginalExtension());

            // Validate file upload
            if (! $inputFile->isValid()) {
                throw new Exception('Uploaded file is invalid: '.$inputFile->getErrorMessage());
            }

            // Store file temporarily
            $tempPath = $inputFile->store('temp/cloudconvert', 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            if (! file_exists($fullPath)) {
                throw new Exception('Failed to store uploaded file temporarily');
            }

            return [
                'path' => $fullPath,
                'format' => $format,
                'original_name' => $originalName,
                'size' => filesize($fullPath),
                'temp_file' => $tempPath,
            ];
        }

        if (! file_exists($inputFile)) {
            throw new Exception("Input file does not exist: {$inputFile}");
        }

        $format = mb_strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));

        return [
            'path' => $inputFile,
            'format' => $format,
            'original_name' => basename($inputFile),
            'size' => filesize($inputFile),
            'temp_file' => null,
        ];
    }

    private function createConversionJob(string $inputPath, string $outputFormat): Job
    {
        try {
            // Validate input format
            $inputFormat = $this->detectInputFormat($inputPath);
            if (! $inputFormat) {
                throw new Exception('Unable to detect input file format');
            }

            // Validate conversion is supported
            $this->validateConversion($inputFormat, $outputFormat);

            Log::info('Creating CloudConvert job', [
                'input_file' => basename($inputPath),
                'input_format' => $inputFormat,
                'output_format' => $outputFormat,
                'file_size' => filesize($inputPath),
            ]);

            // Create job using correct CloudConvert API structure
            $job = (new Job())
                ->addTask((new Task('import/upload', 'import-my-file')))
                ->addTask(
                    (new Task('convert', 'convert-my-file'))
                        ->set('input', 'import-my-file')
                        ->set('output_format', $outputFormat)
                )
                ->addTask(
                    (new Task('export/url', 'export-my-file'))
                        ->set('input', 'convert-my-file')
                );

            $createdJob = $this->cloudConvert->jobs()->create($job);

            Log::info('CloudConvert job created', [
                'job_id' => $createdJob->getId(),
                'status' => $createdJob->getStatus(),
            ]);

            // Upload the file to the import task
            $importTask = $createdJob->getTasks()->whereName('import-my-file')[0] ?? null;
            if (! $importTask) {
                throw new Exception('Import task not found in created job');
            }

            Log::info('Uploading file to CloudConvert', [
                'job_id' => $createdJob->getId(),
                'task_id' => $importTask->getId(),
                'file_size' => filesize($inputPath),
            ]);

            $this->cloudConvert->tasks()->upload($importTask, fopen($inputPath, 'rb'));

            Log::info('CloudConvert job created and file uploaded successfully', [
                'job_id' => $createdJob->getId(),
                'input_format' => $inputFormat,
                'output_format' => $outputFormat,
            ]);

            return $createdJob;

        } catch (Exception $e) {
            Log::error('Failed to create CloudConvert job', [
                'input_file' => basename($inputPath),
                'input_format' => $inputFormat ?? 'unknown',
                'output_format' => $outputFormat,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file_exists' => file_exists($inputPath),
                'file_size' => file_exists($inputPath) ? filesize($inputPath) : 'N/A',
                'api_key_length' => mb_strlen($this->apiKey),
            ]);

            throw new Exception('Failed to create CloudConvert job: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    private function createMergeJob(array $preparedFiles): Job
    {
        $job = new Job();

        // Add import tasks for each file
        foreach ($preparedFiles as $index => $fileInfo) {
            $job->addTask(new Task('import/upload', "import-file-{$index}"));
        }

        // Add merge task
        $inputFiles = [];
        foreach ($preparedFiles as $index => $fileInfo) {
            $inputFiles[] = "import-file-{$index}";
        }

        $job->addTask(
            (new Task('merge', 'merge-files'))
                ->set('input', $inputFiles)
                ->set('output_format', 'pdf')
        );

        // Add export task
        $job->addTask(
            (new Task('export/url', 'export-merged-file'))
                ->set('input', 'merge-files')
        );

        $job = $this->cloudConvert->jobs()->create($job);

        // Upload all files
        foreach ($preparedFiles as $index => $fileInfo) {
            $importTask = $job->getTasks()->whereName("import-file-{$index}")[0] ?? null;
            if (! $importTask) {
                throw new Exception("Import task for file {$index} not found.");
            }
            $this->cloudConvert->tasks()->upload($importTask, fopen($fileInfo['path'], 'rb'));
        }

        return $job;
    }

    // private function createSplitJob(string $inputPath, array $options): Job
    // {
    //     $job = (new Job())
    //         ->addTask((new Task('import/upload', 'import-pdf')));

    //     // Create split task based on options
    //     $splitTask = (new Task('split', 'split-pdf'))
    //         ->set('input', 'import-pdf')
    //         ->set('output_format', 'pdf');

    //     // Configure split options
    //     if ($options['split_mode'] === 'pages' && $options['pages']) {
    //         $splitTask->set('pages', $options['pages']);
    //     } elseif ($options['split_mode'] === 'page_ranges' && $options['page_ranges']) {
    //         $splitTask->set('page_ranges', $options['page_ranges']);
    //     } elseif ($options['split_mode'] === 'fixed_size' && $options['fixed_size']) {
    //         $splitTask->set('fixed_size', $options['fixed_size']);
    //     } else {
    //         // Default: split into individual pages
    //         $splitTask->set('pages', 'all');
    //     }

    //     $job->addTask($splitTask);

    //     // Add export task
    //     $job->addTask(
    //         (new Task('export/url', 'export-split-files'))
    //             ->set('input', 'split-pdf')
    //     );

    //     $job = $this->cloudConvert->jobs()->create($job);

    //     // Upload the PDF file
    //     $importTask = $job->getTasks()->whereName('import-pdf')[0] ?? null;
    //     if (! $importTask) {
    //         throw new Exception('Import task not found.');
    //     }

    //     $this->cloudConvert->tasks()->upload($importTask, fopen($inputPath, 'rb'));

    //     return $job;
    // }
    private function createSplitJob(string $filePath, array $options): Job
    {
        $tasks = [];

        // Import task
        $tasks[] = [
            'operation' => 'import/upload',
            'file' => $filePath,
        ];

        // Split task
        $splitTask = [
            'operation' => 'split',
            'input' => 'import',
            'engine' => 'split-pdf',
        ];

        // Add split-specific options
        if ($options['split_mode'] === 'pages' && ! empty($options['pages'])) {
            $splitTask['pages'] = $options['pages'];
        }

        $tasks[] = $splitTask;

        // Export task
        $tasks[] = [
            'operation' => 'export/url',
            'input' => 'split',
        ];

        return $this->cloudConvert->jobs()->create([
            'tasks' => $tasks,
        ]);
    }

    private function waitForJobCompletion(Job $job, int $timeout = 600): void
    {
        $startTime = time();
        $jobId = $job->getId();

        Log::info('CloudConvert job waiting for completion', [
            'job_id' => $jobId,
            'timeout' => $timeout,
        ]);

        while (true) {
            $job = $this->cloudConvert->jobs()->wait($job);

            Log::debug('CloudConvert job status check', [
                'job_id' => $jobId,
                'status' => $job->getStatus(),
                'elapsed_time' => time() - $startTime,
            ]);

            if ($job->getStatus() === 'finished') {
                Log::info('CloudConvert job completed successfully', [
                    'job_id' => $jobId,
                    'total_time' => time() - $startTime,
                ]);
                break;
            }

            if ($job->getStatus() === 'error') {
                $errorMessage = 'CloudConvert job failed';
                $errorDetails = [];

                // Try to get error details from tasks
                try {
                    $tasks = $job->getTasks();
                    if ($tasks && count($tasks) > 0) {
                        foreach ($tasks as $task) {
                            $taskInfo = [
                                'name' => $task->getName(),
                                'status' => $task->getStatus(),
                                'operation' => $task->getOperation(),
                            ];

                            if ($task->getStatus() === 'error') {
                                // Try to get error message from task result
                                try {
                                    $result = $task->getResult();
                                    if (is_object($result)) {
                                        if (isset($result->message)) {
                                            $taskInfo['error'] = $result->message;
                                            $errorMessage .= ': '.$result->message;
                                        } elseif (isset($result->error)) {
                                            $taskInfo['error'] = $result->error;
                                            $errorMessage .= ': '.$result->error;
                                        } elseif (isset($result->code)) {
                                            $taskInfo['error_code'] = $result->code;
                                            $errorMessage .= ' (Error code: '.$result->code.')';
                                        }
                                    }
                                } catch (Exception $e) {
                                    $taskInfo['error'] = 'Unable to retrieve task error details: '.$e->getMessage();
                                }
                            }

                            $errorDetails[] = $taskInfo;
                        }
                    }
                } catch (Exception $e) {
                    $errorDetails[] = ['general_error' => 'Unable to retrieve job details: '.$e->getMessage()];
                }

                // Log detailed error information
                Log::error('CloudConvert job failed', [
                    'job_id' => $job->getId(),
                    'job_status' => $job->getStatus(),
                    'error_message' => $errorMessage,
                    'task_details' => $errorDetails,
                ]);

                throw new Exception($errorMessage);
            }

            if (time() - $startTime > $timeout) {
                throw new Exception("CloudConvert job timed out after {$timeout} seconds");
            }

            sleep(2);
        }
    }

    private function downloadConvertedFile(Job $job, string $outputFormat): string
    {
        $exportTask = null;
        $exportTaskNames = ['export-my-file', 'export-file', 'export-merged-file'];

        foreach ($job->getTasks() as $task) {
            if (in_array($task->getName(), $exportTaskNames) ||
                str_starts_with($task->getName(), 'export')) {
                $exportTask = $task;
                break;
            }
        }

        if (! $exportTask) {
            // Debug information
            $taskNames = [];
            foreach ($job->getTasks() as $task) {
                $taskNames[] = $task->getName().' ('.$task->getOperation().')';
            }

            Log::error('Export task not found', [
                'job_id' => $job->getId(),
                'available_tasks' => $taskNames,
                'looking_for' => $exportTaskNames,
            ]);

            throw new Exception('Export task not found. Available tasks: '.implode(', ', $taskNames));
        }

        $fileUrl = $exportTask->getResult()->files[0]->url;

        // Generate unique filename
        $fileName = Str::uuid().'.'.$outputFormat;
        $outputPath = storage_path('app/public/converted/'.$fileName);

        // Ensure directory exists
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Download file
        $fileContent = file_get_contents($fileUrl);
        if ($fileContent === false) {
            throw new Exception('Failed to download converted file');
        }

        file_put_contents($outputPath, $fileContent);

        return $outputPath;
    }

    private function downloadSplitFiles(Job $job): array
    {
        $exportTask = null;

        foreach ($job->getTasks() as $task) {
            if ($task->getName() === 'export-split-files') {
                $exportTask = $task;
                break;
            }
        }

        if (! $exportTask) {
            throw new Exception('Export task not found.');
        }

        $files = $exportTask->getResult()->files;
        $downloadedFiles = [];

        foreach ($files as $index => $file) {
            $fileName = Str::uuid().'-page-'.($index + 1).'.pdf';
            $outputPath = storage_path('app/public/converted/'.$fileName);

            // Ensure directory exists
            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            // Download file
            $fileContent = file_get_contents($file->url);
            if ($fileContent === false) {
                throw new Exception('Failed to download split file');
            }

            file_put_contents($outputPath, $fileContent);
            $downloadedFiles[] = $outputPath;
        }

        return $downloadedFiles;
    }

    private function createIndividualPagesSplitJob(string $filePath): Job
    {
        $job = new Job();

        // Import the PDF
        $job->addTask(
            (new Task('import/upload', 'import'))
                ->set('file', $filePath)
        );

        // Split into individual pages
        $job->addTask(
            (new Task('split', 'split-pdf'))
                ->set('input', 'import')
                ->set('mode', 'fixed_size')
                ->set('fixed_size', 1)  // 1 page per output file
        );

        // Export all split files
        $job->addTask(
            (new Task('export/url', 'export'))
                ->set('input', 'split')
        );

        return $this->cloudConvert->jobs()->create($job);
    }

    private function createPdfToExcelJob(string $inputPath, string $outputFormat, array $options): Job
    {
        $job = (new Job())
            ->addTask((new Task('import/upload', 'import-pdf')));

        // Create convert task with PDF to Excel specific options
        $convertTask = (new Task('convert', 'convert-pdf'))
            ->set('input', 'import-pdf')
            ->set('output_format', $outputFormat)
            ->set('engine', 'office');

        // Add PDF specific options
        if (isset($options['page_range'])) {
            $convertTask->set('page_range', $options['page_range']);
        }

        if (isset($options['specific_pages'])) {
            $convertTask->set('pages', $options['specific_pages']);
        }

        if ($options['table_detection'] ?? true) {
            $convertTask->set('detect_tables', true);
        }

        if ($options['preserve_formatting'] ?? true) {
            $convertTask->set('preserve_formatting', true);
        }

        $job->addTask($convertTask);

        // Export task
        $job->addTask(
            (new Task('export/url', 'export-excel'))
                ->set('input', 'convert-pdf')
        );

        $job = $this->cloudConvert->jobs()->create($job);

        // Upload the PDF file
        $importTask = $job->getTasks()->whereName('import-pdf')[0] ?? null;
        if (! $importTask) {
            throw new Exception('Import task not found.');
        }

        $this->cloudConvert->tasks()->upload($importTask, fopen($inputPath, 'rb'));

        return $job;
    }

    private function createCompressionJob(string $inputPath, array $options): Job
    {
        $qualityLevel = $options['quality_level'] ?? 'medium';
        $imageQuality = $options['image_quality'] ?? 75;
        $removeMetadata = $options['remove_metadata'] ?? false;

        // Map quality levels to CloudConvert parameters
        $qualitySettings = [
            'high' => ['image_quality' => 90, 'compression_level' => 'low'],
            'medium' => ['image_quality' => 75, 'compression_level' => 'medium'],
            'maximum' => ['image_quality' => 50, 'compression_level' => 'high'],
        ];

        $settings = $qualitySettings[$qualityLevel] ?? $qualitySettings['medium'];

        $job = (new Job())
            ->addTask((new Task('import/upload', 'import-pdf')))
            ->addTask(
                (new Task('optimize', 'compress-pdf'))
                    ->set('input', 'import-pdf')
                    ->set('image_quality', $imageQuality ?: $settings['image_quality'])
                    ->set('compression_level', $settings['compression_level'])
                    ->set('remove_metadata', $removeMetadata)
            )
            ->addTask(
                (new Task('export/url', 'export-compressed'))
                    ->set('input', 'compress-pdf')
            );

        $job = $this->cloudConvert->jobs()->create($job);

        // Upload the PDF file
        $importTask = $job->getTasks()->whereName('import-pdf')[0] ?? null;
        if (! $importTask) {
            throw new Exception('Import task not found.');
        }

        $this->cloudConvert->tasks()->upload($importTask, fopen($inputPath, 'rb'));

        return $job;
    }

    private function downloadMultipleFiles(Job $job, string $outputFormat): array
    {
        $exportTask = null;
        $exportTaskNames = ['export-excel', 'export-compressed'];

        foreach ($job->getTasks() as $task) {
            if (in_array($task->getName(), $exportTaskNames)) {
                $exportTask = $task;
                break;
            }
        }

        if (! $exportTask) {
            throw new Exception('Export task not found.');
        }

        $files = $exportTask->getResult()->files;
        $downloadedFiles = [];

        foreach ($files as $index => $file) {
            $fileName = Str::uuid().'-page-'.($index + 1).'.'.$outputFormat;
            $outputPath = storage_path('app/public/converted/'.$fileName);

            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $fileContent = file_get_contents($file->url);
            if ($fileContent === false) {
                throw new Exception('Failed to download file');
            }

            file_put_contents($outputPath, $fileContent);
            $downloadedFiles[] = [
                'path' => $outputPath,
                'name' => 'page-'.($index + 1).'.'.$outputFormat,
                'size' => mb_strlen($fileContent),
            ];
        }

        return $downloadedFiles;
    }

    private function cleanupTempFiles(?string $tempFile): void
    {
        if ($tempFile && Storage::disk('local')->exists($tempFile)) {
            Storage::disk('local')->delete($tempFile);
        }
    }
}
