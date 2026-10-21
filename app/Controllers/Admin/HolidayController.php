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
use App\Repositories\HolidayRepository;

final class HolidayController extends Controller
{
    private const TYPES = ['public' => 'Public Holiday', 'religious' => 'Religious Holiday', 'vacation' => 'Vacation', 'emergency' => 'Emergency Closure', 'weekend' => 'Weekend'];
    private const APPLIES_TO = ['all' => 'Everyone', 'students' => 'Students Only', 'staff' => 'Staff Only'];

    public function index(Request $request): Response
    {
        return $this->view('admin.holidays.index', [
            'title'    => 'Holidays',
            'holidays' => (new HolidayRepository())->all(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.holidays.form', [
            'title'      => 'Add Holiday',
            'holiday'    => null,
            'sessions'   => (new AcademicSessionRepository())->all(),
            'types'      => self::TYPES,
            'appliesTo'  => self::APPLIES_TO,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $holiday = (new HolidayRepository())->find((int) $request->parameter('id'));
        if ($holiday === null) {
            throw new HttpException(404);
        }

        return $this->view('admin.holidays.form', [
            'title'     => 'Edit Holiday',
            'holiday'   => $holiday,
            'sessions'  => (new AcademicSessionRepository())->all(),
            'types'     => self::TYPES,
            'appliesTo' => self::APPLIES_TO,
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        if ((new HolidayRepository())->find($id) === null) {
            throw new HttpException(404);
        }

        return $this->save($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new HolidayRepository();
        $holiday = $repo->find($id);
        if ($holiday === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('academic_calendar.holiday_deleted', 'academic_calendar', 'holidays', $id, "Deleted holiday \"{$holiday['title']}\"");
        Session::flash('status', 'Holiday deleted.');

        return $this->redirectRoute('admin.holidays');
    }

    private function save(Request $request, ?int $id): Response
    {
        $repo = new HolidayRepository();

        $validator = Validator::make($request->all(), [
            'title'       => 'required|max:120',
            'description' => 'max:255',
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

        $appliesTo = (string) $request->input('applies_to', 'all');
        if (!array_key_exists($appliesTo, self::APPLIES_TO)) {
            $errors['applies_to'][] = 'Please select a valid option.';
        }

        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');
        if ($dateFrom === '' || strtotime($dateFrom) === false) {
            $errors['date_from'][] = 'Please enter a valid start date.';
        }
        if ($dateTo === '' || strtotime($dateTo) === false) {
            $errors['date_to'][] = 'Please enter a valid end date.';
        }
        if ($errors === [] && strtotime($dateTo) < strtotime($dateFrom)) {
            $errors['date_to'][] = 'End date must be on or after the start date.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $id === null
                ? $this->redirectRoute('admin.holidays.create')
                : $this->redirectRoute('admin.holidays.edit', ['id' => $id]);
        }

        $data = [
            'academic_session_id' => $sessionId,
            'title'               => (string) $request->input('title'),
            'date_from'           => $dateFrom,
            'date_to'             => $dateTo,
            'type'                => $type,
            'applies_to'          => $appliesTo,
            'description'         => (string) $request->input('description', ''),
        ];

        if ($id === null) {
            $newId = $repo->create($data, Auth::id());
            Audit::log('academic_calendar.holiday_created', 'academic_calendar', 'holidays', $newId, "Created holiday \"{$data['title']}\"");
            Session::flash('status', 'Holiday added.');
        } else {
            $repo->update($id, $data);
            Audit::log('academic_calendar.holiday_updated', 'academic_calendar', 'holidays', $id, "Updated holiday \"{$data['title']}\"");
            Session::flash('status', 'Holiday updated.');
        }

        return $this->redirectRoute('admin.holidays');
    }
}
