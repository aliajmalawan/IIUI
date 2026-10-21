<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicSessionRepository;
use App\Repositories\FeeStructureRepository;

final class FeeStructureController extends Controller
{
    private const WING_ORDER = ['primary', 'middle', 'secondary', 'college'];
    private const WING_LABELS = ['primary' => 'Primary Wing', 'middle' => 'Middle Wing', 'secondary' => 'Secondary Wing', 'college' => 'College Wing'];
    private const FREQ_LABELS = ['one_time' => 'One-Time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'bi_annual' => 'Bi-Annual', 'annual' => 'Annual'];

    public function index(Request $request): Response
    {
        $sessionRepo = new AcademicSessionRepository();
        $session = $sessionRepo->current();
        if ($session === null) {
            $all = $sessionRepo->all();
            $session = $all[0] ?? null;
        }

        // Pivoted into wing -> class -> {category_id: amount}, plus the set
        // of categories actually in use (in fee_categories' own display
        // order) — so the table's columns are exactly whatever the admin
        // has entered, not a fixed list that could show empty columns.
        $wings = [];
        $columns = [];

        if ($session !== null) {
            foreach ((new FeeStructureRepository())->forSession((int) $session['id']) as $entry) {
                $wing = (string) $entry['wing'];
                $classId = (int) $entry['class_id'];
                $categoryId = (int) $entry['fee_category_id'];

                $wings[$wing]['label'] ??= self::WING_LABELS[$wing] ?? ucfirst($wing);
                $wings[$wing]['classes'][$classId]['name'] ??= $entry['class_name'];
                $wings[$wing]['classes'][$classId]['amounts'][$categoryId] = $entry['amount'];

                $columns[$categoryId] ??= [
                    'name'      => $entry['category_name'],
                    'sort'      => (int) $entry['category_sort'],
                    'frequency' => $entry['frequency'],
                ];
            }
            uksort($wings, static fn (string $a, string $b) => array_search($a, self::WING_ORDER, true) <=> array_search($b, self::WING_ORDER, true));
            uasort($columns, static fn (array $a, array $b) => $a['sort'] <=> $b['sort']);
        }

        return $this->view('web.pages.fee-structure', [
            'title'       => 'Fee Structure',
            'session'     => $session,
            'wings'       => $wings,
            'columns'     => $columns,
            'freqLabels'  => self::FREQ_LABELS,
        ], 'web');
    }
}
