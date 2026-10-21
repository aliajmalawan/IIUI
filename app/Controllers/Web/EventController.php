<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\EventRepository;

final class EventController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        $repo = new EventRepository();
        $when = (string) $request->query('when', 'upcoming') === 'past' ? 'past' : 'upcoming';
        $category = (string) $request->query('category', '');
        $category = $category !== '' ? $category : null;
        $page = max(1, (int) $request->query('page', 1));
        $offset = self::PER_PAGE * ($page - 1);

        if ($when === 'past') {
            $events = $repo->past($category, self::PER_PAGE, $offset);
            $total = $repo->countPast($category);
        } else {
            $events = $repo->upcoming($category, self::PER_PAGE, $offset);
            $total = $repo->countUpcoming($category);
        }

        return $this->view('web.events.index', [
            'title'          => 'Events',
            'events'         => $events,
            'categories'     => $repo->publicCategories(),
            'activeCategory' => $category,
            'when'           => $when,
            'page'           => $page,
            'lastPage'       => max(1, (int) ceil($total / self::PER_PAGE)),
        ], 'web');
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->parameter('slug');
        $event = (new EventRepository())->findPublicBySlug($slug);

        if ($event === null) {
            throw new HttpException(404);
        }

        return $this->view('web.events.show', [
            'title' => $event['title'],
            'event' => $event,
            'metaTitle'       => (string) ($event['meta_title'] ?? ''),
            'metaDescription' => (string) ($event['meta_description'] ?: ($event['description'] ?? '')),
            'metaImage'       => (string) ($event['banner'] ?? ''),
        ], 'web');
    }
}
