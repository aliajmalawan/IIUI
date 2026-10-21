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
use App\Repositories\AcademicSessionRepository;
use App\Repositories\ExamTermRepository;

final class ExamTermController extends Controller
{
    private const TYPES = ['monthly' => 'Monthly Test', 'mid' => 'Mid Term', 'final' => 'Final Term', 'test' => 'Class Test', 'pre_board' => 'Pre-Board'];
    private const STATUSES = ['planned' => 'Planned', 'ongoing' => 'Ongoing', 'marking' => 'Marking', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    public function index(Request $request): Response
    {
        return $this->view('admin.exam-terms.index', [
            'title' => 'Exam Terms',
            'terms' => (new ExamTermRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.exam-terms.form', [
            'title'    => 'Add Exam Term',
            'term'     => null,
            'sessions' => (new AcademicSessionRepository())->all(),
            'types'    => self::TYPES,
            'statuses' => self::STATUSES,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $term = (new ExamTermRepository())->find((int) $request->parameter('id'));
        if ($term === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.exam-terms.form', [
            'title'    => 'Edit Exam Term',
            'term'     => $term,
            'sessions' => (new AcademicSessionRepository())->all(),
            'types'    => self::TYPES,
            'statuses' => self::STATUSES,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new ExamTermRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ExamTermRepository();
        $term = $repo->find($id);
        if ($term === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('academic_calendar.term_deleted', 'academic_calendar', 'exam_terms', $id, "Deleted exam term \"{$term['name']}\"");
        Session::flash('status', 'Exam term deleted.');

        return $this->redirectRoute('admin.exam-terms');
    }

    /** Gated on the term's own status='completed' — publishing results for a term that hasn't finished doesn't make sense. */
    public function publish(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ExamTermRepository();
        $term = $repo->find($id);
        if ($term === null) {
            throw new HttpException(404);
        }

        if ($term['status'] !== 'completed') {
            Session::flash('status', 'Mark this term "Completed" before publishing its results.');

            return $this->redirectRoute('admin.exam-terms');
        }

        $repo->publishResults($id, (int) Auth::id());
        Audit::log('academic_calendar.results_published', 'academic_calendar', 'exam_terms', $id, "Published results for \"{$term['name']}\"");
        Session::flash('status', 'Results published — visible on the public Academic Calendar page.');

        return $this->redirectRoute('admin.exam-terms');
    }

    public function unpublish(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ExamTermRepository();
        $term = $repo->find($id);
        if ($term === null) {
            throw new HttpException(404);
        }

        $repo->unpublishResults($id);
        Audit::log('academic_calendar.results_unpublished', 'academic_calendar', 'exam_terms', $id, "Unpublished results for \"{$term['name']}\"");
        Session::flash('status', 'Results unpublished.');

        return $this->redirectRoute('admin.exam-terms');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new ExamTermRepository();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:80',
        ]);
        $errors = $validator->errors();

        $sessionId = (int) $request->input('academic_session_id', 0);
        if ((new AcademicSessionRepository())->find($sessionId) === null) {
            $errors['academic_session_id'][] = 'Please select an academic session.';
        }

        $type = (string) $request->input('type', '');
        if (!array_key_exists($type, self::TYPES)) {
            $errors['type'][] = 'Please select a valid type.';
        }

        $status = (string) $request->input('status', 'planned');
        if (!array_key_exists($status, self::STATUSES)) {
            $errors['status'][] = 'Please select a valid status.';
        }

        $startDate = (string) $request->input('start_date', '');
        $endDate = (string) $request->input('end_date', '');
        if ($startDate === '' || strtotime($startDate) === false) {
            $errors['start_date'][] = 'Please enter a valid start date.';
        }
        if ($endDate === '' || strtotime($endDate) === false) {
            $errors['end_date'][] = 'Please enter a valid end date.';
        }
        if ($errors === [] && strtotime($endDate) < strtotime($startDate)) {
            $errors['end_date'][] = 'End date must be on or after the start date.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.exam-terms.create')
                : $this->redirectRoute('admin.exam-terms.edit', ['id' => $id]);
        }

        $data = [
            'academic_session_id' => $sessionId,
            'name'                => (string) $request->input('name'),
            'type'                => $type,
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'status'              => $status,
            'weightage'           => (float) $request->input('weightage', 100),
            'sort_order'          => (int) $request->input('sort_order', 0),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('academic_calendar.term_created', 'academic_calendar', 'exam_terms', $newId, "Created exam term \"{$data['name']}\"");
            Session::flash('status', 'Exam term added.');
        } else {
            $repo->update($id, $data);
            Audit::log('academic_calendar.term_updated', 'academic_calendar', 'exam_terms', $id, "Updated exam term \"{$data['name']}\"");
            Session::flash('status', 'Exam term updated.');
        }

        return $this->redirectRoute('admin.exam-terms');
    }
}
