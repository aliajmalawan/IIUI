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
use App\Repositories\HomepageStatRepository;

final class HomepageStatController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.stats.index', [
            'title' => 'Statistics',
            'stats' => (new HomepageStatRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.stats.form', ['title' => 'Add Statistic', 'stat' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $stat = (new HomepageStatRepository())->find((int) $request->parameter('id'));
        if ($stat === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.stats.form', ['title' => 'Edit Statistic', 'stat' => $stat], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new HomepageStatRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageStatRepository();
        $stat = $repo->find($id);
        if ($stat === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.stat_deleted', 'homepage', 'homepage_stats', $id, "Deleted statistic \"{$stat['label']}\"");
        Session::flash('status', 'Statistic deleted.');

        return $this->redirectRoute('admin.homepage.stats');
    }

    private function save(Request $request, ?int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|max:80',
            'value' => 'required|max:20',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.homepage.stats.create')
                : $this->redirectRoute('admin.homepage.stats.edit', ['id' => $id]);
        }

        $data = [
            'label'      => (string) $request->input('label'),
            'value'      => (string) $request->input('value'),
            'suffix'     => (string) $request->input('suffix', ''),
            'icon'       => (string) $request->input('icon', ''),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active'  => $request->has('is_active') ? 1 : 0,
        ];

        $repo = new HomepageStatRepository();

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.stat_created', 'homepage', 'homepage_stats', $newId, "Created statistic \"{$data['label']}\"");
            Session::flash('status', 'Statistic added.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.stat_updated', 'homepage', 'homepage_stats', $id, "Updated statistic \"{$data['label']}\"");
            Session::flash('status', 'Statistic updated.');
        }

        return $this->redirectRoute('admin.homepage.stats');
    }
}
