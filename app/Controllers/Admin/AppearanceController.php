<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;

/**
 * Personal preference (theme) only — reachable from the profile dropdown.
 * Site-wide settings (school name, logo, contact, social links) live in
 * SiteSettingsController at /admin/settings/{group}.
 */
final class AppearanceController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('admin.appearance', ['title' => 'Appearance'], 'admin');
    }

    public function updateTheme(Request $request): Response
    {
        $theme = (string) $request->input('theme', 'system');

        if (!in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = 'system';
        }

        $userId = (int) Auth::id();
        (new UserRepository())->updateTheme($userId, $theme);
        Audit::log('profile.theme_changed', 'account', 'users', $userId, "Theme preference set to {$theme}");

        if ($request->wantsJson()) {
            return $this->json(['success' => true, 'theme' => $theme]);
        }

        Session::flash('status', 'Your appearance preference has been saved.');

        return $this->redirectRoute('admin.appearance');
    }
}
