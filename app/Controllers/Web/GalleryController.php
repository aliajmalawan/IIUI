<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\GalleryImageRepository;
use App\Repositories\GalleryRepository;

final class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new GalleryRepository();
        $category = (string) $request->query('category', '');

        return $this->view('web.gallery.index', [
            'title'      => 'Gallery',
            'images'     => (new GalleryImageRepository())->publishedFlat($category !== '' ? $category : null),
            'categories' => $repo->publishedCategories(),
            'activeCategory' => $category,
        ], 'web');
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $gallery = (new GalleryRepository())->findPublishedBySlug($slug);

        if ($gallery === null) {
            throw new HttpException(404);
        }

        return $this->view('web.gallery.show', [
            'title'   => $gallery['title'],
            'gallery' => $gallery,
            'images'  => (new GalleryImageRepository())->forGallery((int) $gallery['id']),
        ], 'web');
    }
}
