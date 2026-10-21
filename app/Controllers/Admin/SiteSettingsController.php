<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Repositories\SettingRepository;
use RuntimeException;

/**
 * Site-wide settings — every group is rendered by the same generic,
 * metadata-driven form (type/label/description/options columns on
 * `settings` itself), so adding a group here is the entire job; the view
 * doesn't change. GROUPS is a whitelist, not a config source — a settings
 * row whose group_name isn't listed here simply has no tab and 404s if
 * requested directly.
 */
final class SiteSettingsController extends Controller
{
    private const GROUPS = [
        'general'         => 'General',
        'contact'         => 'Contact',
        'social'          => 'Social Links',
        'theme'           => 'Theme',
        'admission_form'  => 'Admission Form',
        'seo'             => 'SEO',
    ];

    public function index(Request $request): Response
    {
        return $this->redirectRoute('admin.settings.group', ['group' => 'general']);
    }

    public function show(Request $request): Response
    {
        $group = (string) $request->parameter('group');
        if (!array_key_exists($group, self::GROUPS)) {
            throw new HttpException(404);
        }

        return $this->view('admin.settings.index', [
            'title'    => 'Website Settings',
            'group'    => $group,
            'groups'   => self::GROUPS,
            'settings' => (new SettingRepository())->getByGroup($group),
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $group = (string) $request->parameter('group');
        if (!array_key_exists($group, self::GROUPS)) {
            throw new HttpException(404);
        }

        $repo = new SettingRepository();
        $settings = $repo->getByGroup($group);

        $values = [];
        $errors = [];

        foreach ($settings as $setting) {
            $key = (string) $setting['key_name'];
            $type = (string) $setting['type'];

            if ($type === 'image') {
                $file = $request->file($key);
                if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue; // no new file chosen — keep the existing value
                }

                try {
                    $values[$key] = Uploader::storeImage($file, 'branding');
                } catch (RuntimeException $e) {
                    $errors[$key] = [$e->getMessage()];
                }

                continue;
            }

            $raw = $request->input($key);

            $value = match ($type) {
                'boolean' => $request->has($key) ? '1' : '0',
                'number'  => is_numeric($raw) ? (string) $raw : '0',
                default   => trim((string) $raw),
            };

            if ($type === 'email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$key] = ['Must be a valid email address.'];

                continue;
            }
            if ($type === 'url' && $value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                $errors[$key] = ['Must be a valid URL.'];

                continue;
            }

            $values[$key] = $value;
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.settings.group', ['group' => $group]);
        }

        if ($values !== []) {
            $repo->updateMany($values, (int) Auth::id());
            Audit::log('settings.updated', 'settings', 'settings', null, "Updated {$group} settings", null, $values);
        }

        Session::flash('status', self::GROUPS[$group] . ' settings updated successfully.');

        return $this->redirectRoute('admin.settings.group', ['group' => $group]);
    }
}
