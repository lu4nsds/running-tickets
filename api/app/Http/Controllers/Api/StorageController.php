<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    public function serve(string $path)
    {
        if (!Storage::exists($path)) {
            abort(404);
        }

        $content = Storage::get($path);
        $mimeType = Storage::mimeType($path) ?: 'application/octet-stream';

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
