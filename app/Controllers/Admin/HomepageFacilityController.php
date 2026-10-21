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
use App\Core\Validator;
use App\Repositories\HomepageFacilityRepository;
use RuntimeException;

final class HomepageFacilityController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.facilities.index', [
            'title'      => 'Facilities',
            'facilities' => (new HomepageFacilityRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.facilities.form', ['title' => 'Add Facility', 'facility' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $facility = (new HomepageFacilityRepository())->find((int) $request->parameter('id'));
        if ($facility === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.facilities.form', ['title' => 'Edit Facility', 'facility' => $facility], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new HomepageFacilityRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HomepageFacilityRepository();
        $facility = $repo->find($id);
        if ($facility === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.facility_deleted', 'homepage', 'homepage_facilities', $id, "Deleted facility \"{$facility['title']}\"");
        Session::flash('status', 'Facility deleted.');

        return $this->redirectRoute('admin.homepage.facilities');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new HomepageFacilityRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:80',
        ]);
        $errors = $validator->errors();

        $imagePath = $existing['image'] ?? null;
        $file = $request->file('image');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagePath = Uploader::storeImage($file, 'homepage/facilities');
            } catch (RuntimeException $e) {
                $errors['image'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.homepage.facilities.create')
                : $this->redirectRoute('admin.homepage.facilities.edit', ['id' => $id]);
        }

        $data = [
            'title'       => (string) $request->input('title'),
            'description' => (string) $request->input('description', ''),
            'image'       => $imagePath,
            'icon'        => (string) $request->input('icon', ''),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.facility_created', 'homepage', 'homepage_facilities', $newId, "Created facility \"{$data['title']}\"");
            Session::flash('status', 'Facility added.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.facility_updated', 'homepage', 'homepage_facilities', $id, "Updated facility \"{$data['title']}\"");
            Session::flash('status', 'Facility updated.');
        }

        return $this->redirectRoute('admin.homepage.facilities');
    }
}
