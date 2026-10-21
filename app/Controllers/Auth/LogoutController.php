<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class LogoutController extends Controller
{
    /** POST only — a GET logout is CSRF-abusable (docs/04-routing-api.md §3). */
    public function destroy(Request $request): Response
    {
        (new AuthService())->logout();

        return $this->redirect(base_url());
    }
}
