<?php

declare(strict_types=1);

namespace App\Domain\FileCompliance\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

final class FileService
{
    /**
     * Upload files with document number.
     */
    public function uploadFiles(array $files, string $docNum): int
    {
        $uploadedCount = 0;
        $uploadedFileNames = [];

        foreach ($files as $file) {
            if (! $this->isValidFile($file)) {
                Log::warning('FileService: Invalid or unreadable file skipped during upload.', [
                    'doc_num' => $docNum,
                    'file_info' => $this->getFileDebugInfo($file),
                ]);
                continue;
            }

            $originalName = $this->getSafeOriginalName($file);
            $fileName = time() . '-' . $originalName;
            $fileSize = $this->getFileSize($file);
            $mimeType = $this->getFileMimeType($file);

            $storedPath = $file->storeAs('public/files', $fileName);
            if (! $storedPath) {
                Log::error('FileService: Failed to store file to disk.', [
                    'doc_num' => $docNum,
                    'file_name' => $fileName,
                ]);
                continue;
            }

            $fileModel = File::create([
                'doc_id' => $docNum,
                'name' => $fileName,
                'mime_type' => $mimeType,
                'size' => $fileSize,
            ]);

            // Explicitly log creation if activity logger is available
            if (function_exists('activity')) {
                try {
                    $activity = activity()->performedOn($fileModel);
                    if ($user = auth()->user()) {
                        $activity->causedBy($user);
                    }
                    $activity->log('uploaded file: ' . $originalName);
                } catch (\Throwable $e) {
                    Log::warning('FileService: Failed to write activity log.', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $uploadedFileNames[] = $originalName;
            $uploadedCount++;
        }

        return $uploadedCount;
    }

    /**
     * Upload evaluation files with auto-generated doc_id.
     */
    public function uploadEvaluationFiles(array $files, int $month, int $year, string $dept): int
    {
        $prefix = sprintf('%04d-%02d-%s-', $year, $month, strtoupper($dept));
        $uploadedCount = 0;

        foreach ($files as $file) {
            if (! $this->isValidFile($file)) {
                Log::warning('FileService: Invalid or unreadable file skipped during evaluation upload.', [
                    'prefix' => $prefix,
                    'file_info' => $this->getFileDebugInfo($file),
                ]);
                continue;
            }

            $originalName = $this->getSafeOriginalName($file);
            $fileName = time() . '-' . $originalName;
            $fileSize = $this->getFileSize($file);
            $mimeType = $this->getFileMimeType($file);

            $storedPath = $file->storeAs('public/files', $fileName);
            if (! $storedPath) {
                Log::error('FileService: Failed to store evaluation file to disk.', [
                    'prefix' => $prefix,
                    'file_name' => $fileName,
                ]);
                continue;
            }

            $docId = $this->generateDocId($prefix);

            File::create([
                'doc_id' => $docId,
                'name' => $fileName,
                'mime_type' => $mimeType,
                'size' => $fileSize,
            ]);

            $uploadedCount++;
        }

        return $uploadedCount;
    }

    /**
     * Delete file from storage and database.
     */
    public function deleteFile(int $fileId): bool
    {
        $file = File::find($fileId);

        if ($file) {
            if (! empty($file->name) && Storage::exists('public/files/' . $file->name)) {
                Storage::delete('public/files/' . $file->name);
            }
            $file->delete();

            return true;
        }

        return false;
    }

    /**
     * Get files by year, month, and department.
     */
    public function getFilesByFilter(int $year, int $month, string $dept): \Illuminate\Database\Eloquent\Collection
    {
        $pattern = "{$year}-{$month}-{$dept}-%";

        return File::where('doc_id', 'LIKE', $pattern)->get();
    }

    /**
     * Check whether the file object is valid and present on disk.
     */
    private function isValidFile(mixed $file): bool
    {
        if (! $file instanceof SymfonyFile && ! $file instanceof UploadedFile) {
            return false;
        }

        if ($file instanceof UploadedFile && ! $file->isValid()) {
            return false;
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || $realPath === '' || ! file_exists($realPath)) {
            return false;
        }

        return true;
    }

    /**
     * Extract a safe, URL-friendly basename from the uploaded file.
     */
    private function getSafeOriginalName(mixed $file): string
    {
        $originalName = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : $file->getFilename();

        $info = pathinfo(basename($originalName));
        $filename = $info['filename'] ?? 'file';
        $extension = $file instanceof UploadedFile
            ? ($file->getClientOriginalExtension() ?: ($info['extension'] ?? ''))
            : ($file->getExtension() ?: ($info['extension'] ?? ''));

        // Replace spaces, pluses, ampersands and special characters with hyphens
        $cleanName = preg_replace('/[^\w\.-]+/u', '-', $filename);
        $cleanName = trim((string) preg_replace('/-+/', '-', (string) $cleanName), '-._');

        if (empty($cleanName)) {
            $cleanName = 'file_' . uniqid();
        }

        return $cleanName . ($extension !== '' ? '.' . strtolower($extension) : '');
    }

    /**
     * Safely determine file size.
     */
    private function getFileSize(mixed $file): int
    {
        try {
            return (int) $file->getSize();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Safely determine MIME type.
     */
    private function getFileMimeType(mixed $file): string
    {
        try {
            if ($file instanceof UploadedFile) {
                return $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream');
            }

            return $file->getMimeType() ?: 'application/octet-stream';
        } catch (\Throwable) {
            return 'application/octet-stream';
        }
    }

    /**
     * Get debug information about a file for logging.
     */
    private function getFileDebugInfo(mixed $file): array
    {
        if (! is_object($file)) {
            return ['type' => gettype($file)];
        }

        if ($file instanceof UploadedFile) {
            return [
                'type' => get_class($file),
                'isValid' => $file->isValid(),
                'error' => $file->getError(),
                'errorMessage' => $file->getErrorMessage(),
                'clientName' => $file->getClientOriginalName(),
                'realPath' => $file->getRealPath(),
            ];
        }

        return [
            'type' => get_class($file),
            'realPath' => method_exists($file, 'getRealPath') ? $file->getRealPath() : null,
        ];
    }

    /**
     * Generate incremental doc_id for evaluation uploads.
     */
    private function generateDocId(string $prefix): string
    {
        $lastDoc = File::where('doc_id', 'like', $prefix . '%')
            ->orderByDesc('doc_id')
            ->first();

        $incrementNumber = 1;
        if ($lastDoc) {
            preg_match('/(\d+)$/', $lastDoc->doc_id, $matches);
            $incrementNumber = intval($matches[0]) + 1;
        }

        $incrementalDocId = sprintf('%03d', $incrementNumber);

        return $prefix . $incrementalDocId;
    }
}
