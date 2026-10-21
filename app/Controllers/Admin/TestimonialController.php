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
use App\Repositories\TestimonialRepository;
use RuntimeException;

final class TestimonialController extends Controller
{
    private const TYPES = ['student', 'parent', 'alumni', 'staff'];
    private const STATUSES = ['draft', 'published'];

    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.testimonials.index', [
            'title'        => 'Testimonials',
            'testimonials' => (new TestimonialRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.testimonials.form', ['title' => 'Add Testimonial', 'testimonial' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $testimonial = (new TestimonialRepository())->find((int) $request->parameter('id'));
        if ($testimonial === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.testimonials.form', [
            'title' => 'Edit Testimonial', 'testimonial' => $testimonial,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new TestimonialRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new TestimonialRepository();
        $testimonial = $repo->find($id);
        if ($testimonial === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.testimonial_deleted', 'homepage', 'testimonials', $id, "Deleted testimonial from \"{$testimonial['name']}\"");
        Session::flash('status', 'Testimonial deleted.');

        return $this->redirectRoute('admin.homepage.testimonials');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new TestimonialRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'name'    => 'required|max:120',
            'content' => 'required',
        ]);

        $errors = $validator->errors();

        $type = (string) $request->input('type', 'parent');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'parent';
        }

        $status = (string) $request->input('status', 'draft');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'draft';
        }

        $rating = (string) $request->input('rating', '');
        if ($rating !== '' && ((int) $rating < 1 || (int) $rating > 5)) {
            $errors['rating'] = ['Rating must be between 1 and 5.'];
        }

        $photoPath = $existing['photo'] ?? null;
        $file = $request->file('photo');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $photoPath = Uploader::storeImage($file, 'testimonials');
            } catch (RuntimeException $e) {
                $errors['photo'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.homepage.testimonials.create')
                : $this->redirectRoute('admin.homepage.testimonials.edit', ['id' => $id]);
        }

        $data = [
            'name'        => (string) $request->input('name'),
            'designation' => (string) $request->input('designation', ''),
            'photo'       => $photoPath,
            'content'     => (string) $request->input('content'),
            'rating'      => $rating,
            'type'        => $type,
            'is_featured' => $request->has('is_featured') ? 1 : 0,
            'status'      => $status,
            'sort_order'  => (int) $request->input('sort_order', 0),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.testimonial_created', 'homepage', 'testimonials', $newId, "Added testimonial from \"{$data['name']}\"");
            Session::flash('status', 'Testimonial added.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.testimonial_updated', 'homepage', 'testimonials', $id, "Updated testimonial from \"{$data['name']}\"");
            Session::flash('status', 'Testimonial updated.');
        }

        return $this->redirectRoute('admin.homepage.testimonials');
    }
}
