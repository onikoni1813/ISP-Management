<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Package;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Public High-Converting Homepage (/).
     */
    public function index(): Response
    {
        $packages = Package::with('currentPrice')
            ->where('status', 'active')
            ->orderBy('speed_mbps')
            ->get();

        $coverageAreas = Area::with('children')
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return Inertia::render('Website/Home', [
            'packages' => $packages,
            'coverageAreas' => $coverageAreas,
        ]);
    }

    /**
     * About Page (/about).
     */
    public function about(): Response
    {
        return Inertia::render('Website/About');
    }

    /**
     * Dedicated Packages Listing (/packages).
     */
    public function packages(): Response
    {
        $packages = Package::with('currentPrice')
            ->where('status', 'active')
            ->orderBy('speed_mbps')
            ->get();

        return Inertia::render('Website/Packages', [
            'packages' => $packages,
        ]);
    }

    /**
     * Coverage Areas Explorer (/coverage).
     */
    public function coverage(): Response
    {
        $coverageAreas = Area::with('children')
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get();

        return Inertia::render('Website/Coverage', [
            'coverageAreas' => $coverageAreas,
        ]);
    }

    /**
     * Frequently Asked Questions (/faq).
     */
    public function faq(): Response
    {
        return Inertia::render('Website/Faq');
    }

    /**
     * Public ISP Notices & Announcements (/notices).
     */
    public function notices(): Response
    {
        return Inertia::render('Website/Notices');
    }

    /**
     * Contact Us & Support Hotline (/contact).
     */
    public function contact(): Response
    {
        $areas = Area::where('status', 'active')->get(['id', 'name', 'code']);

        return Inertia::render('Website/Contact', [
            'areas' => $areas,
        ]);
    }

    /**
     * Submit Online New Connection Application from Public Website.
     */
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'area_id' => 'required|exists:areas,id',
            'address' => 'required|string|max:500',
            'package_id' => 'required|exists:packages,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['billing_day'] = 1;

        // Auto-create customer from public website application
        $customer = $this->customerService->createCustomer($validated, null);

        return back()->with('success', "Thank you, {$customer->name}! Your connection application has been received (Ref: {$customer->customer_code}). Our field team will contact you within 24 hours.");
    }
}
