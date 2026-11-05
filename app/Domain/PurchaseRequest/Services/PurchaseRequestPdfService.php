<?php

declare(strict_types=1);

namespace App\Domain\PurchaseRequest\Services;

use App\Application\PurchaseRequest\Queries\GetPurchaseRequestDetail;
use App\Application\PurchaseRequest\ViewModels\PurchaseRequestDetailVM;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\UserSignature;
use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class PurchaseRequestPdfService
{
    public function __construct(
        private readonly GetPurchaseRequestDetail $detailQuery,
    ) {}

    /**
     * Build the DomPDF instance for a given Purchase Request.
     */
    public function generate(int|PurchaseRequest $pr, User|Authenticatable $actor): \Barryvdh\DomPDF\PDF
    {
        $purchaseRequest = $pr instanceof PurchaseRequest ? $pr : PurchaseRequest::findOrFail($pr);

        Gate::forUser($actor)->authorize('view', $purchaseRequest);

        // Fetch view model using domain query (handles sensitive pricing rules, item filtering, totals)
        $vm = $this->detailQuery->handle($purchaseRequest->id, $actor);

        // Prepare Base64 company logo
        $logoBase64 = $this->getCompanyLogoBase64();

        // Prepare digital signatures with inline base64 images for reliable offline PDF rendering
        $signatures = $this->prepareSignatures($vm->purchaseRequest);

        $pdf = Pdf::loadView('pdf.pr-pdf', [
            'purchaseRequest' => $vm->purchaseRequest,
            'user' => $actor,
            'userCreatedBy' => $vm->meta['userCreatedBy'] ?? $vm->purchaseRequest->createdBy,
            'filteredItemDetail' => $vm->filteredItemDetail,
            'totals' => $vm->totals,
            'departments' => $vm->departments,
            'approval' => $vm->approval,
            'signatures' => $signatures,
            'logoBase64' => $logoBase64,
            'generatedAt' => now()->timezone('Asia/Jakarta'),
            'generatedBy' => $actor->name,
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('isPhpEnabled', true);
        $pdf->setOption('isRemoteEnabled', false); // All assets are inlined as Base64 for safety & speed

        return $pdf;
    }

    /**
     * Stream PDF in browser (preview mode).
     */
    public function stream(int|PurchaseRequest $pr, User|Authenticatable $actor, ?string $filename = null): Response
    {
        $pdf = $this->generate($pr, $actor);
        $name = $filename ?? $this->generateFilename($pr instanceof PurchaseRequest ? $pr : PurchaseRequest::find($pr));

        return $pdf->stream($name);
    }

    /**
     * Download PDF as an attachment.
     */
    public function download(int|PurchaseRequest $pr, User|Authenticatable $actor, ?string $filename = null): Response
    {
        $pdf = $this->generate($pr, $actor);
        $name = $filename ?? $this->generateFilename($pr instanceof PurchaseRequest ? $pr : PurchaseRequest::find($pr));

        return $pdf->download($name);
    }

    /**
     * Generate standard sanitized PDF filename.
     */
    public function generateFilename(PurchaseRequest $pr): string
    {
        $docNum = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($pr->doc_num ?? 'PR'));
        $prNo = $pr->pr_no ? preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $pr->pr_no) : 'DRAFT';

        return "PR_{$docNum}_{$prNo}.pdf";
    }

    /**
     * Load company logo from public disk and encode to Base64 data URI.
     */
    private function getCompanyLogoBase64(): ?string
    {
        $paths = [
            public_path('image/daijo-logo.png'),
            public_path('image/Asset 1.svg'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                $data = file_get_contents($path);
                if ($data !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($data);
                }
            }
        }

        return null;
    }

    /**
     * Resolve all workflow signatures and convert images to Base64 data URIs.
     *
     * @return array<int, array{
     *     step_code: string,
     *     name: string,
     *     role: string,
     *     at: ?string,
     *     status: string,
     *     image_base64: ?string
     * }>
     */
    private function prepareSignatures(PurchaseRequest $pr): array
    {
        $rawSignatures = $pr->workflow_signatures;
        $prepared = [];

        foreach ($rawSignatures as $sig) {
            $imageBase64 = null;
            $status = $sig['status'] ?? 'pending';

            if ($status === 'signed') {
                $imageBase64 = $this->resolveSignatureBase64($sig, $pr);
            }

            $prepared[] = [
                'step_code' => $sig['step_code'] ?? 'Approver',
                'name' => $sig['name'] ?? '—',
                'role' => $sig['step_code'] ?? 'Approver',
                'at' => $sig['at'] ? (string) $sig['at'] : null,
                'status' => $status,
                'image_base64' => $imageBase64,
            ];
        }

        return $prepared;
    }

    /**
     * Resolve signature image to Base64 data URI from private/public storage or autographs.
     */
    private function resolveSignatureBase64(array $sig, PurchaseRequest $pr): ?string
    {
        // 1. If step is from approval system, look for approval step snapshot
        if (($sig['source'] ?? null) === 'approval_system' && $pr->approvalRequest) {
            $step = $pr->approvalRequest->steps
                ->where('approver_label', $sig['step_code'])
                ->first();

            if ($step && $step->signature_image_path) {
                $base64 = $this->readImageFromStorage($step->signature_image_path);
                if ($base64) {
                    return $base64;
                }
            }
        }

        // 2. Creator signature
        if (($sig['source'] ?? null) === 'creator') {
            $creator = $pr->creator;
            if ($creator) {
                /** @var UserSignature|null $defaultSig */
                $defaultSig = $creator->signatures()
                    ->whereNull('revoked_at')
                    ->orderByDesc('is_default')
                    ->first();

                if ($defaultSig) {
                    $path = $defaultSig->filePath ?? $defaultSig->svgPath;
                    if ($path) {
                        $base64 = $this->readImageFromStorage($path);
                        if ($base64) {
                            return $base64;
                        }
                    }
                }
            }
        }

        // 3. Check if image URL contains signature ID (e.g. /signatures/{id})
        if (! empty($sig['image']) && is_string($sig['image'])) {
            if (preg_match('/signatures\/(\d+)/', $sig['image'], $matches)) {
                $sigId = (int) $matches[1];
                $userSig = UserSignature::find($sigId);
                if ($userSig) {
                    $path = $userSig->filePath ?? $userSig->svgPath;
                    if ($path) {
                        $base64 = $this->readImageFromStorage($path);
                        if ($base64) {
                            return $base64;
                        }
                    }
                }
            }
        }

        // 4. Fallback: check legacy autograph path by user name
        $userName = $sig['name'] ?? null;
        if ($userName) {
            $autographFile = public_path('autographs/' . $userName . '.png');
            if (file_exists($autographFile)) {
                $data = file_get_contents($autographFile);
                if ($data !== false) {
                    return 'data:image/png;base64,' . base64_encode($data);
                }
            }
        }

        return null;
    }

    /**
     * Read file from private or public disk and return data URI.
     */
    private function readImageFromStorage(string $path): ?string
    {
        // Try private disk first (where UserSignatures are stored)
        if (Storage::disk('private')->exists($path)) {
            $content = Storage::disk('private')->get($path);
            $mime = Storage::disk('private')->mimeType($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // Try public disk
        if (Storage::disk('public')->exists($path)) {
            $content = Storage::disk('public')->get($path);
            $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // Try local filesystem direct path
        if (file_exists($path)) {
            $mime = mime_content_type($path) ?: 'image/png';
            $content = file_get_contents($path);
            if ($content !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        return null;
    }
}
