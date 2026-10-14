<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * When maintenance mode is enabled:
     * ONLY the public homepage ('/') is put into maintenance mode.
     * Everything else (PPPoE subscriber portal /account, staff portal /staff,
     * admin portal /admin, login routes, and other public pages) remains fully accessible.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = Setting::get('maintenance_mode', '0') === '1';

        if (!$isMaintenance) {
            return $next($request);
        }

        // If the request is NOT for the homepage, allow it immediately!
        // This ensures login, customer account (/account), staff (/staff), admin (/admin),
        // and all other pages are fully accessible.
        if (!$request->is('/') && $request->path() !== '/') {
            return $next($request);
        }

        // For the homepage ('/'):
        // If an authenticated admin wants to preview the live homepage with ?preview=1:
        $user = $request->user();
        if ($user && $user->hasRole('admin') && $request->has('preview')) {
            return $next($request);
        }

        // Otherwise, render the premium Maintenance Mode page for '/'
        $title = Setting::get('maintenance_title', 'আমরা রক্ষণাবেক্ষণ করছি (Under Maintenance)');
        $message = Setting::get('maintenance_message', 'আমাদের সিস্টেম আপগ্রেড ও নেটওয়ার্ক রক্ষণাবেক্ষণের কাজ চলছে। সাময়িক অসুবিধার জন্য আমরা আন্তরিকভাবে দুঃখিত। খুব শীঘ্রই সাইটটি স্বাভাবিক হবে।');
        $estimatedTime = Setting::get('maintenance_estimated_time', 'শীঘ্রই সম্পন্ন হবে');
        $hotline = Setting::get('noc_hotline', '01711-000000 / 01722-000000');
        $email = Setting::get('support_email', 'support@pirgachainternet.com');

        return response()->view('maintenance', [
            'title' => $title,
            'message' => $message,
            'estimatedTime' => $estimatedTime,
            'hotline' => $hotline,
            'email' => $email,
            'user' => $user,
        ], 503);
    }
}
