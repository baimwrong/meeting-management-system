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
        $user = Auth::user();
        $isAttendee = $meeting->attendees()->where('user_id', $user->id)->exists();
        if (! $user->isAdmin() && $meeting->organizer_id !== $user->id && ! $isAttendee) {
            abort(403, 'Unauthorized. Only meeting organizers or attendees can upload attachments.');
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480', // 20 MB max
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,webp',
            ],
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
        $meeting = $attachment->meeting;
        if ($meeting && $meeting->is_confidential) {
            $user = Auth::user();
            $isAttendee = $meeting->attendees()->where('user_id', $user->id)->exists();
            if (! $user->isAdmin() && $meeting->organizer_id !== $user->id && ! $isAttendee) {
                abort(403, 'Unauthorized. You do not have permission to download attachments for this confidential meeting.');
            }
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }

    public function destroy(MeetingAttachment $attachment): RedirectResponse
    {
        $user = Auth::user();
        $isUploader = $attachment->uploaded_by_id === $user->id;
        $isOrganizer = $attachment->meeting && $attachment->meeting->organizer_id === $user->id;
        if (! $user->isAdmin() && ! $isUploader && ! $isOrganizer) {
            abort(403, 'Unauthorized. Only the uploader, meeting organizer, or an administrator can delete this attachment.');
        }

        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $name = $attachment->original_name;
        $attachment->delete();

        return back()->with('success', "Attachment '{$name}' deleted.");
    }
}
