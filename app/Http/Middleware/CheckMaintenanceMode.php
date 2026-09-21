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
     * Checks if the public website is in maintenance mode.
     * Admin and Staff can always access the site and admin panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = Setting::get('maintenance_mode', '0') === '1';

        if (!$isMaintenance) {
            return $next($request);
        }

        // Always allow admin routes, staff routes, login, logout, and assets
        if (
            $request->is('admin*') ||
            $request->is('staff*') ||
            $request->is('login') ||
            $request->is('admin/login') ||
            $request->is('logout') ||
            $request->is('up') ||
            $request->is('build/*') ||
            $request->is('assets/*')
        ) {
            return $next($request);
        }

        // If user is logged in as admin or staff, let them browse the public website freely
        $user = $request->user();
        if ($user && ($user->hasRole('admin') || $user->hasRole('staff'))) {
            return $next($request);
        }

        // Otherwise, render the premium Maintenance Mode page
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
        ], 503);
    }
}
