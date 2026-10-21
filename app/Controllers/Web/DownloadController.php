<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DownloadRepository;

final class DownloadController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new DownloadRepository();
        $category = (string) $request->query('category', '');
        $category = $category !== '' ? $category : null;

        return $this->view('web.downloads.index', [
            'title'          => 'Downloads',
            'downloads'      => $repo->publicList($category),
            'categories'     => $repo->publicCategories(),
            'activeCategory' => $category,
        ], 'web');
    }

    /** Streams the file outside the webroot — never a direct, browser-rendered URL. */
    public function file(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new DownloadRepository();
        $download = $repo->findPublic($id);

        if ($download === null) {
            throw new HttpException(404);
        }

        $absolutePath = STORAGE_PATH . '/app/uploads/' . ltrim((string) $download['file_path'], '/');
        if (!is_file($absolutePath)) {
            throw new HttpException(404);
        }

        $repo->incrementDownloadCount($id);

        return Response::download($absolutePath, (string) $download['file_name'], (string) $download['mime_type']);
    }
}
