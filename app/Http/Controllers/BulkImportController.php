<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Services\BulkImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BulkImportController extends Controller
{
    protected BulkImportService $importService;

    public function __construct(BulkImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index(): View
    {
        $logs = ImportLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.bulk_import.index', compact('logs'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_type' => 'required|in:users,rooms,meetings',
            'file' => ['required', 'file', 'max:10240', function ($attribute, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['csv', 'txt'], true)) {
                    $fail('The uploaded file must be a valid .csv file.');
                }
            }],
        ]);

        $entityType = $request->input('entity_type');
        $file = $request->file('file');
        $userId = Auth::id();

        try {
            $log = match ($entityType) {
                'users' => $this->importService->importUsers($file, $userId),
                'rooms' => $this->importService->importRooms($file, $userId),
                'meetings' => $this->importService->importMeetings($file, $userId),
            };

            $message = "Import completed for {$entityType}: {$log->success_rows} successful, {$log->failed_rows} failed out of {$log->total_rows} total rows.";

            if ($log->failed_rows > 0) {
                return redirect()->route('admin.bulk-import.show', $log)
                    ->with('warning', $message . ' Some rows had validation errors. Review the report below.');
            }

            return redirect()->route('admin.bulk-import.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import process encountered an unexpected error: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(string $type): Response
    {
        if (!in_array($type, ['users', 'rooms', 'meetings'])) {
            abort(404);
        }

        $csv = $this->importService->generateTemplateCsv($type);
        $fileName = "sample_template_{$type}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    public function show(ImportLog $log): View
    {
        $log->load('user');
        return view('admin.bulk_import.show', compact('log'));
    }
}
