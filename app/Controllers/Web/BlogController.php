<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PostRepository;

/** Public Blog — same posts table as News (post_type='blog'), no categories. */
final class BlogController extends Controller
{
    private const PER_PAGE = 9;
    private const TYPE = 'blog';

    public function index(Request $request): Response
    {
        $repo = new PostRepository();
        $page = max(1, (int) $request->query('page', 1));

        $posts = $repo->published(null, self::PER_PAGE, self::PER_PAGE * ($page - 1), self::TYPE);
        $total = $repo->countPublished(null, self::TYPE);

        return $this->view('web.blog.index', [
            'title'    => 'Blog',
            'posts'    => $posts,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / self::PER_PAGE)),
        ], 'web');
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $repo = new PostRepository();
        $post = $repo->findPublishedBySlug($slug, self::TYPE);

        if ($post === null) {
            throw new HttpException(404);
        }

        $repo->incrementViews((int) $post['id']);

        return $this->view('web.blog.show', [
            'title' => $post['title'],
            'post'  => $post,
            'metaTitle'       => (string) ($post['meta_title'] ?? ''),
            'metaDescription' => (string) ($post['meta_description'] ?: ($post['excerpt'] ?? '')),
            'metaImage'       => (string) ($post['og_image'] ?: ($post['featured_image'] ?? '')),
            'ogType'          => 'article',
        ], 'web');
    }
}
