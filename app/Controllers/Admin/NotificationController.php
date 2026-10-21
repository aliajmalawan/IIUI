<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepository;

final class NotificationController extends Controller
{
    public function markRead(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $userId = (int) Auth::id();

        $marked = (new NotificationRepository())->markRead($id, $userId);

        return $this->json(['success' => $marked]);
    }

    public function markAllRead(Request $request): Response
    {
        (new NotificationRepository())->markAllRead((int) Auth::id());

        return $this->json(['success' => true]);
    }
}
