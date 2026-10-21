<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Log-driver mailer for local development — docs/01-architecture.md §7
 * documents "Mail: Log driver / Mailtrap" for the Development environment.
 * No Composer is installed here, so PHPMailer isn't wired yet; swapping to
 * real SMTP later is a change inside send() only — no call site changes,
 * which is the entire reason this indirection exists.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $driver = (string) config('mail.driver', 'log');

        return match ($driver) {
            'log'   => self::sendToLog($to, $subject, $htmlBody),
            default => self::sendToLog($to, $subject, $htmlBody),
        };
    }

    private static function sendToLog(string $to, string $subject, string $htmlBody): bool
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/mail-' . date('Y-m-d') . '.log';

        $plainBody = trim((string) preg_replace('/\s*\n\s*/', "\n", strip_tags(
            str_replace(['<p>', '</p>', '<br>', '<br/>', '<br />'], ["\n", '', "\n", "\n", "\n"], $htmlBody)
        )));

        $entry = sprintf(
            "----- %s -----\nTo: %s\nSubject: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $plainBody
        );

        error_log($entry, 3, $file);

        return true;
    }
}
