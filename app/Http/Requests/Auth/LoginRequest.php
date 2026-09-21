<?php

namespace App\Http\Requests\Auth;

use App\Models\PppoeCredential;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation (backwards compatibility with 'email' or 'login').
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && !$this->has('login')) {
            $this->merge([
                'login' => $this->input('email'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Supports:
     * 1. Customer subscriber login using PPPoE Username & PPPoE Password.
     * 2. Administrator & Staff login using Email, Phone, or Username.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');
        $remember = $this->boolean('remember');

        // 1. Check if input matches a PPPoE Credential (Customer Broadband Subscriber)
        $pppoe = PppoeCredential::with(['connection.customer.primaryContact'])
            ->where('username', $login)
            ->first();

        if ($pppoe) {
            // Verify PPPoE Password (decrypted via model getter)
            if ($pppoe->password === $password) {
                if ($pppoe->status === 'inactive') {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'login' => 'Your PPPoE broadband account is currently inactive. Please contact NOC support.',
                    ]);
                }

                $customer = $pppoe->connection?->customer;
                if (!$customer || in_array($customer->status, ['archived', 'disconnected'])) {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'login' => 'Your broadband connection has been suspended or disconnected.',
                    ]);
                }

                // Resolve or provision linked User for this Customer
                $user = null;
                if ($customer->user_id) {
                    $user = User::find($customer->user_id);
                }

                if (!$user) {
                    $user = User::where('username', $pppoe->username)
                        ->orWhere('email', $pppoe->username . '@pirgacha.customer')
                        ->first();
                }

                if (!$user) {
                    $contactPhone = $customer->primaryContact?->phone;
                    $contactEmail = $customer->primaryContact?->email ?: ($pppoe->username . '@pirgacha.customer');

                    // Check if user with this email or phone exists
                    $existingUser = User::where('email', $contactEmail)
                        ->when($contactPhone, fn($q) => $q->orWhere('phone', $contactPhone))
                        ->first();

                    if ($existingUser) {
                        $user = $existingUser;
                        $user->username = $pppoe->username;
                        $user->password = Hash::make($password);
                        $user->save();
                    } else {
                        $user = User::create([
                            'name' => $customer->name,
                            'email' => $contactEmail,
                            'username' => $pppoe->username,
                            'phone' => $contactPhone,
                            'password' => Hash::make($password),
                            'status' => 'active',
                            'email_verified_at' => now(),
                        ]);
                    }
                } else {
                    $user->username = $pppoe->username;
                    $user->password = Hash::make($password);
                    $user->status = 'active';
                    $user->save();
                }

                if (!$user->hasRole('customer')) {
                    $user->assignRole('customer');
                }

                if ($customer->user_id !== $user->id) {
                    $customer->update(['user_id' => $user->id]);
                }

                Auth::login($user, $remember);
                RateLimiter::clear($this->throttleKey());
                return;
            }
        }

        // 2. Standard User Authentication (Customer accounts only)
        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Block Admin & Staff on customer portal
            if ($user->hasRole('admin') || $user->hasRole('staff')) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'অ্যাডমিন ও স্টাফদের জন্য এই পোর্টাল নয়। অনুগ্রহ করে অ্যাডমিন/স্টাফ কনসোল (/admin/login)-এ লগইন করুন।',
                ]);
            }

            if ($user->status !== 'active') {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'আপনার গ্রাহক অ্যাকাউন্টটি নিষ্ক্রিয় করা হয়েছে। অনুগ্রহ করে সাপোর্ট টিমে যোগাযোগ করুন।',
                ]);
            }

            Auth::login($user, $remember);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Authentication failed
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => 'ভুল PPPoE ইউজার আইডি বা পাসওয়ার্ড। অনুগ্রহ করে আপনার ব্রডব্যান্ড PPPoE তথ্য দিয়ে চেষ্টা করুন।',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = $this->input('login', $this->input('email', ''));
        return Str::transliterate(Str::lower((string) $identifier).'|'.$this->ip());
    }
}
