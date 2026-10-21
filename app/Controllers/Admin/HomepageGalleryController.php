<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Repositories\HomepageGalleryRepository;
use RuntimeException;

final class HomepageGalleryController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.gallery.index', [
            'title'  => 'Homepage Gallery',
            'images' => (new HomepageGalleryRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.gallery.form', ['title' => 'Add Image', 'image' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        $file = $request->file('image');
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Session::flash('errors', ['image' => ['Please choose an image to upload.']]);

            return $this->redirectRoute('admin.homepage.gallery.create');
        }

        try {
            $path = Uploader::storeImage($file, 'homepage-gallery');
        } catch (RuntimeException $e) {
            Session::flash('errors', ['image' => [$e->getMessage()]]);

            return $this->redirectRoute('admin.homepage.gallery.create');
        }

        $repo = new HomepageGalleryRepository();
        $newId = $repo->create(
            $path,
            (string) $request->input('caption', '') ?: null,
            (int) $request->input('sort_order', 0),
            $request->has('is_active')
        );

        Audit::log('homepage.gallery_image_added', 'homepage', 'homepage_gallery_images', $newId, 'Added a homepage gallery image');
        Session::flash('status', 'Image added.');

        return $this->redirectRoute('admin.homepage.gallery');
    }

    public function edit(Request $request): Response
    {
        $image = (new HomepageGalleryRepository())->find((int) $request->parameter('id'));
        if ($image === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.gallery.form', ['title' => 'Edit Image', 'image' => $image], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageGalleryRepository();
        $existing = $repo->find($id);
        if ($existing === null) {
            throw new HttpException(404);
        }

        $newPath = null;
        $file = $request->file('image');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $newPath = Uploader::storeImage($file, 'homepage-gallery');
            } catch (RuntimeException $e) {
                Session::flash('errors', ['image' => [$e->getMessage()]]);

                return $this->redirectRoute('admin.homepage.gallery.edit', ['id' => $id]);
            }
        }

        $repo->update(
            $id,
            $newPath,
            (string) $request->input('caption', '') ?: null,
            (int) $request->input('sort_order', 0),
            $request->has('is_active')
        );

        if ($newPath !== null) {
            $this->unlinkImage((string) $existing['image_path']);
        }

        Audit::log('homepage.gallery_image_updated', 'homepage', 'homepage_gallery_images', $id, 'Updated a homepage gallery image');
        Session::flash('status', 'Image updated.');

        return $this->redirectRoute('admin.homepage.gallery');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageGalleryRepository();
        $image = $repo->find($id);
        if ($image === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        $this->unlinkImage((string) $image['image_path']);
        Audit::log('homepage.gallery_image_deleted', 'homepage', 'homepage_gallery_images', $id, 'Deleted a homepage gallery image');
        Session::flash('status', 'Image deleted.');

        return $this->redirectRoute('admin.homepage.gallery');
    }

    private function unlinkImage(string $relativePath): void
    {
        $path = PUBLIC_PATH . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
