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
use App\Core\Validator;
use App\Repositories\GalleryRepository;
use RuntimeException;

final class GalleryController extends Controller
{
    private const STATUSES = ['draft', 'published', 'archived'];

    public function index(Request $request): Response
    {
        return $this->view('admin.galleries.index', [
            'title'     => 'Galleries',
            'galleries' => (new GalleryRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.galleries.form', ['title' => 'Add Gallery', 'gallery' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $gallery = (new GalleryRepository())->find((int) $request->parameter('id'));
        if ($gallery === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.galleries.form', ['title' => 'Edit Gallery', 'gallery' => $gallery], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new GalleryRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new GalleryRepository();
        $gallery = $repo->find($id);
        if ($gallery === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('gallery.deleted', 'gallery', 'galleries', $id, "Deleted gallery \"{$gallery['title']}\" and its images");
        Session::flash('status', 'Gallery deleted.');

        return $this->redirectRoute('admin.galleries');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new GalleryRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:180',
        ]);
        $errors = $validator->errors();

        $status = (string) $request->input('status', 'draft');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'draft';
        }

        $galleryDate = (string) $request->input('gallery_date', '');
        if ($galleryDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $galleryDate)) {
            $errors['gallery_date'] = ['Please enter a valid date.'];
        }

        $requestedSlug = (string) $request->input('slug', '');
        $baseSlug = slugify($requestedSlug !== '' ? $requestedSlug : (string) $request->input('title', ''));
        $slug = $repo->uniqueSlug($baseSlug, $id);

        $coverPath = $existing['cover_image'] ?? null;
        $file = $request->file('cover_image');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverPath = Uploader::storeImage($file, 'galleries/covers');
            } catch (RuntimeException $e) {
                $errors['cover_image'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.galleries.create')
                : $this->redirectRoute('admin.galleries.edit', ['id' => $id]);
        }

        $data = [
            'title'        => (string) $request->input('title'),
            'slug'         => $slug,
            'description'  => (string) $request->input('description', ''),
            'cover_image'  => $coverPath,
            'gallery_date' => $galleryDate,
            'category'     => (string) $request->input('category', ''),
            'status'       => $status,
            'sort_order'   => (int) $request->input('sort_order', 0),
            'created_by'   => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('gallery.created', 'gallery', 'galleries', $newId, "Created gallery \"{$data['title']}\"");
            Session::flash('status', 'Gallery created. Now add some photos to it.');

            return $this->redirectRoute('admin.galleries.images', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('gallery.updated', 'gallery', 'galleries', $id, "Updated gallery \"{$data['title']}\"");
        Session::flash('status', 'Gallery updated.');

        return $this->redirectRoute('admin.galleries.edit', ['id' => $id]);
    }
}
