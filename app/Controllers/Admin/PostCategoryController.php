<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\PostCategoryRepository;

final class PostCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.news.categories.index', [
            'title'      => 'News Categories',
            'activeTab'  => 'categories',
            'categories' => (new PostCategoryRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.news.categories.form', [
            'title' => 'Add Category', 'activeTab' => 'categories', 'category' => null,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $category = (new PostCategoryRepository())->find((int) $request->parameter('id'));
        if ($category === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.news.categories.form', [
            'title' => 'Edit Category', 'activeTab' => 'categories', 'category' => $category,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new PostCategoryRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new PostCategoryRepository();
        $category = $repo->find($id);
        if ($category === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('news.category_deleted', 'news', 'post_categories', $id, "Deleted category \"{$category['name']}\"");
        Session::flash('status', 'Category deleted.');

        return $this->redirectRoute('admin.news.categories');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new PostCategoryRepository();

        $validator = Validator::make($request->all(), ['name' => 'required|max:100']);
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.news.categories.create')
                : $this->redirectRoute('admin.news.categories.edit', ['id' => $id]);
        }

        $requestedSlug = (string) $request->input('slug', '');
        $baseSlug = slugify($requestedSlug !== '' ? $requestedSlug : (string) $request->input('name'));
        $slug = $repo->uniqueSlug($baseSlug, $id);

        $data = [
            'name'        => (string) $request->input('name'),
            'slug'        => $slug,
            'description' => (string) $request->input('description', ''),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('news.category_created', 'news', 'post_categories', $newId, "Created category \"{$data['name']}\"");
            Session::flash('status', 'Category created.');
        } else {
            $repo->update($id, $data);
            Audit::log('news.category_updated', 'news', 'post_categories', $id, "Updated category \"{$data['name']}\"");
            Session::flash('status', 'Category updated.');
        }

        return $this->redirectRoute('admin.news.categories');
    }
}
