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
use App\Repositories\PostRepository;
use RuntimeException;

/**
 * Blog — a second content stream on the same `posts` table as News
 * (PostRepository, post_type='blog'), so it gets the same TinyMCE editor,
 * image upload, scheduling, and SEO fields for free. Unlike News, Blog
 * posts have no category (post_categories is a News-specific taxonomy).
 */
final class BlogController extends Controller
{
    private const STATUSES = ['draft', 'published', 'scheduled', 'archived'];
    private const TYPE = 'blog';

    public function index(Request $request): Response
    {
        return $this->view('admin.blog.posts.index', [
            'title' => 'Blog',
            'posts' => (new PostRepository())->all(self::TYPE),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.blog.posts.form', [
            'title' => 'Write Blog Post',
            'post'  => null,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $post = (new PostRepository())->find((int) $request->parameter('id'));
        if ($post === null || $post['post_type'] !== self::TYPE) {
            throw new HttpException(404);
        }

        return $this->view('admin.blog.posts.form', [
            'title' => 'Edit Blog Post',
            'post'  => $post,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $post = (new PostRepository())->find($id);
        if ($post === null || $post['post_type'] !== self::TYPE) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new PostRepository();
        $post = $repo->find($id);
        if ($post === null || $post['post_type'] !== self::TYPE) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('blog.post_deleted', 'blog', 'posts', $id, "Deleted blog post \"{$post['title']}\"");
        Session::flash('status', 'Blog post deleted.');

        return $this->redirectRoute('admin.blog');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new PostRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'title'   => 'required|max:180',
            'content' => 'required',
        ]);
        $errors = $validator->errors();

        $status = (string) $request->input('status', 'draft');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'draft';
        }

        $publishedAt = (string) $request->input('published_at', '');
        if ($status === 'scheduled') {
            if ($publishedAt === '' || strtotime($publishedAt) <= time()) {
                $errors['published_at'] = ['Please pick a future date/time for a scheduled post.'];
            }
        } elseif ($status === 'published' && $publishedAt === '') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $requestedSlug = (string) $request->input('slug', '');
        $baseSlug = slugify($requestedSlug !== '' ? $requestedSlug : (string) $request->input('title', ''));
        $slug = $repo->uniqueSlug($baseSlug, $id);

        $imagePath = $existing['featured_image'] ?? null;
        $file = $request->file('featured_image');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagePath = Uploader::storeImage($file, 'blog');
            } catch (RuntimeException $e) {
                $errors['featured_image'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.blog.create')
                : $this->redirectRoute('admin.blog.edit', ['id' => $id]);
        }

        $data = [
            'post_type'        => self::TYPE,
            'category_id'      => null,
            'title'            => (string) $request->input('title'),
            'slug'             => $slug,
            'excerpt'          => (string) $request->input('excerpt', ''),
            'content'          => (string) $request->input('content', ''),
            'featured_image'   => $imagePath,
            'meta_title'       => (string) $request->input('meta_title', ''),
            'meta_description' => (string) $request->input('meta_description', ''),
            'status'           => $status,
            'is_featured'      => $request->has('is_featured') ? 1 : 0,
            'is_pinned'        => $request->has('is_pinned') ? 1 : 0,
            'published_at'     => $publishedAt,
            'author_id'        => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('blog.post_created', 'blog', 'posts', $newId, "Created blog post \"{$data['title']}\"");
            Session::flash('status', 'Blog post created.');

            return $this->redirectRoute('admin.blog.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('blog.post_updated', 'blog', 'posts', $id, "Updated blog post \"{$data['title']}\"");
        Session::flash('status', 'Blog post updated.');

        return $this->redirectRoute('admin.blog.edit', ['id' => $id]);
    }
}
