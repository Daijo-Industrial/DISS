<?php

namespace App\Http\Controllers;

use App\Domain\FileCompliance\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:32000',
            'doc_num' => 'nullable',
        ]);

        $docNum = (string) ($request->input('doc_num') ?? '');
        $files = $request->file('files');

        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        if (empty($files)) {
            return redirect()->back()->with(['error' => 'No files were provided for upload.']);
        }

        try {
            $uploadedCount = $this->fileService->uploadFiles($files, $docNum);

            if ($uploadedCount === 0) {
                return redirect()->back()->with(['error' => 'No files were uploaded. Please check that the files are valid and within size limits.']);
            }

            return redirect()->back()->with(['success' => "{$uploadedCount} file(s) successfully uploaded!"]);
        } catch (\Throwable $e) {
            Log::error('FileController@upload failed: ' . $e->getMessage(), [
                'exception' => $e,
                'doc_num' => $docNum,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with(['error' => 'File upload failed: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->fileService->deleteFile((int) $id);

            if ($deleted) {
                return redirect()->back()->with(['success' => 'File successfully deleted']);
            }

            return redirect()->back()->with(['error' => 'File not found or already deleted']);
        } catch (\Throwable $e) {
            Log::error('FileController@destroy failed: ' . $e->getMessage(), [
                'file_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(['error' => 'Failed to delete file.']);
        }
    }

    public function uploadEvaluation(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:32000',
            'filter_month' => 'required',
            'filter_year' => 'required',
            'department' => 'required|string',
        ]);

        $month = (int) $request->input('filter_month');
        $year = (int) $request->input('filter_year');
        $dept = (string) $request->input('department');
        $files = $request->file('files');

        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        if (empty($files)) {
            return redirect()->back()->with(['error' => 'No files were provided for evaluation upload.']);
        }

        try {
            $uploadedCount = $this->fileService->uploadEvaluationFiles($files, $month, $year, $dept);

            if ($uploadedCount === 0) {
                return redirect()->back()->with(['error' => 'No files were uploaded. Please check that the files are valid.']);
            }

            return redirect()->back()->with(['success' => "{$uploadedCount} evaluation file(s) successfully uploaded!"]);
        } catch (\Throwable $e) {
            Log::error('FileController@uploadEvaluation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'dept' => $dept,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with(['error' => 'Evaluation file upload failed: ' . $e->getMessage()]);
        }
    }

    public function getFiles(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $dept = (string) $request->input('dept', '');

        try {
            $files = $this->fileService->getFilesByFilter($year, $month, $dept);

            return response()->json(['files' => $files]);
        } catch (\Throwable $e) {
            Log::error('FileController@getFiles failed: ' . $e->getMessage());

            return response()->json(['files' => [], 'error' => 'Unable to retrieve files.'], 500);
        }
    }
}
