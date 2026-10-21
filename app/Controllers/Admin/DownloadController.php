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
use App\Repositories\DownloadRepository;
use RuntimeException;

final class DownloadController extends Controller
{
    private const AUDIENCES = ['public', 'students', 'parents', 'staff'];

    public function index(Request $request): Response
    {
        return $this->view('admin.downloads.index', [
            'title'     => 'Downloads',
            'downloads' => (new DownloadRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.downloads.form', ['title' => 'Add Download', 'download' => null], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $download = (new DownloadRepository())->find((int) $request->parameter('id'));
        if ($download === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.downloads.form', ['title' => 'Edit Download', 'download' => $download], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new DownloadRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new DownloadRepository();
        $download = $repo->find($id);
        if ($download === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        $this->unlinkStoredFile((string) $download['file_path']);
        Audit::log('downloads.deleted', 'downloads', 'downloads', $id, "Deleted download \"{$download['title']}\"");
        Session::flash('status', 'Download deleted.');

        return $this->redirectRoute('admin.downloads');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new DownloadRepository();
        $existing = $id !== null ? $repo->find($id) : null;

        $validator = Validator::make($request->all(), ['title' => 'required|max:180']);
        $errors = $validator->errors();

        $audience = (string) $request->input('audience', 'public');
        if (!in_array($audience, self::AUDIENCES, true)) {
            $audience = 'public';
        }

        $fileData = null;
        $file = $request->file('file');
        $fileProvided = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($fileProvided) {
            try {
                $fileData = Uploader::storeDocument($file, 'downloads');
            } catch (RuntimeException $e) {
                $errors['file'] = [$e->getMessage()];
            }
        } elseif ($id === null) {
            $errors['file'] = ['Please choose a file to upload.'];
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.downloads.create')
                : $this->redirectRoute('admin.downloads.edit', ['id' => $id]);
        }

        $data = [
            'title'       => (string) $request->input('title'),
            'description' => (string) $request->input('description', ''),
            'category'    => (string) $request->input('category', ''),
            'audience'    => $audience,
            'is_active'   => $request->has('is_active') ? 1 : 0,
            'file_path'   => $fileData['path'] ?? $existing['file_path'],
            'file_name'   => $fileData['original_name'] ?? $existing['file_name'],
            'file_size'   => $fileData['size'] ?? $existing['file_size'],
            'mime_type'   => $fileData['mime'] ?? $existing['mime_type'],
            'uploaded_by' => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('downloads.created', 'downloads', 'downloads', $newId, "Added download \"{$data['title']}\"");
            Session::flash('status', 'Download created.');

            return $this->redirectRoute('admin.downloads.edit', ['id' => $newId]);
        }

        $repo->update($id, $data);
        // Only unlink the old file once the new one is safely referenced by the row.
        if ($fileData !== null && $existing !== null && $existing['file_path'] !== $data['file_path']) {
            $this->unlinkStoredFile((string) $existing['file_path']);
        }
        Audit::log('downloads.updated', 'downloads', 'downloads', $id, "Updated download \"{$data['title']}\"");
        Session::flash('status', 'Download updated.');

        return $this->redirectRoute('admin.downloads.edit', ['id' => $id]);
    }

    private function unlinkStoredFile(string $relativePath): void
    {
        $path = STORAGE_PATH . '/app/uploads/' . ltrim($relativePath, '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
