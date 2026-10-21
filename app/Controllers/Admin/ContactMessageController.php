<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Exceptions\HttpException;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\ContactMessageRepository;

final class ContactMessageController extends Controller
{
    private const STATUSES = ['new', 'read', 'replied', 'spam', 'archived'];

    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $repo = new ContactMessageRepository();
        $status = (string) $request->query('status', '');
        $filters = [
            'status' => in_array($status, self::STATUSES, true) ? $status : '',
            'q'      => trim((string) $request->query('q', '')),
        ];

        if ($request->query('export') === 'csv') {
            Audit::log('contact.exported', 'contact', 'contact_messages', null, 'Exported contact messages to CSV');
            $rows = array_map(static fn (array $m): array => [
                $m['id'], $m['created_at'], $m['name'], $m['email'], $m['phone'], $m['subject'], $m['message'], $m['status'], $m['replied_at'],
            ], $repo->search($filters));

            return Csv::response('contact-messages-' . date('Y-m-d') . '.csv',
                ['ID', 'Received', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Replied at'], $rows);
        }

        $total = $repo->countMatching($filters);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);

        return $this->view('admin.contact-messages.index', [
            'title'        => 'Contact Messages',
            'messages'     => $repo->search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters'      => $filters,
            'statusCounts' => $repo->statusCounts(self::STATUSES),
            'statusLabels' => array_combine(self::STATUSES, array_map('ucfirst', self::STATUSES)),
            'total'        => $total,
            'page'         => $page,
            'lastPage'     => $lastPage,
        ], 'admin');
    }

    public function show(Request $request): Response
    {
        $repo = new ContactMessageRepository();
        $id = (int) $request->parameter('id');
        $message = $repo->find($id);
        if ($message === null) {
            throw new HttpException(404);
        }

        $repo->markAsReadIfNew($id);
        if ($message['status'] === 'new') {
            $message['status'] = 'read';
        }

        return $this->view('admin.contact-messages.show', [
            'title'   => 'Message from ' . $message['name'],
            'message' => $message,
        ], 'admin');
    }

    public function reply(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ContactMessageRepository();
        $message = $repo->find($id);
        if ($message === null) {
            throw new HttpException(404);
        }

        $validator = Validator::make($request->all(), ['reply_message' => 'required|max:2000']);
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->all());

            return $this->redirectRoute('admin.contact-messages.show', ['id' => $id]);
        }

        $replyMessage = (string) $request->input('reply_message');
        $repo->saveReply($id, $replyMessage, (int) Auth::id());

        $subject = $message['subject'] ? 'Re: ' . $message['subject'] : 'Re: your message to us';
        Mailer::send($message['email'], $subject, nl2br(e($replyMessage)));

        Audit::log('contact.replied', 'contact', 'contact_messages', $id, "Replied to message from \"{$message['name']}\"");
        Session::flash('status', 'Reply sent.');

        return $this->redirectRoute('admin.contact-messages.show', ['id' => $id]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ContactMessageRepository();
        if ($repo->find($id) === null) {
            throw new HttpException(404);
        }

        $status = (string) $request->input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException(422);
        }

        $repo->updateStatus($id, $status);
        Audit::log('contact.status_updated', 'contact', 'contact_messages', $id, "Marked message as \"{$status}\"");
        Session::flash('status', 'Status updated.');

        return $this->redirectRoute('admin.contact-messages.show', ['id' => $id]);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->parameter('id');
        $repo = new ContactMessageRepository();
        $message = $repo->find($id);
        if ($message === null) {
            throw new HttpException(404);
        }

        $repo->delete($id);
        Audit::log('contact.deleted', 'contact', 'contact_messages', $id, "Deleted message from \"{$message['name']}\"");
        Session::flash('status', 'Message deleted.');

        return $this->redirectRoute('admin.contact-messages');
    }
}
