<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * AES-256-GCM at-rest encryption for the handful of columns docs/05-security.md
 * §"Encryption at rest" names explicitly (CNIC, bank account, 2FA secrets) —
 * the key lives in APP_KEY (.env), separate from the database, so a stolen
 * DB dump alone doesn't reveal them.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    /** Returns iv + auth tag + ciphertext packed into one binary string, ready for a VARBINARY column. */
    public static function encrypt(string $plaintext): string
    {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return $iv . $tag . $ciphertext;
    }

    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($payload, 0, $ivLength);
        $tag = substr($payload, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($payload, $ivLength + self::TAG_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);

        return $plaintext === false ? null : $plaintext;
    }

    /** Deterministic HMAC for equality lookups ("is this CNIC already on file?") without decrypting every row. */
    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, self::key());
    }

    private static function key(): string
    {
        $configured = (string) config('app.key', '');
        if ($configured === '') {
            throw new RuntimeException('APP_KEY is not set in .env — required to encrypt sensitive staff data.');
        }

        // Derive a 32-byte binary key from whatever APP_KEY holds, so its
        // stored form (base64, plain string, etc.) doesn't matter here.
        return hash('sha256', $configured, true);
    }
}
