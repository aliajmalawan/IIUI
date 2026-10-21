<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\AdmissionApplicationRepository;
use App\Repositories\SettingRepository;

final class AdmissionController extends Controller
{
    /**
     * Every admission-form field whose required-ness is admin-configurable
     * (Admin -> Settings -> Admission Form, settings.group_name =
     * 'admission_form', key_name = "require_{field}") — Gender, Class
     * Applying For, and the accuracy declaration are deliberately excluded,
     * see 0041_seed_admission_form_required_settings.sql.
     */
    private const CONFIGURABLE_FIELDS = [
        'applicant_name', 'father_name', 'b_form_no', 'date_of_birth', 'phone', 'address',
        'religion', 'city', 'whatsapp', 'email', 'previous_school',
        'matric_board', 'matric_year', 'matric_marks', 'matric_total_marks',
    ];

    /** @return array<string,bool> field => is required, read from admin settings (defaults to true if never configured). */
    private function requiredFieldsMap(): array
    {
        $settings = new SettingRepository();
        $map = [];
        foreach (self::CONFIGURABLE_FIELDS as $field) {
            $map[$field] = $settings->get('require_' . $field, '1') === '1';
        }

        return $map;
    }

    /** Standalone multi-step application form — /admission-form. */
    public function redirectToForm(Request $request): Response
    {
        return Response::redirect(route('admission.form'), 301);
    }

    public function form(Request $request): Response
    {
        $repo = new AdmissionApplicationRepository();

        $classesByWing = ['primary' => [], 'middle' => [], 'secondary' => [], 'college' => []];
        foreach ($repo->activeClasses() as $class) {
            $classesByWing[$class['wing']][] = $class;
        }

        return $this->view('web.pages.admission-form', [
            'title'         => 'Admission Form',
            'classesByWing' => $classesByWing,
            'streams'       => $repo->activeStreams(),
            'sessionOpen'   => $repo->currentSessionId() !== null,
            'requiredMap'   => $this->requiredFieldsMap(),
        ], 'web');
    }

    public function store(Request $request): Response
    {
        // Browsers never send arrays for these fields; see ContactController::store().
        if (array_filter($request->all(), 'is_array') !== []) {
            Session::flash('errors', ['confirm' => ['We could not process that submission. Please check the form and try again.']]);

            return $this->redirect(base_url('admission-form'));
        }
        // Honeypot — same silent-success-for-bots pattern as ContactController.
        if (trim((string) $request->input('website', '')) !== '') {
            Session::flash('admission_status', 'Thank you — your application has been received.');

            return $this->redirect(base_url('admission-form'));
        }

        $repo = new AdmissionApplicationRepository();
        $classes = $repo->activeClasses();
        $classById = [];
        foreach ($classes as $class) {
            $classById[(int) $class['id']] = $class;
        }

        $required = $this->requiredFieldsMap();
        $requiredRule = static fn (string $field): string => $required[$field] ? 'required|' : '';

        $validator = Validator::make($request->all(), [
            'applicant_name'  => $requiredRule('applicant_name') . 'max:120',
            'father_name'     => $requiredRule('father_name') . 'max:120',
            'b_form_no'       => $requiredRule('b_form_no') . 'max:20',
            // Not rendered on the form, but the controller reads it and the column is varchar(15).
            'father_cnic'     => 'max:15',
            'date_of_birth'   => rtrim($requiredRule('date_of_birth'), '|'),
            'phone'           => $requiredRule('phone') . 'max:20',
            'address'         => $requiredRule('address') . 'max:255',
            'email'           => $requiredRule('email') . 'email|max:191',
            'religion'        => $requiredRule('religion') . 'max:40',
            'city'            => $requiredRule('city') . 'max:80',
            'whatsapp'        => $requiredRule('whatsapp') . 'max:20',
            'previous_school' => $requiredRule('previous_school') . 'max:160',
        ]);
        $errors = $validator->errors();

        $classId = (int) $request->input('class_id', 0);
        $selectedClass = $classById[$classId] ?? null;
        if ($selectedClass === null) {
            $errors['class_id'][] = 'Please select the class being applied for.';
        }

        $gender = (string) $request->input('gender', '');
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors['gender'][] = 'Please select a gender.';
        }

        $dob = (string) $request->input('date_of_birth', '');
        if ($dob !== '') {
            $dobTime = strtotime($dob);
            if ($dobTime === false || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors['date_of_birth'][] = 'Please enter a valid date of birth.';
            } elseif ($dobTime > time()) {
                $errors['date_of_birth'][] = 'The date of birth cannot be in the future.';
            } elseif ($dobTime < strtotime('-40 years')) {
                $errors['date_of_birth'][] = 'Please check the date of birth — it looks too far in the past.';
            }
        }

        foreach (['phone' => 'phone number', 'whatsapp' => 'WhatsApp number'] as $field => $label) {
            $number = trim((string) $request->input($field, ''));
            if ($number !== '' && !isset($errors[$field]) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $number)) {
                $errors[$field][] = "Please enter a valid {$label}, e.g. 0300 1234567.";
            }
        }
        $streamId = $request->input('stream_id') !== '' ? (int) $request->input('stream_id') : null;
        if ($streamId !== null && $streamId !== 0 && !in_array($streamId, array_map(static fn (array $s): int => (int) $s['id'], $repo->activeStreams()), true)) {
            $errors['stream_id'][] = 'Please select a valid stream.';
            $streamId = null;
        }
        if ($selectedClass !== null && $selectedClass['wing'] === 'college' && $streamId === null) {
            $errors['stream_id'][] = 'Please select a stream for college-level classes.';
        }

        // Academic history (Matriculation) is only meaningful for applicants
        // to College-level classes (First/Second Year) — everyone else is
        // applying below Matric and hasn't sat those exams yet, so the form
        // never even shows these fields for them.
        $needsMatricInfo = $selectedClass !== null && $selectedClass['wing'] === 'college';
        if ($needsMatricInfo) {
            // matric_board is varchar(120), matric_year varchar(4), the marks columns are smallint:
            // check the shape here so a strict database never has to reject them.
            $board = trim((string) $request->input('matric_board', ''));
            if (mb_strlen($board) > 120) {
                $errors['matric_board'][] = 'The board name is too long (120 characters at most).';
            }
            $year = trim((string) $request->input('matric_year', ''));
            if ($year !== '' && (!ctype_digit($year) || strlen($year) !== 4 || (int) $year < 1980 || (int) $year > (int) date('Y'))) {
                $errors['matric_year'][] = 'Please enter a valid 4-digit year, e.g. ' . date('Y') . '.';
            }
            $marks = trim((string) $request->input('matric_marks', ''));
            $total = trim((string) $request->input('matric_total_marks', ''));
            foreach (['matric_marks' => $marks, 'matric_total_marks' => $total] as $field => $value) {
                if ($value !== '' && (!ctype_digit($value) || (int) $value > 9999)) {
                    $errors[$field][] = 'Please enter whole numbers only (0 to 9999).';
                }
            }
            if (!isset($errors['matric_marks']) && !isset($errors['matric_total_marks']) && $marks !== '' && $total !== ''
                && ctype_digit($marks) && ctype_digit($total) && (int) $marks > (int) $total) {
                $errors['matric_marks'][] = 'Marks obtained cannot be more than the total marks.';
            }

            foreach (['matric_board' => 'board', 'matric_year' => 'year', 'matric_marks' => 'marks', 'matric_total_marks' => 'total marks'] as $field => $label) {
                if ($required[$field] && trim((string) $request->input($field, '')) === '') {
                    $errors[$field][] = "Please enter the Matriculation {$label}.";
                }
            }
        }

        if (trim((string) $request->input('confirm', '')) === '') {
            $errors['confirm'][] = 'Please confirm the information above is accurate.';
        }

        $sessionId = $repo->currentSessionId();
        if ($sessionId === null) {
            $errors['class_id'][] = 'Admissions are not open for any session right now.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirect(base_url('admission-form'));
        }

        $application = $repo->create([
            'academic_session_id' => $sessionId,
            'class_id'             => $classId,
            'stream_id'            => $streamId,
            'applicant_name'       => (string) $request->input('applicant_name'),
            'father_name'          => (string) $request->input('father_name'),
            'father_cnic'          => (string) $request->input('father_cnic', ''),
            'date_of_birth'        => $dob,
            'gender'               => $gender,
            'b_form_no'            => (string) $request->input('b_form_no'),
            'phone'                => (string) $request->input('phone'),
            'email'                => (string) $request->input('email', ''),
            'address'              => (string) $request->input('address'),
            'religion'             => (string) $request->input('religion', ''),
            'city'                 => (string) $request->input('city', ''),
            'whatsapp'             => (string) $request->input('whatsapp', ''),
            'previous_school'      => (string) $request->input('previous_school', ''),
            'matric_board'         => $needsMatricInfo ? (string) $request->input('matric_board', '') : '',
            'matric_year'          => $needsMatricInfo ? (string) $request->input('matric_year', '') : '',
            'matric_marks'         => $needsMatricInfo ? (string) $request->input('matric_marks', '') : '',
            'matric_total_marks'   => $needsMatricInfo ? (string) $request->input('matric_total_marks', '') : '',
            'ip_address'           => $request->ip(),
        ]);

        $this->notifyAdmin($request, $application['application_no'], $selectedClass['name'] ?? '');

        Session::flash(
            'admission_status',
            "Thank you — your application has been received. Your reference number is {$application['application_no']}. We will contact you about the next steps."
        );

        return $this->redirect(base_url('admission-form'));
    }

    private function notifyAdmin(Request $request, string $applicationNo, string $className): void
    {
        $adminEmail = (new SettingRepository())->get('email', '');
        if ($adminEmail === '') {
            return;
        }

        $name = e((string) $request->input('applicant_name'));
        $father = e((string) $request->input('father_name'));
        $phone = e((string) $request->input('phone'));

        $body = "<p><strong>Reference:</strong> {$applicationNo}</p>"
            . "<p><strong>Applicant:</strong> {$name} (father: {$father})</p>"
            . "<p><strong>Class applied for:</strong> " . e($className) . "</p>"
            . "<p><strong>Contact phone:</strong> {$phone}</p>";

        Mailer::send($adminEmail, 'New admission application — ' . $applicationNo, $body);
    }
}
