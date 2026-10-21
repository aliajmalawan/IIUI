<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\ContactMessageRepository;
use App\Repositories\SettingRepository;

final class ContactController extends Controller
{
    public function store(Request $request): Response
    {
        // Browsers never send arrays for these fields. A crafted request that does would make the
        // (string) casts below raise notices (a 500), so it is rejected as an ordinary invalid submission.
        if (array_filter($request->all(), 'is_array') !== []) {
            Session::flash('errors', ['message' => ['We could not process that submission. Please check the form and try again.']]);

            return $this->redirect($this->contactRedirectTarget($request));
        }
        // Honeypot: a hidden field real visitors never fill in. Bots that
        // blindly fill every field get a generic "success" response — never
        // a hint that anything was rejected — but nothing is stored or sent.
        if (trim((string) $request->input('website', '')) !== '') {
            Session::flash('contact_status', 'Thank you — your message has been sent.');

            return $this->redirect($this->contactRedirectTarget($request));
        }

        // Limits mirror the contact_messages columns: a strict MySQL server (most live hosts) rejects
        // over-long values with a database error instead of quietly truncating them like a local XAMPP does.
        $validator = Validator::make($request->all(), [
            'name'    => 'required|max:120',
            'email'   => 'required|email|max:191',
            'phone'   => 'max:20',
            'subject' => 'max:180',
            'message' => 'required|max:2000',
        ]);
        $errors = $validator->errors();
        $phone = trim((string) $request->input('phone', ''));
        if ($phone !== '' && !isset($errors['phone']) && !preg_match('/^[0-9+\-\s()]{5,20}$/', $phone)) {
            $errors['phone'][] = 'Please enter a valid phone number, e.g. 0300 1234567.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('_old_input', $request->all());

            return $this->redirect($this->contactRedirectTarget($request));
        }

        $repo = new ContactMessageRepository();
        $repo->create([
            'name'       => (string) $request->input('name'),
            'email'      => (string) $request->input('email'),
            'phone'      => (string) $request->input('phone', ''),
            'subject'    => (string) $request->input('subject', ''),
            'message'    => (string) $request->input('message'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->notifyAdmin($request);

        Session::flash('contact_status', 'Thank you — your message has been sent. We will get back to you soon.');

        return $this->redirect($this->contactRedirectTarget($request));
    }

    /**
     * The Contact form now lives both on the homepage (#contact) and its own
     * /contact page, so success/error should land back on whichever one was
     * actually submitted — never a different page entirely. The Referer is
     * attacker-controlled, so it's only trusted after confirming it points
     * at this same site; anything else (or none) falls back to the homepage.
     */
    private function contactRedirectTarget(Request $request): string
    {
        $referer = (string) ($request->header('Referer') ?? '');
        $siteHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $siteHost) {
            $path = rtrim((string) (parse_url($referer, PHP_URL_PATH) ?: ''), '/');

            if (str_ends_with($path, '/contact')) {
                return base_url('contact#contact');
            }
        }

        return base_url('#contact');
    }

    private function notifyAdmin(Request $request): void
    {
        $adminEmail = (new SettingRepository())->get('email', '');
        if ($adminEmail === '') {
            return;
        }

        $name = e((string) $request->input('name'));
        $email = e((string) $request->input('email'));
        $phone = e((string) $request->input('phone', ''));
        $subject = e((string) $request->input('subject', ''));
        $message = nl2br(e((string) $request->input('message')));

        $body = "<p><strong>From:</strong> {$name} ({$email})</p>"
            . ($phone !== '' ? "<p><strong>Phone:</strong> {$phone}</p>" : '')
            . ($subject !== '' ? "<p><strong>Subject:</strong> {$subject}</p>" : '')
            . "<p><strong>Message:</strong></p><p>{$message}</p>";

        Mailer::send($adminEmail, 'New website contact message', $body);
    }
}
