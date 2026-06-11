<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Database\Seeder;

/**
 * Surgical patch: replaces ONLY the imageStates block inside the
 * Invest Bot Dollars Direct Message Handler.
 *
 * Safe to run at any time. All other commands and all other parts of
 * the DM handler are left exactly as they are in the database.
 *
 * Run with:
 *   php artisan db:seed --class=PatchInvestBotImageSeeder
 */
class PatchInvestBotImageSeeder extends Seeder
{
    public function run(): void
    {
        $bot = Bot::where('name', 'Invest Bot Dollars')->first();

        if (! $bot) {
            $this->command->error('Bot "Invest Bot Dollars" not found. Nothing changed.');
            return;
        }

        $cmd = BotCommand::where('bot_id', $bot->id)
            ->where('command_name', '__direct_message_handler_investbot')
            ->first();

        if (! $cmd) {
            $this->command->error('Direct Message Handler command not found. Nothing changed.');
            return;
        }

        $original = (string) $cmd->code;
        $patched  = $this->patchImageBlock($original);

        if ($patched === null) {
            $this->command->error(
                "Could not locate 'const imageStates' or 'if (imageStates[adminState])' ".
                "in the DM handler.\n".
                'Apply the image block fix manually via the bot code editor.'
            );
            return;
        }

        if ($patched === $original) {
            $this->command->info('Image block already up to date — no changes made.');
            return;
        }

        $cmd->code = $patched;
        $cmd->save();

        $this->command->info('✅ DM handler image block patched. All other commands are untouched.');
    }

    // -------------------------------------------------------------------------

    /**
     * Find and replace the imageStates block in the DM handler JS code.
     *
     * Uses PHP-level brace counting instead of regex so that nested { }
     * inside the if block are handled correctly regardless of their exact content.
     *
     * @return string|null  Null if the block was not found.
     */
    private function patchImageBlock(string $code): ?string
    {
        // ── Step 1: locate "const imageStates = {" ───────────────────────────
        $constNeedle = 'const imageStates = {';
        $constPos    = strpos($code, $constNeedle);

        if ($constPos === false) {
            return null;
        }

        // ── Step 2: walk back to the start of the block ───────────────────────
        // Include any "// ── IMAGE URLS" comment on the preceding line.
        $lineStart  = (int) (strrpos(substr($code, 0, $constPos), "\n") + 1);
        $blockStart = $lineStart;

        // Look one line further back for a leading comment
        if ($lineStart > 0) {
            $prevLineEnd = strrpos(rtrim(substr($code, 0, $lineStart - 1), "\r\n"), "\n");
            if ($prevLineEnd !== false) {
                $prevLine = substr($code, $prevLineEnd + 1, $lineStart - $prevLineEnd - 2);
                if (str_contains($prevLine, '//')) {
                    $blockStart = $prevLineEnd + 1;
                }
            }
        }

        // ── Step 3: locate "if (imageStates[adminState]) {" ─────────────────
        $ifNeedle = 'if (imageStates[adminState]) {';
        $ifPos    = strpos($code, $ifNeedle, $constPos);

        if ($ifPos === false) {
            return null;
        }

        // ── Step 4: count braces to find the matching closing } ───────────────
        // Start counting from the opening { on the if line.
        $openBracePos = strpos($code, '{', $ifPos + strlen($ifNeedle) - 1);

        if ($openBracePos === false) {
            return null;
        }

        $depth  = 0;
        $endPos = null;
        $len    = strlen($code);

        for ($i = $openBracePos; $i < $len; $i++) {
            $ch = $code[$i];
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $endPos = $i + 1; // include the closing }
                    break;
                }
            }
        }

        if ($endPos === null) {
            return null;
        }

        // ── Step 5: stitch the patched code together ──────────────────────────
        return substr($code, 0, $blockStart)
             . $this->fixedImageBlock()
             . substr($code, $endPos);
    }

    /**
     * The replacement imageStates block.
     *
     * Changes vs. old version:
     *  – Validates empty URL (not just wrong prefix).
     *  - Error message requires https://.
     *  – Uses named key variable instead of re-indexing imageStates each time.
     *  – Tries sendPhoto preview after saving; sendPhoto now returns {ok} so
     *    checking result.ok is safe and will NOT crash the runtime.
     *  – Falls back to plain-text confirmation when Telegram rejects the URL.
     *  – Shows Admin Panel inline button after saving so admin returns to panel.
     */
    private function fixedImageBlock(): string
    {
        // NOTE: closing marker FIXED; is at column 0 (valid pre-7.3 nowdoc syntax
        // and also valid 7.3+ flexible nowdoc).  The 2-space indent of JS lines
        // is preserved as-is because the closing marker has 0 indent.
        return <<<'FIXED'
  // ── IMAGE URLS ───────────────────────────────────────────────────────────
  const imageStates = {
    admin_set_welcome_image:    'welcome_image_url',
    admin_set_balance_image:    'balance_image_url',
    admin_set_deposit_image:    'deposit_image_url',
    admin_set_investment_image: 'investment_image_url',
    admin_set_withdraw_image:   'withdraw_image_url',
  };
  if (imageStates[adminState]) {
    const key = imageStates[adminState];
    const url = msgText;
    if (!url || !url.startsWith('https://')) {
      await replyHTML('⚠️ URL must start with https://\n\nSend the image URL or /cancel to go back.');
      return;
    }
    await setBotData(key, url);
    await clearAdmin();
    const backKb = inlineMenu([[button('⚙️ Admin Panel', '/admin')]]);
    // sendPhoto returns {ok} since platform fix — safe to check result here
    const preview = await sendPhoto(chat.id, url, {
      caption: `✅ <b>Image saved.</b>\n\n<code>${escapeHTML(url)}</code>`,
      parse_mode: 'HTML',
      reply_markup: backKb.reply_markup,
    });
    if (!preview.ok) {
      await replyHTML(
        `✅ Image URL saved:\n<code>${escapeHTML(url)}</code>\n\n<i>Preview unavailable. The URL will be used when the section is opened.</i>`,
        backKb,
      );
    }
    return;
  }
FIXED;
    }
}
