<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function showDocument($documentId)
    {
        $document = ApplicationDocument::findOrFail($documentId);
        
        // Security check - pastikan user memiliki akses
        $user = Auth::user();
        
        // Validator bisa melihat semua dokumen
        // Student hanya bisa melihat dokumen miliknya sendiri
        if ($user->role === 'student') {
            if ($document->application->user_id !== $user->id) {
                abort(403, 'Unauthorized access to document');
            }
        }
        
        // Cek apakah file ada
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found');
        }
        
        $filePath = Storage::disk('public')->path($document->file_path);
        $mimeType = Storage::disk('public')->mimeType($document->file_path);
        
        // Return file response
        return Response::file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->document_name . '"'
        ]);
    }

    public function downloadDocument($documentId)
    {
        $document = ApplicationDocument::findOrFail($documentId);
        
        // Security check
        $user = Auth::user();
        
        if ($user->role === 'student') {
            if ($document->application->user_id !== $user->id) {
                abort(403, 'Unauthorized access to document');
            }
        }
        
        // Cek apakah file ada
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found');
        }
        
        return Storage::disk('public')->download($document->file_path, $document->document_name);
    }
}