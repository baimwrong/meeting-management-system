<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingAttachmentController extends Controller
{
    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20 MB max
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getClientMimeType();
        $fileSize = $uploadedFile->getSize();

        $path = $uploadedFile->store('meeting_attachments', 'local');

        $meeting->attachments()->create([
            'uploaded_by_id' => Auth::id(),
            'file_name' => basename($path),
            'original_name' => $originalName,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        return back()->with('success', "File '{$originalName}' uploaded successfully.");
    }

    public function download(MeetingAttachment $attachment): StreamedResponse
    {
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }

    public function destroy(MeetingAttachment $attachment): RedirectResponse
    {
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $name = $attachment->original_name;
        $attachment->delete();

        return back()->with('success', "Attachment '{$name}' deleted.");
    }
}
