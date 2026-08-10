<?php

namespace App\Http\Controllers;

use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = Library::latest();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('category', 'like', "%{$searchTerm}%");
            });
        }

        $resources = $query->get();

        return view('library.index', compact('resources'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'file_format' => 'required|string|max:100',
            'cover_icon' => 'nullable|string|max:10',
            'file' => 'required|file|max:20480', // Max 20MB
        ]);

        $filePath = null;
        $fileSize = '0 MB';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $sizeInBytes = $file->getSize();
            $fileSize = round($sizeInBytes / 1024 / 1024, 1) . ' MB';

            // Store in storage/app/public/library_files
            $storedPath = $file->store('library_files', 'public');
            $filePath = $storedPath;
        }

        Library::create([
            'title' => $request->title,
            'category' => $request->category,
            'file_format' => $request->file_format,
            'file_size' => $fileSize,
            'file_path' => $filePath,
            'cover_icon' => $request->cover_icon ?? '📚',
            'downloads' => 0,
            'rating' => 5.0,
        ]);

        return redirect()->route('library.index')->with('success', 'Materi digital berhasil diunggah dan disimpan ke database!');
    }

    public function download(Library $library)
    {
        if ($library->file_path && Storage::disk('public')->exists($library->file_path)) {
            $library->increment('downloads');
            return Storage::disk('public')->download($library->file_path, $library->title . '.' . pathinfo($library->file_path, PATHINFO_EXTENSION));
        }

        return redirect()->back()->with('error', 'Berkas materi tidak ditemukan pada direktori storage proyek.');
    }
}
