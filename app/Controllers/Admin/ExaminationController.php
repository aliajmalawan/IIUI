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
use App\Repositories\ExamRepository;

/**
 * Subject-wise exam schedule (the `exams` table) within an exam term — see
 * the notice above ExamRepository for what's deliberately out of scope
 * (attendance roll-call, marks entry, results publishing).
 */
final class ExaminationController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new ExamRepository();
        $termId = (int) $request->query('exam_term_id', 0) ?: null;
        $classId = (int) $request->query('class_id', 0) ?: null;

        return $this->view('admin.examinations.index', [
            'title'        => 'Examinations',
            'exams'        => $repo->all($termId, $classId),
            'terms'        => $repo->activeTerms(),
            'classes'      => $repo->activeClasses(),
            'activeTermId' => $termId,
            'activeClassId'=> $classId,
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.examinations.form', array_merge($this->formOptions(), [
            'title' => 'Schedule Exam',
            'exam'  => null,
        ]), 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $exam = (new ExamRepository())->find((int) $request->parameter('id'));
        if ($exam === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.examinations.form', array_merge($this->formOptions(), [
            'title' => 'Edit Exam',
            'exam'  => $exam,
        ]), 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new ExamRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ExamRepository();
        $exam = $repo->find($id);
        if ($exam === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('examinations.deleted', 'examinations', 'exams', $id, "Deleted \"{$exam['subject_name']}\" exam ({$exam['exam_date']})");
        Session::flash('status', 'Exam removed from the schedule.');

        return $this->redirectRoute('admin.examinations');
    }

    private function formOptions(): array
    {
        $repo = new ExamRepository();

        return [
            'terms'        => $repo->activeTerms(),
            'classes'      => $repo->activeClasses(),
            'streams'      => $repo->activeStreams(),
            'subjects'     => $repo->activeSubjects(),
            'invigilators' => $repo->invigilators(),
        ];
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new ExamRepository();

        $validator = Validator::make($request->all(), [
            'exam_date' => 'required',
        ]);
        $errors = $validator->errors();

        $termId = (int) $request->input('exam_term_id', 0);
        if ($termId === 0) {
            $errors['exam_term_id'][] = 'Please select an exam term.';
        }

        $classId = (int) $request->input('class_id', 0);
        if ($classId === 0) {
            $errors['class_id'][] = 'Please select a class.';
        }

        $subjectId = (int) $request->input('subject_id', 0);
        if ($subjectId === 0) {
            $errors['subject_id'][] = 'Please select a subject.';
        }

        $examDate = (string) $request->input('exam_date', '');
        if ($examDate !== '' && strtotime($examDate) === false) {
            $errors['exam_date'][] = 'Please provide a valid date.';
        }

        $startTime = (string) $request->input('start_time', '');
        $endTime = (string) $request->input('end_time', '');
        if ($startTime !== '' && $endTime !== '' && $startTime >= $endTime) {
            $errors['end_time'][] = 'End time must be after the start time.';
        }

        $totalMarks = (int) $request->input('total_marks', 100);
        $passingMarks = (int) $request->input('passing_marks', 33);
        if ($passingMarks > $totalMarks) {
            $errors['passing_marks'][] = 'Passing marks cannot exceed total marks.';
        }

        $practicalTotal = (int) $request->input('practical_total', 0);
        $practicalPassing = (int) $request->input('practical_passing', 0);
        if ($practicalPassing > $practicalTotal) {
            $errors['practical_passing'][] = 'Practical passing marks cannot exceed practical total marks.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.examinations.create')
                : $this->redirectRoute('admin.examinations.edit', ['id' => $id]);
        }

        $data = [
            'exam_term_id'      => $termId,
            'class_id'          => $classId,
            'stream_id'         => (int) $request->input('stream_id', 0) ?: null,
            'subject_id'        => $subjectId,
            'exam_date'         => date('Y-m-d', strtotime($examDate)),
            'start_time'        => $startTime !== '' ? $startTime : null,
            'end_time'          => $endTime !== '' ? $endTime : null,
            'total_marks'       => $totalMarks,
            'passing_marks'     => $passingMarks,
            'practical_total'   => $practicalTotal,
            'practical_passing' => $practicalPassing,
            'room_no'           => (string) $request->input('room_no', ''),
            'invigilator_id'    => (int) $request->input('invigilator_id', 0) ?: null,
            'author_id'         => (int) Auth::id(),
        ];

        if ($id === null) {
            $newId = $repo->create($data);
            Audit::log('examinations.created', 'examinations', 'exams', $newId, 'Scheduled a new exam');
            Session::flash('status', 'Exam added to the schedule.');
        } else {
            $repo->update($id, $data);
            Audit::log('examinations.updated', 'examinations', 'exams', $id, 'Updated exam schedule');
            Session::flash('status', 'Exam updated.');
        }

        return $this->redirectRoute('admin.examinations');
    }
}
