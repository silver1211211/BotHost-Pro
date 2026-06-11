<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── /admin command (id 49) ──────────────────────────────────────────

        $admin = DB::table('bot_commands')->where('id', 49)->first();
        if ($admin) {
            $code = $admin->code;

            // 1. Replace the faucetKey getBotData read in the "home" action
            $code = str_replace(
                ' const faucetKey = await getBotData("faucetpay_api_key", null);',
                ' const faucetKeyMasked = await getBotData("faucetpay_api_key_masked", null);',
                $code
            );

            // 2. Replace the display line in the home panel
            $code = str_replace(
                '• <b>API Key:</b> ${faucetKey ? shortText(faucetKey, 8) + "***" : "Not set"}',
                '• <b>API Key:</b> ${faucetKeyMasked ? code(faucetKeyMasked) : "Not set"}',
                $code
            );

            // 3. Replace the faucetpay_balance action guard
            $code = str_replace(
                ' const apiKey = await getBotData("faucetpay_api_key", null);' . "\n" .
                ' if (!apiKey) {',
                ' const apiKeyStatus = await getBotData("faucetpay_api_key_status", null);' . "\n" .
                ' if (!apiKeyStatus) {',
                $code
            );

            DB::table('bot_commands')->where('id', 49)->update(['code' => $code]);
        }

        // ── DM handler command (id 52) ──────────────────────────────────────

        $dm = DB::table('bot_commands')->where('id', 52)->first();
        if ($dm) {
            $code = $dm->code;

            // 4. On validation failure (test.ok check): clear masked + status alongside raw key
            $code = str_replace(
                ' if (!test || !test.ok) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await replyHTML(',
                ' if (!test || !test.ok) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", null);' . "\n" .
                ' await replyHTML(',
                $code
            );

            // 5. On exception (catch block): clear masked + status alongside raw key
            $code = str_replace(
                ' } catch (e) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`❌ <b>API Key Validation Failed</b>' . "\n" .
                '${escapeHTML(e.message || String(e))}',
                ' } catch (e) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`❌ <b>API Key Validation Failed</b>' . "\n" .
                '${escapeHTML(e.message || String(e))}',
                $code
            );

            // 6. On success: save masked key and status before clearing admin_state
            $code = str_replace(
                ' await setUserData("admin_state", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`✅ <b>FaucetPay API Key Updated</b>' . "\n" .
                'Your API key has been validated and saved securely.`' . "\n" .
                ' );' . "\n" .
                ' await runCommand("/admin");' . "\n" .
                ' return;' . "\n" .
                ' }',
                ' const maskedKey = text.length <= 8 ? text.slice(0, 2) + "***" : text.slice(0, 5) + "***" + text.slice(-3);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", maskedKey);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", "saved");' . "\n" .
                ' await setUserData("admin_state", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`✅ <b>FaucetPay API Key Updated</b>' . "\n" .
                'Your API key has been validated and saved securely.`' . "\n" .
                ' );' . "\n" .
                ' await runCommand("/admin");' . "\n" .
                ' return;' . "\n" .
                ' }',
                $code
            );

            // 7. Withdrawal check: use faucetpay_api_key_status instead of raw key
            $code = str_replace(
                ' const apiKey = await getBotData("faucetpay_api_key", null);',
                ' const apiKeyStatus = await getBotData("faucetpay_api_key_status", null);',
                $code
            );
            $code = str_replace(
                ' if (!apiKey) {' . "\n" .
                ' await setUserData("awaiting_withdraw_amount", false);' . "\n" .
                ' await replyHTML(',
                ' if (!apiKeyStatus) {' . "\n" .
                ' await setUserData("awaiting_withdraw_amount", false);' . "\n" .
                ' await replyHTML(',
                $code
            );

            DB::table('bot_commands')->where('id', 52)->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        // ── /admin command (id 49) ──────────────────────────────────────────

        $admin = DB::table('bot_commands')->where('id', 49)->first();
        if ($admin) {
            $code = $admin->code;

            $code = str_replace(
                ' const faucetKeyMasked = await getBotData("faucetpay_api_key_masked", null);',
                ' const faucetKey = await getBotData("faucetpay_api_key", null);',
                $code
            );
            $code = str_replace(
                '• <b>API Key:</b> ${faucetKeyMasked ? code(faucetKeyMasked) : "Not set"}',
                '• <b>API Key:</b> ${faucetKey ? shortText(faucetKey, 8) + "***" : "Not set"}',
                $code
            );
            $code = str_replace(
                ' const apiKeyStatus = await getBotData("faucetpay_api_key_status", null);' . "\n" .
                ' if (!apiKeyStatus) {',
                ' const apiKey = await getBotData("faucetpay_api_key", null);' . "\n" .
                ' if (!apiKey) {',
                $code
            );

            DB::table('bot_commands')->where('id', 49)->update(['code' => $code]);
        }

        // ── DM handler command (id 52) ──────────────────────────────────────

        $dm = DB::table('bot_commands')->where('id', 52)->first();
        if ($dm) {
            $code = $dm->code;

            $code = str_replace(
                ' if (!test || !test.ok) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", null);' . "\n" .
                ' await replyHTML(',
                ' if (!test || !test.ok) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await replyHTML(',
                $code
            );
            $code = str_replace(
                ' } catch (e) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", null);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`❌ <b>API Key Validation Failed</b>' . "\n" .
                '${escapeHTML(e.message || String(e))}',
                ' } catch (e) {' . "\n" .
                ' await setBotData("faucetpay_api_key", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`❌ <b>API Key Validation Failed</b>' . "\n" .
                '${escapeHTML(e.message || String(e))}',
                $code
            );
            $code = str_replace(
                ' const maskedKey = text.length <= 8 ? text.slice(0, 2) + "***" : text.slice(0, 5) + "***" + text.slice(-3);' . "\n" .
                ' await setBotData("faucetpay_api_key_masked", maskedKey);' . "\n" .
                ' await setBotData("faucetpay_api_key_status", "saved");' . "\n" .
                ' await setUserData("admin_state", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`✅ <b>FaucetPay API Key Updated</b>' . "\n" .
                'Your API key has been validated and saved securely.`' . "\n" .
                ' );' . "\n" .
                ' await runCommand("/admin");' . "\n" .
                ' return;' . "\n" .
                ' }',
                ' await setUserData("admin_state", null);' . "\n" .
                ' await replyHTML(' . "\n" .
                '`✅ <b>FaucetPay API Key Updated</b>' . "\n" .
                'Your API key has been validated and saved securely.`' . "\n" .
                ' );' . "\n" .
                ' await runCommand("/admin");' . "\n" .
                ' return;' . "\n" .
                ' }',
                $code
            );
            $code = str_replace(
                ' const apiKeyStatus = await getBotData("faucetpay_api_key_status", null);',
                ' const apiKey = await getBotData("faucetpay_api_key", null);',
                $code
            );
            $code = str_replace(
                ' if (!apiKeyStatus) {' . "\n" .
                ' await setUserData("awaiting_withdraw_amount", false);' . "\n" .
                ' await replyHTML(',
                ' if (!apiKey) {' . "\n" .
                ' await setUserData("awaiting_withdraw_amount", false);' . "\n" .
                ' await replyHTML(',
                $code
            );

            DB::table('bot_commands')->where('id', 52)->update(['code' => $code]);
        }
    }
};
