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
use App\Core\Validator;
use App\Core\Database;
use App\Repositories\AcademicSessionRepository;
use App\Repositories\FeeCategoryRepository;
use App\Repositories\FeeStructureRepository;

final class FeeStructureController extends Controller
{
    private const FREQUENCIES = ['one_time' => 'One-Time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'bi_annual' => 'Bi-Annual', 'annual' => 'Annual'];

    public function index(Request $request): Response
    {
        return $this->view('admin.fee-structures.index', [
            'title' => 'Fee Structure',
            'fees'  => (new FeeStructureRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->formView('Add Fee', null);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $fee = (new FeeStructureRepository())->find((int) $request->parameter('id'));
        if ($fee === null) {
            throw new HttpException(404);
        }

        return $this->formView('Edit Fee', $fee);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new FeeStructureRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new FeeStructureRepository();
        $fee = $repo->find($id);
        if ($fee === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('finance.fee_structure_deleted', 'finance', 'fee_structures', $id, 'Deleted a fee structure entry');
        Session::flash('status', 'Fee entry deleted.');

        return $this->redirectRoute('admin.fee-structures');
    }

    private function formView(string $title, ?array $fee): Response
    {
        return $this->view('admin.fee-structures.form', [
            'title'       => $title,
            'fee'         => $fee,
            'sessions'    => (new AcademicSessionRepository())->all(),
            'classes'     => $this->classList(),
            'categories'  => (new FeeCategoryRepository())->activeList(),
            'frequencies' => self::FREQUENCIES,
        ], 'admin');
    }

    /** @return array<int,array<string,mixed>> active classes, id/name/wing, for the select */
    private function classList(): array
    {
        return Database::connection()
            ->query('SELECT id, name, wing FROM school_classes WHERE is_active = 1 ORDER BY sort_order')
            ->fetchAll();
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new FeeStructureRepository();

        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        $errors = $validator->errors();

        $sessionId = (int) $request->input('academic_session_id', 0);
        if ((new AcademicSessionRepository())->find($sessionId) === null) {
            $errors['academic_session_id'][] = 'Please select an academic session.';
        }

        $classId = (int) $request->input('class_id', 0);
        $categoryId = (int) $request->input('fee_category_id', 0);
        if ($classId <= 0) {
            $errors['class_id'][] = 'Please select a class.';
        }
        if ((new FeeCategoryRepository())->find($categoryId) === null) {
            $errors['fee_category_id'][] = 'Please select a fee head.';
        }

        $frequency = (string) $request->input('frequency', '');
        if (!array_key_exists($frequency, self::FREQUENCIES)) {
            $errors['frequency'][] = 'Please select a valid frequency.';
        }

        $amount = (string) $request->input('amount', '');
        if ($amount !== '' && (!is_numeric($amount) || (float) $amount < 0)) {
            $errors['amount'][] = 'Amount must be a positive number.';
        }

        if ($errors === [] && $classId > 0 && $categoryId > 0 && $repo->exists($sessionId, $classId, $categoryId, $id)) {
            $errors['class_id'][] = 'This class already has an entry for that fee head in this session — edit it instead of adding another.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.fee-structures.create')
                : $this->redirectRoute('admin.fee-structures.edit', ['id' => $id]);
        }

        $data = [
            'academic_session_id' => $sessionId,
            'class_id'            => $classId,
            'fee_category_id'     => $categoryId,
            'amount'              => $amount,
            'frequency'           => $frequency,
            'created_by'          => Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('finance.fee_structure_created', 'finance', 'fee_structures', $newId, 'Added a fee structure entry');
            Session::flash('status', 'Fee added.');
        } else {
            $repo->update($id, $data);
            Audit::log('finance.fee_structure_updated', 'finance', 'fee_structures', $id, 'Updated a fee structure entry');
            Session::flash('status', 'Fee updated.');
        }

        return $this->redirectRoute('admin.fee-structures');
    }
}
