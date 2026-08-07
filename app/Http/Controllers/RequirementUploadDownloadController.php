<?php

namespace App\Http\Controllers;

use App\Models\RequirementUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequirementUploadDownloadController extends Controller
{
    public function show(RequirementUpload $upload, Request $request)
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($upload->path)) {
            abort(444, 'File not found');
        }

        // If 'preview' or inline disposition is requested, stream inline
        if ($request->has('preview') || $request->query('disposition') === 'inline') {
            $fullPath = $disk->path($upload->path);
            $mimeType = $upload->mime_type ?? $disk->mimeType($upload->path) ?? 'application/pdf';

            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($upload->original_name) . '"',
            ]);
        }

        // Otherwise default to attachment download
        return $disk->download($upload->path, $upload->original_name);
    }
}
