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
use App\Repositories\HomepageProgramRepository;

final class HomepageProgramController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.programs.index', [
            'title'    => 'Academic Programs',
            'programs' => (new HomepageProgramRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.programs.form', ['title' => 'Add Program', 'program' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $program = (new HomepageProgramRepository())->find((int) $request->parameter('id'));
        if ($program === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.programs.form', ['title' => 'Edit Program', 'program' => $program], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new HomepageProgramRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageProgramRepository();
        $program = $repo->find($id);
        if ($program === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.program_deleted', 'homepage', 'homepage_programs', $id, "Deleted program \"{$program['title']}\"");
        Session::flash('status', 'Program deleted.');

        return $this->redirectRoute('admin.homepage.programs');
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
                ? $this->redirectRoute('admin.homepage.programs.create')
                : $this->redirectRoute('admin.homepage.programs.edit', ['id' => $id]);
        }

        $data = [
            'title'       => (string) $request->input('title'),
            'grade_range' => (string) $request->input('grade_range', ''),
            'description' => (string) $request->input('description', ''),
            'icon'        => (string) $request->input('icon', ''),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        $repo = new HomepageProgramRepository();

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.program_created', 'homepage', 'homepage_programs', $newId, "Created program \"{$data['title']}\"");
            Session::flash('status', 'Program added.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.program_updated', 'homepage', 'homepage_programs', $id, "Updated program \"{$data['title']}\"");
            Session::flash('status', 'Program updated.');
        }

        return $this->redirectRoute('admin.homepage.programs');
    }
}
