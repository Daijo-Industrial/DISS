<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfProcessingService
{
    /**
     * Sign a PDF with digital signature
     *
     * @param PurchaseOrder $po The purchase order to sign
     * @param int $userId The user performing the signature
     * @return string The path to the signed PDF
     *
     * @throws \Exception
     */
    public function sign(PurchaseOrder $po, int $userId): string
    {
        try {
            $filename = basename($po->filename);

            // Check canonical storage_path first, then fallback to public_path
            $possiblePaths = [
                storage_path("app/public/pdfs/{$filename}"),
                storage_path("app/public/{$filename}"),
                public_path("storage/pdfs/{$filename}"),
                public_path("storage/{$filename}"),
            ];

            $originalPdfPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $originalPdfPath = $path;
                    break;
                }
            }

            // Verify original PDF exists
            if (! $originalPdfPath) {
                throw new \Exception("Original PDF file not found on server: {$filename}");
            }

            // Ensure destination directory exists in storage
            $signedDir = storage_path('app/public/pdfs');
            if (! file_exists($signedDir)) {
                mkdir($signedDir, 0755, true);
            }

            // Generate signed PDF filename and destination path
            $signedFilename = str_replace('.pdf', '_signed.pdf', $filename);
            $signedPdfPath = $signedDir . DIRECTORY_SEPARATOR . $signedFilename;

            // Resolve signature image path for the signing user
            $signaturePath = $this->resolveSignaturePath($userId);

            // Perform PDF signing
            $this->performPdfSigning($originalPdfPath, $signedPdfPath, $signaturePath);

            // Update PO with signed filename
            $po->filename = $signedFilename;
            $po->save();

            Log::info('PDF signed successfully', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'signed_by' => $userId,
                'signed_file' => $signedFilename,
            ]);

            return $signedPdfPath;

        } catch (\Exception $e) {
            Log::error('PDF signing failed', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject a PDF (mark as rejected)
     *
     * @param PurchaseOrder $po The purchase order to reject
     * @param string $reason The rejection reason
     *
     * @throws \Exception
     */
    public function reject(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        try {
            if (! $po) {
                throw new \Exception('Purchase order not found');
            }

            // Use approval system to reject instead of direct status update
            $approvalService = app(\App\Application\Approval\Contracts\Approvals::class);
            $approvalService->reject($po, auth()->id(), $reason);

            Log::info('PDF rejected', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'reason' => $reason,
            ]);

            return $po;

        } catch (\Exception $e) {
            Log::error('PDF rejection failed', [
                'po_id' => $po->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Download a PDF file with security checks
     *
     * @param int $poId The purchase order ID
     * @param int $userId The user requesting download
     *
     * @throws \Exception
     */
    public function download(int $poId, int $userId): BinaryFileResponse
    {
        try {
            $po = PurchaseOrder::findOrFail($poId);
            $filename = $po->filename;

            // Check if file exists
            $path = storage_path("app/public/pdfs/{$filename}");
            if (! file_exists($path)) {
                throw new \Exception('PDF file not found on server');
            }

            // Log download for creator
            if ($po->creator_id === $userId) {
                // TODO: Implement download logging via repository/service
                // For now, we'll handle this in the controller
            }

            Log::info('PDF download initiated', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'downloaded_by' => $userId,
                'filename' => $filename,
            ]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('PDF download failed', [
                'po_id' => $poId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate PDF file upload
     *
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @throws \Exception
     */
    public function validatePdfFile($file): bool
    {
        // Check file type
        if (! $file->isValid() || $file->getMimeType() !== 'application/pdf') {
            throw new \Exception('Invalid file type. Only PDF files are allowed.');
        }

        // Check file size (5MB limit)
        if ($file->getSize() > 5242880) { // 5MB in bytes
            throw new \Exception('File size exceeds 5MB limit.');
        }

        // Additional security checks
        $this->performSecurityChecks($file);

        return true;
    }

    /**
     * Store PDF file with unique filename
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The stored filename
     *
     * @throws \Exception
     */
    public function storePdfFile($file, int $poNumber): string
    {
        try {
            $filename = 'PO_' . $poNumber . '_' . time() . '.pdf';

            $file->storeAs('public/pdfs', $filename);

            Log::info('PDF file stored', [
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('PDF file storage failed', [
                'po_number' => $poNumber,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to store PDF file: ' . $e->getMessage());
        }
    }

    /**
     * Extract PDF metadata
     */
    public function extractMetadata(string $filename): array
    {
        try {
            $path = storage_path("app/public/pdfs/{$filename}");

            if (! file_exists($path)) {
                return [];
            }

            // Basic file information
            $metadata = [
                'filename' => $filename,
                'file_size' => filesize($path),
                'modified_at' => filemtime($path),
                'pages' => $this->getPdfPageCount($path),
            ];

            return $metadata;

        } catch (\Exception $e) {
            Log::warning('Failed to extract PDF metadata', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Perform the actual PDF signing operation
     *
     * @throws \Exception
     */
    private function performPdfSigning(string $originalPath, string $signedPath, ?string $signaturePath = null): void
    {
        try {
            // Initialize FPDI
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($originalPath);

            // Verify signature file exists if provided
            if ($signaturePath && ! file_exists($signaturePath)) {
                $signaturePath = null;
            }

            // Fallback to default Djoni signature if none provided
            if (! $signaturePath) {
                $defaultSig = public_path('autographs/Djoni.png');
                if (file_exists($defaultSig)) {
                    $signaturePath = $defaultSig;
                }
            }

            // Loop through each page and add it to the new PDF preserving dimensions
            for ($pageIndex = 1; $pageIndex <= $pageCount; $pageIndex++) {
                $templateId = $pdf->importPage($pageIndex);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Add signature to the last page if signature image exists
                if ($pageIndex === $pageCount && $signaturePath && file_exists($signaturePath)) {
                    // Position signature nicely in bottom area based on page dimensions
                    $sigWidth = 40;
                    $sigHeight = 20;
                    $x = min(40, max(10, $size['width'] - $sigWidth - 20));
                    $y = max(10, $size['height'] - $sigHeight - 27);

                    $pdf->Image($signaturePath, $x, $y, $sigWidth, $sigHeight);
                }
            }

            // Save the signed PDF
            $pdf->Output($signedPath, 'F');

        } catch (\Exception $e) {
            throw new \Exception('PDF signing operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the signature image path for a given user ID.
     */
    private function resolveSignaturePath(int $userId): ?string
    {
        try {
            // 1. Check user signature repository
            if (interface_exists(\App\Domain\Signature\Repositories\UserSignatureRepository::class)) {
                $repo = app(\App\Domain\Signature\Repositories\UserSignatureRepository::class);
                $signatures = $repo->listByUser($userId, onlyActive: true);
                if (! empty($signatures) && ! empty($signatures[0]->filePath)) {
                    $privatePath = \Illuminate\Support\Facades\Storage::disk('private')->path($signatures[0]->filePath);
                    if (file_exists($privatePath)) {
                        return $privatePath;
                    }
                }
            }

            // 2. Check autographs folder by username
            $user = \App\Models\User::find($userId);
            if ($user && ! empty($user->name)) {
                $userAutograph = public_path("autographs/{$user->name}.png");
                if (file_exists($userAutograph)) {
                    return $userAutograph;
                }
            }

            // 3. Fallback to default Djoni.png
            $defaultAutograph = public_path('autographs/Djoni.png');
            if (file_exists($defaultAutograph)) {
                return $defaultAutograph;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve signature path', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return public_path('autographs/Djoni.png');
        }
    }

    /**
     * Get PDF page count
     */
    private function getPdfPageCount(string $path): int
    {
        try {
            $pdf = new Fpdi;

            return $pdf->setSourceFile($path);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Perform security checks on uploaded file
     *
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @throws \Exception
     */
    private function performSecurityChecks($file): void
    {
        // Check for malicious content in filename
        $originalName = $file->getClientOriginalName();
        if (preg_match('/[<>\"\|\?\*\x00-\x1f]/', $originalName)) {
            throw new \Exception('Invalid characters in filename');
        }

        // Additional security checks can be added here
        // - File content scanning
        // - Virus scanning integration
        // - PDF structure validation
    }
}
