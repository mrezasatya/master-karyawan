<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use App\Services\AccountService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $key = 'login:'.Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $account = UserAccount::where('username', trim($data['username']))->first();
        $valid = $account && Hash::check($data['password'], $account->password_hash) && $account->isActive();

        if (! $valid) {
            RateLimiter::hit($key, 300);
            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                Audit::security('login_locked', ['username' => Str::limit($data['username'], 50, '')], $account?->id);
            }
            // Pesan sama untuk NIK tidak terdaftar, password salah, maupun akun nonaktif.
            throw ValidationException::withMessages(['username' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        Auth::login($account);
        $request->session()->regenerate();
        $account->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended($this->home($account));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $account = $request->user();
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        if (AccountService::looksLikeBirthDate($request->input('password'), $account->employee) || Str::contains($request->input('password'), $account->username)) {
            throw ValidationException::withMessages(['password' => 'Password baru tidak boleh memuat tanggal lahir atau NIK Anda.']);
        }

        $account->forceFill([
            'password_hash' => $request->input('password'),
            'must_change_password' => false,
        ])->save();
        Audit::log('user_account', $account->id, 'PasswordChange');
        $request->session()->regenerate();

        return redirect($this->home($account))->with('success', 'Password berhasil diganti.');
    }

    public static function home(UserAccount $account): string
    {
        return $account->isHr() ? route('dashboard') : route('portal.index');
    }
}
