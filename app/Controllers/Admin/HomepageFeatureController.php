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
use App\Repositories\HomepageFeatureRepository;

final class HomepageFeatureController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.features.index', [
            'title'    => 'Why Choose Us',
            'features' => (new HomepageFeatureRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.features.form', ['title' => 'Add Feature', 'feature' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $feature = (new HomepageFeatureRepository())->find((int) $request->parameter('id'));
        if ($feature === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.features.form', ['title' => 'Edit Feature', 'feature' => $feature], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new HomepageFeatureRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageFeatureRepository();
        $feature = $repo->find($id);
        if ($feature === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.feature_deleted', 'homepage', 'homepage_features', $id, "Deleted feature \"{$feature['title']}\"");
        Session::flash('status', 'Feature deleted.');

        return $this->redirectRoute('admin.homepage.features');
    }

    private function save(Request $request, ?int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:80',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.homepage.features.create')
                : $this->redirectRoute('admin.homepage.features.edit', ['id' => $id]);
        }

        $data = [
            'title'       => (string) $request->input('title'),
            'description' => (string) $request->input('description', ''),
            'icon'        => (string) $request->input('icon', ''),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        $repo = new HomepageFeatureRepository();

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.feature_created', 'homepage', 'homepage_features', $newId, "Created feature \"{$data['title']}\"");
            Session::flash('status', 'Feature added.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.feature_updated', 'homepage', 'homepage_features', $id, "Updated feature \"{$data['title']}\"");
            Session::flash('status', 'Feature updated.');
        }

        return $this->redirectRoute('admin.homepage.features');
    }
}
