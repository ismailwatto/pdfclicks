<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;

trait FileValidation
{
    protected function validateFileSize(string $file, int $maxSizeInMB = 15): void
    {
        $fileSize = filesize($file);
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;

        if ($fileSize > $maxSizeInBytes) {
            throw new Exception("File size ({$this->formatFileSize($fileSize)}) exceeds maximum allowed size ({$maxSizeInMB}MB)");
        }
    }

    protected function validateFileType(string $file, array $allowedTypes): void
    {
        $extension = mb_strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Safely check MIME type only if file exists
        if (file_exists($file) && is_readable($file)) {
            $mimeType = mime_content_type($file);
        } else {
            // Fallback to extension-based validation if file doesn't exist
            $mimeType = $this->getMimeTypeByExtension($extension);
        }

        if (! in_array($extension, $allowedTypes) && ! in_array($mimeType, $this->getMimeTypes($allowedTypes))) {
            throw new Exception('Invalid file type. Allowed types: '.implode(', ', $allowedTypes));
        }
    }

    protected function validateFileExists(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new Exception("File is not readable: {$filePath}");
        }
    }

    protected function validatePdfFile(string $file): void
    {
        $this->validateFileType($file, ['pdf']);
        $this->validateFileSize($file);
        $this->validateFileExists($file);

        // Basic PDF header validation only if file exists and is readable
        if (file_exists($file) && is_readable($file)) {
            $handle = fopen($file, 'rb');
            if (! $handle) {
                throw new Exception('Cannot open file for reading');
            }

            $header = fread($handle, 8);
            fclose($handle);

            if (mb_strpos($header, '%PDF-') !== 0) {
                throw new Exception('Invalid PDF file format');
            }
        }
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2).' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    private function getMimeTypes(array $extensions): array
    {
        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
        ];

        return array_intersect_key($mimeMap, array_flip($extensions));
    }

    private function getMimeTypeByExtension(string $extension): string
    {
        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
        ];

        return $mimeMap[$extension] ?? 'application/octet-stream';
    }
}
