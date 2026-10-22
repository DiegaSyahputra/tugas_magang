<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingMessageController extends Controller
{
    // Kirim pesan chat baru
    public function store(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $meeting->messages()->create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    // Endpoint JSON untuk polling chat (dipanggil tiap beberapa detik lewat JS)
    public function index(Meeting $meeting)
    {
        $messages = $meeting->messages()
            ->oldest()
            ->orderBy('id', 'asc')
            ->get(['id', 'sender_name', 'message', 'created_at']);

        return response()->json($messages);
    }
}
