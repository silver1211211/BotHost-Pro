<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\TelegramFileReference;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class BotTelegramFileController extends Controller
{
    public function show(Request $request, Bot $bot, string $fileHash, TelegramBotService $telegram): SymfonyResponse
    {
        abort_unless($bot->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $reference = TelegramFileReference::query()
            ->where('bot_id', $bot->id)
            ->where('file_hash', $fileHash)
            ->firstOrFail();

        $token = $this->decryptBotToken($bot);
        if (! filled($token)) {
            abort(404);
        }

        $download = $telegram->downloadFile($token, $reference->file_path);
        if (! ($download['ok'] ?? false)) {
            abort(404);
        }

        $reference->forceFill(['last_accessed_at' => now()])->save();

        return response($download['body'] ?? '', 200, [
            'Content-Type' => $download['content_type'] ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline; filename="'.basename($reference->file_path).'"',
        ]);
    }

    private function decryptBotToken(Bot $bot): ?string
    {
        $encrypted = $bot->getRawOriginal('token_encrypted');

        if (! filled($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }
}
