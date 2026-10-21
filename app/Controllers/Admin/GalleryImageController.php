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
use App\Repositories\GalleryImageRepository;
use App\Repositories\GalleryRepository;
use RuntimeException;

final class GalleryImageController extends Controller
{
    public function index(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);

        return $this->view('admin.galleries.images', [
            'title'   => 'Manage Photos',
            'gallery' => $gallery,
            'images'  => (new GalleryImageRepository())->forGallery((int) $gallery['id']),
        ], 'admin');
    }

    /** Accepts multiple files at once (images[]) — a gallery is rarely just one photo. */
    public function store(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $galleryId = (int) $gallery['id'];

        $files = $request->file('images');
        $fileList = $this->normalizeFilesArray($files);

        if ($fileList === []) {
            Session::flash('errors', ['images' => ['Please choose at least one photo to upload.']]);

            return $this->redirectRoute('admin.galleries.images', ['id' => $galleryId]);
        }

        $repo = new GalleryImageRepository();
        $sortOrder = $repo->nextSortOrder($galleryId);
        $uploaded = 0;
        $failed = [];

        foreach ($fileList as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $path = Uploader::storeImage($file, 'galleries/' . $galleryId);
                $thumb = Uploader::thumbnail($path, 640);
                $newId = $repo->create($galleryId, $path, null, null, $sortOrder, $thumb !== $path ? $thumb : null);
                ++$sortOrder;
                ++$uploaded;

                // The gallery's first-ever photo becomes its cover automatically
                // if none was set — a bare album with no thumbnail looks broken.
                if ($gallery['cover_image'] === null && $uploaded === 1) {
                    (new GalleryRepository())->setCoverImage($galleryId, $path);
                }
            } catch (RuntimeException $e) {
                $failed[] = ($file['name'] ?? 'file') . ': ' . $e->getMessage();
            }
        }

        if ($uploaded > 0) {
            Audit::log('gallery.images_uploaded', 'gallery', 'galleries', $galleryId, "Uploaded {$uploaded} photo(s) to \"{$gallery['title']}\"");
        }

        if ($failed === []) {
            Session::flash('status', "{$uploaded} photo(s) uploaded.");
        } else {
            Session::flash('status', $uploaded > 0 ? "{$uploaded} photo(s) uploaded; some were skipped." : null);
            Session::flash('errors', ['images' => $failed]);
        }

        return $this->redirectRoute('admin.galleries.images', ['id' => $galleryId]);
    }

    public function storeVideo(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $galleryId = (int) $gallery['id'];

        $videoId = youtube_id((string) $request->input('video_url', ''));
        if ($videoId === null) {
            Session::flash('errors', ['images' => ['That does not look like a YouTube link. Paste a link such as https://www.youtube.com/watch?v=... or https://youtu.be/...']]);

            return $this->redirectRoute('admin.galleries.images', ['id' => $galleryId]);
        }

        $caption = trim((string) $request->input('caption', ''));
        $repo = new GalleryImageRepository();
        $repo->createVideo($galleryId, $videoId, $caption !== '' ? mb_substr($caption, 0, 255) : null, $repo->nextSortOrder($galleryId));
        Audit::log('gallery.video_added', 'gallery', 'galleries', $galleryId, "Added a video to \"{$gallery['title']}\"");
        Session::flash('status', 'Video added.');

        return $this->redirectRoute('admin.galleries.images', ['id' => $galleryId]);
    }

    public function edit(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $image = $this->resolveImage($request, (int) $gallery['id']);

        return $this->view('admin.galleries.image-form', ['title' => 'Edit Photo', 'gallery' => $gallery, 'image' => $image], 'admin');
    }

    public function update(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $image = $this->resolveImage($request, (int) $gallery['id']);

        (new GalleryImageRepository())->updateDetails(
            (int) $image['id'],
            (string) $request->input('caption', '') ?: null,
            (string) $request->input('alt_text', '') ?: null,
            (int) $request->input('sort_order', 0)
        );

        Session::flash('status', 'Photo updated.');

        return $this->redirectRoute('admin.galleries.images', ['id' => $gallery['id']]);
    }

    public function destroy(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $image = $this->resolveImage($request, (int) $gallery['id']);

        (new GalleryImageRepository())->delete((int) $image['id']);
        Audit::log('gallery.image_deleted', 'gallery', 'gallery_images', (int) $image['id'], "Deleted a photo from \"{$gallery['title']}\"");
        Session::flash('status', 'Photo deleted.');

        return $this->redirectRoute('admin.galleries.images', ['id' => $gallery['id']]);
    }

    public function setCover(Request $request): Response
    {
        $gallery = $this->resolveGallery($request);
        $image = $this->resolveImage($request, (int) $gallery['id']);

        (new GalleryRepository())->setCoverImage((int) $gallery['id'], (string) $image['image_path']);
        Session::flash('status', 'Cover photo updated.');

        return $this->redirectRoute('admin.galleries.images', ['id' => $gallery['id']]);
    }

    private function resolveGallery(Request $request): array
    {
        $gallery = (new GalleryRepository())->find((int) $request->parameter('id'));
        if ($gallery === null) {
            throw new HttpException(404);
        }

        return $gallery;
    }

    private function resolveImage(Request $request, int $galleryId): array
    {
        $image = (new GalleryImageRepository())->find((int) $request->parameter('imageId'));
        if ($image === null || (int) $image['gallery_id'] !== $galleryId) {
            throw new HttpException(404);
        }

        return $image;
    }

    /** Normalizes PHP's "array of files" $_FILES shape into a flat list of single-file arrays. */
    private function normalizeFilesArray(mixed $files): array
    {
        if ($files === null || !is_array($files)) {
            return [];
        }

        // A single <input type="file" name="images"> (no []) arrives as one flat array.
        if (isset($files['name']) && !is_array($files['name'])) {
            return [$files];
        }

        // <input type="file" name="images[]" multiple> arrives as parallel arrays.
        $count = count($files['name'] ?? []);
        $list = [];
        for ($i = 0; $i < $count; $i++) {
            $list[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }

        return $list;
    }
}
