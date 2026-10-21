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
use App\Repositories\SliderRepository;
use RuntimeException;

final class SliderController extends Controller
{
    private const TEXT_POSITIONS = ['left', 'center', 'right'];

    public function index(Request $request): Response
    {
        return $this->view('admin.homepage.hero.index', [
            'title'   => 'Hero Slider',
            'slides'  => (new SliderRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.homepage.hero.form', [
            'title' => 'Add Slide',
            'slide' => null,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $slide = (new SliderRepository())->find((int) $request->parameter('id'));
        if ($slide === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.homepage.hero.form', [
            'title' => 'Edit Slide',
            'slide' => $slide,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new SliderRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new SliderRepository();
        $slide = $repo->find($id);
        if ($slide === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('homepage.slide_deleted', 'homepage', 'sliders', $id, "Deleted slide \"{$slide['title']}\"");
        Session::flash('status', 'Slide deleted.');

        return $this->redirectRoute('admin.homepage.hero');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new SliderRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'title'           => 'required|max:180',
            'button_text'     => 'max:60',
            'overlay_opacity' => 'required',
        ]);

        $errors = $validator->errors();

        $textPosition = (string) $request->input('text_position', 'left');
        if (!in_array($textPosition, self::TEXT_POSITIONS, true)) {
            $textPosition = 'left';
        }

        $opacity = (int) $request->input('overlay_opacity', 70);
        if ($opacity < 0 || $opacity > 100) {
            $errors['overlay_opacity'] = ['Overlay opacity must be between 0 and 100.'];
        }

        $imagePath = $existing['image'] ?? null;
        $file = $request->file('image');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagePath = Uploader::storeImage($file, 'sliders', MAX_UPLOAD_HERO_IMAGE_BYTES);
            } catch (RuntimeException $e) {
                $errors['image'] = [$e->getMessage()];
            }
        } elseif ($imagePath === null) {
            $errors['image'] = ['A slide image is required.'];
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.homepage.hero.create')
                : $this->redirectRoute('admin.homepage.hero.edit', ['id' => $id]);
        }

        $data = [
            'title'           => (string) $request->input('title'),
            'subtitle'        => (string) $request->input('subtitle', ''),
            'description'     => (string) $request->input('description', ''),
            'image'           => $imagePath,
            'mobile_image'    => null,
            'button_text'     => (string) $request->input('button_text', ''),
            'button_url'      => (string) $request->input('button_url', ''),
            'text_position'   => $textPosition,
            'overlay_opacity' => $opacity,
            'sort_order'      => (int) $request->input('sort_order', 0),
            'is_active'       => $request->has('is_active') ? 1 : 0,
            'starts_at'       => (string) $request->input('starts_at', ''),
            'ends_at'         => (string) $request->input('ends_at', ''),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('homepage.slide_created', 'homepage', 'sliders', $newId, "Created slide \"{$data['title']}\"");
            Session::flash('status', 'Slide created.');
        } else {
            $repo->update($id, $data);
            Audit::log('homepage.slide_updated', 'homepage', 'sliders', $id, "Updated slide \"{$data['title']}\"");
            Session::flash('status', 'Slide updated.');
        }

        return $this->redirectRoute('admin.homepage.hero');
    }
}
