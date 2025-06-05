<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate the input
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx',
            'summary' => 'required|string',
        ]);

        $uploadedFile = $request->file('file');

        // 2. Create the database entry first without filename
        $document = Document::create([
            'type' => $uploadedFile->extension() === 'pdf' ? 'pdf' : 'doc',
            'filename' => '', // placeholder, will update later
            'summary' => $validated['summary'],
            'external_app_id' => $request->input('external_app_id'),
            'telegram_chat_id' => $request->input('telegram_chat_id'),
            'telegram_username' => $request->input('telegram_username'),
            'telegram_user_id' => $request->input('telegram_user_id'),
        ]);

        // 3. Rename and store file
        $originalName = $uploadedFile->getClientOriginalName();
        $newFilename = $document->id . '-' . $originalName;
        $path = $uploadedFile->storeAs('documents', $newFilename);

        // 4. Update the filename in DB
        $document->update(['filename' => $newFilename]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }
}
