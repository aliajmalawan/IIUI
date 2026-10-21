<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\MediaLibrary;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use RuntimeException;

final class MediaLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $folder = (string) $request->query('folder', '');
        $all = MediaLibrary::scan();
        $files = $folder !== '' ? array_values(array_filter($all, static fn (array $f) => $f['folder'] === $folder)) : $all;

        return $this->view('admin.media-library.index', [
            'title'        => 'Media Library',
            'files'        => $files,
            'folders'      => MediaLibrary::folders(),
            'activeFolder' => $folder,
            'totalCount'   => count($all),
            'totalBytes'   => array_sum(array_column($all, 'size_bytes')),
        ], 'admin');
    }

    public function destroy(Request $request): Response
    {
        $path = (string) $request->input('path', '');

        try {
            MediaLibrary::delete($path);
            Audit::log('media.deleted', 'media-library', 'media', null, "Deleted file \"{$path}\"");
            Session::flash('status', 'File deleted.');
        } catch (RuntimeException $e) {
            Session::flash('status', $e->getMessage());
        }

        return $this->redirectRoute('admin.media-library');
    }
}
