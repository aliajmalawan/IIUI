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
use App\Repositories\EventRepository;
use RuntimeException;

final class EventController extends Controller
{
    private const STATUSES = ['draft', 'published', 'cancelled', 'completed'];

    public function index(Request $request): Response
    {
        return $this->view('admin.events.index', [
            'title'  => 'Events',
            'events' => (new EventRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.events.form', ['title' => 'Add Event', 'event' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $event = (new EventRepository())->find((int) $request->parameter('id'));
        if ($event === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.events.form', ['title' => 'Edit Event', 'event' => $event], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new EventRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new EventRepository();
        $event = $repo->find($id);
        if ($event === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('events.deleted', 'events', 'events', $id, "Deleted event \"{$event['title']}\"");
        Session::flash('status', 'Event deleted.');

        return $this->redirectRoute('admin.events');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new EventRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), [
            'title'          => 'required|max:180',
            'start_datetime' => 'required',
        ]);
        $errors = $validator->errors();

        $startDatetime = (string) $request->input('start_datetime', '');
        $endDatetime = (string) $request->input('end_datetime', '');
        if ($startDatetime !== '' && $endDatetime !== '' && strtotime($endDatetime) < strtotime($startDatetime)) {
            $errors['end_datetime'] = ['The end date/time cannot be before the start date/time.'];
        }

        $status = (string) $request->input('status', 'draft');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'draft';
        }

        $requestedSlug = (string) $request->input('slug', '');
        $baseSlug = slugify($requestedSlug !== '' ? $requestedSlug : (string) $request->input('title', ''));
        $slug = $repo->uniqueSlug($baseSlug, $id);

        $bannerPath = $existing['banner'] ?? null;
        $file = $request->file('banner');
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $bannerPath = Uploader::storeImage($file, 'events');
            } catch (RuntimeException $e) {
                $errors['banner'] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.events.create')
                : $this->redirectRoute('admin.events.edit', ['id' => $id]);
        }

        $data = [
            'title'                 => (string) $request->input('title'),
            'slug'                  => $slug,
            'description'           => (string) $request->input('description', ''),
            'content'               => (string) $request->input('content', ''),
            'banner'                => $bannerPath,
            'start_datetime'        => date('Y-m-d H:i:s', strtotime($startDatetime)),
            'end_datetime'          => $endDatetime !== '' ? date('Y-m-d H:i:s', strtotime($endDatetime)) : null,
            'venue'                 => (string) $request->input('venue', ''),
            'organizer'             => (string) $request->input('organizer', ''),
            'category'              => (string) $request->input('category', ''),
            'registration_required' => $request->has('registration_required') ? 1 : 0,
            'registration_url'      => (string) $request->input('registration_url', ''),
            'capacity'              => (int) $request->input('capacity', 0) ?: null,
            'is_featured'           => $request->has('is_featured') ? 1 : 0,
            'status'                => $status,
            'meta_title'            => (string) $request->input('meta_title', ''),
            'meta_description'      => (string) $request->input('meta_description', ''),
            'author_id'             => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('events.created', 'events', 'events', $newId, "Created event \"{$data['title']}\"");
            Session::flash('status', 'Event created.');

            return $this->redirectRoute('admin.events.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        Audit::log('events.updated', 'events', 'events', $id, "Updated event \"{$data['title']}\"");
        Session::flash('status', 'Event updated.');

        return $this->redirectRoute('admin.events.edit', ['id' => $id]);
    }
}
