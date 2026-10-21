<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuthService;

final class PasswordResetController extends Controller
{
    public function showRequest(Request $request): Response
    {
        return $this->view('auth.forgot-password', ['title' => 'Forgot Password'], 'auth');
    }

    public function sendLink(Request $request): Response
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());

            return $this->redirectRoute('password.request');
        }

        (new AuthService())->sendResetLink((string) $request->input('email'));

        // Same message whether or not the address is registered — docs/05-security.md §2.
        Session::flash('status', 'If that email address is registered, a reset link has been sent.');

        return $this->redirectRoute('password.request');
    }

    public function showReset(Request $request): Response
    {
        return $this->view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $request->parameter('token'),
            'email' => $request->query('email', ''),
        ], 'auth');
    }

    public function update(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:10|strong_password|confirmed',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());

            return $this->redirectRoute('password.reset', ['token' => (string) $request->input('token')]);
        }

        [$success, $error] = (new AuthService())->resetPassword(
            (string) $request->input('token'),
            (string) $request->input('password')
        );

        if (!$success) {
            Session::flash('errors', ['token' => [$error]]);

            return $this->redirectRoute('password.request');
        }

        Session::flash('status', 'Your password has been reset. You can now log in.');

        return $this->redirectRoute('login');
    }
}
