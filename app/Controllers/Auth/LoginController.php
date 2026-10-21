<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuthService;

final class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth.login', ['title' => 'Login'], 'auth');
    }

    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'password'   => 'required',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->only(['identifier']));

            return $this->redirectRoute('login');
        }

        [$success, $error] = (new AuthService())->attempt(
            (string) $request->input('identifier'),
            (string) $request->input('password'),
            $request->ip(),
            $request->userAgent()
        );

        if (!$success) {
            Session::flash('errors', ['identifier' => [$error]]);
            Session::flash('_old_input', $request->only(['identifier']));

            return $this->redirectRoute('login');
        }

        $intended = Session::get('_intended_url');
        Session::remove('_intended_url');

        return $this->redirect(is_string($intended) ? base_url($intended) : route('admin.dashboard'));
    }
}
