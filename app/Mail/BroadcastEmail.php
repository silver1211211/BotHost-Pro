<?php

namespace App\Mail;

use App\Models\AdminBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class BroadcastEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly AdminBroadcast $broadcast) {}

    public function build(): self
    {
        $mail = $this->subject($this->broadcast->title);
        $message = (string) $this->broadcast->message;

        if ($this->broadcast->message_type === 'html') {
            return $mail->html($this->sanitizeHtml($message));
        }

        return $mail->text('emails.broadcast-text', [
            'title' => $this->broadcast->title,
            'body' => $message,
        ]);
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';

        return Str::of($html)->limit(50000, '')->toString();
    }
}
