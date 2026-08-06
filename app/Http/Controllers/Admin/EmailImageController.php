<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailImageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $file = $request->file('image');
        $filename = 'email-'.Str::random(20).'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('public/email-images', $filename);

        $url = Storage::url($path);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
