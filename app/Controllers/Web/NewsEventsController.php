<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\EventRepository;
use App\Repositories\PostCategoryRepository;
use App\Repositories\PostRepository;

/**
 * Combined "News & Events" feed (reference: darsons.edu.pk/news.php shows
 * both in one filterable list, not two separate pages) — merges `posts` and
 * `events` into one normalized, chronologically-sorted array and paginates
 * in PHP after merging, since the two live in different tables with
 * different date columns. /news/{slug} and /events/{slug} (detail pages),
 * /news/category/{slug}, and the dedicated /events listing are untouched —
 * this only replaces what the public sees at the bare /news index.
 */
final class NewsEventsController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        $categories = (new PostCategoryRepository())->activeList();
        $categorySlugs = array_column($categories, 'slug', 'slug');
        $filter = (string) $request->query('filter', 'all');

        $items = match (true) {
            $filter === 'events' => $this->eventItems(),
            array_key_exists($filter, $categorySlugs) => $this->postItems($this->categoryIdForSlug($categories, $filter)),
            default => array_merge($this->postItems(null), $this->eventItems()),
        };

        usort($items, static fn (array $a, array $b) => $b['sort_ts'] <=> $a['sort_ts']);

        $page = max(1, (int) $request->query('page', 1));
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $pageItems = array_slice($items, (min($page, $lastPage) - 1) * self::PER_PAGE, self::PER_PAGE);

        return $this->view('web.news-events.index', [
            'title'         => 'News & Events',
            'items'         => $pageItems,
            'categories'    => $categories,
            'activeFilter'  => $filter,
            'page'          => $page,
            'lastPage'      => $lastPage,
        ], 'web');
    }

    /** @return array<int,array<string,mixed>> normalized news-post items */
    private function postItems(?int $categoryId): array
    {
        $posts = (new PostRepository())->publishedAll($categoryId);

        return array_map(static function (array $p): array {
            $ts = strtotime((string) $p['published_at']) ?: 0;

            return [
                'type'     => 'news',
                'title'    => $p['title'],
                'excerpt'  => (string) ($p['excerpt'] ?? ''),
                'image'    => $p['featured_image'],
                'tag'      => $p['category_name'] ?? 'News',
                'date'     => date('d M Y', $ts),
                'sort_ts'  => $ts,
                'is_pinned' => (bool) $p['is_pinned'],
                'url'      => route('news.show', ['slug' => $p['slug']]),
            ];
        }, $posts);
    }

    /** @return array<int,array<string,mixed>> normalized event items */
    private function eventItems(): array
    {
        $events = (new EventRepository())->publishedAll();

        return array_map(static function (array $e): array {
            $ts = strtotime((string) $e['start_datetime']) ?: 0;

            return [
                'type'     => 'event',
                'title'    => $e['title'],
                'excerpt'  => (string) ($e['description'] ?? ''),
                'image'    => $e['banner'],
                'tag'      => $e['category'] ?: 'Event',
                'date'     => date('d M Y', $ts),
                'sort_ts'  => $ts,
                'is_pinned' => (bool) $e['is_featured'],
                'url'      => route('events.show', ['slug' => $e['slug']]),
            ];
        }, $events);
    }

    private function categoryIdForSlug(array $categories, string $slug): ?int
    {
        foreach ($categories as $c) {
            if ($c['slug'] === $slug) {
                return (int) $c['id'];
            }
        }

        return null;
    }
}
