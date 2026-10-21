<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PostCategoryRepository;
use App\Repositories\PostRepository;

final class PostController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        return $this->listing($request, null);
    }

    public function category(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $category = (new PostCategoryRepository())->findBySlug($slug);

        if ($category === null) {
            throw new HttpException(404);
        }

        return $this->listing($request, $category);
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $repo = new PostRepository();
        $post = $repo->findPublishedBySlug($slug);

        if ($post === null) {
            throw new HttpException(404);
        }

        $repo->incrementViews((int) $post['id']);

        return $this->view('web.news.show', [
            'title' => $post['title'],
            'post'  => $post,
            'metaTitle'       => (string) ($post['meta_title'] ?? ''),
            'metaDescription' => (string) ($post['meta_description'] ?: ($post['excerpt'] ?? '')),
            'metaImage'       => (string) ($post['og_image'] ?: ($post['featured_image'] ?? '')),
            'ogType'          => 'article',
        ], 'web');
    }

    private function listing(Request $request, ?array $category): Response
    {
        $repo = new PostRepository();
        $page = max(1, (int) $request->query('page', 1));
        $categoryId = $category !== null ? (int) $category['id'] : null;

        $posts = $repo->published($categoryId, self::PER_PAGE, (self::PER_PAGE) * ($page - 1));
        $total = $repo->countPublished($categoryId);

        return $this->view('web.news.index', [
            'title'      => $category !== null ? $category['name'] : 'News',
            'posts'      => $posts,
            'categories' => (new PostCategoryRepository())->activeList(),
            'activeCategory' => $category,
            'page'       => $page,
            'lastPage'   => max(1, (int) ceil($total / self::PER_PAGE)),
        ], 'web');
    }
}
