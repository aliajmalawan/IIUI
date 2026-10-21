<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Repositories\SettingRepository;
use RuntimeException;

/**
 * About Section + CTA — single content blocks stored as 'homepage'-group
 * rows in the generic `settings` table (see Step 6.1's SiteSettingsController
 * for the pattern this reuses). Filtered here by key prefix so the two
 * homepage builder tabs each only touch their own fields.
 */
final class HomepageContentController extends Controller
{
    public function showAbout(Request $request): Response
    {
        return $this->view('admin.homepage.about', [
            'title'    => 'About Section',
            'settings' => $this->filtered('about_'),
        ], 'admin');
    }

    public function updateAbout(Request $request): Response
    {
        return $this->save($request, 'about_', 'admin.homepage.about');
    }

    public function showWelcome(Request $request): Response
    {
        return $this->view('admin.homepage.welcome', [
            'title'    => 'Welcome Section',
            'settings' => $this->filtered('welcome_'),
        ], 'admin');
    }

    public function updateWelcome(Request $request): Response
    {
        return $this->save($request, 'welcome_', 'admin.homepage.welcome');
    }

    public function showCta(Request $request): Response
    {
        return $this->view('admin.homepage.cta', [
            'title'    => 'Call To Action',
            'settings' => $this->filtered('cta_'),
        ], 'admin');
    }

    public function updateCta(Request $request): Response
    {
        return $this->save($request, 'cta_', 'admin.homepage.cta');
    }

    public function showFaculty(Request $request): Response
    {
        return $this->view('admin.homepage.faculty', [
            'title'    => 'Our Faculty Section',
            'settings' => $this->filtered('faculty_'),
        ], 'admin');
    }

    public function updateFaculty(Request $request): Response
    {
        return $this->save($request, 'faculty_', 'admin.homepage.faculty');
    }

    public function showCampus(Request $request): Response
    {
        return $this->view('admin.homepage.campus', [
            'title'    => 'Vibrant Campus Section',
            'settings' => $this->filtered('campus_'),
        ], 'admin');
    }

    public function updateCampus(Request $request): Response
    {
        return $this->save($request, 'campus_', 'admin.homepage.campus');
    }

    public function showSections(Request $request): Response
    {
        return $this->view('admin.homepage.sections', [
            'title'    => 'Section Titles',
            'settings' => $this->filtered('section_'),
        ], 'admin');
    }

    public function updateSections(Request $request): Response
    {
        return $this->save($request, 'section_', 'admin.homepage.sections');
    }

    /** @return array<int,array<string,mixed>> */
    private function filtered(string $prefix): array
    {
        $all = (new SettingRepository())->getByGroup('homepage');

        return array_values(array_filter($all, static fn (array $row) => str_starts_with($row['key_name'], $prefix)));
    }

    private function save(Request $request, string $prefix, string $redirectRoute): Response
    {
        $repo = new SettingRepository();
        $settings = $this->filtered($prefix);

        $values = [];
        $errors = [];

        foreach ($settings as $setting) {
            $key = (string) $setting['key_name'];
            $type = (string) $setting['type'];

            if ($type === 'image') {
                $file = $request->file($key);
                $hasNewFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                $wantsRemove = $request->input($key . '_remove') === '1';

                if (!$hasNewFile && !$wantsRemove) {
                    continue;
                }

                if ($hasNewFile) {
                    try {
                        $values[$key] = Uploader::storeImage($file, 'homepage');
                    } catch (RuntimeException $e) {
                        $errors[$key] = [$e->getMessage()];
                        continue;
                    }
                } else {
                    $values[$key] = '';
                }

                // Only unlink the old file once the new value (a fresh path,
                // or '' for a deliberate removal) is safely staged — never
                // delete on a failed upload above.
                $oldPath = (string) $setting['value'];
                if ($oldPath !== '') {
                    $this->unlinkImage($oldPath);
                }

                continue;
            }

            $values[$key] = trim((string) $request->input($key));
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);

            return $this->redirectRoute($redirectRoute);
        }

        if ($values !== []) {
            $repo->updateMany($values, (int) Auth::id());
            Audit::log('homepage.content_updated', 'homepage', 'settings', null, 'Updated homepage ' . rtrim($prefix, '_') . ' content', null, $values);
        }

        Session::flash('status', 'Content updated successfully.');

        return $this->redirectRoute($redirectRoute);
    }

    private function unlinkImage(string $relativePath): void
    {
        $path = PUBLIC_PATH . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
