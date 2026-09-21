<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthenticatedSessionController extends Controller
{
    /**
     * Display the admin/staff login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/AdminLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle incoming admin/staff authentication.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['login']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $login = trim($validated['login']);
        $password = $validated['password'];
        $remember = $request->boolean('remember');

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('username', $login)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'লগইন তথ্য সঠিক নয়। অনুগ্রহ করে সঠিক ইমেইল, ফোন অথবা ইউজারনেম এবং পাসওয়ার্ড দিন।',
            ]);
        }

        // Strict access check: Only Admin and Staff are allowed
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'অননুমোদিত অ্যাক্সেস। এটি শুধুমাত্র অ্যাডমিন এবং অনুমোদিত স্টাফদের জন্য সংরক্ষিত কনসোল।',
            ]);
        }

        // Active status check
        if ($user->status !== 'active') {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'login' => 'আপনার স্টাফ অ্যাকাউন্টটি বর্তমানে নিষ্ক্রিয় করা হয়েছে। অ্যাডমিনের সাথে যোগাযোগ করুন।',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $remember);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('staff.dashboard', absolute: false));
    }
}
