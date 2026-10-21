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
use App\Repositories\PostCategoryRepository;
use App\Repositories\PostRepository;
use RuntimeException;

final class PostController extends Controller
{
    private const STATUSES = ['draft', 'published', 'scheduled', 'archived'];

    public function index(Request $request): Response
    {
        return $this->view('admin.news.posts.index', [
            'title'     => 'News & Articles',
            'activeTab' => 'posts',
            'posts'     => (new PostRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.news.posts.form', [
            'title'      => 'Write Article',
            'activeTab'  => 'posts',
            'post'       => null,
            'categories' => (new PostCategoryRepository())->activeList(),
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $post = (new PostRepository())->find((int) $request->parameter('id'));
        if ($post === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.news.posts.form', [
            'title'      => 'Edit Article',
            'activeTab'  => 'posts',
            'post'       => $post,
            'categories' => (new PostCategoryRepository())->activeList(),
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new PostRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new PostRepository();
        $post = $repo->find($id);
        if ($post === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('news.post_deleted', 'news', 'posts', $id, "Deleted article \"{$post['title']}\"");
        Session::flash('status', 'Article deleted.');

        return $this->redirectRoute('admin.news');
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
                $errors['published_at'] = ['Please pick a future date/time for a scheduled article.'];
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
                $imagePath = Uploader::storeImage($file, 'news');
            } catch (RuntimeException $e) {
                $errors['featured_image'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.news.create')
                : $this->redirectRoute('admin.news.edit', ['id' => $id]);
        }

        $categoryId = (int) $request->input('category_id', 0) ?: null;

        $data = [
            'category_id'      => $categoryId,
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
            Audit::log('news.post_created', 'news', 'posts', $newId, "Created article \"{$data['title']}\"");
            Session::flash('status', 'Article created.');

            return $this->redirectRoute('admin.news.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('news.post_updated', 'news', 'posts', $id, "Updated article \"{$data['title']}\"");
        Session::flash('status', 'Article updated.');

        return $this->redirectRoute('admin.news.edit', ['id' => $id]);
    }
}
