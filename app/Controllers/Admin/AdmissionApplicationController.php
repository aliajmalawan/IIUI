<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\AdmissionApplicationRepository;
use App\Repositories\StudentRepository;

final class AdmissionApplicationController extends Controller
{
    /**
     * Statuses reachable from the plain "mark as..." buttons. test_scheduled
     * and test_taken are deliberately excluded here — they're only ever
     * reached through scheduleTest()/recordScore() below, which capture the
     * date/roll-no/score that status transition implies; a bare status flip
     * with no data attached wouldn't mean anything for those two.
     */
    private const STATUSES = ['under_review', 'shortlisted', 'interview', 'admitted', 'waitlisted', 'rejected', 'withdrawn'];

    /** Every status an application can be in, in pipeline order — used for the inbox filter tabs. */
    private const FILTER_STATUSES = ['submitted', 'under_review', 'shortlisted', 'test_scheduled', 'test_taken', 'interview', 'admitted', 'waitlisted', 'rejected', 'withdrawn'];
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $repo = new AdmissionApplicationRepository();
        $status = (string) $request->query('status', '');
        $filters = [
            'status' => in_array($status, self::FILTER_STATUSES, true) ? $status : '',
            'q'      => trim((string) $request->query('q', '')),
        ];

        if ($request->query('export') === 'csv') {
            Audit::log('admissions.exported', 'admissions', 'admission_applications', null, 'Exported admission applications to CSV');
            // Contact details for follow-up only — CNIC / B-Form numbers are deliberately left out of the file.
            $rows = array_map(static fn (array $a): array => [
                $a['application_no'], $a['created_at'], $a['applicant_name'], $a['father_name'], $a['gender'], $a['date_of_birth'],
                $a['class_name'], $a['stream_name'], $a['phone'], $a['whatsapp'], $a['email'], $a['city'], $a['previous_school'],
                $a['status'], $a['test_date'], $a['test_score'], $a['merit_position'],
            ], $repo->search($filters));

            return Csv::response('admission-applications-' . date('Y-m-d') . '.csv',
                ['Application No', 'Received', 'Applicant', 'Father', 'Gender', 'Date of birth', 'Class', 'Stream', 'Phone', 'WhatsApp', 'Email', 'City', 'Previous school', 'Status', 'Test date', 'Test score', 'Merit position'], $rows);
        }

        $total = $repo->countMatching($filters);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        return $this->view('admin.admissions.index', [
            'title'        => 'Admission Applications',
            'applications' => $repo->search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters'      => $filters,
            'statusCounts' => $repo->statusCounts(self::FILTER_STATUSES),
            'statusLabels' => array_combine(self::FILTER_STATUSES, array_map(static fn (string $s): string => ucwords(str_replace('_', ' ', $s)), self::FILTER_STATUSES)),
            'total'        => $total,
            'page'         => $page,
            'lastPage'     => $lastPage,
        ], 'admin');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $application = (new AdmissionApplicationRepository())->find($id);
        if ($application === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.admissions.show', [
            'title'       => 'Application ' . $application['application_no'],
            'application' => $application,
        ], 'admin');
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $status = (string) $request->input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException(422);
        }

        $repo->updateStatus($id, $status, (int) Auth::id());
        Audit::log('admission.status_updated', 'admissions', 'admission_applications', $id, "Marked application as \"{$status}\"");
        Session::flash('status', 'Status updated.');

        return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
    }

    public function saveRemarks(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), ['remarks' => 'max:255']);
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        $repo->saveRemarks($id, (string) $request->input('remarks', ''));
        Audit::log('admission.remarks_saved', 'admissions', 'admission_applications', $id, 'Updated review remarks');
        Session::flash('status', 'Remarks saved.');

        return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
    }

    public function scheduleTest(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), [
            'test_date'    => 'required',
            'test_roll_no' => 'max:20',
        ]);
        $errors = $validator->errors();

        $testDate = (string) $request->input('test_date', '');
        if ($testDate !== '' && strtotime($testDate) === false) {
            $errors['test_date'][] = 'Please provide a valid test date and time.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        $rollNo = trim((string) $request->input('test_roll_no', ''));
        $repo->scheduleTest($id, date('Y-m-d H:i:s', strtotime($testDate)), $rollNo !== '' ? $rollNo : null);
        Audit::log('admission.test_scheduled', 'admissions', 'admission_applications', $id, 'Scheduled the entrance test');
        Session::flash('status', 'Entrance test scheduled.');

        return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
    }

    public function recordScore(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), ['test_score' => 'required']);
        $errors = $validator->errors();

        $rawScore = (string) $request->input('test_score', '');
        if ($rawScore !== '' && !is_numeric($rawScore)) {
            $errors['test_score'][] = 'Please enter a valid numeric score.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        $repo->recordTestScore($id, (float) $rawScore);
        Audit::log('admission.test_score_recorded', 'admissions', 'admission_applications', $id, "Recorded entrance test score: {$rawScore}");
        Session::flash('status', 'Test score recorded.');

        return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
    }

    public function saveMerit(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), ['interview_remarks' => 'max:255']);
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        $meritRaw = trim((string) $request->input('merit_position', ''));
        $meritPosition = $meritRaw !== '' && ctype_digit($meritRaw) ? (int) $meritRaw : null;
        $interviewRemarks = trim((string) $request->input('interview_remarks', ''));

        $repo->saveMeritAndInterview($id, $meritPosition, $interviewRemarks !== '' ? $interviewRemarks : null);
        Audit::log('admission.merit_updated', 'admissions', 'admission_applications', $id, 'Updated merit position / interview remarks');
        Session::flash('status', 'Saved.');

        return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
    }

    /**
     * Creates the enrolled-student record for an admitted applicant, copying
     * over the fields the two tables share and linking the two rows via
     * `converted_student_id`. Everything the application never collected
     * (mother's info, section placement, blood group, ...) is left for the
     * admin to fill in on the Student edit page this redirects to.
     */
    public function convert(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        $application = $repo->find($id);
        if ($application === null) {
            throw new HttpException(404);
        }

        if ($application['converted_student_id'] !== null) {
            Session::flash('status', 'This application has already been converted to a student record.');

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        if ($application['status'] !== 'admitted') {
            Session::flash('errors', ['status' => ['Only applications marked "Admitted" can be converted to a student record.']]);

            return $this->redirectRoute('admin.admissions.show', ['id' => $id]);
        }

        $nameParts = explode(' ', trim((string) $application['applicant_name']), 2);
        $studentRepo = new StudentRepository();

        $studentId = $studentRepo->create([
            'registration_no'    => $studentRepo->nextRegistrationNo(),
            'admission_no'       => $studentRepo->nextAdmissionNo(),
            'first_name'         => $nameParts[0],
            'last_name'          => $nameParts[1] ?? '',
            'name_ur'            => '',
            'father_name'        => $application['father_name'],
            'father_cnic'        => (string) ($application['father_cnic'] ?? ''),
            'father_occupation'  => '',
            'father_phone'       => (string) $application['phone'],
            'mother_name'        => '',
            'mother_cnic'        => '',
            'gender'             => $application['gender'],
            'date_of_birth'      => $application['date_of_birth'],
            'b_form_no'          => (string) ($application['b_form_no'] ?? ''),
            'religion'           => (string) ($application['religion'] ?? ''),
            'nationality'        => '',
            'blood_group'        => '',
            'photo'              => null,
            'address'            => (string) ($application['address'] ?? ''),
            'city'               => (string) ($application['city'] ?? ''),
            'district'           => '',
            'province'           => '',
            'postal_code'        => '',
            'phone'              => (string) $application['phone'],
            'email'              => (string) ($application['email'] ?? ''),
            'admission_date'     => date('Y-m-d'),
            'admission_class_id' => (int) $application['class_id'],
            'current_class_id'   => (int) $application['class_id'],
            'current_section_id' => null,
            'status'             => 'active',
            'previous_school'    => (string) ($application['previous_school'] ?? ''),
            'previous_class'     => '',
            'transport_required' => 0,
            'is_orphan'          => 0,
            'is_disabled'        => 0,
            'special_notes'      => '',
            'author_id'          => (int) Auth::id(),
        ]);

        $repo->markConverted($id, $studentId);
        Audit::log('admission.converted', 'admissions', 'admission_applications', $id, "Converted application \"{$application['application_no']}\" to student record #{$studentId}");
        Session::flash('status', 'Application converted — complete the remaining student details below.');

        return $this->redirectRoute('admin.students.edit', ['id' => $studentId]);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new AdmissionApplicationRepository();
        $application = $repo->find($id);
        if ($application === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('admission.deleted', 'admissions', 'admission_applications', $id, "Deleted application \"{$application['application_no']}\"");
        Session::flash('status', 'Application deleted.');

        return $this->redirectRoute('admin.admissions');
    }
}
