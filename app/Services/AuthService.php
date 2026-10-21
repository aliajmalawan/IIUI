<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Hash;
use App\Core\Logger;
use App\Core\Mailer;
use App\Repositories\UserRepository;

/**
 * Login and password-reset business logic — docs/05-security.md §2–3.
 * Controllers call this; nothing here touches $_POST/$_SESSION directly.
 */
final class AuthService
{
    private const RESET_TOKEN_MINUTES = 60;

    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    /**
     * @return array{0: bool, 1: string} [success, error message when it failed]
     */
    public function attempt(string $identifier, string $password, string $ip, string $userAgent): array
    {
        $ipLimit = (array) config('security.throttle.login_ip', ['max_attempts' => 10, 'decay_minutes' => 60]);

        if ($this->users->recentFailedAttemptsFromIp($ip, (int) $ipLimit['decay_minutes']) >= (int) $ipLimit['max_attempts']) {
            return [false, 'Too many failed attempts from this location. Please try again later.'];
        }

        $accountLimit = (array) config('security.throttle.login', ['max_attempts' => 5, 'decay_minutes' => 15]);

        // Always the same generic message on failure — never confirm which
        // accounts exist (docs/05-security.md §2).
        $genericError = 'Invalid credentials.';

        $user = $this->users->findByIdentifier($identifier);

        if ($user === null) {
            $this->users->logAttempt($identifier, $ip, $userAgent, false, 'no_such_user');

            return [false, $genericError];
        }

        if ($this->users->isLockedOut($user)) {
            $this->users->logAttempt($identifier, $ip, $userAgent, false, 'locked');

            return [false, 'This account is temporarily locked due to repeated failed attempts. Please try again later.'];
        }

        if ($user['status'] !== 'active') {
            $this->users->logAttempt($identifier, $ip, $userAgent, false, 'inactive');

            return [false, $genericError];
        }

        if (!Hash::verify($password, $user['password_hash'])) {
            $this->users->incrementFailedAttempts(
                (int) $user['id'],
                (int) $user['failed_attempts'],
                (int) $accountLimit['max_attempts'],
                (int) $accountLimit['decay_minutes']
            );
            $this->users->logAttempt($identifier, $ip, $userAgent, false, 'bad_password');

            return [false, $genericError];
        }

        if (Hash::needsRehash($user['password_hash'])) {
            $this->users->updatePasswordHash((int) $user['id'], Hash::make($password));
        }

        $this->users->recordSuccessfulLogin((int) $user['id'], $ip);
        $this->users->logAttempt($identifier, $ip, $userAgent, true);

        Auth::login($user);
        Audit::log('auth.login', 'auth', 'users', (int) $user['id'], 'User logged in');

        return [true, ''];
    }

    public function logout(): void
    {
        Audit::log('auth.logout', 'auth', 'users', Auth::id(), 'User logged out');
        Auth::logout();
    }

    /**
     * Deliberately silent when the email doesn't exist — the caller always
     * shows the same "if that address is registered…" message either way.
     */
    public function sendResetLink(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->users->createPasswordReset($email, $tokenHash, self::RESET_TOKEN_MINUTES, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        $resetUrl = base_url('reset-password/' . $token) . '?email=' . urlencode($email);

        Mailer::send(
            $email,
            'Reset your password',
            '<p>Click the link below to reset your password. This link expires in '
                . self::RESET_TOKEN_MINUTES . ' minutes.</p>'
            . '<p><a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a></p>'
            . '<p>If you did not request this, you can safely ignore this email.</p>'
        );
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public function resetPassword(string $token, string $password): array
    {
        $tokenHash = hash('sha256', $token);
        $reset = $this->users->findValidPasswordReset($tokenHash);

        if ($reset === null) {
            return [false, 'This password reset link is invalid or has expired.'];
        }

        $user = $this->users->findByEmail((string) $reset['email']);
        if ($user === null) {
            return [false, 'This password reset link is invalid or has expired.'];
        }

        $this->users->updatePasswordHash((int) $user['id'], Hash::make($password));
        $this->users->markPasswordResetUsed((int) $reset['id']);

        Logger::info('Password reset completed', ['user_id' => $user['id']]);
        Audit::log('auth.password_reset', 'auth', 'users', (int) $user['id'], 'Password reset via emailed link');

        return [true, ''];
    }
}
