<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicSessionRepository;
use App\Repositories\ExamTermRepository;
use App\Repositories\HolidayRepository;

final class AcademicCalendarController extends Controller
{
    private const EXAM_TYPE_LABELS = ['monthly' => 'Monthly Test', 'mid' => 'Mid Term', 'final' => 'Final Term', 'test' => 'Class Test', 'pre_board' => 'Pre-Board'];
    private const HOLIDAY_TYPE_LABELS = ['public' => 'Public Holiday', 'religious' => 'Religious Holiday', 'vacation' => 'Vacation', 'emergency' => 'Emergency Closure', 'weekend' => 'Weekend'];

    public function index(Request $request): Response
    {
        $sessionRepo = new AcademicSessionRepository();
        $session = $sessionRepo->current();

        // No session flagged current (fresh install, or between sessions) —
        // fall back to the most recently started one rather than showing
        // nothing at all.
        if ($session === null) {
            $all = $sessionRepo->all();
            $session = $all[0] ?? null;
        }

        $items = [];
        if ($session !== null) {
            foreach ((new ExamTermRepository())->forSession((int) $session['id']) as $term) {
                $items[] = [
                    'start'                => $term['start_date'],
                    'end'                  => $term['end_date'],
                    'title'                => $term['name'],
                    'kind'                 => 'exam',
                    'type'                 => $term['type'],
                    'type_label'           => self::EXAM_TYPE_LABELS[$term['type']] ?? ucfirst($term['type']),
                    'status'               => $term['status'],
                    'description'          => null,
                    'is_result_published'  => (int) $term['is_result_published'] === 1,
                    'published_at'         => $term['published_at'],
                ];
            }
            foreach ((new HolidayRepository())->forSession((int) $session['id']) as $holiday) {
                $items[] = [
                    'start'       => $holiday['date_from'],
                    'end'         => $holiday['date_to'],
                    'title'       => $holiday['title'],
                    'kind'        => 'holiday',
                    'type'        => $holiday['type'],
                    'type_label'  => self::HOLIDAY_TYPE_LABELS[$holiday['type']] ?? ucfirst($holiday['type']),
                    'status'      => null,
                    'description' => $holiday['description'],
                ];
            }
            usort($items, static fn (array $a, array $b) => strtotime((string) $a['start']) <=> strtotime((string) $b['start']));
        }

        // Group into month buckets ("August 2026" => [...]) for the
        // timeline — the whole reason to merge exam terms and holidays into
        // one list first is so they interleave chronologically inside each
        // month instead of appearing as two disconnected sections.
        $months = [];
        foreach ($items as $item) {
            $key = date('Y-m', strtotime((string) $item['start']));
            $months[$key]['label'] ??= date('F Y', strtotime((string) $item['start']));
            $months[$key]['items'][] = $item;
        }

        return $this->view('web.pages.academic-calendar', [
            'title'   => 'Academic Calendar',
            'session' => $session,
            'months'  => $months,
        ], 'web');
    }
}
