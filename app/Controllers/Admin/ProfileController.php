<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Hash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\Validator;
use App\Repositories\UserRepository;
use RuntimeException;

final class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('admin.profile', [
            'title' => 'My Profile',
            'user'  => Auth::user(),
        ], 'admin');
    }

    public function update(Request $request): Response
    {
        $userId = (int) Auth::id();
        $users = new UserRepository();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:80',
            'email'      => 'required|email',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('_old_input', $request->only(['first_name', 'last_name', 'email', 'phone']));

            return $this->redirectRoute('admin.profile');
        }

        $email = (string) $request->input('email');
        if ($users->emailTakenByAnotherUser($email, $userId)) {
            Session::flash('errors', ['email' => ['This email address is already in use.']]);
            Session::flash('_old_input', $request->only(['first_name', 'last_name', 'email', 'phone']));

            return $this->redirectRoute('admin.profile');
        }

        $users->updateProfile(
            $userId,
            (string) $request->input('first_name'),
            $request->input('last_name') !== null ? (string) $request->input('last_name') : null,
            $email,
            $request->input('phone') !== null ? (string) $request->input('phone') : null
        );

        $avatarFile = $request->file('avatar');
        $hasNewAvatar = $avatarFile !== null && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $wantsAvatarRemoved = $request->input('avatar_remove') === '1';
        $oldAvatarPath = (string) (Auth::user()['avatar'] ?? '');

        if ($hasNewAvatar) {
            try {
                $users->updateAvatar($userId, Uploader::storeImage($avatarFile, 'avatars'));
                $this->unlinkAvatar($oldAvatarPath);
            } catch (RuntimeException $e) {
                Session::flash('errors', ['avatar' => [$e->getMessage()]]);

                return $this->redirectRoute('admin.profile');
            }
        } elseif ($wantsAvatarRemoved) {
            $users->updateAvatar($userId, null);
            $this->unlinkAvatar($oldAvatarPath);
        }

        Audit::log('profile.updated', 'account', 'users', $userId, 'Profile details updated');
        Session::flash('status', 'Your profile has been updated.');

        return $this->redirectRoute('admin.profile');
    }

    public function updatePassword(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|min:10|strong_password|confirmed',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());

            return $this->redirectRoute('admin.profile');
        }

        if ($user === null || !Hash::verify((string) $request->input('current_password'), (string) $user['password_hash'])) {
            Session::flash('errors', ['current_password' => ['Your current password is incorrect.']]);

            return $this->redirectRoute('admin.profile');
        }

        (new UserRepository())->updatePasswordHash($userId, Hash::make((string) $request->input('password')));
        Audit::log('profile.password_changed', 'account', 'users', $userId, 'Password changed from profile settings');

        Session::flash('status', 'Your password has been changed.');

        return $this->redirectRoute('admin.profile');
    }

    private function unlinkAvatar(string $relativePath): void
    {
        $path = PUBLIC_PATH . $relativePath;
        if ($relativePath !== '' && is_file($path)) {
            @unlink($path);
        }
    }
}
