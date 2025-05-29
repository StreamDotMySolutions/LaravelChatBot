<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatHistory;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $chatId = $request->input('chat_id');
        $userMessage = $request->input('text');

        if (!$chatId || !$userMessage) {
            return response()->json(['error' => 'chat_id or text missing'], 400);
        }

        // Simpan mesej user
        ChatHistory::create([
            'chat_id' => $chatId,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // // Ambil mesej lepas untuk context (10 terbaru)
        // $history = ChatHistory::where('chat_id', $chatId)
        //             ->orderBy('created_at', 'asc')
        //             ->take(10)
        //             ->get(['role', 'content'])
        //             ->toArray();
        $history = ChatHistory::where('chat_id', $chatId)
                        ->orderBy('created_at', 'desc')  // ambil yang terbaru
                        ->take(10)
                        ->get(['role', 'content'])
                        ->reverse()                      // susun semula ikut masa
                        ->values()
                        ->toArray();


        // $messages = array_map(function ($row) {
        //     return ['role' => $row['role'], 'content' => $row['content']];
        // }, $history);

        $messages = [];

        foreach ($history as $row) {
            $messages[] = [
                'role' => $row['role'],
                'content' => $row['content'],
            ];
        }

        // Hantar ke OpenAI
        $openaiResponse = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => $messages,
        ]);

        if (!$openaiResponse->ok()) {
            return response()->json([
                'error' => 'OpenAI API error',
                'details' => $openaiResponse->body()
            ], 500);
        }

        $reply = $openaiResponse->json('choices.0.message.content');

        // Simpan balasan bot
        ChatHistory::create([
            'chat_id' => $chatId,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        // Balas ke N8N
        return response()->json(['reply' => $reply]);
    }
}
