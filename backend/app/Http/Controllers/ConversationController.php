<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index()
    {
        return Conversation::with('personage')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'personage_id' => 'required|exists:personages,id',
            'titel' => 'required|string|max:255',
            'tree' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        return Conversation::create($validated);
    }

    public function show(Conversation $conversation)
    {
        $conversation->load('personage');
        return response()->json($conversation);
    }

    public function update(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'personage_id' => 'exists:personages,id',
            'titel' => 'string|max:255',
            'tree' => 'array',
            'is_active' => 'boolean',
        ]);

        $conversation->update($validated);
        return $conversation;
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->delete();
        return response()->noContent();
    }
}
