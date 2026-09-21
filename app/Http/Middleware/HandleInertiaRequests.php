<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'roles' => $user->roles->pluck('slug')->all(),
                    'permissions' => $user->hasRole('admin')
                        ? ['*']
                        : $user->permissions->pluck('slug')->merge(
                            $user->roles->flatMap->permissions->pluck('slug')
                        )->unique()->values()->all(),
                ] : null,
            ],
            'company' => [
                'hotline' => \App\Models\Setting::get('noc_hotline', '01711-000000 / 01722-000000'),
                'email' => \App\Models\Setting::get('support_email', 'support@pirgachainternet.com'),
                'address' => \App\Models\Setting::get('office_address', 'Town Center, Pirgacha Sadar, Rangpur'),
                'working_hours' => \App\Models\Setting::get('working_hours', '24 Hours Daily (7 Days a Week)'),
            ],
            'unread_complaints' => $user ? (
                $user->hasRole('admin')
                    ? \App\Models\Complaint::where('status', 'open')->count()
                    : \App\Models\Complaint::where('assigned_to', $user->id)->whereIn('status', ['open', 'assigned', 'in_progress'])->count()
            ) : 0,
        ];
    }
}
