<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    /**
     * Show document in browser (untuk view)
     */
    public function showDocument(ApplicationDocument $document)
    {
        // Security check - hanya validator dan admin yang bisa akses
        if (!Auth::user() || !in_array(Auth::user()->role, ['validator', 'admin'])) {
            abort(403, 'Unauthorized access');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan');
        }

        $filePath = storage_path('app/public/' . $document->file_path);
        
        // Get file info
        $mimeType = Storage::disk('public')->mimeType($document->file_path);
        $fileSize = Storage::disk('public')->size($document->file_path);
        
        // Log access for debugging
        \Log::info('Document accessed:', [
            'document_id' => $document->id,
            'file_path' => $document->file_path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'accessor' => Auth::user()->email
        ]);

        // Untuk PDF, gambar, dan file yang bisa ditampilkan di browser
        if (in_array($mimeType, [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain'
        ])) {
            return Response::file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $document->document_name . '"'
            ]);
        }

        // Untuk file lain, paksa download
        return $this->downloadDocument($document);
    }

    /**
     * Download document
     */
    public function downloadDocument(ApplicationDocument $document)
    {
        // Security check - hanya validator dan admin yang bisa akses
        if (!Auth::user() || !in_array(Auth::user()->role, ['validator', 'admin'])) {
            abort(403, 'Unauthorized access');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan');
        }

        $filePath = storage_path('app/public/' . $document->file_path);
        
        // Log download for debugging
        \Log::info('Document downloaded:', [
            'document_id' => $document->id,
            'file_path' => $document->file_path,
            'downloader' => Auth::user()->email
        ]);

        return Response::download($filePath, $document->document_name);
    }

    /**
     * Debug file info (hanya untuk development)
     */
    public function debugFile(ApplicationDocument $document)
    {
        if (!config('app.debug')) {
            abort(404);
        }

        $data = [
            'document' => [
                'id' => $document->id,
                'name' => $document->document_name,
                'type' => $document->document_type,
                'file_path' => $document->file_path,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ],
            'storage_info' => [
                'public_path' => storage_path('app/public'),
                'full_path' => storage_path('app/public/' . $document->file_path),
                'file_exists' => file_exists(storage_path('app/public/' . $document->file_path)),
                'storage_exists' => Storage::disk('public')->exists($document->file_path),
                'is_readable' => file_exists(storage_path('app/public/' . $document->file_path)) ? 
                    is_readable(storage_path('app/public/' . $document->file_path)) : false,
            ],
            'file_info' => file_exists(storage_path('app/public/' . $document->file_path)) ? [
                'size' => filesize(storage_path('app/public/' . $document->file_path)),
                'mime_type' => mime_content_type(storage_path('app/public/' . $document->file_path)),
                'modified' => date('Y-m-d H:i:s', filemtime(storage_path('app/public/' . $document->file_path))),
                'permissions' => substr(sprintf('%o', fileperms(storage_path('app/public/' . $document->file_path))), -4),
            ] : null,
            'directory_info' => [
                'public_dir_exists' => is_dir(storage_path('app/public')),
                'public_dir_readable' => is_readable(storage_path('app/public')),
                'document_dir' => dirname(storage_path('app/public/' . $document->file_path)),
                'document_dir_exists' => is_dir(dirname(storage_path('app/public/' . $document->file_path))),
            ]
        ];

        return response()->json($data, 200, [], JSON_PRETTY_PRINT);
    }
}