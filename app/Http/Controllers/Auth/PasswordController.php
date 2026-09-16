<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use App\Support\Email;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function email(Request $request)
    {
        $request->merge(['email' => Email::normalize($request->input('email'))]);
        $data = $request->validate(['email' => ['required', 'email', 'max:254']]);
        SendPasswordResetLink::dispatch($data['email']);

        return back()->with('status', __('auth.reset_sent'));
    }

    public function reset(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/D', $token)
            || ! is_string($email) || strlen($email) > 254 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->view('auth.invalid-reset', [], 400);
        }

        return view('auth.reset', compact('token', 'email'));
    }

    public function update(Request $request)
    {
        $request->merge(['email' => Email::normalize($request->input('email'))]);
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'confirmed', 'max:128', PasswordRule::min(12)],
        ]);
        $status = Password::reset($data, function (User $user, string $password) use ($data) {
            DB::transaction(function () use ($user, $password, $data) {
                $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                // Recheck inside the lock so two simultaneous uses cannot both succeed.
                if (! $locked->canAuthenticate() || ! Password::tokenExists($locked, $data['token'])) {
                    throw ValidationException::withMessages(['email' => __('auth.reset_invalid')]);
                }
                $locked->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'auth_version' => $locked->auth_version + 1])->save();
                Password::deleteToken($locked);
                event(new PasswordReset($locked));
            });
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __('auth.reset_invalid')]);
        }

        return redirect()->route('login')->with('status', __('auth.password_updated'));
    }
}
